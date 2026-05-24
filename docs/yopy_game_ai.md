Here is the compiled documentation and source code for the **YOPY AI Behavior Module** in a single Markdown file.

---

# YOPY AI Behavior Module Documentation

This document contains the deterministic, rule-based AI logic for the YOPY educational platform. It includes the core analyzer that processes behavioral signals and the state model used to track emotional transitions.

## 1. BehaviorAnalyzer.php
**Purpose:** Processes raw game signals (latency, error rates, speed trends) and maps them to one of seven emotional states using weighted heuristics.

```php
<?php
/**
 * services/BehaviorAnalyzer.php
 * YOPY — Behavioural Signal Analyser
 */

class BehaviorAnalyzer
{
    private const T = [
        'fast_latency'       =>  600,   
        'slow_latency'       => 2000,   
        'high_variance'      =>  800,   
        'low_error_rate'     =>  0.20,
        'high_error_rate'    =>  0.55,
        'repeated_pair_warn' =>  2,     
        'high_excess_ratio'  =>  0.50,  
        'hesitation_warn'    =>  3,     
        'help_warn'          =>  2,     
        'decel_warn'         =>  400,   
    ];

    private const DIMENSIONS = [
        'calm' => [
            ['error_rate',           '-', 2.0],
            ['avg_latency_ms',       '+', 0.5],   
            ['latency_std_ms',       '-', 1.5],   
            ['hesitation_count',     '-', 1.0],
            ['repeated_pair_errors', '-', 1.0],
        ],
        'focused' => [
            ['error_rate',           '-', 2.5],
            ['avg_latency_ms',       '-', 1.5],   
            ['latency_std_ms',       '-', 1.5],   
            ['excess_flips_ratio',   '-', 1.0],
        ],
        'confident' => [
            ['error_rate',           '-', 3.0],
            ['avg_latency_ms',       '-', 2.0],
            ['hesitation_count',     '-', 1.5],
            ['help_requests',        '-', 1.0],
            ['repeated_pair_errors', '-', 1.5],
        ],
        'impulsive' => [
            ['clicks_per_minute',    '+', 2.5],   
            ['error_rate',           '+', 2.5],
            ['latency_std_ms',       '+', 1.0],
            ['excess_flips_ratio',   '+', 1.5],
        ],
        'frustrated' => [
            ['error_rate',           '+', 2.5],
            ['repeated_pair_errors', '+', 3.0],   
            ['max_error_streak',     '+', 2.0],
            ['speed_trend_ms',       '+', 1.5],   
            ['help_requests',        '+', 1.0],
        ],
        'anxious' => [
            ['hesitation_count',     '+', 3.0],
            ['latency_std_ms',       '+', 2.5],
            ['help_requests',        '+', 1.5],
            ['avg_latency_ms',       '+', 1.0],
        ],
        'fatigued' => [
            ['speed_trend_ms',       '+', 3.0],   
            ['session_duration_ms',  '+', 1.5],   
            ['error_rate',           '+', 1.5],   
            ['hesitation_count',     '+', 1.5],
        ],
    ];

    public static function analyze(PDO $pdo, int $sessionId, array $signals): array
    {
        $norm = self::normalise($signals);
        $scores = self::scoreDimensions($norm);

        arsort($scores);
        $dominant   = array_key_first($scores);
        $topScore   = $scores[$dominant];
        $confidence = self::softmaxConfidence($scores, $dominant);

        $signalId = self::persistSignals($pdo, $sessionId, $signals);
        self::persistState($pdo, $sessionId, $signalId, $dominant, $confidence, $scores);

        return [
            'state'      => $dominant,
            'confidence' => round($confidence, 4),
            'scores'     => array_map(fn($v) => round($v, 4), $scores),
        ];
    }

    private static function normalise(array $s): array
    {
        return [
            'avg_latency_ms'       => self::clamp($s['avg_latency_ms']      ?? 0, 200,  4000),
            'latency_std_ms'       => self::clamp($s['latency_std_ms']      ?? 0, 0,    2000),
            'speed_trend_ms'       => self::clamp($s['speed_trend_ms']      ?? 0, -1000, 2000),
            'error_rate'           => self::clamp($s['error_rate']          ?? 0, 0,    1),
            'repeated_pair_errors' => self::clamp($s['repeated_pair_errors']?? 0, 0,    10) / 10,
            'max_error_streak'     => self::clamp($s['max_error_streak']    ?? 0, 0,    10) / 10,
            'excess_flips_ratio'   => self::clamp($s['excess_flips_ratio']  ?? 0, 0,    3) / 3,
            'hesitation_count'     => self::clamp($s['hesitation_count']    ?? 0, 0,    10) / 10,
            'clicks_per_minute'    => self::clamp($s['clicks_per_minute']   ?? 0, 0,    120) / 120,
            'help_requests'        => self::clamp($s['help_requests']       ?? 0, 0,    5) / 5,
            'session_duration_ms'  => self::clamp($s['session_duration_ms'] ?? 0, 30000, 900000),
        ];
    }

    private static function clamp(float $val, float $min, float $max): float
    {
        return ($val - $min) / max($max - $min, 1);
    }

    private static function scoreDimensions(array $norm): array
    {
        $scores = [];
        foreach (self::DIMENSIONS as $state => $rules) {
            $totalWeight = array_sum(array_column($rules, 2));
            $weighted    = 0.0;

            foreach ($rules as [$signal, $direction, $weight]) {
                $v = $norm[$signal] ?? 0.5;
                $evidence = ($direction === '+') ? $v : (1.0 - $v);
                $weighted += $evidence * $weight;
            }
            $scores[$state] = $totalWeight > 0 ? $weighted / $totalWeight : 0.0;
        }
        return $scores;
    }

    private static function softmaxConfidence(array $scores, string $dominant): float
    {
        $exp = array_map('exp', $scores);
        $sum = array_sum($exp);
        return $sum > 0 ? $exp[$dominant] / $sum : 0.0;
    }

    private static function persistSignals(PDO $pdo, int $sessionId, array $s): int
    {
        $stmt = $pdo->prepare('
            INSERT INTO behavior_signals
                (session_id, session_duration_ms, avg_latency_ms, latency_std_ms,
                 speed_trend_ms, error_rate, repeated_pair_errors, max_error_streak,
                 total_flips, excess_flips_ratio, hesitation_count,
                 clicks_per_minute, help_requests, matched_pairs, total_attempts)
            VALUES
                (:sid, :dur, :lat, :std,
                 :trend, :err, :rep, :streak,
                 :flips, :excess, :hes,
                 :cpm, :help, :matched, :attempts)
            ON DUPLICATE KEY UPDATE
                session_duration_ms  = VALUES(session_duration_ms),
                avg_latency_ms       = VALUES(avg_latency_ms),
                latency_std_ms       = VALUES(latency_std_ms),
                speed_trend_ms       = VALUES(speed_trend_ms),
                error_rate           = VALUES(error_rate),
                repeated_pair_errors = VALUES(repeated_pair_errors),
                max_error_streak     = VALUES(max_error_streak),
                total_flips          = VALUES(total_flips),
                excess_flips_ratio   = VALUES(excess_flips_ratio),
                hesitation_count     = VALUES(hesitation_count),
                clicks_per_minute    = VALUES(clicks_per_minute),
                help_requests        = VALUES(help_requests),
                matched_pairs        = VALUES(matched_pairs),
                total_attempts       = VALUES(total_attempts)
        ');

        $stmt->execute([
            ':sid'      => $sessionId,
            ':dur'      => (int) ($s['session_duration_ms']  ?? 0),
            ':lat'      => (int) ($s['avg_latency_ms']        ?? 0),
            ':std'      => (int) ($s['latency_std_ms']        ?? 0),
            ':trend'    => (int) ($s['speed_trend_ms']        ?? 0),
            ':err'      => (float)($s['error_rate']           ?? 0),
            ':rep'      => (int) ($s['repeated_pair_errors']  ?? 0),
            ':streak'   => (int) ($s['max_error_streak']      ?? 0),
            ':flips'    => (int) ($s['total_flips']           ?? 0),
            ':excess'   => (float)($s['excess_flips_ratio']   ?? 0),
            ':hes'      => (int) ($s['hesitation_count']      ?? 0),
            ':cpm'      => (float)($s['clicks_per_minute']    ?? 0),
            ':help'     => (int) ($s['help_requests']         ?? 0),
            ':matched'  => (int) ($s['matched_pairs']         ?? 0),
            ':attempts' => (int) ($s['total_attempts']        ?? 0),
        ]);

        return (int) $pdo->query(
            "SELECT signal_id FROM behavior_signals WHERE session_id = $sessionId"
        )->fetchColumn();
    }

    private static function persistState(PDO $pdo, int $sessionId, int $signalId, string $state, float $confidence, array $scores): void 
    {
        $note = self::buildNote($state, $confidence);
        $stmt = $pdo->prepare('
            INSERT INTO emotional_states
                (session_id, signal_id, dominant_state, confidence, dimension_scores, summary_note)
            VALUES
                (:sid, :sgid, :state, :conf, :scores, :note)
            ON DUPLICATE KEY UPDATE
                signal_id        = VALUES(signal_id),
                dominant_state   = VALUES(dominant_state),
                confidence       = VALUES(confidence),
                dimension_scores = VALUES(dimension_scores),
                summary_note     = VALUES(summary_note)
        ');
        $stmt->execute([
            ':sid'    => $sessionId,
            ':sgid'   => $signalId,
            ':state'  => $state,
            ':conf'   => $confidence,
            ':scores' => json_encode($scores),
            ':note'   => $note,
        ]);
    }

    private static function buildNote(string $state, float $confidence): string
    {
        $pct   = round($confidence * 100);
        $notes = [
            'calm'       => "Your child played in a relaxed, unhurried way ({$pct}% confidence).",
            'focused'    => "Your child showed strong focus with consistent, accurate moves ({$pct}%).",
            'confident'  => "Your child was fast, accurate, and needed no hints — very confident ({$pct}%).",
            'impulsive'  => "Your child clicked quickly but made many mistakes — may have been over-excited ({$pct}%).",
            'frustrated' => "Your child repeated the same errors and slowed down — possible frustration ({$pct}%).",
            'anxious'    => "Your child hesitated often and played erratically — possible anxiety ({$pct}%).",
            'fatigued'   => "Your child's pace and accuracy dropped over time — they may have been tired ({$pct}%).",
        ];
        return $notes[$state] ?? "State: $state (confidence {$pct}%).";
    }
}
```

---

## 2. EmotionState.php
**Purpose:** A Model/Value Object used to wrap database results, determine if intervention is needed, and provide UI theme attributes.

```php
<?php
/**
 * models/EmotionState.php
 * YOPY — Emotion State Model
 */

class EmotionState
{
    public int $sessionId;
    public string $dominantState;
    public float $confidence;
    public array $dimensionScores;
    public string $summaryNote;
    public string $analysedAt;

    public function __construct(
        int $sessionId, 
        string $dominantState, 
        float $confidence, 
        array $dimensionScores, 
        string $summaryNote,
        string $analysedAt = ''
    ) {
        $this->sessionId       = $sessionId;
        $this->dominantState   = $dominantState;
        $this->confidence      = $confidence;
        $this->dimensionScores = $dimensionScores;
        $this->summaryNote     = $summaryNote;
        $this->analysedAt      = $analysedAt ?: date('Y-m-d H:i:s');
    }

    public static function getLatestBySession(PDO $pdo, int $sessionId): ?self
    {
        $stmt = $pdo->prepare("
            SELECT * FROM emotional_states 
            WHERE session_id = :sid 
            ORDER BY analysed_at DESC LIMIT 1
        ");
        $stmt->execute([':sid' => $sessionId]);
        $row = $stmt->fetch();

        if (!$row) return null;

        return new self(
            (int)$row['session_id'],
            $row['dominant_state'],
            (float)$row['confidence'],
            json_decode($row['dimension_scores'], true) ?: [],
            $row['summary_note'],
            $row['analysed_at']
        );
    }

    public function requiresIntervention(): bool
    {
        $negativeStates = ['frustrated', 'anxious', 'fatigued'];
        return in_array($this->dominantState, $negativeStates) && $this->confidence > 0.5;
    }

    public function getThemeAttributes(): array
    {
        return match($this->dominantState) {
            'calm'       => ['color' => '#a1c4fd', 'icon' => 'leaf-outline',    'label' => 'Zen'],
            'focused'    => ['color' => '#84fab0', 'icon' => 'eye-outline',     'label' => 'Sharp'],
            'confident'  => ['color' => '#fccb90', 'icon' => 'star-outline',    'label' => 'Pro'],
            'impulsive'  => ['color' => '#ff9a9e', 'icon' => 'flash-outline',   'label' => 'Fast'],
            'frustrated' => ['color' => '#f5576c', 'icon' => 'alert-circle',    'label' => 'Struggling'],
            'anxious'    => ['color' => '#667eea', 'icon' => 'help-circle',     'label' => 'Unsure'],
            'fatigued'   => ['color' => '#30cfd0', 'icon' => 'moon-outline',    'label' => 'Tired'],
            default      => ['color' => '#e2e2e2', 'icon' => 'radio-button-on', 'label' => 'Unknown'],
        };
    }

    public function toArray(): array
    {
        return [
            'session_id'     => $this->sessionId,
            'state'          => $this->dominantState,
            'confidence_pct' => round($this->confidence * 100),
            'note'           => $this->summaryNote,
            'timestamp'      => $this->analysedAt,
            'ui'             => $this->getThemeAttributes()
        ];
    }
}
```