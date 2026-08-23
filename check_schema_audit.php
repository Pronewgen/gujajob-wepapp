<?php
require __DIR__ . '/vendor/autoload.php';

$app = require __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$db = \Illuminate\Support\Facades\DB::connection('oracle');

$tables = ['MATERIAL_WITHDRAWN', 'MATERIAL_PROCUREMENT', 'MATERIAL_PROCUREMENT_LIST', 'MATERIAL_WITHDRAWN_LIST', 'MATERIAL_INSPECTION'];

foreach ($tables as $table) {
    echo "\n=== $table ===\n";
    try {
        $cols = $db->select("SELECT column_name, data_type, data_length, nullable FROM all_tab_columns WHERE table_name = ? ORDER BY column_id", [$table]);
        foreach ($cols as $c) {
            echo "  " . $c->column_name . " | " . $c->data_type . "(" . $c->data_length . ") | " . ($c->nullable === 'Y' ? 'NULL' : 'NOT NULL') . "\n";
        }
    } catch (Exception $e) {
        echo "  ERROR: " . $e->getMessage() . "\n";
    }
}

// Check for Procurement Method table
echo "\n=== SEARCHING FOR PROCUREMENT METHOD TABLE ===\n";
try {
    $rows = $db->select("SELECT table_name FROM all_tables WHERE table_name LIKE '%PROCURE%' OR table_name LIKE '%METHOD%' OR table_name LIKE '%PURCHASE%' ORDER BY table_name");
    foreach ($rows as $r) echo "  " . $r->table_name . "\n";
} catch (Exception $e) {
    echo "  ERROR: " . $e->getMessage() . "\n";
}

echo "\n=== SEARCHING FOR MATERIAL RELATED TABLES ===\n";
try {
    $rows = $db->select("SELECT table_name FROM all_tables WHERE table_name LIKE 'MATERIAL%' ORDER BY table_name");
    foreach ($rows as $r) echo "  " . $r->table_name . "\n";
} catch (Exception $e) {
    echo "  ERROR: " . $e->getMessage() . "\n";
}
