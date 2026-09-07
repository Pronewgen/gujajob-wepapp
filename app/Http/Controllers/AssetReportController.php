<?php

namespace App\Http\Controllers;

use App\Services\OrganizationVisibilityService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class AssetReportController extends Controller
{
    private const PER_PAGE = 10;

    /** รอจัดสรร, จำหน่ายแล้ว, ชำรุด, สูญหาย — excluded from the fiscal-year ledger report. */
    private const LEDGER_EXCLUDED_STATUSES = ['1', '4', '5', '6'];

    public function __construct(private readonly OrganizationVisibilityService $orgVisibility) {}

    /**
     * Display the asset report form
     */
    public function index(): View
    {
        return view('asset.ASS-009-print-asset-report.index', [
            'pageTitle' => 'จัดพิมพ์รายงานครุภัณฑ์',
        ]);
    }

    /**
     * Search for asset categories via autocomplete
     */
    public function searchCategories(Request $request): JsonResponse
    {
        $keyword = trim($request->string('q')->value());
        $visibleOrgIds = $this->orgVisibility->visibleOrgIds((int) Auth::user()->org_id);

        if ($keyword === '' || empty($visibleOrgIds)) {
            return response()->json(['data' => []]);
        }

        $escaped = $this->escapeLike($keyword);

        $categories = DB::connection('oracle')
            ->table('ASSET_CATEGORY')
            ->selectRaw("id, asscat_code, asscat_name")
            ->whereRaw("UPPER(asscat_code) LIKE UPPER(?) OR UPPER(asscat_name) LIKE UPPER(?)", 
                       ["%{$escaped}%", "%{$escaped}%"])
            ->orderBy('asscat_code')
            ->limit(20)
            ->get();

        $data = $categories->map(fn ($c) => [
            'id'    => $c->id,
            'code'  => $c->asscat_code,
            'name'  => $c->asscat_name,
            'label' => "{$c->asscat_code} {$c->asscat_name}",
        ]);

        return response()->json(['data' => $data]);
    }

    /**
     * Search for assets via autocomplete
     */
    public function searchAssets(Request $request): JsonResponse
    {
        $keyword = trim($request->string('q')->value());
        $categoryId = (int) $request->input('category_id', 0);
        $visibleOrgIds = $this->orgVisibility->visibleOrgIds((int) Auth::user()->org_id);

        // An asset code is only searchable within a chosen category.
        if (empty($visibleOrgIds) || $categoryId <= 0) {
            return response()->json(['data' => []]);
        }

        $assets = DB::connection('oracle')
            ->table('ASSET')
            ->join('ASSET_CATEGORY', 'ASSET.asscat_id', '=', 'ASSET_CATEGORY.id')
            ->selectRaw("ASSET.id, ASSET.asscat_id AS category_id, ASSET.ass_code, ASSET_CATEGORY.asscat_name, ASSET_CATEGORY.asscat_code")
            ->whereIn('ASSET.org_id', $visibleOrgIds)
            ->where('ASSET.asscat_id', $categoryId);

        if ($keyword !== '') {
            $escaped = $this->escapeLike($keyword);
            $assets->whereRaw(
                "(UPPER(ASSET.ass_code) LIKE UPPER(?) OR UPPER(ASSET_CATEGORY.asscat_code || ASSET.ass_code) LIKE UPPER(?))",
                ["%{$escaped}%", "%{$escaped}%"]
            );
        }

        $assets = $assets
            ->orderBy('ASSET.ass_code')
            ->limit(20)
            ->get();

        $data = $assets->map(fn ($a) => [
            'id'    => $a->id,
            'category_id' => $a->category_id,
            'code'  => $a->ass_code,
            'name'  => $a->asscat_name,
            'label' => "{$a->asscat_code}{$a->ass_code} — {$a->asscat_name}",
        ]);

        return response()->json(['data' => $data]);
    }

    /**
     * Search for organizations via autocomplete
     */
    public function searchOrganizations(Request $request): JsonResponse
    {
        $keyword = trim($request->string('q')->value());
        $visibleOrgIds = $this->orgVisibility->visibleOrgIds((int) Auth::user()->org_id);

        if ($keyword === '' || empty($visibleOrgIds)) {
            return response()->json(['data' => []]);
        }

        $escaped = $this->escapeLike($keyword);

        $orgs = DB::connection('oracle')
            ->table('GLB_ORGANIZATION')
            ->select('org_id', 'org_name')
            ->whereIn('org_id', $visibleOrgIds)
            ->whereRaw("UPPER(org_name) LIKE UPPER(?)", ["%{$escaped}%"])
            ->orderBy('org_name')
            ->limit(20)
            ->get();

        $data = $orgs->map(fn ($o) => [
            'id'    => $o->org_id,
            'name'  => $o->org_name,
            'label' => $o->org_name,
        ]);

        return response()->json(['data' => $data]);
    }

    /**
     * Search for sub-organizations via autocomplete
     */
    public function searchSubOrganizations(Request $request): JsonResponse
    {
        $keyword = trim($request->string('q')->value());
        $parentOrgId = (int) $request->input('parent_org_id', 0);
        $visibleOrgIds = $this->orgVisibility->visibleOrgIds((int) Auth::user()->org_id);

        if ($keyword === '' || empty($visibleOrgIds)) {
            return response()->json(['data' => []]);
        }

        $escaped = $this->escapeLike($keyword);

        $query = DB::connection('oracle')
            ->table('GLB_ORGANIZATION')
            ->select('org_id', 'org_name')
            ->whereIn('org_id', $visibleOrgIds)
            ->whereRaw("UPPER(org_name) LIKE UPPER(?)", ["%{$escaped}%"]);

        // If a parent org is specified, filter by hierarchy
        if ($parentOrgId > 0) {
            $query->where('org_org_id', $parentOrgId);
        }

        $subOrgs = $query->orderBy('org_name')
            ->limit(20)
            ->get();

        $data = $subOrgs->map(fn ($o) => [
            'id'    => $o->org_id,
            'name'  => $o->org_name,
            'label' => $o->org_name,
        ]);

        return response()->json(['data' => $data]);
    }

    /**
     * Generate asset register report (Report Type 1)
     */
    public function reportAssetRegister(Request $request): View|RedirectResponse
    {
        $categoryId = (int) $request->input('category_id', 0);
        $assetId = (int) $request->input('asset_id', 0);
        $visibleOrgIds = $this->orgVisibility->visibleOrgIds((int) Auth::user()->org_id);

        if ($assetId > 0 && ($error = $this->validateAssetBelongsToCategory($assetId, $categoryId, $visibleOrgIds)) !== null) {
            return redirect()
                ->route('asset.reports.index')
                ->withErrors(['report' => $error]);
        }

        $assets = $this->assetRegisterQuery($visibleOrgIds, $categoryId, $assetId)
            ->orderBy('ASSET.ass_code')
            ->get();

        return view('asset.ASS-009-print-asset-report.report-register', [
            'pageTitle'   => 'ทะเบียนคุมครุภัณฑ์',
            'assets'      => $assets,
            'generatedAt' => now('Asia/Bangkok')->addYears(543)->format('d/m/Y H.i') . ' น.',
        ]);
    }

    /** Download the current asset register preview as an A4 landscape PDF. */
    public function downloadAssetRegister(Request $request)
    {
        $categoryId = (int) $request->input('category_id', 0);
        $assetId = (int) $request->input('asset_id', 0);
        $visibleOrgIds = $this->orgVisibility->visibleOrgIds((int) Auth::user()->org_id);

        if ($assetId > 0 && ($error = $this->validateAssetBelongsToCategory($assetId, $categoryId, $visibleOrgIds)) !== null) {
            return redirect()->route('asset.reports.index')->withErrors(['report' => $error]);
        }

        $assets = $this->assetRegisterQuery($visibleOrgIds, $categoryId, $assetId)
            ->orderBy('ASSET.ass_code')
            ->get();

        if ($assets->isEmpty()) {
            return redirect()
                ->route('asset.reports.register', $request->only(['category_id', 'asset_id']))
                ->withErrors(['report' => 'ไม่พบข้อมูลสำหรับสร้างไฟล์รายงาน']);
        }

        $pdf = $this->buildAssetRegisterPdf($assets);

        // PDF_TEST_MODE: same PDF bytes, served inline so the browser can preview it
        // in a popup instead of downloading a file on every test run.
        if (config('app.pdf_test_mode')) {
            return $pdf->stream('asset-register-report.pdf');
        }

        return $pdf->download('asset-register-report.pdf');
    }

    /** Build the asset register PDF used by both test mode and the real download. */
    private function buildAssetRegisterPdf(\Illuminate\Support\Collection $assets)
    {
        return Pdf::loadView('asset.ASS-009-print-asset-report.report-register-pdf', [
            'assets'      => $assets,
            'generatedAt' => now('Asia/Bangkok')->addYears(543)->format('d/m/Y H.i') . ' น.',
        ])->setPaper('a4', 'landscape');
    }

    /**
     * Build the shared Oracle dataset used by both the register preview and PDF download.
     *
     * @param  array<int, int>  $visibleOrgIds
     */
    private function assetRegisterQuery(array $visibleOrgIds, int $categoryId, int $assetId): \Illuminate\Database\Query\Builder
    {
        $query = DB::connection('oracle')
            ->table('ASSET')
            ->join('ASSET_CATEGORY', 'ASSET.asscat_id', '=', 'ASSET_CATEGORY.id')
            ->leftJoin('GLB_ORGANIZATION AS ORG', 'ASSET.org_id', '=', 'ORG.org_id')
            ->leftJoin('GLB_ORGANIZATION AS SUB_ORG', 'ASSET.sub_org_id', '=', 'SUB_ORG.org_id')
            ->leftJoin('DEALER', 'ASSET.dealer_id', '=', 'DEALER.id')
            ->selectRaw("
                ASSET.id, ASSET.ass_code, ASSET_CATEGORY.asscat_code, ASSET_CATEGORY.asscat_name,
                ASSET_CATEGORY.asscat_unit, ASSET_CATEGORY.depreciation_rate,
                ASSET.ass_desc, ASSET.ass_model, ASSET.ass_serail, ASSET.inspect_date,
                TO_CHAR(ASSET.inspect_date, 'DD/MM/YYYY') AS inspect_date_th,
                ASSET.ass_price, ASSET.ass_lifetime, ASSET.ass_contact_no,
                TO_CHAR(ASSET.ass_contact_date, 'DD/MM/YYYY') AS ass_contact_date_th,
                ASSET.ass_trans_remark, ASSET.remarks, ASSET.remain_price, ASSET.ass_status,
                ORG.org_name, ORG.zone_flg AS org_zone_flg, SUB_ORG.org_name AS sub_org_name,
                DEALER.dealer_name
            ")
            ->whereIn('ASSET.org_id', $visibleOrgIds);

        if ($categoryId > 0) {
            $query->where('ASSET.asscat_id', $categoryId);
        }

        if ($assetId > 0) {
            $query->where('ASSET.id', $assetId);
        }

        return $query;
    }

    /**
     * Generate asset ledger report (Report Type 2)
     */
    public function reportAssetLedger(Request $request): View
    {
        [$assets, $fiscalYear, $orgId, $subOrgId] = $this->assetLedgerData($request);

        return view('asset.ASS-009-print-asset-report.report-ledger', [
            'pageTitle'     => 'รายงานทะเบียนครุภัณฑ์',
            'assets'        => $assets,
            'fiscalYear'    => $fiscalYear,
            'reportOrgLine' => $this->ledgerOrgLine($orgId, $subOrgId),
            'pdfUrl'        => route('asset.reports.ledger.download', $request->only(['fiscal_year', 'org_id', 'sub_org_id'])),
        ]);
    }

    /** Download the fiscal-year ledger report as an A4 landscape PDF. */
    public function downloadAssetLedger(Request $request)
    {
        [$assets, $fiscalYear, $orgId, $subOrgId] = $this->assetLedgerData($request);

        $pdf = Pdf::loadView('asset.ASS-009-print-asset-report.report-ledger-pdf', [
            'assets'        => $assets,
            'fiscalYear'    => $fiscalYear,
            'reportOrgLine' => $this->ledgerOrgLine($orgId, $subOrgId),
        ])->setPaper('a4', 'landscape');

        // PDF_TEST_MODE: same PDF bytes, served inline for popup preview instead of downloading.
        return config('app.pdf_test_mode')
            ? $pdf->stream('asset-ledger-report.pdf')
            : $pdf->download('asset-ledger-report.pdf');
    }

    /**
     * Shared dataset for the ledger preview and its PDF, so both always match.
     *
     * @return array{0: \Illuminate\Support\Collection, 1: int, 2: int, 3: int}
     */
    private function assetLedgerData(Request $request): array
    {
        $fiscalYear = (int) $request->input('fiscal_year', 0);
        $orgId = (int) $request->input('org_id', 0);
        $subOrgId = (int) $request->input('sub_org_id', 0);
        $visibleOrgIds = $this->orgVisibility->visibleOrgIds((int) Auth::user()->org_id);

        // Thai fiscal year: 1 Oct of the given BE year through 30 Sep of the next.
        $ceYear = $fiscalYear - 543;
        $startDate = "{$ceYear}-10-01";
        $endDate = ($ceYear + 1) . '-09-30';

        $statusCases = collect(AssetController::ASSET_STATUS)
            ->map(fn (array $meta, string $code) => "WHEN '{$code}' THEN '{$meta['label']}'")
            ->implode(' ');

        $query = DB::connection('oracle')
            ->table('ASSET')
            ->join('ASSET_CATEGORY', 'ASSET.asscat_id', '=', 'ASSET_CATEGORY.id')
            ->leftJoin('GLB_ORGANIZATION AS ORG', 'ASSET.org_id', '=', 'ORG.org_id')
            ->leftJoin('GLB_ORGANIZATION AS SUB_ORG', 'ASSET.sub_org_id', '=', 'SUB_ORG.org_id')
            ->selectRaw("
                ASSET.id,
                ASSET.ass_code,
                ASSET.ass_desc,
                ASSET.ass_model,
                ASSET.ass_serail,
                ASSET.ass_price,
                ASSET.remarks,
                ASSET.ass_status,
                ASSET.inspect_date,
                TO_CHAR(ASSET.inspect_date, 'DD/MM/') || TO_CHAR(ASSET.inspect_date + INTERVAL '543' YEAR(3), 'YYYY') AS inspect_date_th,
                ASSET_CATEGORY.asscat_code,
                ASSET_CATEGORY.asscat_group,
                ASSET_CATEGORY.asscat_name,
                ASSET_CATEGORY.asscat_unit,
                ORG.org_name,
                SUB_ORG.org_name AS sub_org_name,
                CASE ASSET.ass_status {$statusCases} ELSE 'อื่น ๆ' END AS status_label
            ")
            ->whereIn('ASSET.org_id', $visibleOrgIds)
            ->whereRaw('ASSET.inspect_date >= ? AND ASSET.inspect_date <= ?', [$startDate, $endDate])
            ->whereNotIn('ASSET.ass_status', self::LEDGER_EXCLUDED_STATUSES);

        // 0 means "no filter". Selecting a parent unit must also cover the units beneath it,
        // since assets are attached to the leaf org, not the parent.
        if ($orgId > 0) {
            $query->whereIn('ASSET.org_id', $this->orgBranchIds($orgId));
        }

        if ($subOrgId > 0) {
            $query->whereIn('ASSET.sub_org_id', $this->orgBranchIds($subOrgId));
        }

        $assets = $query
            ->orderBy('ASSET.inspect_date')
            ->orderBy('ASSET.ass_code')
            ->get();

        return [$assets, $fiscalYear, $orgId, $subOrgId];
    }

    /**
     * The org itself plus every organization below it in the GLB_ORGANIZATION tree.
     *
     * @return list<int>
     */
    private function orgBranchIds(int $orgId): array
    {
        $rows = DB::connection('oracle')->select(
            'SELECT org_id FROM GLB_ORGANIZATION START WITH org_id = ? CONNECT BY PRIOR org_id = org_org_id',
            [$orgId]
        );

        return array_map(static fn ($r) => (int) $r->org_id, $rows) ?: [$orgId];
    }

    /** Build the "[หน่วยงานย่อย] [หน่วยงาน] [ส่วนราชการ]" line shown under the ledger report title. */
    private function ledgerOrgLine(int $orgId, int $subOrgId): string
    {
        $ids = array_values(array_filter([$subOrgId, $orgId]));

        if (empty($ids)) {
            return '';
        }

        $orgs = DB::connection('oracle')
            ->table('GLB_ORGANIZATION')
            ->whereIn('org_id', $ids)
            ->pluck('org_name', 'org_id');

        $zoneFlg = DB::connection('oracle')
            ->table('GLB_ORGANIZATION')
            ->where('org_id', $orgId ?: $subOrgId)
            ->value('zone_flg');

        $parts = collect($ids)
            ->map(fn (int $id) => $orgs[$id] ?? null)
            ->filter();

        if (strtoupper(trim((string) $zoneFlg)) === 'C') {
            $parts->push('กรมส่งเสริมสหกรณ์');
        }

        return $parts->unique()->implode(' ');
    }

    /**
     * Ensure the requested asset really belongs to the requested category and is visible to the user.
     * Returns an error message, or null when the pair is valid.
     *
     * @param  array<int, int>  $visibleOrgIds
     */
    private function validateAssetBelongsToCategory(int $assetId, int $categoryId, array $visibleOrgIds): ?string
    {
        if ($categoryId <= 0) {
            return 'กรุณาเลือกประเภทครุภัณฑ์ก่อนเลือกรหัสครุภัณฑ์';
        }

        if (empty($visibleOrgIds)) {
            return 'ไม่พบครุภัณฑ์ที่เลือกในหน่วยงานที่ท่านมีสิทธิ์เข้าถึง';
        }

        $asset = DB::connection('oracle')
            ->table('ASSET')
            ->select('asscat_id')
            ->where('id', $assetId)
            ->whereIn('org_id', $visibleOrgIds)
            ->first();

        if ($asset === null) {
            return 'ไม่พบครุภัณฑ์ที่เลือกในหน่วยงานที่ท่านมีสิทธิ์เข้าถึง';
        }

        if ((int) $asset->asscat_id !== $categoryId) {
            return 'ไม่พบครุภัณฑ์ที่ตรงกับประเภทครุภัณฑ์ที่เลือก';
        }

        return null;
    }

    /**
     * Helper to escape LIKE wildcards
     */
    private function escapeLike(string $value): string
    {
        return str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $value);
    }
}

