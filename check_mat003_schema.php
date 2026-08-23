<?php
// MAT-003 Oracle schema investigation
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$db = DB::connection('oracle');

echo "=== ALL TABLES ===\n";
$tables = $db->select("SELECT table_name FROM user_tables ORDER BY table_name");
foreach ($tables as $t) {
    echo $t->table_name . "\n";
}

echo "\n=== SEQUENCES ===\n";
$seqs = $db->select("SELECT sequence_name FROM user_sequences ORDER BY sequence_name");
foreach ($seqs as $s) {
    echo $s->sequence_name . "\n";
}

echo "\n=== WITHDRAW-RELATED TABLES (columns) ===\n";
$withdrawTables = [];
foreach ($tables as $t) {
    $name = $t->table_name;
    if (stripos($name, 'WITHDRAW') !== false ||
        stripos($name, 'ISSUE') !== false ||
        stripos($name, 'DRAWN') !== false ||
        stripos($name, 'REQUISIT') !== false ||
        stripos($name, 'STOCK') !== false ||
        stripos($name, 'LEDGER') !== false ||
        stripos($name, 'BALANCE') !== false ||
        stripos($name, 'MAT') !== false) {
        $withdrawTables[] = $name;
    }
}

foreach ($withdrawTables as $tname) {
    echo "\n--- TABLE: $tname ---\n";
    $cols = $db->select("SELECT column_name, data_type, data_length, data_precision, data_scale, nullable
                         FROM user_tab_columns WHERE table_name = ? ORDER BY column_id", [$tname]);
    foreach ($cols as $c) {
        $type = $c->data_type;
        if ($c->data_precision) $type .= "({$c->data_precision},{$c->data_scale})";
        elseif ($c->data_length) $type .= "({$c->data_length})";
        echo "  {$c->column_name} {$type} " . ($c->nullable === 'N' ? 'NOT NULL' : '') . "\n";
    }

    // constraints
    $constraints = $db->select(
        "SELECT c.constraint_name, c.constraint_type, c.search_condition,
                r.table_name AS ref_table, cc.column_name
         FROM user_constraints c
         JOIN user_cons_columns cc ON cc.constraint_name = c.constraint_name
         LEFT JOIN user_constraints r ON r.constraint_name = c.r_constraint_name
         WHERE c.table_name = ?
         ORDER BY c.constraint_type, cc.position",
        [$tname]
    );
    if ($constraints) {
        echo "  CONSTRAINTS:\n";
        foreach ($constraints as $con) {
            echo "    [{$con->constraint_type}] {$con->constraint_name} col={$con->column_name}";
            if ($con->ref_table) echo " -> {$con->ref_table}";
            if ($con->search_condition) echo " CHECK: {$con->search_condition}";
            echo "\n";
        }
    }
}

echo "\n=== MATERIALS TABLE COLUMNS ===\n";
$cols = $db->select("SELECT column_name, data_type, data_length, data_precision, data_scale, nullable
                     FROM user_tab_columns WHERE table_name = 'MATERIALS' ORDER BY column_id");
foreach ($cols as $c) {
    $type = $c->data_type;
    if ($c->data_precision) $type .= "({$c->data_precision},{$c->data_scale})";
    elseif ($c->data_length) $type .= "({$c->data_length})";
    echo "  {$c->column_name} {$type} " . ($c->nullable === 'N' ? 'NOT NULL' : '') . "\n";
}

echo "\n=== VIEWS ===\n";
$views = $db->select("SELECT view_name FROM user_views ORDER BY view_name");
foreach ($views as $v) {
    echo $v->view_name . "\n";
}

echo "\nDONE\n";
