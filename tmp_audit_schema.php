<?php
// Temporary audit script — delete after use
require __DIR__ . '/vendor/autoload.php';
$app = require __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

$tables = [
    'ASSET_TYPES', 'ASSET_TYPE', 'ASS_TYPE', 'ASSET_CATEGORIES', 'ASSET_CATEGORY',
    'ASSETS', 'ASSET', 'ASS_REGISTRATION', 'ASSET_REGISTRATION',
    'UNITS', 'UNIT', 'MAT_UNIT', 'MATERIAL_UNIT', 'UNIT_MASTERS',
    'GLB_ORGANIZATION',
    'MATERIAL_INVENTORY',
];

echo "=== TABLE EXISTENCE CHECK ===\n";
foreach ($tables as $tbl) {
    $rows = DB::connection('oracle')->select(
        "SELECT COUNT(*) AS cnt FROM all_tables WHERE UPPER(table_name)=UPPER(:t) AND owner = SYS_CONTEXT('USERENV','CURRENT_SCHEMA')",
        ['t' => $tbl]
    );
    $exists = ($rows[0]->cnt ?? 0) > 0;
    echo ($exists ? "EXISTS" : "MISSING") . "  $tbl\n";
}

echo "\n=== COLUMNS of existing key tables ===\n";
$checkCols = ['GLB_ORGANIZATION', 'MATERIAL_INVENTORY'];

// Also detect asset/unit tables from schema
$detected = DB::connection('oracle')->select(
    "SELECT table_name FROM all_tables WHERE owner = SYS_CONTEXT('USERENV','CURRENT_SCHEMA') AND UPPER(table_name) LIKE '%ASSET%' ORDER BY table_name"
);
echo "\nAll tables with ASSET in name:\n";
foreach ($detected as $r) echo "  " . $r->table_name . "\n";

$detected2 = DB::connection('oracle')->select(
    "SELECT table_name FROM all_tables WHERE owner = SYS_CONTEXT('USERENV','CURRENT_SCHEMA') AND UPPER(table_name) LIKE '%UNIT%' ORDER BY table_name"
);
echo "\nAll tables with UNIT in name:\n";
foreach ($detected2 as $r) echo "  " . $r->table_name . "\n";

$detected3 = DB::connection('oracle')->select(
    "SELECT table_name FROM all_tables WHERE owner = SYS_CONTEXT('USERENV','CURRENT_SCHEMA') AND (UPPER(table_name) LIKE '%MATERIAL%' OR UPPER(table_name) LIKE '%MAT%') ORDER BY table_name"
);
echo "\nAll tables with MATERIAL/MAT in name:\n";
foreach ($detected3 as $r) echo "  " . $r->table_name . "\n";

foreach ($checkCols as $tbl) {
    echo "\n--- $tbl ---\n";
    $cols = DB::connection('oracle')->select(
        "SELECT column_name, data_type, nullable FROM all_tab_columns WHERE UPPER(table_name)=? AND owner=SYS_CONTEXT('USERENV','CURRENT_SCHEMA') ORDER BY column_id",
        [strtoupper($tbl)]
    );
    foreach ($cols as $col) {
        echo "  {$col->column_name}  {$col->data_type}  nullable={$col->nullable}\n";
    }
}

// Show MATERIALS columns
echo "\n--- MATERIALS ---\n";
$cols = DB::connection('oracle')->select(
    "SELECT column_name, data_type, nullable FROM all_tab_columns WHERE UPPER(table_name)='MATERIALS' AND owner=SYS_CONTEXT('USERENV','CURRENT_SCHEMA') ORDER BY column_id"
);
foreach ($cols as $col) {
    echo "  {$col->column_name}  {$col->data_type}  nullable={$col->nullable}\n";
}
