<?php
require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();
use Illuminate\Support\Facades\DB;

// Check SYS_USER table
echo "=== SYS_USER schema ===" . PHP_EOL;
try {
    $cols = DB::connection('oracle')->select("SELECT column_name, data_type, data_length, nullable FROM all_tab_columns WHERE table_name='SYS_USER' ORDER BY column_id");
    foreach ($cols as $c) echo "  {$c->column_name} ({$c->data_type},{$c->data_length}) null={$c->nullable}" . PHP_EOL;
    if (empty($cols)) echo "  (table not found)" . PHP_EOL;
} catch (Exception $e) { echo "  ERROR: " . $e->getMessage() . PHP_EOL; }

// Sample SYS_USER data
echo PHP_EOL . "=== SYS_USER sample data ===" . PHP_EOL;
try {
    $rows = DB::connection('oracle')->select("SELECT * FROM SYS_USER WHERE ROWNUM <= 5");
    foreach ($rows as $r) { echo "  "; print_r((array)$r); }
    if (empty($rows)) echo "  (no rows)" . PHP_EOL;
} catch (Exception $e) { echo "  ERROR: " . $e->getMessage() . PHP_EOL; }

// Check MATERIAL_WITHDRAWN columns (withdraw_type etc.)
echo PHP_EOL . "=== MATERIAL_WITHDRAWN columns ===" . PHP_EOL;
try {
    $cols = DB::connection('oracle')->select("SELECT column_name, data_type, data_length FROM all_tab_columns WHERE table_name='MATERIAL_WITHDRAWN' ORDER BY column_id");
    foreach ($cols as $c) echo "  {$c->column_name} ({$c->data_type},{$c->data_length})" . PHP_EOL;
} catch (Exception $e) { echo "  ERROR: " . $e->getMessage() . PHP_EOL; }

// Check existing withdraw_type values
echo PHP_EOL . "=== MATERIAL_WITHDRAWN data (withdraw_type values) ===" . PHP_EOL;
try {
    $rows = DB::connection('oracle')->select("SELECT mat_wd_code, status, withdraw_type, mat_wd_person FROM MATERIAL_WITHDRAWN ORDER BY id DESC");
    foreach ($rows as $r) echo "  code={$r->mat_wd_code} status={$r->status} type={$r->withdraw_type} person={$r->mat_wd_person}" . PHP_EOL;
} catch (Exception $e) { echo "  ERROR: " . $e->getMessage() . PHP_EOL; }

// Check sort-icon CSS component
echo PHP_EOL . "=== sort-icon.css content ===" . PHP_EOL;
echo file_get_contents('resources/css/components/sort-icon.css') . PHP_EOL;
