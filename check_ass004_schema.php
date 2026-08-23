<?php
define('LARAVEL_START', microtime(true));
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Http\Kernel::class);
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

$oracle = DB::connection('oracle');

// Find all asset-related tables
echo "=== ASSET-RELATED TABLES ===\n";
$tables = $oracle->select("
    SELECT table_name FROM all_tables
    WHERE owner = SYS_CONTEXT('USERENV','CURRENT_SCHEMA')
      AND table_name LIKE 'ASSET%'
    ORDER BY table_name
");
foreach ($tables as $t) {
    echo $t->table_name . "\n";
}

// Check ASSET table key columns
echo "\n=== ASSET TABLE COLUMNS ===\n";
$cols = $oracle->select("
    SELECT column_name, data_type, nullable, data_length, data_precision, data_scale
    FROM all_tab_columns
    WHERE table_name = 'ASSET'
      AND owner = SYS_CONTEXT('USERENV','CURRENT_SCHEMA')
    ORDER BY column_id
");
foreach ($cols as $c) {
    echo sprintf("  %-30s %s(%s) %s\n", $c->column_name, $c->data_type, $c->data_length ?? $c->data_precision, $c->nullable === 'Y' ? 'NULL' : 'NOT NULL');
}

// Check if ASS_ASSIGNMENT or similar tables exist
echo "\n=== ALL TABLES WITH 'ASS' IN NAME ===\n";
$assTables = $oracle->select("
    SELECT table_name FROM all_tables
    WHERE owner = SYS_CONTEXT('USERENV','CURRENT_SCHEMA')
      AND (table_name LIKE 'ASS%' OR table_name LIKE '%ASSIGN%' OR table_name LIKE '%ALLOC%')
    ORDER BY table_name
");
foreach ($assTables as $t) {
    echo $t->table_name . "\n";
}

// Check GLB_ORGANIZATION columns
echo "\n=== GLB_ORGANIZATION KEY COLUMNS ===\n";
$orgCols = $oracle->select("
    SELECT column_name, data_type
    FROM all_tab_columns
    WHERE table_name = 'GLB_ORGANIZATION'
      AND owner = SYS_CONTEXT('USERENV','CURRENT_SCHEMA')
      AND column_name IN ('ORG_ID','ORG_CODE','ORG_NAME','ORG_ORG_ID','ZONE_FLG','ORG_GROUP_ID','ORG_TYPE','GROUP_ID','GROUP_CODE','GROUP_NAME','ORG_LEVEL')
    ORDER BY column_id
");
foreach ($orgCols as $c) {
    echo sprintf("  %-30s %s\n", $c->column_name, $c->data_type);
}

// Check all GLB_ORGANIZATION columns
echo "\n=== ALL GLB_ORGANIZATION COLUMNS ===\n";
$allOrgCols = $oracle->select("
    SELECT column_name, data_type
    FROM all_tab_columns
    WHERE table_name = 'GLB_ORGANIZATION'
      AND owner = SYS_CONTEXT('USERENV','CURRENT_SCHEMA')
    ORDER BY column_id
");
foreach ($allOrgCols as $c) {
    echo sprintf("  %-30s %s\n", $c->column_name, $c->data_type);
}

// Check SYS_USER columns
echo "\n=== SYS_USER TABLE COLUMNS ===\n";
$userCols = $oracle->select("
    SELECT column_name, data_type, nullable
    FROM all_tab_columns
    WHERE table_name = 'SYS_USER'
      AND owner = SYS_CONTEXT('USERENV','CURRENT_SCHEMA')
    ORDER BY column_id
");
foreach ($userCols as $c) {
    echo sprintf("  %-30s %s %s\n", $c->column_name, $c->data_type, $c->nullable === 'Y' ? 'NULL' : 'NOT NULL');
}

// Check ASSET_CATEGORY columns
echo "\n=== ASSET_CATEGORY COLUMNS ===\n";
$catCols = $oracle->select("
    SELECT column_name, data_type
    FROM all_tab_columns
    WHERE table_name = 'ASSET_CATEGORY'
      AND owner = SYS_CONTEXT('USERENV','CURRENT_SCHEMA')
    ORDER BY column_id
");
foreach ($catCols as $c) {
    echo sprintf("  %-30s %s\n", $c->column_name, $c->data_type);
}

// Check for sequences related to ASS or ASSET
echo "\n=== ASS/ASSET-RELATED SEQUENCES ===\n";
$seqs = $oracle->select("
    SELECT sequence_name FROM all_sequences
    WHERE sequence_owner = SYS_CONTEXT('USERENV','CURRENT_SCHEMA')
      AND (sequence_name LIKE 'ASS%' OR sequence_name LIKE '%ASSET%' OR sequence_name LIKE '%ASSIGN%')
    ORDER BY sequence_name
");
foreach ($seqs as $s) {
    echo $s->sequence_name . "\n";
}

// Sample of ASSET status values
echo "\n=== ASSET STATUS VALUES ===\n";
$statuses = $oracle->select("
    SELECT ass_status, COUNT(*) as cnt
    FROM ASSET
    GROUP BY ass_status
    ORDER BY ass_status
");
foreach ($statuses as $s) {
    echo sprintf("  status=%s count=%d\n", $s->ass_status, $s->cnt);
}

echo "\nDone.\n";
