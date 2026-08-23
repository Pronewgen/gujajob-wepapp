<?php
require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$conn = Illuminate\Support\Facades\DB::connection('oracle');

// Count assets
$total = $conn->selectOne("SELECT COUNT(*) as cnt FROM ASSET");
echo "Total assets: " . $total->cnt . "\n";

// Check most recent assets
$recent = $conn->select("SELECT id, ass_code, ass_status, ass_contact_date, inspect_date FROM ASSET ORDER BY id DESC FETCH FIRST 5 ROWS ONLY");
echo "\nRecent 5 assets:\n";
foreach ($recent as $r) {
    echo "  id={$r->id} code={$r->ass_code} status={$r->ass_status} contact={$r->ass_contact_date} inspect={$r->inspect_date}\n";
}
