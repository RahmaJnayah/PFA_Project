<?php

declare(strict_types=1);

session_start();

if (empty($_SESSION['admin_logged_in'])) {
    http_response_code(403);
    echo 'Admin login required.';
    exit;
}

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/BehaviorAnalyzer.php';

$pdo = Database::connect();
$children = $pdo->query('SELECT child_id, nickname, age FROM children ORDER BY nickname ASC')->fetchAll();

$analysis = null;
$error = null;
$selectedChildId = 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $childId = (int) ($_POST['child_id'] ?? 0);
    if ($childId > 0) {
        $analyzer = new BehaviorAnalyzer($pdo);
        $analysis = $analyzer->analyzeChildAllSessions($childId);
        if ($analysis === null) {
            $error = 'No sessions found for this child.';
        }
        $selectedChildId = $childId;
    } else {
        $error = 'Please select a child.';
    }
}

function h(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Behavior Analysis Test</title>
    <style>
        body { font-family: Arial, sans-serif; padding: 24px; background: #f7f3ef; color: #2f1e12; }
        .card { background: #fff; border: 1px solid #e1d7cc; border-radius: 8px; padding: 16px; margin-top: 16px; }
        label, select, button { font-size: 14px; }
        table { width: 100%; border-collapse: collapse; margin-top: 12px; }
        th, td { border-bottom: 1px solid #e1d7cc; padding: 8px; text-align: left; }
        .muted { color: #6b5544; }
    </style>
</head>
<body>
    <h1>Behavior Analysis Test</h1>
    <p class="muted">Runs analysis across all sessions for a selected child.</p>

    <form method="post">
        <label for="child_id">Child</label>
        <select id="child_id" name="child_id">
            <option value="">Select a child</option>
            <?php foreach ($children as $child): ?>
                <option value="<?= (int) $child['child_id'] ?>" <?= $selectedChildId === (int) $child['child_id'] ? 'selected' : '' ?>>
                    <?= h($child['nickname']) ?> (age <?= (int) $child['age'] ?>)
                </option>
            <?php endforeach; ?>
        </select>
        <button type="submit">Run analysis</button>
    </form>

    <?php if ($error): ?>
        <div class="card">
            <strong><?= h($error) ?></strong>
        </div>
    <?php endif; ?>

    <?php if ($analysis): ?>
        <div class="card">
            <h2>Result</h2>
            <p><strong>State:</strong> <?= h($analysis['dominant_state']) ?> (<?= h((string) $analysis['confidence']) ?>%)</p>
            <p class="muted">Sessions analyzed: <?= (int) $analysis['session_count'] ?></p>
            <table>
                <thead>
                    <tr>
                        <th>Bucket</th>
                        <th>Score</th>
                    </tr>
                </thead>
                <tbody>
                    <tr><td>Focus</td><td><?= h((string) $analysis['scores']['focus']) ?></td></tr>
                    <tr><td>Frustration</td><td><?= h((string) $analysis['scores']['frustration']) ?></td></tr>
                    <tr><td>Boredom</td><td><?= h((string) $analysis['scores']['boredom']) ?></td></tr>
                    <tr><td>Joy</td><td><?= h((string) $analysis['scores']['joy']) ?></td></tr>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</body>
</html>
