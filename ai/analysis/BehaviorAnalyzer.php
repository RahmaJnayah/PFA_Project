<?php
declare(strict_types=1);

class BehaviorAnalyzer
{
	private PDO $pdo;
	private array $config;

	public function __construct(PDO $pdo, ?array $config = null)
	{
		$this->pdo = $pdo;
		$this->config = $config ?? self::loadConfig();
	}

	public static function loadConfig(): array
	{
		$config = include __DIR__ . '/analysis_config.php';
		if (!is_array($config)) {
			$config = [];
		}

		$overridePath = __DIR__ . '/analysis_config.override.php';
		if (is_file($overridePath)) {
			$override = include $overridePath;
			if (is_array($override)) {
				$config = self::mergeConfig($config, $override);
			}
		}

		return $config;
	}

	private static function mergeConfig(array $base, array $override): array
	{
		foreach ($override as $key => $value) {
			if (is_array($value) && isset($base[$key]) && is_array($base[$key])) {
				$base[$key] = self::mergeConfig($base[$key], $value);
			} else {
				$base[$key] = $value;
			}
		}
		return $base;
	}

	public function runPeriodAnalysis(
		DateTimeImmutable $periodStart,
		DateTimeImmutable $periodEnd,
		?int $minSessions = null
	): array {
		$minSessions = $minSessions ?? (int) ($this->config['min_sessions'] ?? 2);
		$eligible = $this->getEligibleChildren($periodStart, $periodEnd, $minSessions);
		$stored = 0;
		$skipped = 0;
		$errors = 0;

		foreach ($eligible as $childId => $sessionCount) {
			if ($this->analysisExists($childId, $periodStart, $periodEnd)) {
				$skipped++;
				continue;
			}

			$analysis = $this->analyzeChildPeriod($childId, $periodStart, $periodEnd, $minSessions, true);
			if ($analysis === null) {
				$skipped++;
				continue;
			}

			if ($this->storeAnalysis($analysis)) {
				$stored++;
			} else {
				$errors++;
			}
		}

		return [
			'eligible_children' => count($eligible),
			'stored' => $stored,
			'skipped' => $skipped,
			'errors' => $errors,
		];
	}

	public function analyzeChildPeriod(
		int $childId,
		DateTimeImmutable $periodStart,
		DateTimeImmutable $periodEnd,
		?int $minSessions = null,
		bool $mergeHistory = true
	): ?array {
		$minSessions = $minSessions ?? (int) ($this->config['min_sessions'] ?? 2);
		$childAge = $this->getChildAge($childId);
		$sessions = $this->fetchSessions($childId, $periodStart, $periodEnd);

		$sessionResults = [];
		$dataEligibleSessions = 0;

		foreach ($sessions as $session) {
			$result = $this->analyzeSession($session, $childAge);
			if ($result['data_quality'] !== 'no_data') {
				$dataEligibleSessions++;
			}
			$sessionResults[] = $result;
		}

		if ($dataEligibleSessions < $minSessions) {
			return null;
		}

		$aggregate = [
			'focus' => 0.0,
			'frustration' => 0.0,
			'boredom' => 0.0,
			'joy' => 0.0,
		];

		$perGame = [];
		$ruleHits = [];
		$dataQualityCounts = [
			'raw_signals' => 0,
			'summary_only' => 0,
			'no_data' => 0,
		];

		foreach ($sessionResults as $result) {
			$weight = $result['weight'];
			$scores = $result['scores'];

			$aggregate['focus'] += $scores['focus'] * $weight;
			$aggregate['frustration'] += $scores['frustration'] * $weight;
			$aggregate['boredom'] += $scores['boredom'] * $weight;
			$aggregate['joy'] += $scores['joy'] * $weight;

			$slug = $result['game_slug'] ?: 'unknown';
			if (!isset($perGame[$slug])) {
				$perGame[$slug] = [
					'weight' => 0.0,
					'sessions' => 0,
				];
			}
			$perGame[$slug]['weight'] += $weight;
			$perGame[$slug]['sessions']++;

			$dataQualityCounts[$result['data_quality']]++;

			foreach ($result['rule_hits'] as $rule => $count) {
				$ruleHits[$rule] = ($ruleHits[$rule] ?? 0) + $count;
			}
		}

		$merged = $aggregate;
		$sourceWeights = [];
		if ($mergeHistory) {
			$merged = $this->mergeWithHistory($childId, $merged, $periodEnd, $sourceWeights);
		}

		[$state, $confidence] = $this->resolveState($merged);

		return [
			'child_id' => $childId,
			'period_start' => $periodStart->format('Y-m-d H:i:s'),
			'period_end' => $periodEnd->format('Y-m-d H:i:s'),
			'session_count' => $dataEligibleSessions,
			'scores' => $aggregate,
			'merged_scores' => $merged,
			'dominant_state' => $state,
			'confidence' => $confidence,
			'details' => [
				'raw_scores' => $aggregate,
				'merged_scores' => $merged,
				'per_game' => $perGame,
				'rule_hits' => $ruleHits,
				'data_quality' => $dataQualityCounts,
				'source_weights' => $sourceWeights,
				'notes' => $this->buildNotes(),
			],
			'source_weights' => $sourceWeights,
		];
	}

	public function analyzeChildAllSessions(int $childId): ?array
	{
		$bounds = $this->getChildSessionBounds($childId);
		if ($bounds === null) {
			return null;
		}

		return $this->analyzeChildPeriod(
			$childId,
			new DateTimeImmutable($bounds['start_time']),
			new DateTimeImmutable($bounds['end_time']),
			1,
			false
		);
	}

	public function storeAnalysis(array $analysis): bool
	{
		$stmt = $this->pdo->prepare(
			'INSERT INTO child_behavior_analysis
				(child_id, period_start, period_end, session_count,
				 focus_points, frustration_points, boredom_points, joy_points,
				 dominant_state, confidence, details_json, source_weights, created_at)
			 VALUES
				(:child_id, :period_start, :period_end, :session_count,
				 :focus_points, :frustration_points, :boredom_points, :joy_points,
				 :dominant_state, :confidence, :details_json, :source_weights, NOW())
			 ON DUPLICATE KEY UPDATE
				 session_count = VALUES(session_count),
				 focus_points = VALUES(focus_points),
				 frustration_points = VALUES(frustration_points),
				 boredom_points = VALUES(boredom_points),
				 joy_points = VALUES(joy_points),
				 dominant_state = VALUES(dominant_state),
				 confidence = VALUES(confidence),
				 details_json = VALUES(details_json),
				 source_weights = VALUES(source_weights),
				 created_at = NOW()'
		);

		return $stmt->execute([
			':child_id' => $analysis['child_id'],
			':period_start' => $analysis['period_start'],
			':period_end' => $analysis['period_end'],
			':session_count' => $analysis['session_count'],
			':focus_points' => $analysis['merged_scores']['focus'],
			':frustration_points' => $analysis['merged_scores']['frustration'],
			':boredom_points' => $analysis['merged_scores']['boredom'],
			':joy_points' => $analysis['merged_scores']['joy'],
			':dominant_state' => $analysis['dominant_state'],
			':confidence' => $analysis['confidence'],
			':details_json' => json_encode($analysis['details'], JSON_UNESCAPED_UNICODE),
			':source_weights' => json_encode($analysis['source_weights'], JSON_UNESCAPED_UNICODE),
		]);
	}

	public function analyzeSessionRecord(array $session, ?int $childAge): array
	{
		$result = $this->analyzeSession($session, $childAge);
		[$state, $confidence] = $this->resolveState($result['scores']);

		return [
			'session_id' => $result['session_id'],
			'game_slug' => $result['game_slug'],
			'scores' => $result['scores'],
			'state' => $state,
			'confidence' => $confidence,
			'data_quality' => $result['data_quality'],
		];
	}

	private function analysisExists(int $childId, DateTimeImmutable $start, DateTimeImmutable $end): bool
	{
		$stmt = $this->pdo->prepare(
			'SELECT 1 FROM child_behavior_analysis
			 WHERE child_id = :child_id
			   AND period_start = :period_start
			   AND period_end = :period_end
			 LIMIT 1'
		);
		$stmt->execute([
			':child_id' => $childId,
			':period_start' => $start->format('Y-m-d H:i:s'),
			':period_end' => $end->format('Y-m-d H:i:s'),
		]);

		return (bool) $stmt->fetchColumn();
	}

	private function getEligibleChildren(
		DateTimeImmutable $periodStart,
		DateTimeImmutable $periodEnd,
		int $minSessions
	): array {
		$stmt = $this->pdo->prepare(
			'SELECT child_id, COUNT(*) AS session_count
			 FROM game_behaviors
			 WHERE start_time >= :start_time AND start_time < :end_time
			 GROUP BY child_id
			 HAVING COUNT(*) >= :min_sessions'
		);
		$stmt->execute([
			':start_time' => $periodStart->format('Y-m-d H:i:s'),
			':end_time' => $periodEnd->format('Y-m-d H:i:s'),
			':min_sessions' => $minSessions,
		]);

		$results = [];
		foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
			$results[(int) $row['child_id']] = (int) $row['session_count'];
		}

		return $results;
	}

	private function fetchSessions(
		int $childId,
		DateTimeImmutable $periodStart,
		DateTimeImmutable $periodEnd
	): array {
		$stmt = $this->pdo->prepare(
			'SELECT b.session_id, b.child_id, b.game_id, b.start_time, b.end_time,
					b.signals, b.raw_signals, g.slug, g.name
			 FROM game_behaviors b
			 JOIN games g ON g.game_id = b.game_id
			 WHERE b.child_id = :child_id
			   AND b.start_time >= :start_time
			   AND b.start_time < :end_time
			 ORDER BY b.start_time ASC'
		);
		$stmt->execute([
			':child_id' => $childId,
			':start_time' => $periodStart->format('Y-m-d H:i:s'),
			':end_time' => $periodEnd->format('Y-m-d H:i:s'),
		]);

		return $stmt->fetchAll(PDO::FETCH_ASSOC);
	}

	private function getChildSessionBounds(int $childId): ?array
	{
		$stmt = $this->pdo->prepare(
			'SELECT MIN(start_time) AS start_time, MAX(end_time) AS end_time
			 FROM game_behaviors
			 WHERE child_id = :child_id'
		);
		$stmt->execute([':child_id' => $childId]);
		$row = $stmt->fetch(PDO::FETCH_ASSOC);

		if (!$row || empty($row['start_time'])) {
			return null;
		}

		$end = $row['end_time'] ?: $row['start_time'];
		return [
			'start_time' => $row['start_time'],
			'end_time' => $end,
		];
	}

	private function getChildAge(int $childId): ?int
	{
		$stmt = $this->pdo->prepare('SELECT age FROM children WHERE child_id = :child_id');
		$stmt->execute([':child_id' => $childId]);
		$age = $stmt->fetchColumn();

		if ($age === false || $age === null) {
			return null;
		}

		return (int) $age;
	}

	private function analyzeSession(array $session, ?int $childAge): array
	{
		$slug = $session['slug'] ?: $this->slugify((string) ($session['name'] ?? ''));
		$gameWeight = $this->getGameWeight($slug);
		$signals = $this->decodeJson($session['signals'] ?? null, []);
		$rawSignals = $this->decodeJson($session['raw_signals'] ?? null, []);

		$rawEvents = $this->normalizeRawEvents($rawSignals);
		if (empty($rawEvents)) {
			$rawEvents = $this->fetchRawEventsFromTable((int) $session['session_id']);
		}

		$scores = [
			'focus' => 0.0,
			'frustration' => 0.0,
			'boredom' => 0.0,
			'joy' => 0.0,
		];
		$ruleHits = [];

		$dataQuality = 'no_data';
		if (!empty($rawEvents)) {
			$dataQuality = 'raw_signals';
			$this->applyEventRules($rawEvents, $slug, $childAge, $scores, $ruleHits);
		} elseif (!empty($signals)) {
			$dataQuality = 'summary_only';
			$this->applySummaryRules($signals, $slug, $childAge, $scores, $ruleHits);
		}

		$dataQualityWeight = $this->config['data_quality_weights'][$dataQuality] ?? 1.0;
		$weight = $gameWeight * $dataQualityWeight;

		return [
			'session_id' => (int) $session['session_id'],
			'game_slug' => $slug,
			'scores' => $scores,
			'rule_hits' => $ruleHits,
			'data_quality' => $dataQuality,
			'weight' => $weight,
		];
	}

	private function applyEventRules(
		array $events,
		string $gameSlug,
		?int $childAge,
		array &$scores,
		array &$ruleHits
	): void {
		usort($events, static fn($a, $b) => $a['ts'] <=> $b['ts']);

		$baseline = $this->getBaselineLatency($gameSlug, $childAge);
		$decayInterval = (int) ($this->config['decay_interval_ms'] ?? 30000);
		$decayMultiplier = (float) ($this->config['decay_multiplier'] ?? 0.8);

		$longPauseMs = (int) ($this->config['boredom_long_pause_ms'] ?? 10000);
		$sluggishRatio = (float) ($this->config['boredom_latency_ratio'] ?? 2.0);
		$sluggishNeeded = (int) ($this->config['boredom_consecutive'] ?? 3);
		$focusConsecutive = (int) ($this->config['focus_consecutive_success'] ?? 3);
		$focusVariance = (float) ($this->config['focus_latency_variance'] ?? 0.15);
		$quickRecoveryMultiplier = (float) ($this->config['quick_recovery_multiplier'] ?? 1.2);
		$spamWindow = (int) ($this->config['frustration_spam_window_ms'] ?? 500);
		$spamCount = (int) ($this->config['frustration_spam_count'] ?? 3);
		$errorSpiralWindow = (int) ($this->config['frustration_error_spiral_window_ms'] ?? 3000);
		$joyStreak = (int) ($this->config['joy_success_streak'] ?? 5);

		$lastDecayTs = $events[0]['ts'] ?? 0;
		$lastSignalTs = $events[0]['ts'] ?? 0;
		$lastErrorTs = null;
		$lastErrorForSpiral = null;
		$lastSpamTs = null;
		$sluggishCount = 0;
		$successStreak = 0;
		$successLatencies = [];
		$reactionWindow = [];
		$lastReaction = null;
		$lastReactionTs = null;
		$memoryErrorCount = 0;
		$memoryPairTrack = $this->detectPairTracking($events);
		$lastErrorPair = null;
		$memoryGame = ($gameSlug === 'memory_game');

		$reactionByTs = [];
		foreach ($events as $event) {
			if ($event['signal'] === 'reaction' && $event['value'] !== null) {
				$reactionByTs[$event['ts']] = (float) $event['value'];
			}
		}

		foreach ($events as $event) {
			$ts = $event['ts'];

			while ($ts - $lastDecayTs >= $decayInterval) {
				$scores['focus'] *= $decayMultiplier;
				$scores['frustration'] *= $decayMultiplier;
				$scores['boredom'] *= $decayMultiplier;
				$scores['joy'] *= $decayMultiplier;
				$ruleHits['decay_applied'] = ($ruleHits['decay_applied'] ?? 0) + 1;
				$lastDecayTs += $decayInterval;
			}

			if ($lastSignalTs && ($ts - $lastSignalTs) > $longPauseMs) {
				$scores['boredom'] += 5;
				$ruleHits['long_pause'] = ($ruleHits['long_pause'] ?? 0) + 1;
			}
			$lastSignalTs = $ts;

			if ($event['signal'] === 'reaction') {
				$reactionWindow[] = $ts;
				while (!empty($reactionWindow) && ($ts - $reactionWindow[0]) > $spamWindow) {
					array_shift($reactionWindow);
				}
				if (count($reactionWindow) >= $spamCount && ($lastSpamTs === null || ($ts - $lastSpamTs) > $spamWindow)) {
					$scores['frustration'] += 5;
					$ruleHits['spam_detector'] = ($ruleHits['spam_detector'] ?? 0) + 1;
					$lastSpamTs = $ts;
				}

				if ($event['value'] !== null) {
					$ratio = ((float) $event['value']) / $baseline;
					if ($ratio > $sluggishRatio) {
						$sluggishCount++;
						if ($sluggishCount >= $sluggishNeeded) {
							$scores['boredom'] += 3;
							$ruleHits['sluggish'] = ($ruleHits['sluggish'] ?? 0) + 1;
							$sluggishCount = 0;
						}
					} else {
						$sluggishCount = 0;
					}

					$lastReaction = (float) $event['value'];
					$lastReactionTs = $ts;
				}
			}

			if ($event['signal'] === 'error') {
				$memoryErrorCount++;
				$successStreak = 0;
				$successLatencies = [];

				$errorEligible = true;
				if ($memoryGame && $memoryErrorCount <= 2) {
					$errorEligible = false;
				}

				if ($errorEligible) {
					if ($lastErrorForSpiral !== null && ($ts - $lastErrorForSpiral) <= $errorSpiralWindow) {
						$spiralPoints = 4;
						if ($memoryGame && !$memoryPairTrack['enabled']) {
							$spiralPoints = 2;
							$ruleHits['error_spiral_memory_fallback'] = ($ruleHits['error_spiral_memory_fallback'] ?? 0) + 1;
						} else {
							$ruleHits['error_spiral'] = ($ruleHits['error_spiral'] ?? 0) + 1;
						}
						$scores['frustration'] += $spiralPoints;
					}

					if ($memoryPairTrack['enabled']) {
						$pairValue = $memoryPairTrack['extractor']($event);
						if ($pairValue !== null && $pairValue === $lastErrorPair) {
							$scores['frustration'] += 4;
							$ruleHits['memory_pair_repeat'] = ($ruleHits['memory_pair_repeat'] ?? 0) + 1;
						}
						$lastErrorPair = $pairValue;
					}
				}

				$lastErrorForSpiral = $ts;
				$lastErrorTs = $ts;
			}

			if ($event['signal'] === 'success') {
				$successStreak++;

				if ($lastErrorTs !== null && ($ts - $lastErrorTs) <= ($quickRecoveryMultiplier * $baseline)) {
					$scores['focus'] += 2;
					$ruleHits['quick_recovery'] = ($ruleHits['quick_recovery'] ?? 0) + 1;
					$lastErrorTs = null;
				}

				$latency = null;
				if (isset($reactionByTs[$ts])) {
					$latency = $reactionByTs[$ts];
				} elseif ($lastReaction !== null && $lastReactionTs !== null && ($ts - $lastReactionTs) <= ($baseline * 2)) {
					$latency = $lastReaction;
				}

				if ($latency !== null) {
					$successLatencies[] = $latency;
					if (count($successLatencies) === $focusConsecutive) {
						$minLatency = min($successLatencies);
						$maxLatency = max($successLatencies);
						$variance = ($maxLatency - $minLatency) / $baseline;
						if ($variance <= $focusVariance) {
							$scores['focus'] += 3;
							$ruleHits['rhythmic_success'] = ($ruleHits['rhythmic_success'] ?? 0) + 1;
						}
						array_shift($successLatencies);
					}
				}

				if ($successStreak >= $joyStreak) {
					$scores['joy'] += 2;
					$ruleHits['joy_streak'] = ($ruleHits['joy_streak'] ?? 0) + 1;
					$successStreak = 0;
				}
			}
		}
	}

	private function applySummaryRules(
		array $signals,
		string $gameSlug,
		?int $childAge,
		array &$scores,
		array &$ruleHits
	): void {
		$baseline = $this->getBaselineLatency($gameSlug, $childAge);
		$baselineError = $this->getBaselineErrorRate($childAge);

		$latencyAvg = isset($signals['latency_avg']) ? (float) $signals['latency_avg'] : null;
		$errorRate = isset($signals['error_rate']) ? (float) $signals['error_rate'] : null;
		$successCount = isset($signals['success']) ? (int) $signals['success'] : (int) ($signals['success_count'] ?? 0);

		if ($latencyAvg !== null && $latencyAvg > ($baseline * 2.0)) {
			$scores['boredom'] += 3;
			$ruleHits['summary_sluggish'] = ($ruleHits['summary_sluggish'] ?? 0) + 1;
		}

		if ($errorRate !== null && $errorRate > $baselineError) {
			$scores['frustration'] += 3;
			$ruleHits['summary_error_rate'] = ($ruleHits['summary_error_rate'] ?? 0) + 1;
		}

		if ($errorRate !== null && $latencyAvg !== null && $errorRate < ($baselineError * 0.5) && $latencyAvg <= ($baseline * 1.1)) {
			$scores['focus'] += 2;
			$ruleHits['summary_focus'] = ($ruleHits['summary_focus'] ?? 0) + 1;
		}

		if ($successCount >= 5 && ($errorRate === null || $errorRate < ($baselineError * 0.7))) {
			$scores['joy'] += 1;
			$ruleHits['summary_joy'] = ($ruleHits['summary_joy'] ?? 0) + 1;
		}
	}

	private function resolveState(array $scores): array
	{
		$sum = array_sum($scores);
		if ($sum <= 0.0) {
			return ['Neutral / Evaluating', 0.0];
		}

		arsort($scores);
		$keys = array_keys($scores);
		$values = array_values($scores);
		$maxKey = $keys[0] ?? null;
		$maxValue = $values[0] ?? 0.0;
		$secondValue = $values[1] ?? 0.0;

		$confidence = round(($maxValue / $sum) * 100, 2);
		$diffRatio = ($maxValue - $secondValue) / $sum;
		if ($confidence < 40 || $diffRatio < 0.10) {
			return ['Neutral / Evaluating', $confidence];
		}

		switch ($maxKey) {
			case 'frustration':
				if ($confidence > 40) {
					return ['Agitated / Angry', $confidence];
				}
				break;
			case 'focus':
				if ($confidence > 50) {
					return ['High Engagement', $confidence];
				}
				break;
			case 'boredom':
				return ['Disengaged / Sad', $confidence];
			case 'joy':
				return ['Happy / Confident', $confidence];
		}

		return ['Neutral / Evaluating', $confidence];
	}

	private function mergeWithHistory(int $childId, array $scores, DateTimeImmutable $periodEnd, array &$sourceWeights): array
	{
		$weights = $this->config['analysis_weights'] ?? ['previous_1' => 0.10, 'previous_2' => 0.03];
		$decayDays = (float) ($this->config['analysis_weights_decay_days'] ?? 7.0);

		$stmt = $this->pdo->prepare(
			'SELECT period_end, focus_points, frustration_points, boredom_points, joy_points
			 FROM child_behavior_analysis
			 WHERE child_id = :child_id
			 ORDER BY period_end DESC
			 LIMIT 2'
		);
		$stmt->execute([':child_id' => $childId]);
		$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

		foreach ($rows as $index => $row) {
			$key = $index === 0 ? 'previous_1' : 'previous_2';
			$weight = (float) ($weights[$key] ?? 0.0);
			if ($weight <= 0.0) {
				continue;
			}

			$previousEnd = new DateTimeImmutable($row['period_end']);
			$daysDiff = max(0.0, ($periodEnd->getTimestamp() - $previousEnd->getTimestamp()) / 86400);
			$timeDecay = $decayDays > 0 ? pow(0.5, $daysDiff / $decayDays) : 1.0;
			$finalWeight = $weight * $timeDecay;

			$scores['focus'] += ((float) $row['focus_points']) * $finalWeight;
			$scores['frustration'] += ((float) $row['frustration_points']) * $finalWeight;
			$scores['boredom'] += ((float) $row['boredom_points']) * $finalWeight;
			$scores['joy'] += ((float) $row['joy_points']) * $finalWeight;

			$sourceWeights[] = [
				'analysis_period_end' => $row['period_end'],
				'base_weight' => $weight,
				'time_decay' => round($timeDecay, 4),
				'final_weight' => round($finalWeight, 4),
			];
		}

		return $scores;
	}

	private function fetchRawEventsFromTable(int $sessionId): array
	{
		$stmt = $this->pdo->prepare(
			'SELECT `signal`, value, ts FROM game_events
			 WHERE session_id = :session_id
			 ORDER BY ts ASC'
		);
		$stmt->execute([':session_id' => $sessionId]);

		$events = [];
		foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
			$events[] = [
				'signal' => $row['signal'],
				'value' => $row['value'] !== null ? (float) $row['value'] : null,
				'ts' => (int) $row['ts'],
			];
		}

		return $events;
	}

	private function normalizeRawEvents(array $rawSignals): array
	{
		if (empty($rawSignals)) {
			return [];
		}

		$events = [];
		foreach ($rawSignals as $entry) {
			if (!is_array($entry) || empty($entry['signal']) || !isset($entry['ts'])) {
				continue;
			}
			$value = null;
			if (array_key_exists('value', $entry) && $entry['value'] !== null) {
				$value = (float) $entry['value'];
			}
			$event = [
				'signal' => (string) $entry['signal'],
				'value' => $value,
				'ts' => (int) $entry['ts'],
			];
			if (array_key_exists('pair_id', $entry)) {
				$event['pair_id'] = $entry['pair_id'];
			} elseif (array_key_exists('pair', $entry)) {
				$event['pair'] = $entry['pair'];
			}
			$events[] = $event;
		}

		return $events;
	}

	private function detectPairTracking(array $events): array
	{
		foreach ($events as $event) {
			if ((isset($event['pair_id']) && $event['pair_id'] !== null) || (isset($event['pair']) && $event['pair'] !== null)) {
				return [
					'enabled' => true,
					'extractor' => static function (array $entry): ?string {
						$pair = $entry['pair_id'] ?? ($entry['pair'] ?? null);
						if ($pair === null) {
							return null;
						}
						return is_scalar($pair) ? (string) $pair : null;
					},
				];
			}
		}

		return [
			'enabled' => false,
			'extractor' => static fn(): ?string => null,
		];
	}

	private function getBaselineLatency(string $gameSlug, ?int $childAge): float
	{
		$group = $this->resolveAgeGroup($childAge);
		$latencyMap = $this->config['baseline_latency_ms'] ?? [];
		if (isset($latencyMap[$gameSlug][$group])) {
			return (float) $latencyMap[$gameSlug][$group];
		}

		if (isset($latencyMap['default'][$group])) {
			return (float) $latencyMap['default'][$group];
		}

		return 1500.0;
	}

	private function getBaselineErrorRate(?int $childAge): float
	{
		$group = $this->resolveAgeGroup($childAge);
		$errorMap = $this->config['baseline_error_rate'] ?? [];
		if (isset($errorMap[$group])) {
			return (float) $errorMap[$group];
		}
		return 0.12;
	}

	private function resolveAgeGroup(?int $age): string
	{
		if ($age === null || $age <= 0) {
			return '7-9';
		}

		if ($age <= 6) {
			return '4-6';
		}
		if ($age <= 9) {
			return '7-9';
		}
		return '10-12';
	}

	private function getGameWeight(string $gameSlug): float
	{
		$weights = $this->config['game_weights'] ?? [];
		if (isset($weights[$gameSlug])) {
			return (float) $weights[$gameSlug];
		}
		return (float) ($this->config['default_weight'] ?? 1.0);
	}

	private function decodeJson($value, array $default): array
	{
		if ($value === null || $value === '') {
			return $default;
		}

		if (is_array($value)) {
			return $value;
		}

		$decoded = json_decode((string) $value, true);
		return is_array($decoded) ? $decoded : $default;
	}

	private function slugify(string $value): string
	{
		$value = strtolower(trim($value));
		$value = preg_replace('/[^a-z0-9]+/', '_', $value);
		return trim((string) $value, '_');
	}

	private function buildNotes(): array
	{
		return [
			'memory_game_pair_rule' => 'Pair metadata is required to apply the same-pair frustration rule in Memory Match. Without pair IDs, error-based frustration is not counted for that game.',
			'hardware_assumption' => 'Latency baselines assume mouse input. If touchscreen support is added, update baselines or apply a latency multiplier.',
			'behavioral_disclaimer' => 'This analysis is a behavioral mood indicator, not a clinical diagnosis.',
		];
	}
}
