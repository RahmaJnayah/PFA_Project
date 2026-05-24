<?php
/**
 * services/ScoreService.php
 * Shared score persistence — used by all 17 YOPY games.
 *
 * SRS: REQ-18 | BR-05, BR-06
 * ARCH-03: Repository pattern — all SQL lives here, never in controllers.
 */
class ScoreService
{
    // Score boundaries enforced server-side (BR-06, REQ-18)
    const MIN_POINTS          = 0;
    const MAX_POINTS          = 10000;
    const MIN_COMPLETION_TIME = 3;   // seconds — impossible to finish faster

    /**
     * Validate then persist a score.
     *
     * Throws \InvalidArgumentException (→ HTTP 422) for bad values (BR-06).
     * Throws \PDOException with SQLSTATE 23000 (→ HTTP 409) on duplicate (BR-05).
     *
     * @throws \InvalidArgumentException
     * @throws \PDOException
     */
    public static function save(
        PDO $pdo,
        int $sessionId,
        int $points,
        int $completionTime
    ): void {
        // ── BR-06: validate range before any DB write ──────────────────────
        if ($points < self::MIN_POINTS || $points > self::MAX_POINTS) {
            throw new \InvalidArgumentException(
                "Points {$points} out of range [" . self::MIN_POINTS . '–' . self::MAX_POINTS . '] (BR-06)'
            );
        }
        if ($completionTime < self::MIN_COMPLETION_TIME) {
            throw new \InvalidArgumentException(
                "Completion time {$completionTime}s below minimum " . self::MIN_COMPLETION_TIME . 's (BR-06)'
            );
        }

        // ── INSERT — UNIQUE(session_id) triggers 23000 on duplicate (BR-05) ─
        $stmt = $pdo->prepare(
            'INSERT INTO scores (session_id, points, completion_time, created_at)
             VALUES (:session_id, :points, :completion_time, NOW())'
        );
        $stmt->execute([
            ':session_id'      => $sessionId,
            ':points'          => $points,
            ':completion_time' => $completionTime,
        ]);

        // Close the parent session now that score is saved
        $close = $pdo->prepare(
            'UPDATE game_sessions SET end_time = NOW() WHERE session_id = :sid'
        );
        $close->execute([':sid' => $sessionId]);
    }

    /**
     * Fetch the score for a given session (used by dashboard).
     * Returns null if no score exists yet.
     */
    public static function getBySession(PDO $pdo, int $sessionId): ?array
    {
        $stmt = $pdo->prepare(
            'SELECT points, completion_time, created_at
             FROM scores WHERE session_id = :sid LIMIT 1'
        );
        $stmt->execute([':sid' => $sessionId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }
}
