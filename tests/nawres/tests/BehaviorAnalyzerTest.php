<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;


/**
 * Test Suite : BehaviorAnalyzer
 *
 * Couverture :
 *   - Tests fonctionnels  : logique métier (règles, scoring, états, pondération)
 *   - Tests de robustesse : entrées invalides, nulles, vides, limites extrêmes
 *
 * Dépendances : PHPUnit ^10, PHP >= 8.1
 * Lancement   : ./vendor/bin/phpunit tests/BehaviorAnalyzerTest.php
 */
class BehaviorAnalyzerTest extends TestCase
{
    // ─────────────────────────────────────────────
    //  Helpers
    // ─────────────────────────────────────────────

    /** Retourne un mock PDO qui ne lève jamais d'exception. */
    private function makePdo(): PDO
    {
        return $this->createMock(PDO::class);
    }

    /** Retourne l'analyseur avec la config de prod (depuis le fichier). */
    private function makeAnalyzer(?array $config = null): BehaviorAnalyzer
    {
        $pdo = $this->makePdo();
        return new BehaviorAnalyzer($pdo, $config ?? $this->defaultConfig());
    }

    /** Config minimale isolée des fichiers disque pour les tests unitaires. */
    private function defaultConfig(): array
    {
        return [
            'default_weight'                    => 1.0,
            'game_weights'                      => [
                'memory_game'   => 1.2,
                'snake_retro'   => 1.2,
                'whack_a_mole'  => 1.2,
                'tower_blocks'  => 1.15,
                'canon_defender'=> 1.15,
                'math_sprint'   => 1.1,
                'tile_puzzle'   => 0.95,
                'sudoku'        => 0.9,
            ],
            'baseline_latency_ms'               => [
                'memory_game' => ['4-6' => 2500, '7-9' => 1600, '10-12' => 1000],
                'snake_retro' => ['4-6' => 1200, '7-9' => 800,  '10-12' => 500],
                'default'     => ['4-6' => 2000, '7-9' => 1300, '10-12' => 900],
            ],
            'baseline_error_rate'               => ['4-6' => 0.20, '7-9' => 0.12, '10-12' => 0.06],
            'decay_interval_ms'                 => 30000,
            'decay_multiplier'                  => 0.8,
            'boredom_latency_ratio'             => 2.0,
            'boredom_consecutive'               => 3,
            'boredom_long_pause_ms'             => 10000,
            'focus_latency_variance'            => 0.15,
            'focus_consecutive_success'         => 3,
            'quick_recovery_multiplier'         => 1.2,
            'frustration_spam_window_ms'        => 500,
            'frustration_spam_count'            => 3,
            'frustration_error_spiral_window_ms'=> 3000,
            'joy_success_streak'                => 5,
            'analysis_weights'                  => ['previous_1' => 0.10, 'previous_2' => 0.03],
            'analysis_weights_decay_days'       => 7.0,
            'data_quality_weights'              => [
                'raw_signals'   => 1.0,
                'summary_only'  => 0.6,
                'no_data'       => 0.0,
            ],
            'min_sessions' => 2,
        ];
    }

    /**
     * Construit une session factice avec signaux bruts (raw_signals JSON).
     *
     * @param list<array{signal:string,ts:int,value?:float|null}> $rawEvents
     */
    private function makeSession(array $rawEvents, string $slug = 'snake_retro', int $sessionId = 1): array
    {
        return [
            'session_id'  => $sessionId,
            'child_id'    => 42,
            'game_id'     => 1,
            'start_time'  => '2024-01-01 10:00:00',
            'end_time'    => '2024-01-01 10:30:00',
            'signals'     => null,
            'raw_signals' => json_encode($rawEvents),
            'slug'        => $slug,
            'name'        => $slug,
        ];
    }

    /**
     * Construit une session résumé (signals JSON, sans raw_signals).
     *
     * @param array{latency_avg?:float,error_rate?:float,success?:int} $signals
     */
    private function makeSummarySession(array $signals, string $slug = 'snake_retro'): array
    {
        return [
            'session_id'  => 1,
            'child_id'    => 42,
            'game_id'     => 1,
            'start_time'  => '2024-01-01 10:00:00',
            'end_time'    => '2024-01-01 10:30:00',
            'signals'     => json_encode($signals),
            'raw_signals' => null,
            'slug'        => $slug,
            'name'        => $slug,
        ];
    }

    // ─────────────────────────────────────────────
    //  GROUPE 1 — resolveState (état dominant)
    // ─────────────────────────────────────────────

    /** @dataProvider provideResolveState */
    public function test_resolveState(array $scores, string $expectedState): void
    {
        // resolveState est privé → on passe par analyzeSessionRecord
        $analyzer = $this->makeAnalyzer();

        // On injecte des scores artificiels via un stub de analyzeSession.
        // La méthode publique analyzeSessionRecord appelle resolveState.
        // On construit une session vide (no_data) et on vérifie que le fallback
        // "Neutral" s'applique, puis on teste via la réflexion.
        $ref    = new ReflectionClass(BehaviorAnalyzer::class);
        $method = $ref->getMethod('resolveState');
        $method->setAccessible(true);

        [$state] = $method->invoke($analyzer, $scores);

        $this->assertSame($expectedState, $state);
    }

    public static function provideResolveState(): array
    {
        return [
            'tous zéros → Neutral'                    => [
                ['focus' => 0, 'frustration' => 0, 'boredom' => 0, 'joy' => 0],
                'Neutral / Evaluating',
            ],
            'frustration dominante haute → Agitated'  => [
                ['focus' => 2, 'frustration' => 20, 'boredom' => 1, 'joy' => 1],
                'Agitated / Angry',
            ],
            'focus dominant fort → High Engagement'   => [
                ['focus' => 30, 'frustration' => 2, 'boredom' => 1, 'joy' => 2],
                'High Engagement',
            ],
            'boredom dominant → Disengaged'           => [
                ['focus' => 2, 'frustration' => 2, 'boredom' => 20, 'joy' => 1],
                'Disengaged / Sad',
            ],
            'joy dominant → Happy'                    => [
                ['focus' => 2, 'frustration' => 1, 'boredom' => 1, 'joy' => 20],
                'Happy / Confident',
            ],
            'scores très proches → Neutral (faible diff)' => [
                ['focus' => 10, 'frustration' => 10, 'boredom' => 9, 'joy' => 9],
                'Neutral / Evaluating',
            ],
            'focus légèrement dominant sous 50% → Neutral' => [
                ['focus' => 12, 'frustration' => 9, 'boredom' => 9, 'joy' => 8],
                'Neutral / Evaluating',
            ],
        ];
    }

    // ─────────────────────────────────────────────
    //  GROUPE 2 — resolveAgeGroup
    // ─────────────────────────────────────────────

    /** @dataProvider provideAgeGroups */
    public function test_resolveAgeGroup(?int $age, string $expected): void
    {
        $analyzer = $this->makeAnalyzer();
        $ref      = new ReflectionClass(BehaviorAnalyzer::class);
        $method   = $ref->getMethod('resolveAgeGroup');
        $method->setAccessible(true);

        $this->assertSame($expected, $method->invoke($analyzer, $age));
    }

    public static function provideAgeGroups(): array
    {
        return [
            'null → groupe par défaut 7-9'  => [null, '7-9'],
            'age 0 → groupe par défaut 7-9' => [0,    '7-9'],
            'age -5 → groupe par défaut 7-9'=> [-5,   '7-9'],
            'age 4 → 4-6'                   => [4,    '4-6'],
            'age 6 → 4-6'                   => [6,    '4-6'],
            'age 7 → 7-9'                   => [7,    '7-9'],
            'age 9 → 7-9'                   => [9,    '7-9'],
            'age 10 → 10-12'                => [10,   '10-12'],
            'age 12 → 10-12'                => [12,   '10-12'],
            'age 15 → 10-12 (hors range)'   => [15,   '10-12'],
        ];
    }

    // ─────────────────────────────────────────────
    //  GROUPE 3 — getBaselineLatency
    // ─────────────────────────────────────────────

    public function test_getBaselineLatency_knownGame_knownAge(): void
    {
        $analyzer = $this->makeAnalyzer();
        $ref      = new ReflectionClass(BehaviorAnalyzer::class);
        $m        = $ref->getMethod('getBaselineLatency');
        $m->setAccessible(true);

        $this->assertSame(1600.0, $m->invoke($analyzer, 'memory_game', 8));
    }

    public function test_getBaselineLatency_unknownGame_fallsBackToDefault(): void
    {
        $analyzer = $this->makeAnalyzer();
        $ref      = new ReflectionClass(BehaviorAnalyzer::class);
        $m        = $ref->getMethod('getBaselineLatency');
        $m->setAccessible(true);

        $this->assertSame(1300.0, $m->invoke($analyzer, 'nonexistent_game', 8));
    }

    public function test_getBaselineLatency_noConfigAtAll_returns1500(): void
    {
        $analyzer = $this->makeAnalyzer(['baseline_latency_ms' => []]);
        $ref      = new ReflectionClass(BehaviorAnalyzer::class);
        $m        = $ref->getMethod('getBaselineLatency');
        $m->setAccessible(true);

        $this->assertSame(1500.0, $m->invoke($analyzer, 'whatever', null));
    }

    // ─────────────────────────────────────────────
    //  GROUPE 4 — getGameWeight
    // ─────────────────────────────────────────────

    public function test_getGameWeight_knownSlug(): void
    {
        $analyzer = $this->makeAnalyzer();
        $ref      = new ReflectionClass(BehaviorAnalyzer::class);
        $m        = $ref->getMethod('getGameWeight');
        $m->setAccessible(true);

        $this->assertSame(1.2, $m->invoke($analyzer, 'memory_game'));
    }

    public function test_getGameWeight_unknownSlug_returnsDefault(): void
    {
        $analyzer = $this->makeAnalyzer();
        $ref      = new ReflectionClass(BehaviorAnalyzer::class);
        $m        = $ref->getMethod('getGameWeight');
        $m->setAccessible(true);

        $this->assertSame(1.0, $m->invoke($analyzer, 'unknown_game_xyz'));
    }

    // ─────────────────────────────────────────────
    //  GROUPE 5 — decodeJson
    // ─────────────────────────────────────────────

    /** @dataProvider provideDecodeJson */
    public function test_decodeJson($input, array $default, array $expected): void
    {
        $analyzer = $this->makeAnalyzer();
        $ref      = new ReflectionClass(BehaviorAnalyzer::class);
        $m        = $ref->getMethod('decodeJson');
        $m->setAccessible(true);

        $this->assertSame($expected, $m->invoke($analyzer, $input, $default));
    }

    public static function provideDecodeJson(): array
    {
        return [
            'null → default'                => [null,          ['x' => 1], ['x' => 1]],
            'chaîne vide → default'         => ['',            ['x' => 1], ['x' => 1]],
            'json valide'                   => ['{"a":2}',     [],          ['a' => 2]],
            'json invalide → default'       => ['not-json',    ['x' => 1], ['x' => 1]],
            'json tableau indexé'           => ['[1,2,3]',     [],          [1, 2, 3]],
            'déjà un tableau → passthrough' => [['k' => 'v'],  [],          ['k' => 'v']],
        ];
    }

    // ─────────────────────────────────────────────
    //  GROUPE 6 — normalizeRawEvents
    // ─────────────────────────────────────────────

    public function test_normalizeRawEvents_filtersInvalidEntries(): void
    {
        $analyzer = $this->makeAnalyzer();
        $ref      = new ReflectionClass(BehaviorAnalyzer::class);
        $m        = $ref->getMethod('normalizeRawEvents');
        $m->setAccessible(true);

        $raw = [
            ['signal' => 'reaction', 'ts' => 1000, 'value' => 500.0],
            ['signal' => '',         'ts' => 2000],          // signal vide → ignoré
            ['ts' => 3000],                                   // pas de signal → ignoré
            'not_an_array',                                   // scalaire → ignoré
            ['signal' => 'error', 'ts' => 4000],             // value absente → null
            ['signal' => 'success', 'ts' => 5000, 'value' => null],
        ];

        $result = $m->invoke($analyzer, $raw);

        $this->assertCount(3, $result);
        $this->assertSame('reaction', $result[0]['signal']);
        $this->assertSame(500.0,      $result[0]['value']);
        $this->assertSame('error',    $result[1]['signal']);
        $this->assertNull($result[1]['value']);
        $this->assertSame('success',  $result[2]['signal']);
    }

    public function test_normalizeRawEvents_emptyInput_returnsEmpty(): void
    {
        $analyzer = $this->makeAnalyzer();
        $ref      = new ReflectionClass(BehaviorAnalyzer::class);
        $m        = $ref->getMethod('normalizeRawEvents');
        $m->setAccessible(true);

        $this->assertSame([], $m->invoke($analyzer, []));
    }

    public function test_normalizeRawEvents_preservesPairId(): void
    {
        $analyzer = $this->makeAnalyzer();
        $ref      = new ReflectionClass(BehaviorAnalyzer::class);
        $m        = $ref->getMethod('normalizeRawEvents');
        $m->setAccessible(true);

        $raw    = [['signal' => 'error', 'ts' => 1000, 'pair_id' => 'AB']];
        $result = $m->invoke($analyzer, $raw);

        $this->assertArrayHasKey('pair_id', $result[0]);
        $this->assertSame('AB', $result[0]['pair_id']);
    }

    // ─────────────────────────────────────────────
    //  GROUPE 7 — detectPairTracking
    // ─────────────────────────────────────────────

    public function test_detectPairTracking_withPairId_enabled(): void
    {
        $analyzer = $this->makeAnalyzer();
        $ref      = new ReflectionClass(BehaviorAnalyzer::class);
        $m        = $ref->getMethod('detectPairTracking');
        $m->setAccessible(true);

        $events = [['signal' => 'error', 'ts' => 1000, 'pair_id' => 'XY']];
        $result = $m->invoke($analyzer, $events);

        $this->assertTrue($result['enabled']);
        $this->assertSame('XY', ($result['extractor'])($events[0]));
    }

    public function test_detectPairTracking_withoutPairId_disabled(): void
    {
        $analyzer = $this->makeAnalyzer();
        $ref      = new ReflectionClass(BehaviorAnalyzer::class);
        $m        = $ref->getMethod('detectPairTracking');
        $m->setAccessible(true);

        $events = [['signal' => 'error', 'ts' => 1000]];
        $result = $m->invoke($analyzer, $events);

        $this->assertFalse($result['enabled']);
    }

    // ─────────────────────────────────────────────
    //  GROUPE 8 — slugify
    // ─────────────────────────────────────────────

    /** @dataProvider provideSlugs */
    public function test_slugify(string $input, string $expected): void
    {
        $analyzer = $this->makeAnalyzer();
        $ref      = new ReflectionClass(BehaviorAnalyzer::class);
        $m        = $ref->getMethod('slugify');
        $m->setAccessible(true);

        $this->assertSame($expected, $m->invoke($analyzer, $input));
    }

    public static function provideSlugs(): array
    {
        return [
            'espaces → underscores'          => ['Memory Game',   'memory_game'],
            'tirets → underscores'           => ['snake-retro',   'snake_retro'],
            'majuscules → minuscules'        => ['MATH SPRINT',   'math_sprint'],
            'underscores de bord supprimés'  => ['  tower_blocks ', 'tower_blocks'],
            'chaîne vide'                    => ['',              ''],
            'caractères spéciaux'            => ['game!!!v2',     'game_v2'],
        ];
    }

    // ─────────────────────────────────────────────
    //  GROUPE 9 — applySummaryRules (functional)
    // ─────────────────────────────────────────────

    public function test_applySummaryRules_highLatency_increasesBoredom(): void
    {
        $analyzer = $this->makeAnalyzer();
        $ref      = new ReflectionClass(BehaviorAnalyzer::class);
        $m        = $ref->getMethod('applySummaryRules');
        $m->setAccessible(true);

        $scores   = ['focus' => 0.0, 'frustration' => 0.0, 'boredom' => 0.0, 'joy' => 0.0];
        $ruleHits = [];
        // baseline snake_retro age 8 = 800 ms → 2× = 1600 ms
        $signals = ['latency_avg' => 2000.0, 'error_rate' => 0.05];

        $args = [$signals, 'snake_retro', 8, &$scores, &$ruleHits];
        $m->invokeArgs($analyzer, $args);

        $this->assertGreaterThan(0.0, $scores['boredom']);
        $this->assertArrayHasKey('summary_sluggish', $ruleHits);
    }

    public function test_applySummaryRules_highErrorRate_increasesFrustration(): void
    {
        $analyzer = $this->makeAnalyzer();
        $ref      = new ReflectionClass(BehaviorAnalyzer::class);
        $m        = $ref->getMethod('applySummaryRules');
        $m->setAccessible(true);

        $scores   = ['focus' => 0.0, 'frustration' => 0.0, 'boredom' => 0.0, 'joy' => 0.0];
        $ruleHits = [];
        // baseline error rate age 8 = 0.12 → on dépasse avec 0.25
        $signals = ['error_rate' => 0.25, 'latency_avg' => 1000.0];

        $args = [$signals, 'snake_retro', 8, &$scores, &$ruleHits];
        $m->invokeArgs($analyzer, $args);

        $this->assertGreaterThan(0.0, $scores['frustration']);
        $this->assertArrayHasKey('summary_error_rate', $ruleHits);
    }

    public function test_applySummaryRules_lowErrorAndLatency_increasesFocus(): void
    {
        $analyzer = $this->makeAnalyzer();
        $ref      = new ReflectionClass(BehaviorAnalyzer::class);
        $m        = $ref->getMethod('applySummaryRules');
        $m->setAccessible(true);

        $scores   = ['focus' => 0.0, 'frustration' => 0.0, 'boredom' => 0.0, 'joy' => 0.0];
        $ruleHits = [];
        // baseline snake_retro age 8 = 800 ms, error 0.12 ; on met 0.05 errors et 800 ms
        $signals = ['error_rate' => 0.04, 'latency_avg' => 800.0, 'success' => 10];

        $args = [$signals, 'snake_retro', 8, &$scores, &$ruleHits];
        $m->invokeArgs($analyzer, $args);

        $this->assertGreaterThan(0.0, $scores['focus']);
        $this->assertArrayHasKey('summary_focus', $ruleHits);
    }

    public function test_applySummaryRules_highSuccessCount_incrementsJoy(): void
    {
        $analyzer = $this->makeAnalyzer();
        $ref      = new ReflectionClass(BehaviorAnalyzer::class);
        $m        = $ref->getMethod('applySummaryRules');
        $m->setAccessible(true);

        $scores   = ['focus' => 0.0, 'frustration' => 0.0, 'boredom' => 0.0, 'joy' => 0.0];
        $ruleHits = [];
        $signals  = ['success' => 10, 'error_rate' => 0.05]; // error_rate < 0.7× baseline

        $args = [$signals, 'snake_retro', 8, &$scores, &$ruleHits];
        $m->invokeArgs($analyzer, $args);

        $this->assertGreaterThan(0.0, $scores['joy']);
        $this->assertArrayHasKey('summary_joy', $ruleHits);
    }

    public function test_applySummaryRules_emptySignals_noScoreChange(): void
    {
        $analyzer = $this->makeAnalyzer();
        $ref      = new ReflectionClass(BehaviorAnalyzer::class);
        $m        = $ref->getMethod('applySummaryRules');
        $m->setAccessible(true);

        $scores   = ['focus' => 0.0, 'frustration' => 0.0, 'boredom' => 0.0, 'joy' => 0.0];
        $ruleHits = [];

        $args = [[], 'snake_retro', 8, &$scores, &$ruleHits];
        $m->invokeArgs($analyzer, $args);

        $this->assertSame(0.0, $scores['focus'] + $scores['frustration'] + $scores['boredom'] + $scores['joy']);
    }

    // ─────────────────────────────────────────────
    //  GROUPE 10 — applyEventRules (functional)
    // ─────────────────────────────────────────────

    public function test_applyEventRules_longPause_increasesBoredom(): void
    {
        $analyzer = $this->makeAnalyzer();
        $ref      = new ReflectionClass(BehaviorAnalyzer::class);
        $m        = $ref->getMethod('applyEventRules');
        $m->setAccessible(true);

        $scores   = ['focus' => 0.0, 'frustration' => 0.0, 'boredom' => 0.0, 'joy' => 0.0];
        $ruleHits = [];

        // Pause de 15 000 ms entre deux events (> boredom_long_pause_ms=10000)
        $events = [
            ['signal' => 'reaction', 'ts' => 0,     'value' => 800.0],
            ['signal' => 'reaction', 'ts' => 15000, 'value' => 800.0],
        ];

        $args = [$events, 'snake_retro', 8, &$scores, &$ruleHits];
        $m->invokeArgs($analyzer, $args);

        $this->assertGreaterThan(0.0, $scores['boredom']);
        $this->assertArrayHasKey('long_pause', $ruleHits);
    }

    public function test_applyEventRules_spamReactions_increasesFrustration(): void
    {
        $analyzer = $this->makeAnalyzer();
        $ref      = new ReflectionClass(BehaviorAnalyzer::class);
        $m        = $ref->getMethod('applyEventRules');
        $m->setAccessible(true);

        $scores   = ['focus' => 0.0, 'frustration' => 0.0, 'boredom' => 0.0, 'joy' => 0.0];
        $ruleHits = [];

        // 3 réactions en moins de 500 ms → spam_detector
        $events = [
            ['signal' => 'reaction', 'ts' => 100, 'value' => null],
            ['signal' => 'reaction', 'ts' => 200, 'value' => null],
            ['signal' => 'reaction', 'ts' => 300, 'value' => null],
        ];

        $args = [$events, 'snake_retro', 8, &$scores, &$ruleHits];
        $m->invokeArgs($analyzer, $args);

        $this->assertGreaterThan(0.0, $scores['frustration']);
        $this->assertArrayHasKey('spam_detector', $ruleHits);
    }

    public function test_applyEventRules_errorSpiral_increasesFrustration(): void
    {
        $analyzer = $this->makeAnalyzer();
        $ref      = new ReflectionClass(BehaviorAnalyzer::class);
        $m        = $ref->getMethod('applyEventRules');
        $m->setAccessible(true);

        $scores   = ['focus' => 0.0, 'frustration' => 0.0, 'boredom' => 0.0, 'joy' => 0.0];
        $ruleHits = [];

        // Deux erreurs à 1000 ms d'intervalle (< errorSpiralWindow=3000)
        $events = [
            ['signal' => 'error', 'ts' => 1000],
            ['signal' => 'error', 'ts' => 2000],
        ];

        $args = [$events, 'snake_retro', 8, &$scores, &$ruleHits];
        $m->invokeArgs($analyzer, $args);

        $this->assertGreaterThan(0.0, $scores['frustration']);
        $this->assertArrayHasKey('error_spiral', $ruleHits);
    }

    public function test_applyEventRules_joyStreak_incrementsJoy(): void
    {
        $analyzer = $this->makeAnalyzer();
        $ref      = new ReflectionClass(BehaviorAnalyzer::class);
        $m        = $ref->getMethod('applyEventRules');
        $m->setAccessible(true);

        $scores   = ['focus' => 0.0, 'frustration' => 0.0, 'boredom' => 0.0, 'joy' => 0.0];
        $ruleHits = [];

        // 5 succès consécutifs (joy_success_streak=5)
        $events = [];
        for ($i = 1; $i <= 5; $i++) {
            $events[] = ['signal' => 'success', 'ts' => $i * 1000];
        }

        $args = [$events, 'snake_retro', 8, &$scores, &$ruleHits];
        $m->invokeArgs($analyzer, $args);

        $this->assertGreaterThan(0.0, $scores['joy']);
        $this->assertArrayHasKey('joy_streak', $ruleHits);
    }

    public function test_applyEventRules_quickRecovery_increasesFocus(): void
    {
        $analyzer = $this->makeAnalyzer();
        $ref      = new ReflectionClass(BehaviorAnalyzer::class);
        $m        = $ref->getMethod('applyEventRules');
        $m->setAccessible(true);

        $scores   = ['focus' => 0.0, 'frustration' => 0.0, 'boredom' => 0.0, 'joy' => 0.0];
        $ruleHits = [];

        // baseline snake_retro age 8 = 800 ms ; quickRecoveryMultiplier=1.2 → 960 ms
        // Succès à +500 ms après une erreur = récupération rapide
        $events = [
            ['signal' => 'error',   'ts' => 1000],
            ['signal' => 'success', 'ts' => 1500],
        ];

        $args = [$events, 'snake_retro', 8, &$scores, &$ruleHits];
        $m->invokeArgs($analyzer, $args);

        $this->assertGreaterThan(0.0, $scores['focus']);
        $this->assertArrayHasKey('quick_recovery', $ruleHits);
    }

    public function test_applyEventRules_sluggishReactions_incrementsBoredom(): void
    {
        $analyzer = $this->makeAnalyzer();
        $ref      = new ReflectionClass(BehaviorAnalyzer::class);
        $m        = $ref->getMethod('applyEventRules');
        $m->setAccessible(true);

        $scores   = ['focus' => 0.0, 'frustration' => 0.0, 'boredom' => 0.0, 'joy' => 0.0];
        $ruleHits = [];

        // baseline 800 ms × 2 = 1600 ms ; on envoie 3 réactions à 2000 ms (> ratio)
        $events = [
            ['signal' => 'reaction', 'ts' => 0,    'value' => 2000.0],
            ['signal' => 'reaction', 'ts' => 3000, 'value' => 2000.0],
            ['signal' => 'reaction', 'ts' => 6000, 'value' => 2000.0],
        ];

        $args = [$events, 'snake_retro', 8, &$scores, &$ruleHits];
        $m->invokeArgs($analyzer, $args);

        $this->assertGreaterThan(0.0, $scores['boredom']);
        $this->assertArrayHasKey('sluggish', $ruleHits);
    }

    public function test_applyEventRules_decayApplied_afterInterval(): void
    {
        $analyzer = $this->makeAnalyzer();
        $ref      = new ReflectionClass(BehaviorAnalyzer::class);
        $m        = $ref->getMethod('applyEventRules');
        $m->setAccessible(true);

        // On donne d'abord quelques points de frustration, puis on saute 31 s → decay
        $scores   = ['focus' => 0.0, 'frustration' => 10.0, 'boredom' => 0.0, 'joy' => 0.0];
        $ruleHits = [];

        $events = [
            ['signal' => 'error', 'ts' => 0],
            ['signal' => 'error', 'ts' => 31000], // > decay_interval_ms=30000
        ];

        $args = [$events, 'snake_retro', 8, &$scores, &$ruleHits];
        $m->invokeArgs($analyzer, $args);

        $this->assertArrayHasKey('decay_applied', $ruleHits);
    }

    // ─────────────────────────────────────────────
    //  GROUPE 11 — memory_game : règle pair repeat
    // ─────────────────────────────────────────────

    public function test_memoryGame_sameErrorPairTwice_increasesFrustration(): void
    {
        $analyzer = $this->makeAnalyzer();
        $ref      = new ReflectionClass(BehaviorAnalyzer::class);
        $m        = $ref->getMethod('applyEventRules');
        $m->setAccessible(true);

        $scores   = ['focus' => 0.0, 'frustration' => 0.0, 'boredom' => 0.0, 'joy' => 0.0];
        $ruleHits = [];

        // memory_game avec pair_id : même paire AB touchée deux fois
        // Les 2 premières erreurs sont "gratuites" (memoryErrorCount <= 2), la 3e est éligible
        $events = [
            ['signal' => 'error', 'ts' => 1000, 'pair_id' => 'AB'],
            ['signal' => 'error', 'ts' => 2000, 'pair_id' => 'CD'],
            ['signal' => 'error', 'ts' => 3000, 'pair_id' => 'AB'], // même paire → +4
        ];

        $m->invoke($analyzer, $events, 'memory_game', 8, $scores, $ruleHits);

        $this->assertArrayHasKey('memory_pair_repeat', $ruleHits);
        $this->assertGreaterThan(0.0, $scores['frustration']);
    }

    // ─────────────────────────────────────────────
    //  GROUPE 12 — analyzeSessionRecord (public API)
    // ─────────────────────────────────────────────

    public function test_analyzeSessionRecord_rawSignals_returnsRawQuality(): void
    {
        // Mock PDO qui ne fait rien (pas de fetchRawEventsFromTable si raw_signals non vide)
        $pdoStmt = $this->createMock(PDOStatement::class);
        $pdoStmt->method('execute')->willReturn(true);
        $pdoStmt->method('fetchAll')->willReturn([]);

        $pdo = $this->createMock(PDO::class);
        $pdo->method('prepare')->willReturn($pdoStmt);

        $analyzer = new BehaviorAnalyzer($pdo, $this->defaultConfig());

        $events  = [['signal' => 'success', 'ts' => 1000]];
        $session = $this->makeSession($events, 'snake_retro');

        $result = $analyzer->analyzeSessionRecord($session, 8);

        $this->assertSame('raw_signals', $result['data_quality']);
        $this->assertArrayHasKey('scores', $result);
        $this->assertArrayHasKey('state', $result);
        $this->assertArrayHasKey('confidence', $result);
    }

    public function test_analyzeSessionRecord_noData_returnsNoDataQuality(): void
    {
        $pdoStmt = $this->createMock(PDOStatement::class);
        $pdoStmt->method('execute')->willReturn(true);
        $pdoStmt->method('fetchAll')->willReturn([]);

        $pdo = $this->createMock(PDO::class);
        $pdo->method('prepare')->willReturn($pdoStmt);

        $analyzer = new BehaviorAnalyzer($pdo, $this->defaultConfig());

        $session = $this->makeSession([], 'snake_retro');
        $session['raw_signals'] = null; // aucune donnée brute

        $result = $analyzer->analyzeSessionRecord($session, 8);

        $this->assertSame('no_data', $result['data_quality']);
    }

    public function test_analyzeSessionRecord_summaryOnly_returnsCorrectQuality(): void
    {
        $pdoStmt = $this->createMock(PDOStatement::class);
        $pdoStmt->method('execute')->willReturn(true);
        $pdoStmt->method('fetchAll')->willReturn([]);

        $pdo = $this->createMock(PDO::class);
        $pdo->method('prepare')->willReturn($pdoStmt);

        $analyzer = new BehaviorAnalyzer($pdo, $this->defaultConfig());
        $session  = $this->makeSummarySession(['latency_avg' => 900.0, 'error_rate' => 0.10]);

        $result = $analyzer->analyzeSessionRecord($session, 8);

        $this->assertSame('summary_only', $result['data_quality']);
    }

    // ─────────────────────────────────────────────
    //  GROUPE 13 — storeAnalysis (mock PDO)
    // ─────────────────────────────────────────────

    public function test_storeAnalysis_executesInsertAndReturnsTrue(): void
    {
        $stmt = $this->createMock(PDOStatement::class);
        $stmt->expects($this->once())->method('execute')->willReturn(true);

        $pdo = $this->createMock(PDO::class);
        $pdo->expects($this->once())->method('prepare')->willReturn($stmt);

        $analyzer = new BehaviorAnalyzer($pdo, $this->defaultConfig());

        $analysis = [
            'child_id'      => 1,
            'period_start'  => '2024-01-01 00:00:00',
            'period_end'    => '2024-01-02 00:00:00',
            'session_count' => 3,
            'merged_scores' => ['focus' => 5.0, 'frustration' => 2.0, 'boredom' => 1.0, 'joy' => 4.0],
            'dominant_state'=> 'High Engagement',
            'confidence'    => 62.5,
            'details'       => [],
            'source_weights'=> [],
        ];

        $this->assertTrue($analyzer->storeAnalysis($analysis));
    }

    public function test_storeAnalysis_pdoFailure_returnsFalse(): void
    {
        $stmt = $this->createMock(PDOStatement::class);
        $stmt->method('execute')->willReturn(false);

        $pdo = $this->createMock(PDO::class);
        $pdo->method('prepare')->willReturn($stmt);

        $analyzer = new BehaviorAnalyzer($pdo, $this->defaultConfig());

        $analysis = [
            'child_id'      => 1,
            'period_start'  => '2024-01-01 00:00:00',
            'period_end'    => '2024-01-02 00:00:00',
            'session_count' => 3,
            'merged_scores' => ['focus' => 1.0, 'frustration' => 0.0, 'boredom' => 0.0, 'joy' => 0.0],
            'dominant_state'=> 'Neutral / Evaluating',
            'confidence'    => 0.0,
            'details'       => [],
            'source_weights'=> [],
        ];

        $this->assertFalse($analyzer->storeAnalysis($analysis));
    }

    // ─────────────────────────────────────────────
    //  GROUPE 14 — runPeriodAnalysis (mock PDO complet)
    // ─────────────────────────────────────────────

    public function test_runPeriodAnalysis_noEligibleChildren_returnsZeroCounts(): void
    {
        // getEligibleChildren → tableau vide
        $stmt = $this->createMock(PDOStatement::class);
        $stmt->method('execute')->willReturn(true);
        $stmt->method('fetchAll')->willReturn([]);

        $pdo = $this->createMock(PDO::class);
        $pdo->method('prepare')->willReturn($stmt);

        $analyzer = new BehaviorAnalyzer($pdo, $this->defaultConfig());
        $end      = new DateTimeImmutable('2024-01-02');
        $start    = new DateTimeImmutable('2024-01-01');

        $result = $analyzer->runPeriodAnalysis($start, $end, 2);

        $this->assertSame(0, $result['eligible_children']);
        $this->assertSame(0, $result['stored']);
        $this->assertSame(0, $result['skipped']);
        $this->assertSame(0, $result['errors']);
    }

    // ─────────────────────────────────────────────
    //  GROUPE 15 — analyzeChildAllSessions (mock PDO)
    // ─────────────────────────────────────────────

    public function test_analyzeChildAllSessions_noSessions_returnsNull(): void
    {
        // getChildSessionBounds → row vide
        $stmt = $this->createMock(PDOStatement::class);
        $stmt->method('execute')->willReturn(true);
        $stmt->method('fetch')->willReturn(['start_time' => null, 'end_time' => null]);

        $pdo = $this->createMock(PDO::class);
        $pdo->method('prepare')->willReturn($stmt);

        $analyzer = new BehaviorAnalyzer($pdo, $this->defaultConfig());

        $this->assertNull($analyzer->analyzeChildAllSessions(999));
    }

    // ─────────────────────────────────────────────
    //  GROUPE 16 — mergeWithHistory (reflection)
    // ─────────────────────────────────────────────

    public function test_mergeWithHistory_noHistory_scoresUnchanged(): void
    {
        $stmt = $this->createMock(PDOStatement::class);
        $stmt->method('execute')->willReturn(true);
        $stmt->method('fetchAll')->willReturn([]);

        $pdo = $this->createMock(PDO::class);
        $pdo->method('prepare')->willReturn($stmt);

        $analyzer = new BehaviorAnalyzer($pdo, $this->defaultConfig());
        $ref      = new ReflectionClass(BehaviorAnalyzer::class);
        $m        = $ref->getMethod('mergeWithHistory');
        $m->setAccessible(true);

        $scores        = ['focus' => 10.0, 'frustration' => 2.0, 'boredom' => 1.0, 'joy' => 3.0];
        $sourceWeights = [];

        $merged = $m->invoke($analyzer, 42, $scores, new DateTimeImmutable('2024-01-02'), $sourceWeights);

        $this->assertSame(10.0, $merged['focus']);
        $this->assertSame(2.0,  $merged['frustration']);
        $this->assertEmpty($sourceWeights);
    }

    public function test_mergeWithHistory_withPreviousAnalysis_addsWeightedScores(): void
    {
        $previousRow = [
            'period_end'           => '2024-01-01 00:00:00',
            'focus_points'         => 10.0,
            'frustration_points'   => 2.0,
            'boredom_points'       => 1.0,
            'joy_points'           => 3.0,
        ];

        $stmt = $this->createMock(PDOStatement::class);
        $stmt->method('execute')->willReturn(true);
        $stmt->method('fetchAll')->willReturn([$previousRow]);

        $pdo = $this->createMock(PDO::class);
        $pdo->method('prepare')->willReturn($stmt);

        $analyzer = new BehaviorAnalyzer($pdo, $this->defaultConfig());
        $ref      = new ReflectionClass(BehaviorAnalyzer::class);
        $m        = $ref->getMethod('mergeWithHistory');
        $m->setAccessible(true);

        $scores        = ['focus' => 5.0, 'frustration' => 1.0, 'boredom' => 0.0, 'joy' => 0.0];
        $sourceWeights = [];

        $merged = $m->invoke($analyzer, 1, $scores, new DateTimeImmutable('2024-01-01 01:00:00'), $sourceWeights);

        // La valeur de focus doit augmenter (poids > 0, time_decay ≈ 1 car délai minime)
        $this->assertGreaterThan(5.0, $merged['focus']);
        $this->assertCount(1, $sourceWeights);
        $this->assertArrayHasKey('final_weight', $sourceWeights[0]);
    }

    // ─────────────────────────────────────────────
    //  GROUPE 17 — Tests de robustesse
    // ─────────────────────────────────────────────

    public function test_robustness_analyzeSessionRecord_nullAge(): void
    {
        $pdoStmt = $this->createMock(PDOStatement::class);
        $pdoStmt->method('execute')->willReturn(true);
        $pdoStmt->method('fetchAll')->willReturn([]);

        $pdo = $this->createMock(PDO::class);
        $pdo->method('prepare')->willReturn($pdoStmt);

        $analyzer = new BehaviorAnalyzer($pdo, $this->defaultConfig());
        $session  = $this->makeSession([['signal' => 'success', 'ts' => 1000]], 'snake_retro');

        // Ne doit pas lever d'exception avec age=null
        $result = $analyzer->analyzeSessionRecord($session, null);

        $this->assertIsArray($result);
    }

    public function test_robustness_analyzeSessionRecord_unknownGameSlug(): void
    {
        $pdoStmt = $this->createMock(PDOStatement::class);
        $pdoStmt->method('execute')->willReturn(true);
        $pdoStmt->method('fetchAll')->willReturn([]);

        $pdo = $this->createMock(PDO::class);
        $pdo->method('prepare')->willReturn($pdoStmt);

        $analyzer = new BehaviorAnalyzer($pdo, $this->defaultConfig());
        $session  = $this->makeSession([['signal' => 'success', 'ts' => 1000]], 'nonexistent_game_slug');

        $result = $analyzer->analyzeSessionRecord($session, 8);

        $this->assertIsArray($result);
        $this->assertSame('nonexistent_game_slug', $result['game_slug']);
    }

    public function test_robustness_analyzeSessionRecord_invalidJsonRawSignals(): void
    {
        $pdoStmt = $this->createMock(PDOStatement::class);
        $pdoStmt->method('execute')->willReturn(true);
        $pdoStmt->method('fetchAll')->willReturn([]);

        $pdo = $this->createMock(PDO::class);
        $pdo->method('prepare')->willReturn($pdoStmt);

        $analyzer = new BehaviorAnalyzer($pdo, $this->defaultConfig());

        $session                = $this->makeSession([], 'snake_retro');
        $session['raw_signals'] = '{invalid-json!!}'; // JSON invalide

        $result = $analyzer->analyzeSessionRecord($session, 8);

        $this->assertIsArray($result);
        // Le décodage échoue silencieusement → no_data ou summary_only
        $this->assertContains($result['data_quality'], ['no_data', 'summary_only', 'raw_signals']);
    }

    public function test_robustness_analyzeSessionRecord_emptyEventsArray(): void
    {
        $pdoStmt = $this->createMock(PDOStatement::class);
        $pdoStmt->method('execute')->willReturn(true);
        $pdoStmt->method('fetchAll')->willReturn([]);

        $pdo = $this->createMock(PDO::class);
        $pdo->method('prepare')->willReturn($pdoStmt);

        $analyzer = new BehaviorAnalyzer($pdo, $this->defaultConfig());
        $session  = $this->makeSession([], 'snake_retro');

        $result = $analyzer->analyzeSessionRecord($session, 8);

        $this->assertSame('no_data', $result['data_quality']);
        $this->assertSame(0.0, $result['scores']['focus']);
    }

    public function test_robustness_applyEventRules_singleEvent_noException(): void
    {
        $analyzer = $this->makeAnalyzer();
        $ref      = new ReflectionClass(BehaviorAnalyzer::class);
        $m        = $ref->getMethod('applyEventRules');
        $m->setAccessible(true);

        $scores   = ['focus' => 0.0, 'frustration' => 0.0, 'boredom' => 0.0, 'joy' => 0.0];
        $ruleHits = [];

        // Un seul event : aucune comparaison précédente possible
        $events = [['signal' => 'success', 'ts' => 1000]];

        $m->invoke($analyzer, $events, 'snake_retro', 8, $scores, $ruleHits);

        // Pas d'exception, scores inchangés ou légèrement modifiés
        $this->assertIsFloat($scores['focus']);
    }

    public function test_robustness_resolveState_negativeScores(): void
    {
        $analyzer = $this->makeAnalyzer();
        $ref      = new ReflectionClass(BehaviorAnalyzer::class);
        $m        = $ref->getMethod('resolveState');
        $m->setAccessible(true);

        // Scores négatifs (ne devraient pas arriver mais on vérifie la robustesse)
        $scores = ['focus' => -5.0, 'frustration' => -3.0, 'boredom' => -2.0, 'joy' => -1.0];
        [$state, $confidence] = $m->invoke($analyzer, $scores);

        $this->assertSame('Neutral / Evaluating', $state);
        $this->assertSame(0.0, $confidence);
    }

    public function test_robustness_mergeConfig_deepMerge(): void
    {
        $base     = ['a' => ['x' => 1, 'y' => 2], 'b' => 3];
        $override = ['a' => ['y' => 99, 'z' => 5], 'c' => 7];

        $ref = new ReflectionClass(BehaviorAnalyzer::class);
        $m   = $ref->getMethod('mergeConfig');
        $m->setAccessible(true);

        $result = $m->invoke(null, $base, $override);

        $this->assertSame(1,  $result['a']['x']); // conservé
        $this->assertSame(99, $result['a']['y']); // écrasé
        $this->assertSame(5,  $result['a']['z']); // ajouté
        $this->assertSame(3,  $result['b']);       // inchangé
        $this->assertSame(7,  $result['c']);       // ajouté
    }

    public function test_robustness_runPeriodAnalysis_defaultMinSessionsFromConfig(): void
    {
        $stmt = $this->createMock(PDOStatement::class);
        $stmt->method('execute')->willReturn(true);
        $stmt->method('fetchAll')->willReturn([]);

        $pdo = $this->createMock(PDO::class);
        $pdo->method('prepare')->willReturn($stmt);

        $config           = $this->defaultConfig();
        $config['min_sessions'] = 5;   // valeur personnalisée
        $analyzer = new BehaviorAnalyzer($pdo, $config);

        $end    = new DateTimeImmutable('2024-01-02');
        $start  = new DateTimeImmutable('2024-01-01');
        $result = $analyzer->runPeriodAnalysis($start, $end, null); // null → lit la config

        // Pas d'exception ; 0 enfants éligibles car le mock retourne []
        $this->assertSame(0, $result['eligible_children']);
    }

    public function test_robustness_getBaselineErrorRate_nullAge_returnsDefault(): void
    {
        $analyzer = $this->makeAnalyzer();
        $ref      = new ReflectionClass(BehaviorAnalyzer::class);
        $m        = $ref->getMethod('getBaselineErrorRate');
        $m->setAccessible(true);

        $rate = $m->invoke($analyzer, null);

        $this->assertSame(0.12, $rate); // groupe 7-9 par défaut
    }

    public function test_robustness_normalizeRawEvents_allInvalidEntries_returnsEmpty(): void
    {
        $analyzer = $this->makeAnalyzer();
        $ref      = new ReflectionClass(BehaviorAnalyzer::class);
        $m        = $ref->getMethod('normalizeRawEvents');
        $m->setAccessible(true);

        $raw    = [[], 'foo', 42, null, ['signal' => '']];
        $result = $m->invoke($analyzer, $raw);

        $this->assertSame([], $result);
    }
}
