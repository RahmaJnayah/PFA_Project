<?php

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    echo "CLI only.\n";
    exit(1);
}

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/BehaviorAnalyzer.php';

$pdo = Database::connect();
$analyzer = new BehaviorAnalyzer($pdo);

$periodEnd = new DateTimeImmutable('now');
$periodStart = $periodEnd->sub(new DateInterval('P1D'));

$summary = $analyzer->runPeriodAnalysis($periodStart, $periodEnd, null);

echo "Behavior analysis completed.\n";
echo "Eligible children: {$summary['eligible_children']}\n";
echo "Stored analyses: {$summary['stored']}\n";
echo "Skipped: {$summary['skipped']}\n";
echo "Errors: {$summary['errors']}\n";
