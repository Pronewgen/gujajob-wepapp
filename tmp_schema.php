<?php
// Check actual data in MATERIAL_WITHDRAWN and schema

// Schema of MATERIAL_WITHDRAWN
$cols = DB::connection('oracle')->select(
    "SELECT COLUMN_ID, COLUMN_NAME, DATA_TYPE, DATA_LENGTH, NULLABLE
     FROM USER_TAB_COLUMNS WHERE TABLE_NAME='MATERIAL_WITHDRAWN' ORDER BY COLUMN_ID"
);
echo "=== MATERIAL_WITHDRAWN COLUMNS ===\n";
foreach ($cols as $c) { echo $c->column_id.'|'.$c->column_name.'|'.$c->data_type.'('.$c->data_length.')|NULL='.$c->nullable."\n"; }

// All rows
$rows = DB::connection('oracle')->select("SELECT id, mat_wd_code, status FROM MATERIAL_WITHDRAWN ORDER BY id");
echo "\n=== ALL MATERIAL_WITHDRAWN ROWS ===\n";
echo "Total: ".count($rows)."\n";
foreach ($rows as $r) { echo $r->id.'|'.$r->mat_wd_code.'|status='.$r->status."\n"; }

// MATERIAL_PROCUREMENT schema + codes
$pcols = DB::connection('oracle')->select(
    "SELECT COLUMN_ID, COLUMN_NAME, DATA_TYPE, DATA_LENGTH
     FROM USER_TAB_COLUMNS WHERE TABLE_NAME='MATERIAL_PROCUREMENT' ORDER BY COLUMN_ID"
);
echo "\n=== MATERIAL_PROCUREMENT COLUMNS ===\n";
foreach ($pcols as $c) { echo $c->column_id.'|'.$c->column_name.'|'.$c->data_type.'('.$c->data_length.")\n"; }

$prows = DB::connection('oracle')->select("SELECT id, mat_pro_code FROM MATERIAL_PROCUREMENT ORDER BY id");
echo "\n=== ALL MATERIAL_PROCUREMENT ROWS ===\n";
echo "Total: ".count($prows)."\n";
foreach ($prows as $r) { echo $r->id.'|'.$r->mat_pro_code."\n"; }
