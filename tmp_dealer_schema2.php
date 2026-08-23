<?php
require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();
$conn = DB::connection('oracle');

// Province columns
$p = $conn->select("SELECT COLUMN_NAME, DATA_TYPE, DATA_LENGTH FROM ALL_TAB_COLUMNS WHERE TABLE_NAME='GLB_PROVINCE' ORDER BY COLUMN_ID");
echo 'GLB_PROVINCE:' . PHP_EOL;
foreach ($p as $c) echo '  ' . $c->column_name . ' ' . $c->data_type . PHP_EOL;

// Amphur columns
$a = $conn->select("SELECT COLUMN_NAME, DATA_TYPE, DATA_LENGTH FROM ALL_TAB_COLUMNS WHERE TABLE_NAME='GLB_AMPHUR' ORDER BY COLUMN_ID");
echo PHP_EOL . 'GLB_AMPHUR:' . PHP_EOL;
foreach ($a as $c) echo '  ' . $c->column_name . ' ' . $c->data_type . PHP_EOL;

// Tambon columns
$t = $conn->select("SELECT COLUMN_NAME, DATA_TYPE, DATA_LENGTH FROM ALL_TAB_COLUMNS WHERE TABLE_NAME='GLB_TAMBON' ORDER BY COLUMN_ID");
echo PHP_EOL . 'GLB_TAMBON:' . PHP_EOL;
foreach ($t as $c) echo '  ' . $c->column_name . ' ' . $c->data_type . PHP_EOL;

// Check if DEALER_TYPE has a master table
$dt = $conn->select("SELECT TABLE_NAME FROM ALL_TABLES WHERE TABLE_NAME LIKE '%DEALER_TYPE%' OR TABLE_NAME LIKE '%SUPPLIER_TYPE%' OR TABLE_NAME LIKE '%VENDOR_TYPE%'");
echo PHP_EOL . 'DEALER_TYPE master tables:' . PHP_EOL;
foreach ($dt as $d) echo '  ' . $d->table_name . PHP_EOL;
if (empty($dt)) echo '  (none found)' . PHP_EOL;

// Row counts
$pc = $conn->selectOne('SELECT COUNT(*) AS cnt FROM GLB_PROVINCE');
$ac = $conn->selectOne('SELECT COUNT(*) AS cnt FROM GLB_AMPHUR');
$tc = $conn->selectOne('SELECT COUNT(*) AS cnt FROM GLB_TAMBON');
$dc = $conn->selectOne('SELECT COUNT(*) AS cnt FROM DEALER');
echo PHP_EOL . 'Row counts: PROVINCE=' . $pc->cnt . ' AMPHUR=' . $ac->cnt . ' TAMBON=' . $tc->cnt . ' DEALER=' . $dc->cnt . PHP_EOL;

// Sample DEALER_TYPE values
$types = $conn->select('SELECT DISTINCT DEALER_TYPE FROM DEALER WHERE DEALER_TYPE IS NOT NULL ORDER BY DEALER_TYPE');
echo 'DEALER_TYPE values:' . PHP_EOL;
foreach ($types as $r) echo '  [' . $r->dealer_type . ']' . PHP_EOL;
