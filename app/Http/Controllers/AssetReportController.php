<?php

namespace App\Http\Controllers;

use App\Services\OrganizationVisibilityService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class AssetReportController extends Controller
{
    private const PER_PAGE = 10;

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
        if ($keyword === '' || empty($visibleOrgIds) || $categoryId <= 0) {
            return response()->json(['data' => []]);
        }

        $escaped = $this->escapeLike($keyword);

        $assets = DB::connection('oracle')
            ->table('ASSET')
            ->join('ASSET_CATEGORY', 'ASSET.asscat_id', '=', 'ASSET_CATEGORY.id')
            ->selectRaw("ASSET.id, ASSET.ass_code, ASSET_CATEGORY.asscat_name, ASSET_CATEGORY.asscat_code")
            ->whereIn('ASSET.org_id', $visibleOrgIds)
            ->where('ASSET.ass_status', '1')
            ->where('ASSET.asscat_id', $categoryId)
            ->whereRaw("UPPER(ASSET.ass_code) LIKE UPPER(?)", ["%{$escaped}%"])
            ->orderBy('ASSET.ass_code')
            ->limit(20)
            ->get();

        $data = $assets->map(fn ($a) => [
            'id'    => $a->id,
            'code'  => $a->ass_code,
            'name'  => $a->asscat_name,
            'label' => "{$a->ass_code} — {$a->asscat_name}",
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
            $query->where('parent_org_id', $parentOrgId);
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

        $query = DB::connection('oracle')
            ->table('ASSET')
            ->join('ASSET_CATEGORY', 'ASSET.asscat_id', '=', 'ASSET_CATEGORY.id')
            ->leftJoin('GLB_ORGANIZATION', 'ASSET.org_id', '=', 'GLB_ORGANIZATION.org_id')
            ->selectRaw("
                ASSET.id,
                ASSET.ass_code,
                ASSET_CATEGORY.asscat_code,
                ASSET_CATEGORY.asscat_name,
                ASSET.ass_desc,
                ASSET.inspect_date,
                TO_CHAR(ASSET.inspect_date, 'DD-MM-') || TO_CHAR(ASSET.inspect_date + INTERVAL '543' YEAR(3), 'YYYY') AS inspect_date_th,
                ASSET.ass_price,
                ASSET.remain_price,
                ASSET.ass_status,
                GLB_ORGANIZATION.org_name,
                CASE
                    WHEN ASSET.ass_status = '1' THEN 'ใช้งานได้'
                    WHEN ASSET.ass_status = '2' THEN 'ชำรุด'
                    WHEN ASSET.ass_status = '3' THEN 'สูญหาย'
                    WHEN ASSET.ass_status = '4' THEN 'หมดอายุ'
                    WHEN ASSET.ass_status = '5' THEN 'ชำรุด'
                    WHEN ASSET.ass_status = '6' THEN 'สูญหาย'
                    ELSE 'อื่น ๆ'
                END AS status_label
            ")
            ->whereIn('ASSET.org_id', $visibleOrgIds)
            ->where('ASSET.ass_status', '1');

        if ($categoryId > 0) {
            $query->where('ASSET.asscat_id', $categoryId);
        }

        if ($assetId > 0) {
            $query->where('ASSET.id', $assetId);
        }

        $assets = $query->orderBy('ASSET.ass_code')->paginate(self::PER_PAGE);

        return view('asset.ASS-009-print-asset-report.report-register', [
            'pageTitle' => 'รายงานทะเบียนคุมทรัพย์สิน',
            'assets'    => $assets,
        ]);
    }

    /**
     * Generate asset ledger report (Report Type 2)
     */
    public function reportAssetLedger(Request $request): View
    {
        $fiscalYear = (int) $request->input('fiscal_year', 0);
        $orgId = (int) $request->input('org_id', 0);
        $subOrgId = (int) $request->input('sub_org_id', 0);
        $visibleOrgIds = $this->orgVisibility->visibleOrgIds((int) Auth::user()->org_id);

        // Calculate fiscal year range (Oct X - Sep X+1)
        $ceYear = $fiscalYear - 543;
        $startDate = "{$ceYear}-10-01";
        $endDate = ($ceYear + 1) . "-09-30";

        $query = DB::connection('oracle')
            ->table('ASSET')
            ->join('ASSET_CATEGORY', 'ASSET.asscat_id', '=', 'ASSET_CATEGORY.id')
            ->leftJoin('GLB_ORGANIZATION', 'ASSET.org_id', '=', 'GLB_ORGANIZATION.org_id')
            ->selectRaw("
                ASSET.id,
                ASSET.ass_code,
                ASSET_CATEGORY.asscat_code,
                ASSET_CATEGORY.asscat_name,
                ASSET.ass_desc,
                ASSET.inspect_date,
                TO_CHAR(ASSET.inspect_date, 'DD-MM-') || TO_CHAR(ASSET.inspect_date + INTERVAL '543' YEAR(3), 'YYYY') AS inspect_date_th,
                ASSET.ass_price,
                ASSET.remain_price,
                ASSET.ass_status,
                GLB_ORGANIZATION.org_name,
                CASE
                    WHEN ASSET.ass_status = '1' THEN 'ใช้งานได้'
                    WHEN ASSET.ass_status = '2' THEN 'ชำรุด'
                    WHEN ASSET.ass_status = '3' THEN 'สูญหาย'
                    WHEN ASSET.ass_status = '4' THEN 'หมดอายุ'
                    WHEN ASSET.ass_status = '5' THEN 'ชำรุด'
                    WHEN ASSET.ass_status = '6' THEN 'สูญหาย'
                    ELSE 'อื่น ๆ'
                END AS status_label
            ")
            ->whereIn('ASSET.org_id', $visibleOrgIds)
            ->whereRaw('ASSET.inspect_date >= ? AND ASSET.inspect_date <= ?', [$startDate, $endDate])
            ->whereRaw("ASSET.ass_status IN ('1', '2', '3', '4')");

        if ($orgId > 0) {
            $query->where('ASSET.org_id', $orgId);
        }

        if ($subOrgId > 0) {
            $query->where('ASSET.org_id', $subOrgId);
        }

        $assets = $query->orderBy('ASSET.ass_code')->paginate(self::PER_PAGE);

        return view('asset.ASS-009-print-asset-report.report-ledger', [
            'pageTitle'   => 'รายงานทะเบียนครุภัณฑ์',
            'assets'      => $assets,
            'fiscalYear'  => $ceYear,
            'orgId'       => $orgId,
            'subOrgId'    => $subOrgId,
        ]);
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
            return 'รหัสครุภัณฑ์ที่เลือกไม่อยู่ในประเภทครุภัณฑ์ที่เลือก';
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

