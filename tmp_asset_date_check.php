<?php
require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

// Check ASSET table date columns
$result = Illuminate\Support\Facades\DB::connection('oracle')
    ->select("SELECT column_name, data_type, data_length, nullable FROM all_tab_columns WHERE table_name='ASSET' AND column_name IN ('ASS_CONTACT_DATE','INSPECT_DATE','ASS_TRANS_DATE') ORDER BY column_name");

echo "ASSET DATE COLUMNS:\n";
foreach ($result as $row) {
    echo "  $row->column_name: type=$row->data_type, nullable=$row->nullable\n";
}

// Check a few recent assets with dates
$assets = Illuminate\Support\Facades\DB::connection('oracle')
    ->select("SELECT id, ass_code, ass_contact_date, inspect_date FROM ASSET WHERE ass_contact_date IS NOT NULL OR inspect_date IS NOT NULL ORDER BY id DESC FETCH FIRST 3 ROWS ONLY");

echo "\nRECENT ASSETS WITH DATES:\n";
foreach ($assets as $a) {
    echo "  id={$a->id} code={$a->ass_code} contact_date={$a->ass_contact_date} inspect_date={$a->inspect_date}\n";
}

// Check NLS session params
$nls = Illuminate\Support\Facades\DB::connection('oracle')
    ->select("SELECT parameter, value FROM nls_session_parameters WHERE parameter IN ('NLS_DATE_FORMAT', 'NLS_DATE_LANGUAGE')");
echo "\nNLS_SESSION_PARAMETERS:\n";
foreach ($nls as $n) {
    echo "  $n->parameter = $n->value\n";
}
