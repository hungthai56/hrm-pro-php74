<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../src/PayrollCalculator.php';

$periodId = (int)($argv[1] ?? 0);
if ($periodId <= 0) {
    die("Usage: php cron/generate_payroll.php <period_id>\n");
}
$calculator = new PayrollCalculator(db());
$count = $calculator->generateForPeriod($periodId);
echo "Generated payroll for {$count} employees\n";
