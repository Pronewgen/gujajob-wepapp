<?php
// test pages
// Dry-run check for MWD-69000007 — run via: php artisan tinker --execute="require base_path('tmp_dryrun.php');"

$exact = 'MWD-69000007';

// Header
$headers = DB::connection('oracle')->select(
    "SELECT id, mat_wd_code, mat_wd_date, mat_wd_person, org_id, status, created_by, updated_by
     FROM MATERIAL_WITHDRAWN WHERE mat_wd_code = ?",
    [$exact]
);
echo "\n=== HEADER (exact match) ===\n";
echo "Count: " . count($headers) . "\n";
foreach ($headers as $h) { echo json_encode((array)$h) . "\n"; }

if (count($headers) !== 1) { echo "ABORT: expected exactly 1 header, found " . count($headers) . "\n"; return; }
$hId = $headers[0]->id;

// Detail
$details = DB::connection('oracle')->select(
    "SELECT id, mat_wd_id, mat_id, wd_amount FROM MATERIAL_WITHDRAWN_LIST WHERE mat_wd_id = ?",
    [$hId]
);
echo "\n=== DETAIL rows ===\n";
echo "Count: " . count($details) . "\n";
foreach ($details as $d) { echo json_encode((array)$d) . "\n"; }

// Inspection (transfer)
$insps = DB::connection('oracle')->select(
    "SELECT id, mat_wd_id, mat_insp_code FROM MATERIAL_INSPECTION WHERE mat_wd_id = ?",
    [$hId]
);
echo "\n=== MATERIAL_INSPECTION ===\n";
echo "Count: " . count($insps) . "\n";
foreach ($insps as $i) { echo json_encode((array)$i) . "\n"; }

// Status check
$statusMap = [0=>'DRAFT', 1=>'PENDING', 2=>'APPROVED', 3=>'REJECTED'];
$status = (int) $headers[0]->status;
echo "\n=== STATUS ===\n";
echo "status=" . $status . " (" . ($statusMap[$status] ?? 'UNKNOWN') . ")\n";

// Stock impact?
if ($status === 2) {
    echo "\n⚠️  APPROVED — this record has stock impact. Must reverse before delete.\n";
    // Check stock impact via inspection list
    foreach ($insps as $insp) {
        $lists = DB::connection('oracle')->select(
            "SELECT id, mat_isp_id, mat_id, isp_amount FROM MATERIAL_INSPECTION_LIST WHERE mat_isp_id = ?",
            [$insp->id]
        );
        echo "INSPECTION_LIST count=" . count($lists) . "\n";
    }
} else {
    echo "\n✓ NOT APPROVED — safe to delete header + detail (no stock impact).\n";
}

// Other MAT_WITHDRAWN codes in DB
$allCodes = DB::connection('oracle')->select(
    "SELECT mat_wd_code, status FROM MATERIAL_WITHDRAWN ORDER BY mat_wd_code"
);
echo "\n=== ALL MAT_WITHDRAWN CODES ===\n";
echo "Total: " . count($allCodes) . "\n";
foreach ($allCodes as $c) {
    echo $c->mat_wd_code . " | status=" . $c->status . "\n";
}

// All MAT_PRO codes
$proCodes = DB::connection('oracle')->select(
    "SELECT mat_pro_code FROM MATERIAL_PROCUREMENT ORDER BY mat_pro_code"
);
echo "\n=== ALL MATERIAL_PROCUREMENT CODES ===\n";
echo "Total: " . count($proCodes) . "\n";
foreach ($proCodes as $p) {
    echo $p->mat_pro_code . "\n";
}
