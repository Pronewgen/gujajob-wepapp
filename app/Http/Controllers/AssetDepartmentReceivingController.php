<?php

namespace App\Http\Controllers;

use App\Models\Asset;
use App\Models\AssetAssignment;
use App\Services\OrganizationVisibilityService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
use Throwable;

class AssetDepartmentReceivingController extends Controller
{
    private const PER_PAGE = 10;

    public function __construct(private readonly OrganizationVisibilityService $orgVisibility) {}

    // ──────────────────────────────────────────────────────────────────────
    //  LIST
    // ──────────────────────────────────────────────────────────────────────

    public function index(Request $request): View
    {
        $searchBy  = $request->input('search_by', 'all');
        $keyword   = trim($request->string('keyword')->value());
        $category  = trim($request->string('category')->value());
        $sort      = $request->input('sort', '');
        $direction = strtolower($request->input('direction', 'asc')) === 'desc' ? 'desc' : 'asc';

        $visibleOrgIds = $this->orgVisibility->visibleOrgIds((int) Auth::user()->org_id);

        // Sub-query: latest active assignment per asset
        $assignSub = DB::connection('oracle')
            ->table('ASSET_ASSIGNMENT_LIST AS aal')
            ->join('ASSET_ASSIGNMENT AS aa', 'aa.id', '=', 'aal.ass_assign_id')
            ->whereIn('aa.status', [AssetAssignment::STATUS_ACTIVE, AssetAssignment::STATUS_RECEIVED])
            ->selectRaw('aal.asset_id, MAX(aa.id) AS latest_assign_id')
            ->groupBy('aal.asset_id');

        $query = DB::connection('oracle')
            ->table('ASSET AS a')
            ->joinSub($assignSub, 'la', 'la.asset_id', '=', 'a.id')
            ->join('ASSET_ASSIGNMENT AS aa', 'aa.id', '=', 'la.latest_assign_id')
            ->join('ASSET_CATEGORY AS c', 'c.id', '=', 'a.asscat_id')
            ->join('GLB_ORGANIZATION AS torg', 'torg.org_id', '=', 'aa.target_org_id')
            ->selectRaw("
                a.id,
                a.ass_code,
                c.asscat_code,
                c.asscat_name,
                c.asscat_group,
                a.ass_price,
                torg.org_name AS target_org_name,
                aa.id AS assignment_id,
                aa.target_org_id,
                aa.status AS aa_status,
                a.ass_trans_date,
                CASE WHEN a.ass_trans_date IS NOT NULL THEN
                    TO_CHAR(a.ass_trans_date,'DD-MM-')||TO_CHAR(a.ass_trans_date+INTERVAL '543' YEAR(3),'YYYY')
                END AS ass_trans_date_th
            ");

        if (empty($visibleOrgIds)) {
            $query->whereRaw('1=0');
        } else {
            $query->whereIn('aa.target_org_id', $visibleOrgIds);
        }

        if ($keyword !== '') {
            $escaped = $this->escapeLike($keyword);
            if ($searchBy === 'code') {
                $query->whereRaw("UPPER(a.ass_code) LIKE UPPER(?) ESCAPE '\\'", ["%{$escaped}%"]);
            } elseif ($searchBy === 'name') {
                $query->whereRaw("UPPER(c.asscat_name) LIKE UPPER(?) ESCAPE '\\'", ["%{$escaped}%"]);
            } else {
                $query->where(function ($q) use ($escaped) {
                    $q->whereRaw("UPPER(a.ass_code) LIKE UPPER(?) ESCAPE '\\'", ["%{$escaped}%"])
                      ->orWhereRaw("UPPER(c.asscat_name) LIKE UPPER(?) ESCAPE '\\'", ["%{$escaped}%"]);
                });
            }
        }

        if ($category !== '') {
            $query->where('c.asscat_group', $category);
        }

        // Sorting
        $sortMap = [
            'code'     => 'a.ass_code',
            'name'     => 'c.asscat_name',
            'category' => 'c.asscat_group',
            'price'    => 'a.ass_price',
            'date'     => 'a.ass_trans_date',
        ];

        if (isset($sortMap[$sort])) {
            $query->orderByRaw("{$sortMap[$sort]} {$direction}");
        } else {
            // Default: sort by display code (ASSCAT_CODE||ASS_CODE when received, else ASS_CODE)
            $query->orderByRaw("CASE WHEN a.ass_trans_date IS NOT NULL THEN c.asscat_code || a.ass_code ELSE a.ass_code END ASC, a.id ASC");
        }

        $assets = $query->paginate(self::PER_PAGE)->withQueryString();

        // Category options
        $categoryOptions = $this->getCategoryOptions($visibleOrgIds);

        return view('asset.ASS-005-receive-department-registered-asset.index', [
            'pageTitle'       => 'รับครุภัณฑ์ลงทะเบียนหน่วยงาน',
            'assets'          => $assets,
            'searchBy'        => $searchBy,
            'keyword'         => $keyword,
            'category'        => $category,
            'sort'            => $sort,
            'direction'       => $direction,
            'categoryOptions' => $categoryOptions,
        ]);
    }

    // ──────────────────────────────────────────────────────────────────────
    //  RECEIVE FORM
    // ──────────────────────────────────────────────────────────────────────

    public function receive(int $id): View
    {
        $asset = $this->findAssetForCurrentUser($id);

        if (! $asset || $asset->aa_status !== AssetAssignment::STATUS_ACTIVE) {
            abort(404);
        }

        // Load sub-orgs under the assignment's target org
        $subOrgs = DB::connection('oracle')
            ->table('GLB_ORGANIZATION')
            ->where('org_org_id', $asset->target_org_id)
            ->orderBy('org_name')
            ->get(['org_id', 'org_name']);

        return view('asset.ASS-005-receive-department-registered-asset.receive', [
            'pageTitle'   => 'รับครุภัณฑ์ลงทะเบียนหน่วยงาน',
            'asset'       => $asset,
            'subOrgs'     => $subOrgs,
            'currentUser' => Auth::user(),
        ]);
    }

    // ──────────────────────────────────────────────────────────────────────
    //  STORE (POST)
    // ──────────────────────────────────────────────────────────────────────

    public function store(Request $request, int $id): RedirectResponse
    {
        $asset = $this->findAssetForCurrentUser($id);

        if (! $asset || $asset->aa_status !== AssetAssignment::STATUS_ACTIVE) {
            abort(404);
        }

        $validated = $request->validate([
            'receive_date' => 'required|date',
            'sub_org_id'   => 'required|integer',
            'remark'       => 'nullable|string|max:500',
        ]);

        $visibleOrgIds = $this->orgVisibility->visibleOrgIds((int) Auth::user()->org_id);
        $subOrgId = (int) $validated['sub_org_id'];

        // Validate sub_org_id is a child of the assignment's target org
        $isValidSubOrg = DB::connection('oracle')
            ->table('GLB_ORGANIZATION')
            ->where('org_id', $subOrgId)
            ->where('org_org_id', $asset->target_org_id)
            ->exists();

        if (! $isValidSubOrg) {
            abort(403, 'หน่วยงานย่อยที่เลือกไม่ถูกต้อง');
        }

        if (! in_array($subOrgId, $visibleOrgIds, true)) {
            abort(403, 'หน่วยงานย่อยที่เลือกไม่ถูกต้อง');
        }

        $user = Auth::user();

        try {
            DB::connection('oracle')->transaction(function () use ($id, $asset, $validated, $subOrgId, $user) {
                // remain_price=1 triggers พร้อมจำหน่าย (status 3), otherwise ปกติ (status 2)
                $currentRemain = DB::connection('oracle')->table('ASSET')->where('id', $id)->value('remain_price');
                $newStatus = ((float) $currentRemain === 1.0) ? '3' : '2';

                Asset::where('id', $id)->update([
                    'ass_trans_date'   => $validated['receive_date'],
                    'ass_trans_remark' => $validated['remark'] ?? null,
                    'sub_org_id'       => $subOrgId,
                    'ass_status'       => $newStatus,
                    'updated_by'       => $user->id,
                ]);
                DB::connection('oracle')->table('ASSET_ASSIGNMENT')
                    ->where('id', $asset->assignment_id)
                    ->update([
                        'status'     => AssetAssignment::STATUS_RECEIVED,
                        'updated_by' => $user->id,
                        'updated_at' => DB::raw('SYSTIMESTAMP'),
                    ]);
            });
        } catch (Throwable $e) {
            Log::error('ASS-005 store failed', ['id' => $id, 'error' => $e->getMessage()]);

            return redirect()->back()->withInput()
                ->with('error', 'เกิดข้อผิดพลาดในการบันทึกข้อมูล กรุณาลองใหม่อีกครั้ง');
        }

        return redirect()->route('asset.department-receiving.index')
            ->with('success', 'บันทึกการรับครุภัณฑ์เรียบร้อยแล้ว');
    }

    // ──────────────────────────────────────────────────────────────────────
    //  EDIT FORM
    // ──────────────────────────────────────────────────────────────────────

    public function edit(int $id): mixed
    {
        $asset = $this->findAssetForCurrentUser($id);

        if (! $asset) {
            abort(404);
        }

        // Not received yet — redirect to the receive form instead
        if ($asset->aa_status === AssetAssignment::STATUS_ACTIVE) {
            return redirect()->route('asset.department-receiving.receive', $id);
        }

        return view('asset.ASS-005-receive-department-registered-asset.edit', [
            'pageTitle' => 'แก้ไขการรับครุภัณฑ์ลงทะเบียนหน่วยงาน',
            'asset'     => $asset,
        ]);
    }

    // ──────────────────────────────────────────────────────────────────────
    //  UPDATE (PUT)
    // ──────────────────────────────────────────────────────────────────────

    public function update(Request $request, int $id): RedirectResponse
    {
        $asset = $this->findAssetForCurrentUser($id);

        if (! $asset || $asset->aa_status !== AssetAssignment::STATUS_RECEIVED) {
            abort(404);
        }

        $validated = $request->validate([
            'receive_date' => 'required|date',
            'sub_org_id'   => 'required|integer',
            'remark'       => 'nullable|string|max:500',
        ]);

        $visibleOrgIds = $this->orgVisibility->visibleOrgIds((int) Auth::user()->org_id);
        $subOrgId = (int) $validated['sub_org_id'];

        // Validate sub_org_id is a child of the assignment's target org
        $isValidSubOrg = DB::connection('oracle')
            ->table('GLB_ORGANIZATION')
            ->where('org_id', $subOrgId)
            ->where('org_org_id', $asset->target_org_id)
            ->exists();

        if (! $isValidSubOrg) {
            abort(403, 'หน่วยงานย่อยที่เลือกไม่ถูกต้อง');
        }

        if (! in_array($subOrgId, $visibleOrgIds, true)) {
            abort(403, 'หน่วยงานย่อยที่เลือกไม่ถูกต้อง');
        }

        $user = Auth::user();

        try {
            DB::connection('oracle')->transaction(function () use ($id, $asset, $validated, $subOrgId, $user) {
                Asset::where('id', $id)->update([
                    'ass_trans_date'   => $validated['receive_date'],
                    'ass_trans_remark' => $validated['remark'] ?? null,
                    'sub_org_id'       => $subOrgId,
                    'updated_by'       => $user->id,
                ]);
                DB::connection('oracle')->table('ASSET_ASSIGNMENT')
                    ->where('id', $asset->assignment_id)
                    ->update([
                        'updated_by' => $user->id,
                        'updated_at' => DB::raw('SYSTIMESTAMP'),
                    ]);
            });
        } catch (Throwable $e) {
            Log::error('ASS-005 update failed', ['id' => $id, 'error' => $e->getMessage()]);

            return redirect()->back()->withInput()
                ->with('error', 'เกิดข้อผิดพลาดในการบันทึกข้อมูล กรุณาลองใหม่อีกครั้ง');
        }

        return redirect()->route('asset.department-receiving.show', $id)
            ->with('success', 'บันทึกการแก้ไขข้อมูลการรับครุภัณฑ์เรียบร้อยแล้ว');
    }

    // ──────────────────────────────────────────────────────────────────────
    //  SHOW
    // ──────────────────────────────────────────────────────────────────────

    public function show(int $id): View
    {
        $asset = $this->findAssetForCurrentUser($id);

        if (! $asset) {
            abort(404);
        }

        return view('asset.ASS-005-receive-department-registered-asset.show', [
            'pageTitle' => 'รับครุภัณฑ์ลงทะเบียนหน่วยงาน',
            'asset'     => $asset,
        ]);
    }

    // ──────────────────────────────────────────────────────────────────────
    //  PRIVATE HELPERS
    // ──────────────────────────────────────────────────────────────────────

    private function findAssetForCurrentUser(int $id): mixed
    {
        $visibleOrgIds = $this->orgVisibility->visibleOrgIds((int) Auth::user()->org_id);

        if (empty($visibleOrgIds)) {
            return null;
        }

        $assignSub = DB::connection('oracle')
            ->table('ASSET_ASSIGNMENT_LIST AS aal')
            ->join('ASSET_ASSIGNMENT AS aa', 'aa.id', '=', 'aal.ass_assign_id')
            ->whereIn('aa.status', [AssetAssignment::STATUS_ACTIVE, AssetAssignment::STATUS_RECEIVED])
            ->selectRaw('aal.asset_id, MAX(aa.id) AS latest_assign_id')
            ->groupBy('aal.asset_id');

        return DB::connection('oracle')
            ->table('ASSET AS a')
            ->joinSub($assignSub, 'la', 'la.asset_id', '=', 'a.id')
            ->join('ASSET_ASSIGNMENT AS aa', 'aa.id', '=', 'la.latest_assign_id')
            ->join('ASSET_CATEGORY AS c', 'c.id', '=', 'a.asscat_id')
            ->join('GLB_ORGANIZATION AS torg', 'torg.org_id', '=', 'aa.target_org_id')
            ->leftJoin('GLB_ORGANIZATION AS sorg', 'a.sub_org_id', '=', 'sorg.org_id')
            ->selectRaw("
                a.id,
                a.ass_code,
                a.ass_model,
                a.ass_serail,
                a.ass_price,
                a.sub_org_id,
                a.inspect_date,
                TO_CHAR(a.inspect_date, 'YYYY-MM-DD') AS inspect_date_iso,
                CASE WHEN a.inspect_date IS NOT NULL THEN
                    TO_CHAR(a.inspect_date,'DD-MM-')||TO_CHAR(a.inspect_date+INTERVAL '543' YEAR(3),'YYYY')
                END AS inspect_date_th,
                c.asscat_name,
                c.asscat_group,
                torg.org_name AS target_org_name,
                torg.org_id AS target_org_id,
                sorg.org_name AS sub_org_name,
                aa.id AS assignment_id,
                aa.status AS aa_status,
                aa.assign_date,
                a.ass_trans_date,
                a.ass_trans_person,
                a.ass_trans_remark,
                TO_CHAR(a.ass_trans_date, 'YYYY-MM-DD') AS ass_trans_date_iso,
                CASE WHEN a.ass_trans_date IS NOT NULL THEN
                    TO_CHAR(a.ass_trans_date,'DD-MM-')||TO_CHAR(a.ass_trans_date+INTERVAL '543' YEAR(3),'YYYY')
                END AS ass_trans_date_th
            ")
            ->where('a.id', $id)
            ->whereIn('aa.target_org_id', $visibleOrgIds)
            ->first();
    }

    private function getCategoryOptions(array $visibleOrgIds): array
    {
        if (empty($visibleOrgIds)) {
            return [];
        }

        $assignSub = DB::connection('oracle')
            ->table('ASSET_ASSIGNMENT_LIST AS aal')
            ->join('ASSET_ASSIGNMENT AS aa', 'aa.id', '=', 'aal.ass_assign_id')
            ->whereIn('aa.status', [AssetAssignment::STATUS_ACTIVE, AssetAssignment::STATUS_RECEIVED])
            ->selectRaw('aal.asset_id, MAX(aa.id) AS latest_assign_id')
            ->groupBy('aal.asset_id');

        return DB::connection('oracle')
            ->table('ASSET AS a')
            ->joinSub($assignSub, 'la', 'la.asset_id', '=', 'a.id')
            ->join('ASSET_ASSIGNMENT AS aa', 'aa.id', '=', 'la.latest_assign_id')
            ->join('ASSET_CATEGORY AS c', 'c.id', '=', 'a.asscat_id')
            ->whereIn('aa.target_org_id', $visibleOrgIds)
            ->whereNotNull('c.asscat_group')
            ->where('c.asscat_group', '!=', '')
            ->selectRaw('DISTINCT c.asscat_group')
            ->orderBy('c.asscat_group')
            ->pluck('asscat_group')
            ->toArray();
    }

    private function escapeLike(string $value): string
    {
        return str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $value);
    }
}
