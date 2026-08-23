<?php
// Check MATERIAL_WITHDRAWN_LIST, MATERIAL_INSPECTION, and MATERIAL_PROCUREMENT dates

// MATERIAL_WITHDRAWN_LIST schema + data
$wdlCols = DB::connection('oracle')->select(
    "SELECT COLUMN_ID, COLUMN_NAME, DATA_TYPE, DATA_LENGTH FROM USER_TAB_COLUMNS WHERE TABLE_NAME='MATERIAL_WITHDRAWN_LIST' ORDER BY COLUMN_ID"
);
echo "=== MATERIAL_WITHDRAWN_LIST COLUMNS ===\n";
foreach ($wdlCols as $c) { echo $c->column_id.'|'.$c->column_name.'|'.$c->data_type.'('.$c->data_length.")\n"; }

$wdlRows = DB::connection('oracle')->select("SELECT id, mat_wd_id, mat_id, wd_amount FROM MATERIAL_WITHDRAWN_LIST ORDER BY mat_wd_id, id");
echo "\n=== MATERIAL_WITHDRAWN_LIST DATA ===\n";
echo "Total: ".count($wdlRows)."\n";
foreach ($wdlRows as $r) { echo "id={$r->id}|mat_wd_id={$r->mat_wd_id}|mat_id={$r->mat_id}|qty={$r->wd_amount}\n"; }

// MATERIAL_INSPECTION schema
$inspCols = DB::connection('oracle')->select(
    "SELECT COLUMN_ID, COLUMN_NAME, DATA_TYPE, DATA_LENGTH FROM USER_TAB_COLUMNS WHERE TABLE_NAME='MATERIAL_INSPECTION' ORDER BY COLUMN_ID"
);
echo "\n=== MATERIAL_INSPECTION COLUMNS ===\n";
foreach ($inspCols as $c) { echo $c->column_id.'|'.$c->column_name.'|'.$c->data_type.'('.$c->data_length.")\n"; }

$inspRows = DB::connection('oracle')->select("SELECT * FROM MATERIAL_INSPECTION ORDER BY id");
echo "\n=== MATERIAL_INSPECTION DATA ===\n";
echo "Total: ".count($inspRows)."\n";
foreach ($inspRows as $r) { echo json_encode((array)$r)."\n"; }

// Check MATERIAL_PROCUREMENT dates for document number assignment
$procDates = DB::connection('oracle')->select(
    "SELECT id, mat_pro_code, mat_pro_date FROM MATERIAL_PROCUREMENT ORDER BY mat_pro_date ASC, id ASC FETCH FIRST 5 ROWS ONLY"
);
echo "\n=== MATERIAL_PROCUREMENT (first 5 by date) ===\n";
foreach ($procDates as $r) { echo "id={$r->id}|code={$r->mat_pro_code}|date={$r->mat_pro_date}\n"; }

$procLast = DB::connection('oracle')->select(
    "SELECT id, mat_pro_code, mat_pro_date FROM MATERIAL_PROCUREMENT ORDER BY mat_pro_date DESC, id DESC FETCH FIRST 5 ROWS ONLY"
);
echo "\n=== MATERIAL_PROCUREMENT (last 5 by date) ===\n";
foreach ($procLast as $r) { echo "id={$r->id}|code={$r->mat_pro_code}|date={$r->mat_pro_date}\n"; }

// Check MATERIAL_WITHDRAWN dates
$wdDates = DB::connection('oracle')->select(
    "SELECT id, mat_wd_code, mat_wd_date, status FROM MATERIAL_WITHDRAWN ORDER BY id"
);
echo "\n=== MATERIAL_WITHDRAWN (with dates) ===\n";
foreach ($wdDates as $r) { echo "id={$r->id}|code={$r->mat_wd_code}|date={$r->mat_wd_date}|status={$r->status}\n"; }
