<?php
require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();
$conn = DB::connection('oracle');

$cols = $conn->select("SELECT COLUMN_NAME, DATA_TYPE, DATA_LENGTH, NULLABLE FROM ALL_TAB_COLUMNS WHERE TABLE_NAME='DEALER' ORDER BY COLUMN_ID");
echo 'DEALER COLUMNS:' . PHP_EOL;
foreach ($cols as $c) { echo '  ' . $c->column_name . ' ' . $c->data_type . '(' . $c->data_length . ') NULL=' . $c->nullable . PHP_EOL; }

$fks = $conn->select("SELECT a.COLUMN_NAME, c.R_CONSTRAINT_NAME FROM ALL_CONS_COLUMNS a JOIN ALL_CONSTRAINTS c ON a.CONSTRAINT_NAME=c.CONSTRAINT_NAME WHERE c.TABLE_NAME='DEALER' AND c.CONSTRAINT_TYPE='R'");
echo PHP_EOL . 'DEALER FK:' . PHP_EOL;
foreach ($fks as $f) { echo '  ' . $f->column_name . ' -> ' . $f->r_constraint_name . PHP_EOL; }

$seq = $conn->select("SELECT SEQUENCE_NAME FROM ALL_SEQUENCES WHERE SEQUENCE_NAME LIKE '%DEALER%'");
echo PHP_EOL . 'DEALER SEQ:' . PHP_EOL;
foreach ($seq as $s) { echo '  ' . $s->sequence_name . PHP_EOL; }

// Tables referencing DEALER
$refs = $conn->select("SELECT c.TABLE_NAME, a.COLUMN_NAME FROM ALL_CONS_COLUMNS a JOIN ALL_CONSTRAINTS c ON a.CONSTRAINT_NAME=c.CONSTRAINT_NAME JOIN ALL_CONSTRAINTS r ON c.R_CONSTRAINT_NAME=r.CONSTRAINT_NAME WHERE r.TABLE_NAME='DEALER' AND c.CONSTRAINT_TYPE='R'");
echo PHP_EOL . 'TABLES REFERENCING DEALER:' . PHP_EOL;
foreach ($refs as $r) { echo '  ' . $r->table_name . '.' . $r->column_name . PHP_EOL; }
