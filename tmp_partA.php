<?php
require 'vendor/autoload.php';
$a=require 'bootstrap/app.php';
$a->make('Illuminate\Contracts\Console\Kernel')->bootstrap();
$o = DB::connection('oracle');

function cols($o,$t){
    $r=$o->select('SELECT column_name c,data_type t,data_length l,nullable n FROM all_tab_columns WHERE table_name=? ORDER BY column_id',[$t]);
    if(!$r){echo "  (not found)\n";return;}
    foreach($r as $row) echo "  {$row->c} ({$row->t} {$row->l}) null={$row->n}\n";
}

echo "=SYS_USER=\n"; cols($o,'SYS_USER');
echo "\n=MAT_WP_PERSON=\n"; cols($o,'MAT_WP_PERSON');
echo "\n=WITHDRAW tables=\n";
foreach($o->select("SELECT table_name FROM all_tables WHERE table_name LIKE '%WITHDRAW%' OR table_name LIKE '%WD%' ORDER BY table_name") as $r) echo "  {$r->table_name}\n";
echo "\n=MATERIAL_WITHDRAWN cols=\n"; cols($o,'MATERIAL_WITHDRAWN');
echo "\n=Status distribution=\n";
try{foreach($o->select('SELECT status,withdraw_type,COUNT(*) cnt FROM MATERIAL_WITHDRAWN GROUP BY status,withdraw_type ORDER BY status') as $r) echo "  status={$r->status} type={$r->withdraw_type} cnt={$r->cnt}\n";}catch(Exception $e){echo "  ERR:{$e->getMessage()}\n";}
echo "\n=page-container max-width in MAT-003=\n";
preg_match('/\.page-container\s*\{[^}]+max-width:\s*(\S+)/s',file_get_contents('resources/css/material/MAT-003-withdraw-material/style.css'),$m);
echo "  ".($m[1]??'not found')."\n";
