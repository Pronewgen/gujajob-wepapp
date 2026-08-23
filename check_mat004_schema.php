<?php
require_once 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();
use Illuminate\Support\Facades\DB;

echo '=== CURRENT USER ==='.PHP_EOL;
$rows = DB::connection('oracle')->select('SELECT USER AS CURRENT_SCHEMA FROM DUAL');
echo $rows[0]->current_schema.PHP_EOL;

echo PHP_EOL.'=== TABLES WITH INSPECTION ==='.PHP_EOL;
$tabs = DB::connection('oracle')->select("SELECT TABLE_NAME FROM USER_TABLES WHERE TABLE_NAME LIKE '%INSPECTION%' ORDER BY TABLE_NAME");
foreach($tabs as $t) echo $t->table_name.PHP_EOL;

echo PHP_EOL.'=== MATERIAL_INSPECTION COLUMNS ==='.PHP_EOL;
$cols = DB::connection('oracle')->select("SELECT COLUMN_ID, COLUMN_NAME, DATA_TYPE, DATA_LENGTH, DATA_PRECISION, DATA_SCALE, NULLABLE, DATA_DEFAULT FROM USER_TAB_COLUMNS WHERE TABLE_NAME = 'MATERIAL_INSPECTION' ORDER BY COLUMN_ID");
foreach($cols as $c) echo $c->column_id.': '.$c->column_name.' '.$c->data_type.'('.($c->data_precision ?? $c->data_length).') NULL='.$c->nullable.' DEFAULT='.$c->data_default.PHP_EOL;

echo PHP_EOL.'=== MATERIAL_INSPECTION CONSTRAINTS ==='.PHP_EOL;
$cons = DB::connection('oracle')->select("SELECT c.CONSTRAINT_NAME, c.CONSTRAINT_TYPE, cc.COLUMN_NAME, c.R_CONSTRAINT_NAME, c.STATUS FROM USER_CONSTRAINTS c JOIN USER_CONS_COLUMNS cc ON c.CONSTRAINT_NAME = cc.CONSTRAINT_NAME WHERE c.TABLE_NAME = 'MATERIAL_INSPECTION' ORDER BY c.CONSTRAINT_NAME, cc.POSITION");
foreach($cons as $c) echo $c->constraint_name.' '.$c->constraint_type.' col='.$c->column_name.' ref='.$c->r_constraint_name.PHP_EOL;

echo PHP_EOL.'=== MATERIAL_INSPECTION SAMPLE DATA ==='.PHP_EOL;
$rows = DB::connection('oracle')->select('SELECT * FROM MATERIAL_INSPECTION FETCH FIRST 10 ROWS ONLY');
if(count($rows)>0){
    echo implode(' | ', array_keys((array)$rows[0])).PHP_EOL;
    foreach($rows as $r) echo implode(' | ', array_map(fn($v)=>$v??'NULL', (array)$r)).PHP_EOL;
} else {
    echo 'NO DATA'.PHP_EOL;
}

echo PHP_EOL.'=== MATERIAL_INSPECTION_LIST COLUMNS ==='.PHP_EOL;
$cols2 = DB::connection('oracle')->select("SELECT COLUMN_ID, COLUMN_NAME, DATA_TYPE, DATA_LENGTH, DATA_PRECISION, DATA_SCALE, NULLABLE, DATA_DEFAULT FROM USER_TAB_COLUMNS WHERE TABLE_NAME = 'MATERIAL_INSPECTION_LIST' ORDER BY COLUMN_ID");
foreach($cols2 as $c) echo $c->column_id.': '.$c->column_name.' '.$c->data_type.'('.($c->data_precision ?? $c->data_length).') NULL='.$c->nullable.' DEFAULT='.$c->data_default.PHP_EOL;

echo PHP_EOL.'=== MATERIAL_INSPECTION_LIST CONSTRAINTS ==='.PHP_EOL;
$cons2 = DB::connection('oracle')->select("SELECT c.CONSTRAINT_NAME, c.CONSTRAINT_TYPE, cc.COLUMN_NAME, c.R_CONSTRAINT_NAME, c.STATUS FROM USER_CONSTRAINTS c JOIN USER_CONS_COLUMNS cc ON c.CONSTRAINT_NAME = cc.CONSTRAINT_NAME WHERE c.TABLE_NAME = 'MATERIAL_INSPECTION_LIST' ORDER BY c.CONSTRAINT_NAME, cc.POSITION");
foreach($cons2 as $c) echo $c->constraint_name.' '.$c->constraint_type.' col='.$c->column_name.' ref='.$c->r_constraint_name.PHP_EOL;

echo PHP_EOL.'=== MATERIAL_INSPECTION_LIST SAMPLE ==='.PHP_EOL;
$rows2 = DB::connection('oracle')->select('SELECT * FROM MATERIAL_INSPECTION_LIST FETCH FIRST 10 ROWS ONLY');
if(count($rows2)>0){
    echo implode(' | ', array_keys((array)$rows2[0])).PHP_EOL;
    foreach($rows2 as $r) echo implode(' | ', array_map(fn($v)=>$v??'NULL', (array)$r)).PHP_EOL;
} else {
    echo 'NO DATA'.PHP_EOL;
}
