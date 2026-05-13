<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../src/AttendanceProcessor.php';

$from = $argv[1] ?? date('Y-m-01');
$to = $argv[2] ?? date('Y-m-d');
$processor = new AttendanceProcessor(db());
$count = $processor->processRange($from, $to);
echo "Processed {$count} attendance daily rows from {$from} to {$to}\n";
