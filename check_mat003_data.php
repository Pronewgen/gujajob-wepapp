<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$db = DB::connection('oracle');

echo "=== GLB_ORGANIZATION columns ===\n";
$cols = $db->select("SELECT column_name, data_type, data_length FROM user_tab_columns WHERE table_name = 'GLB_ORGANIZATION' ORDER BY column_id");
foreach ($cols as $c) echo "  {$c->column_name} {$c->data_type}({$c->data_length})\n";

echo "\n=== GLB_ORGANIZATION data (first 5) ===\n";
$rows = $db->select("SELECT * FROM GLB_ORGANIZATION WHERE ROWNUM <= 5");
foreach ($rows as $r) { print_r((array)$r); }

echo "\n=== MATERIAL_INVENTORY data (first 5) ===\n";
$rows = $db->select("SELECT mi.*, m.mat_code, m.mat_name FROM MATERIAL_INVENTORY mi JOIN MATERIALS m ON m.id = mi.mat_id WHERE ROWNUM <= 5");
foreach ($rows as $r) { print_r((array)$r); }

echo "\n=== MATERIAL_WITHDRAWN data (first 3) ===\n";
$rows = $db->select("SELECT * FROM MATERIAL_WITHDRAWN WHERE ROWNUM <= 3");
foreach ($rows as $r) { print_r((array)$r); }

echo "\n=== MATERIAL_WITHDRAWN STATUS distinct values ===\n";
$rows = $db->select("SELECT DISTINCT STATUS FROM MATERIAL_WITHDRAWN ORDER BY STATUS");
foreach ($rows as $r) { echo "STATUS=" . json_encode((array)$r) . "\n"; }

echo "\n=== MATERIAL_WITHDRAWN_LIST first 3 ===\n";
$rows = $db->select("SELECT * FROM MATERIAL_WITHDRAWN_LIST WHERE ROWNUM <= 3");
foreach ($rows as $r) { print_r((array)$r); }

echo "\n=== SEQUENCE nextvals ===\n";
$seqs = ['MATERIAL_WITHDRAWN_SEQ', 'MATERIAL_WITHDRAWN_LIST_SEQ'];
foreach ($seqs as $s) {
    $r = $db->select("SELECT {$s}.NEXTVAL FROM DUAL");
    echo "{$s} next = " . $r[0]->nextval . "\n";
}

echo "\n=== MAT_WD_CODE pattern check (first 5) ===\n";
$rows = $db->select("SELECT id, mat_wd_code, mat_wd_date, status FROM MATERIAL_WITHDRAWN ORDER BY id DESC FETCH FIRST 5 ROWS ONLY");
foreach ($rows as $r) { print_r((array)$r); }

echo "\nDONE\n";
