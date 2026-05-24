<?php
require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/../src/BehaviorAnalyzer.php';

$pdo = new class extends PDO {
    public function __construct(){}
};

// Minimal config matching tests' defaultConfig
$config = [
    'default_weight' => 1.0,
    'game_weights' => [
        'memory_game' => 1.2,
        'snake_retro' => 1.2,
    ],
    'baseline_latency_ms' => [
        'snake_retro' => ['4-6' => 1200, '7-9' => 800, '10-12' => 500],
        'default' => ['4-6'=>2000,'7-9'=>1300,'10-12'=>900],
    ],
    'baseline_error_rate' => ['4-6'=>0.20,'7-9'=>0.12,'10-12'=>0.06],
    'decay_interval_ms' => 30000,
    'decay_multiplier' => 0.8,
    'boredom_latency_ratio' => 2.0,
    'boredom_consecutive' => 3,
    'boredom_long_pause_ms' => 10000,
    'focus_latency_variance' => 0.15,
    'focus_consecutive_success' => 3,
    'quick_recovery_multiplier' => 1.2,
    'frustration_spam_window_ms' => 500,
    'frustration_spam_count' => 3,
    'frustration_error_spiral_window_ms' => 3000,
    'joy_success_streak' => 5,
    'analysis_weights' => ['previous_1'=>0.10,'previous_2'=>0.03],
    'analysis_weights_decay_days' => 7.0,
    'data_quality_weights' => ['raw_signals'=>1.0,'summary_only'=>0.6,'no_data'=>0.0],
];

$an = new BehaviorAnalyzer($pdo, $config);

$scores = ['focus'=>0.0,'frustration'=>0.0,'boredom'=>0.0,'joy'=>0.0];
$ruleHits = [];
$ref = new ReflectionClass(BehaviorAnalyzer::class);
$m = $ref->getMethod('applySummaryRules');
$m->setAccessible(true);
$signals = ['latency_avg'=>2000.0,'error_rate'=>0.05];
$m->invoke($an, $signals, 'snake_retro', 8, $scores, $ruleHits);
var_dump($scores, $ruleHits);
