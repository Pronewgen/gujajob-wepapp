<?php
require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$conn = Illuminate\Support\Facades\DB::connection('oracle');

// Get a valid asscat_id and org_id
$cat = $conn->selectOne("SELECT id FROM ASSET_CATEGORY FETCH FIRST 1 ROWS ONLY");
$org = $conn->selectOne("SELECT org_id FROM GLB_ORGANIZATION FETCH FIRST 1 ROWS ONLY");

echo "Using asscat_id={$cat->id}, org_id={$org->org_id}\n";

// Test: create asset with dates using Eloquent
$nextId = (int) $conn->selectOne('SELECT ASSET_SEQ.NEXTVAL AS next_id FROM DUAL')->next_id;
$code = 'TST-DATE-' . $nextId;

try {
    App\Models\Asset::create([
        'id'               => $nextId,
        'asscat_id'        => (int)$cat->id,
        'ass_code'         => $code,
        'org_id'           => (int)$org->org_id,
        'ass_status'       => '1',
        'ass_contact_date' => '2026-08-10',
        'inspect_date'     => '2026-08-17',
        'created_by'       => 1,
        'updated_by'       => 1,
    ]);

    // Verify
    $saved = $conn->selectOne("SELECT ass_contact_date, inspect_date FROM ASSET WHERE id = ?", [$nextId]);
    echo "Saved: contact={$saved->ass_contact_date}, inspect={$saved->inspect_date}\n";

    // Clean up
    $conn->delete("DELETE FROM ASSET WHERE id = ?", [$nextId]);
    echo "Cleaned up. Test PASSED.\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
