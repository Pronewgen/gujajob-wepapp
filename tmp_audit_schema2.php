<?php
require __DIR__ . '/vendor/autoload.php';
$app = require __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

function showCols($table) {
    echo "\n--- $table ---\n";
    $cols = DB::connection('oracle')->select(
        "SELECT column_name, data_type, data_length, nullable FROM all_tab_columns WHERE UPPER(table_name)=? AND owner=SYS_CONTEXT('USERENV','CURRENT_SCHEMA') ORDER BY column_id",
        [strtoupper($table)]
    );
    foreach ($cols as $col) {
        echo "  {$col->column_name}  {$col->data_type}({$col->data_length})  nullable={$col->nullable}\n";
    }
    // show row count
    $cnt = DB::connection('oracle')->selectOne("SELECT COUNT(*) AS c FROM $table");
    echo "  [ROWS: {$cnt->c}]\n";
}

showCols('ASSET_CATEGORY');
showCols('ASSET');
showCols('MATERIAL_INVENTORY');

// Check PK constraints
echo "\n=== PRIMARY KEYS ===\n";
foreach (['ASSET_CATEGORY', 'ASSET', 'MATERIAL_INVENTORY'] as $tbl) {
    $pks = DB::connection('oracle')->select(
        "SELECT cols.column_name FROM all_constraints cons JOIN all_cons_columns cols ON cons.constraint_name=cols.constraint_name AND cons.owner=cols.owner WHERE cons.constraint_type='P' AND UPPER(cols.table_name)=? AND cons.owner=SYS_CONTEXT('USERENV','CURRENT_SCHEMA')",
        [strtoupper($tbl)]
    );
    $pkCols = implode(', ', array_column($pks, 'column_name'));
    echo "$tbl PK: $pkCols\n";
}

// Check sequences
echo "\n=== SEQUENCES ===\n";
$seqs = DB::connection('oracle')->select(
    "SELECT sequence_name FROM all_sequences WHERE sequence_owner=SYS_CONTEXT('USERENV','CURRENT_SCHEMA') AND (UPPER(sequence_name) LIKE '%ASSET%' OR UPPER(sequence_name) LIKE '%CATEGORY%' OR UPPER(sequence_name) LIKE '%UNIT%') ORDER BY sequence_name"
);
foreach ($seqs as $s) echo "  {$s->sequence_name}\n";

// Sample data from ASSET_CATEGORY
echo "\n=== ASSET_CATEGORY sample data ===\n";
$rows = DB::connection('oracle')->select("SELECT * FROM ASSET_CATEGORY WHERE ROWNUM <= 3");
foreach ($rows as $r) {
    print_r((array)$r);
}

// Check for FK from ASSET to ASSET_CATEGORY
echo "\n=== FK from ASSET ===\n";
$fks = DB::connection('oracle')->select(
    "SELECT a.constraint_name, a.column_name, c_pk.table_name AS ref_table, b.column_name AS ref_col
     FROM all_cons_columns a
     JOIN all_constraints c ON a.constraint_name=c.constraint_name AND a.owner=c.owner
     JOIN all_constraints c_pk ON c.r_constraint_name=c_pk.constraint_name AND c.r_owner=c_pk.owner
     JOIN all_cons_columns b ON b.constraint_name=c_pk.constraint_name AND b.owner=c_pk.owner
     WHERE c.constraint_type='R' AND UPPER(a.table_name)='ASSET' AND a.owner=SYS_CONTEXT('USERENV','CURRENT_SCHEMA')"
);
foreach ($fks as $fk) {
    echo "  {$fk->column_name} -> {$fk->ref_table}.{$fk->ref_col}\n";
}
