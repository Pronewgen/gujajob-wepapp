<?php
require __DIR__ . '/vendor/autoload.php';
$app = require __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();
use Illuminate\Support\Facades\DB;

function cols($t) {
    $r = DB::connection('oracle')->select("SELECT column_name FROM all_tab_columns WHERE UPPER(table_name)=? AND owner=SYS_CONTEXT('USERENV','CURRENT_SCHEMA') ORDER BY column_id", [strtoupper($t)]);
    echo "$t: " . implode(', ', array_column($r, 'column_name')) . "\n";
}
cols('MATERIAL_PROCUREMENT');
cols('MATERIAL_WITHDRAWN');
cols('MATERIAL_INSPECTION');
echo "\nSample GLB_ORGANIZATION (first 3):\n";
$r = DB::connection('oracle')->select("SELECT org_id, org_code, org_name FROM GLB_ORGANIZATION WHERE ROWNUM<=3 ORDER BY org_id");
foreach($r as $o) echo "  {$o->org_id} | {$o->org_code} | {$o->org_name}\n";
