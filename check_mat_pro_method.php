<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
$pdo = DB::connection('oracle')->getPdo();

echo "=== Tables matching METHOD/PRO ===\n";
$stmt = $pdo->query("SELECT TABLE_NAME FROM USER_TABLES WHERE TABLE_NAME LIKE '%METHOD%' OR TABLE_NAME LIKE '%MAT_PRO%' ORDER BY TABLE_NAME");
foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $r) { echo $r['TABLE_NAME'] . "\n"; }

echo "\n=== MAT_PRO_METHOD columns (if exists) ===\n";
$stmt2 = $pdo->query("SELECT COLUMN_ID, COLUMN_NAME, DATA_TYPE, DATA_LENGTH, NULLABLE FROM USER_TAB_COLUMNS WHERE TABLE_NAME = 'MAT_PRO_METHOD' ORDER BY COLUMN_ID");
$rows = $stmt2->fetchAll(PDO::FETCH_ASSOC);
if (empty($rows)) {
    echo "Table MAT_PRO_METHOD does NOT exist\n";
} else {
    foreach ($rows as $r) { echo implode(' | ', $r) . "\n"; }
}
