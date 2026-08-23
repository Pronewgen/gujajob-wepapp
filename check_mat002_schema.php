<?php
define('LARAVEL_START', microtime(true));
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(\Illuminate\Contracts\Http\Kernel::class);
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$db = \Illuminate\Support\Facades\DB::connection('oracle');

echo "=== 1. Columns of MATERIAL_PROCUREMENT ===\n";
$cols = $db->select("
    SELECT column_name, data_type, data_length, nullable
    FROM all_tab_columns
    WHERE UPPER(table_name) = 'MATERIAL_PROCUREMENT'
    ORDER BY column_id
");
foreach ($cols as $c) {
    echo "  {$c->column_name} | {$c->data_type}({$c->data_length}) | NULL={$c->nullable}\n";
}

echo "\n=== 2. Constraints on MATERIAL_PROCUREMENT ===\n";
$cons = $db->select("
    SELECT c.constraint_name, c.constraint_type, c.status,
           col.column_name,
           r.table_name as ref_table, rc.column_name as ref_col
    FROM all_constraints c
    JOIN all_cons_columns col ON col.constraint_name = c.constraint_name
    LEFT JOIN all_constraints r ON r.constraint_name = c.r_constraint_name
    LEFT JOIN all_cons_columns rc ON rc.constraint_name = c.r_constraint_name
    WHERE UPPER(c.table_name) = 'MATERIAL_PROCUREMENT'
    ORDER BY c.constraint_type, c.constraint_name
");
foreach ($cons as $c) {
    echo "  [{$c->constraint_type}] {$c->constraint_name} col={$c->column_name} ref_table={$c->ref_table} ref_col={$c->ref_col} status={$c->status}\n";
}

echo "\n=== 3. FKs referencing MATERIAL_PROCUREMENT from other tables ===\n";
$refs = $db->select("
    SELECT c.table_name, c.constraint_name, col.column_name,
           r.table_name as ref_table, rc.column_name as ref_col
    FROM all_constraints c
    JOIN all_cons_columns col ON col.constraint_name = c.constraint_name
    JOIN all_constraints r ON r.constraint_name = c.r_constraint_name
    JOIN all_cons_columns rc ON rc.constraint_name = c.r_constraint_name
    WHERE UPPER(r.table_name) = 'MATERIAL_PROCUREMENT'
      AND c.constraint_type = 'R'
");
foreach ($refs as $c) {
    echo "  {$c->table_name}.{$c->column_name} -> {$c->ref_table}.{$c->ref_col} [{$c->constraint_name}]\n";
}

echo "\n=== 4. Triggers on MATERIAL_PROCUREMENT ===\n";
$trigs = $db->select("
    SELECT trigger_name, trigger_type, triggering_event, status
    FROM all_triggers
    WHERE UPPER(table_name) = 'MATERIAL_PROCUREMENT'
");
foreach ($trigs as $t) {
    echo "  {$t->trigger_name} | {$t->trigger_type} | {$t->triggering_event} | {$t->status}\n";
}

echo "\n=== 5. All records in MATERIAL_PROCUREMENT ===\n";
$rows = $db->select("
    SELECT id, mat_pro_code, TO_CHAR(mat_pro_date,'YYYY-MM-DD') as mat_pro_date
    FROM MATERIAL_PROCUREMENT
    ORDER BY id
");
echo "  Total: " . count($rows) . "\n";
foreach ($rows as $r) {
    echo "  id={$r->id} | code={$r->mat_pro_code} | date={$r->mat_pro_date}\n";
}

echo "\n=== 6. Indexes on MATERIAL_PROCUREMENT ===\n";
$idx = $db->select("
    SELECT i.index_name, i.uniqueness, ic.column_name
    FROM all_indexes i
    JOIN all_ind_columns ic ON ic.index_name = i.index_name
    WHERE UPPER(i.table_name) = 'MATERIAL_PROCUREMENT'
    ORDER BY i.index_name, ic.column_position
");
foreach ($idx as $i) {
    echo "  {$i->index_name} | {$i->uniqueness} | col={$i->column_name}\n";
}

echo "\n=== 7. FK columns in MATERIAL_PROCUREMENT_LIST ===\n";
$fks = $db->select("
    SELECT c.constraint_name, c.constraint_type, c.status,
           col.column_name,
           r.table_name as ref_table, rc.column_name as ref_col
    FROM all_constraints c
    JOIN all_cons_columns col ON col.constraint_name = c.constraint_name
    LEFT JOIN all_constraints r ON r.constraint_name = c.r_constraint_name
    LEFT JOIN all_cons_columns rc ON rc.constraint_name = c.r_constraint_name
    WHERE UPPER(c.table_name) = 'MATERIAL_PROCUREMENT_LIST'
      AND c.constraint_type IN ('R','P','U')
    ORDER BY c.constraint_type, c.constraint_name
");
foreach ($fks as $c) {
    echo "  [{$c->constraint_type}] {$c->constraint_name} col={$c->column_name} ref_table={$c->ref_table} ref_col={$c->ref_col} status={$c->status}\n";
}

echo "\nDone.\n";

require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$pdo = DB::connection('oracle')->getPdo();

echo "=== MATERIAL_PROCUREMENT COLUMNS ===\n";
$r = $pdo->query("SELECT column_name, data_type, data_length, data_precision, nullable FROM user_tab_columns WHERE table_name='MATERIAL_PROCUREMENT' ORDER BY column_id")->fetchAll(PDO::FETCH_ASSOC);
foreach ($r as $row) echo implode(' | ', $row) . "\n";

echo "\n=== CONSTRAINTS ===\n";
$r = $pdo->query("SELECT constraint_name, constraint_type, search_condition FROM user_constraints WHERE table_name='MATERIAL_PROCUREMENT'")->fetchAll(PDO::FETCH_ASSOC);
foreach ($r as $row) echo implode(' | ', $row) . "\n";

echo "\n=== TRIGGERS ===\n";
$r = $pdo->query("SELECT trigger_name, status FROM user_triggers WHERE table_name='MATERIAL_PROCUREMENT'")->fetchAll(PDO::FETCH_ASSOC);
foreach ($r as $row) echo implode(' | ', $row) . "\n";
if (empty($r)) echo "(none)\n";

echo "\n=== SEQUENCES ===\n";
$r = $pdo->query("SELECT sequence_name FROM user_sequences")->fetchAll(PDO::FETCH_COLUMN);
foreach ($r as $s) echo $s . "\n";
if (empty($r)) echo "(none)\n";

echo "\n=== EXISTING mat_pro_code VALUES ===\n";
$r = $pdo->query("SELECT mat_pro_code, mat_pro_date, vat_type, vat_rate FROM MATERIAL_PROCUREMENT ORDER BY id")->fetchAll(PDO::FETCH_ASSOC);
foreach ($r as $row) echo implode(' | ', $row) . "\n";
if (empty($r)) echo "(none)\n";

echo "\n=== vat_type distinct values ===\n";
$r = $pdo->query("SELECT DISTINCT vat_type FROM MATERIAL_PROCUREMENT")->fetchAll(PDO::FETCH_COLUMN);
print_r($r);
