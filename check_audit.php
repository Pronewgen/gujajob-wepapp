<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
use Illuminate\Support\Facades\DB;

// Check existing MAT-002 codes
$codes = DB::connection('oracle')->select('SELECT MAT_PRO_CODE, VAT_TYPE, VAT_RATE FROM MATERIAL_PROCUREMENT ORDER BY ID');
echo 'MATERIAL_PROCUREMENT rows: '.count($codes).PHP_EOL;
foreach($codes as $r) echo json_encode((array)$r).PHP_EOL;

// Check existing MAT-003 codes
$wds = DB::connection('oracle')->select('SELECT MAT_WD_CODE, STATUS FROM MATERIAL_WITHDRAWN ORDER BY ID');
echo 'MATERIAL_WITHDRAWN rows: '.count($wds).PHP_EOL;
foreach($wds as $r) echo json_encode((array)$r).PHP_EOL;

// Check if WITHDRAW_TYPE column exists
$col = DB::connection('oracle')->select("SELECT column_name FROM all_tab_columns WHERE table_name = 'MATERIAL_WITHDRAWN' AND column_name = 'WITHDRAW_TYPE'");
echo 'WITHDRAW_TYPE exists: '.(count($col)>0?'YES':'NO').PHP_EOL;

// Check all tables with 'METHOD' or 'PROCURE' in name
$tbl = DB::connection('oracle')->select("SELECT table_name FROM all_tables WHERE table_name LIKE '%METHOD%' OR table_name LIKE '%PROCU%' ORDER BY table_name");
echo 'Method/Procurement tables: '.PHP_EOL;
foreach($tbl as $r) echo json_encode((array)$r).PHP_EOL;

// MAT_PRO_CODE length
$len = DB::connection('oracle')->select("SELECT char_length FROM all_tab_columns WHERE table_name='MATERIAL_PROCUREMENT' AND column_name='MAT_PRO_CODE'");
echo 'MAT_PRO_CODE max length: '.(isset($len[0])?$len[0]->char_length:'?').PHP_EOL;

$len2 = DB::connection('oracle')->select("SELECT char_length FROM all_tab_columns WHERE table_name='MATERIAL_WITHDRAWN' AND column_name='MAT_WD_CODE'");
echo 'MAT_WD_CODE max length: '.(isset($len2[0])?$len2[0]->char_length:'?').PHP_EOL;

// Oracle Sequences related to procurement/withdrawn
$seqs = DB::connection('oracle')->select("SELECT sequence_name FROM all_sequences WHERE sequence_name LIKE '%MATERIAL%' OR sequence_name LIKE '%MAT%' ORDER BY sequence_name");
echo 'Sequences: '.PHP_EOL;
foreach($seqs as $r) echo json_encode((array)$r).PHP_EOL;
