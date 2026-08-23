<?php
require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
$pdo = DB::connection('oracle')->getPdo();

$total = $pdo->query('SELECT COUNT(1) FROM MATERIALS')->fetchColumn();
echo "Total rows : {$total}\n";

$rows = $pdo->query("SELECT MIN(MAT_CODE) AS mn, MAX(MAT_CODE) AS mx FROM MATERIALS")->fetch(PDO::FETCH_NUM);
echo "Code range : {$rows[0]} to {$rows[1]}\n";

echo "Units used : ";
foreach ($pdo->query('SELECT DISTINCT UNIT FROM MATERIALS ORDER BY UNIT') as $u) echo $u[0] . ' ';
echo "\n";

$cats = $pdo->query("SELECT COUNT(DISTINCT UNIT) FROM MATERIALS")->fetchColumn();
echo "Distinct units: {$cats}\n";
