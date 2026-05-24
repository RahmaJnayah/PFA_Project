<?php
/**
 * services/GameService.php
 * Shared game session management — used by all 17 YOPY games.
 *
 * SRS: REQ-13, REQ-14, REQ-18 | BR-07, BR-08
 * ARCH-03: Repository pattern — all SQL lives here, never in controllers.
 *
 * Version unifiée : utilise game_behaviors comme table unique (session + score + comportement)
 */
class GameService
{
    /**
     * Resolve a game's numeric ID from its slug name.
     * Returns null if the game doesn't exist or is inactive (REQ-39).
     */
    public static function getIdBySlug(PDO $pdo, string $slug): ?int
    {
        $stmt = $pdo->prepare(
            'SELECT game_id FROM games WHERE name = :slug AND is_active = 1 LIMIT 1'
        );
        $stmt->execute([':slug' => $slug]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ? (int) $row['game_id'] : null;
    }

    /**
     * Create a new game session (stored in game_behaviors).
     * Difficulty is locked here and stored server-side (BR-08).
     * Returns the new session_id or null on failure.
     */
    public static function createSession(
        PDO    $pdo,
        int    $childId,
        int    $gameId,
        string $difficulty = 'easy'
    ): ?int {
        $allowed = ['easy', 'medium', 'hard'];
        if (!in_array($difficulty, $allowed, true)) {
            $difficulty = 'easy';
        }

        $stmt = $pdo->prepare(
            'INSERT INTO game_behaviors (child_id, game_id, difficulty, start_time, signals, raw_signals)
             VALUES (:child_id, :game_id, :difficulty, NOW(), \'{}\', \'[]\')'
        );
        $stmt->execute([
            ':child_id'   => $childId,
            ':game_id'    => $gameId,
            ':difficulty' => $difficulty,
        ]);

        $id = (int) $pdo->lastInsertId();
        return $id > 0 ? $id : null;
    }

    /**
     * Mark a session as finished and record end_time.
     * Also updates points and completion_time if provided.
     * Called by the score handler after a successful save.
     */
    public static function closeSession(PDO $pdo, int $sessionId, ?int $points = null, ?int $completionTime = null): void
    {
        if ($points !== null && $completionTime !== null) {
            $stmt = $pdo->prepare(
                'UPDATE game_behaviors 
                 SET end_time = NOW(), points = :points, completion_time = :completion_time
                 WHERE session_id = :sid'
            );
            $stmt->execute([
                ':sid'             => $sessionId,
                ':points'          => $points,
                ':completion_time' => $completionTime,
            ]);
        } else {
            $stmt = $pdo->prepare(
                'UPDATE game_behaviors SET end_time = NOW() WHERE session_id = :sid'
            );
            $stmt->execute([':sid' => $sessionId]);
        }
    }

    /**
     * Verify that a game session belongs to the given child (BR-02).
     * Returns false → caller should respond HTTP 403.
     */
    public static function sessionBelongsToChild(
        PDO $pdo,
        int $sessionId,
        int $childId
    ): bool {
        $stmt = $pdo->prepare(
            'SELECT 1 FROM game_behaviors
             WHERE session_id = :sid AND child_id = :cid LIMIT 1'
        );
        $stmt->execute([':sid' => $sessionId, ':cid' => $childId]);
        return (bool) $stmt->fetchColumn();
    }

    /**
     * Return the locked difficulty for an existing session (BR-08).
     * Use this to ignore any difficulty value sent by the client.
     */
    public static function getSessionDifficulty(PDO $pdo, int $sessionId): string
    {
        $stmt = $pdo->prepare(
            'SELECT difficulty FROM game_behaviors WHERE session_id = :sid LIMIT 1'
        );
        $stmt->execute([':sid' => $sessionId]);
        return $stmt->fetchColumn() ?: 'easy';
    }

    /**
     * Save or merge behavioral data (signals and raw events) into an existing session.
     * This is called by behavior_api.php (or can be used by games directly).
     * Uses JSON_MERGE_PATCH to accumulate partial flushes.
     */
    public static function saveBehavior(
        PDO $pdo,
        int $sessionId,
        array $aggregated,
        array $rawSignals
    ): void {
        $stmt = $pdo->prepare(
            'UPDATE game_behaviors
             SET signals = JSON_MERGE_PATCH(signals, :signals),
                 raw_signals = JSON_MERGE_PATCH(COALESCE(raw_signals, \'[]\'), :raw_signals)
             WHERE session_id = :sid'
        );
        $stmt->execute([
            ':sid'         => $sessionId,
            ':signals'     => json_encode($aggregated, JSON_UNESCAPED_UNICODE),
            ':raw_signals' => json_encode($rawSignals, JSON_UNESCAPED_UNICODE),
        ]);
    }

    /**
     * Get all data for a session (including points, times, signals) for reporting.
     */
    public static function getSessionData(PDO $pdo, int $sessionId): ?array
    {
        $stmt = $pdo->prepare(
            'SELECT session_id, child_id, game_id, start_time, end_time,
                    difficulty, points, completion_time, signals, raw_signals
             FROM game_behaviors
             WHERE session_id = :sid LIMIT 1'
        );
        $stmt->execute([':sid' => $sessionId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }
}