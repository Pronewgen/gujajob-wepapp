<?php
require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

// Try raw insert to understand Oracle date format
$conn = Illuminate\Support\Facades\DB::connection('oracle');

// Test 1: What does a 'date' cast model send?
$asset = new App\Models\Asset();
echo "getDateFormat(): " . $asset->getDateFormat() . "\n";

// Test 2: Try inserting a date string directly
try {
    $conn->insert("INSERT INTO ASSET (id, asscat_id, ass_code, org_id, ass_status, created_by, updated_by, ass_contact_date, inspect_date) VALUES (99999991, 1, 'TEST-DATE', 1, '1', 1, 1, ?, ?)", ['2026-08-10', '2026-08-17']);
    echo "Direct string insert: OK\n";
    $conn->delete("DELETE FROM ASSET WHERE id = 99999991");
} catch (Exception $e) {
    echo "Direct string insert FAILED: " . $e->getMessage() . "\n";
}

// Test 3: Try inserting with time component
try {
    $conn->insert("INSERT INTO ASSET (id, asscat_id, ass_code, org_id, ass_status, created_by, updated_by, ass_contact_date, inspect_date) VALUES (99999991, 1, 'TEST-DATE', 1, '1', 1, 1, ?, ?)", ['2026-08-10 00:00:00', '2026-08-17 00:00:00']);
    echo "Full datetime insert: OK\n";
    $conn->delete("DELETE FROM ASSET WHERE id = 99999991");
} catch (Exception $e) {
    echo "Full datetime insert FAILED: " . $e->getMessage() . "\n";
}
