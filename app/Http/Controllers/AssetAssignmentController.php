<?php

namespace App\Http\Controllers;

use App\Models\AssetAssignment;
use App\Models\AssetAssignmentList;
use App\Models\GlbOrganization;
use App\Services\OrganizationVisibilityService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
use Throwable;

class AssetAssignmentController extends Controller
{
    private const PER_PAGE = 10;

    public function __construct(private readonly OrganizationVisibilityService $orgVisibility) {}

    // ──────────────────────────────────────────────────────────────────────
    //  LIST
    // ──────────────────────────────────────────────────────────────────────

    public function index(Request $request): View
    {
        $filterOrgId    = (int) $request->input('filter_org_id', 0);
        $filterSubOrgId = (int) $request->input('filter_sub_org_id', 0);
        $filterAssigner = trim($request->string('filter_assigner_id')->value());

        $visibleOrgIds = $this->orgVisibility->visibleOrgIds((int) Auth::user()->org_id);

        $query = DB::connection('oracle')
            ->table('ASSET_ASSIGNMENT AS aa')
            ->join('GLB_ORGANIZATION AS org', 'aa.org_id', '=', 'org.org_id')
            ->join('GLB_ORGANIZATION AS torg', 'aa.target_org_id', '=', 'torg.org_id')
            ->leftJoin('GLB_ORGANIZATION AS sorg', 'aa.target_sub_org_id', '=', 'sorg.org_id')
            ->join('SYS_USER AS u', 'aa.assigner_id', '=', 'u.id')
            ->selectRaw('
                aa.id,
                aa.org_id,
                aa.target_org_id,
                aa.target_sub_org_id,
                aa.assigner_id,
                aa.assign_date,
                aa.status,
                torg.org_name     AS target_org_name,
                sorg.org_name     AS target_sub_org_name,
                u.user_name       AS assigner_name,
                (SELECT COUNT(*) FROM ASSET_ASSIGNMENT_LIST aal WHERE aal.ass_assign_id = aa.id) AS item_count
            ')
            ->where('aa.status', AssetAssignment::STATUS_ACTIVE)
            ->orderByDesc('aa.assign_date')
            ->orderByDesc('aa.id');

        if (empty($visibleOrgIds)) {
            $query->whereRaw('1 = 0');
        } else {
            $query->whereIn('aa.org_id', $visibleOrgIds);
        }

        if ($filterOrgId > 0) {
            $query->where('aa.target_org_id', $filterOrgId);
        }
        if ($filterSubOrgId > 0) {
            $query->where('aa.target_sub_org_id', $filterSubOrgId);
        }
        if ($filterAssigner !== '') {
            $query->where('aa.assigner_id', (int) $filterAssigner);
        }

        $records = $query->paginate(self::PER_PAGE)->withQueryString();

        // Labels for current filters
        $filterOrgName = $filterOrgId > 0
            ? (GlbOrganization::query()
                ->whereIn('org_id', $visibleOrgIds)
                ->where('org_id', $filterOrgId)
                ->value('org_name') ?? '')
            : '';
        $filterSubOrgName = $filterSubOrgId > 0
            ? (GlbOrganization::query()
                ->whereIn('org_id', $visibleOrgIds)
                ->where('org_id', $filterSubOrgId)
                ->value('org_name') ?? '')
            : '';
        $filterAssignerName = '';
        if ($filterAssigner !== '') {
            $filterAssignerName = DB::connection('oracle')
                ->table('SYS_USER')
                ->whereIn('org_id', $visibleOrgIds)
                ->where('id', (int) $filterAssigner)
                ->value('user_name') ?? '';
        }

        return view('asset.ASS-004-assign-asset-to-department.index', [
            'pageTitle'           => 'จัดสรรครุภัณฑ์ให้หน่วยงาน',
            'records'             => $records,
            'filterOrgId'         => $filterOrgId,
            'filterOrgName'       => $filterOrgName,
            'filterSubOrgId'      => $filterSubOrgId,
            'filterSubOrgName'    => $filterSubOrgName,
            'filterAssignerId'    => $filterAssigner,
            'filterAssignerName'  => $filterAssignerName,
        ]);
    }

    // ──────────────────────────────────────────────────────────────────────
    //  CREATE FORM
    // ──────────────────────────────────────────────────────────────────────

    public function create(Request $request): View
    {
        $user       = Auth::user();
        $userOrgId  = (int) $user->org_id;
        $userOrg    = GlbOrganization::where('org_id', $userOrgId)->first(['org_id', 'org_name']);
        $visibleOrgIds = $this->orgVisibility->visibleOrgIds($userOrgId);

        // Available assets: status='1' and NOT already in an active assignment
        $assignedAssetIds = DB::connection('oracle')
            ->table('ASSET_ASSIGNMENT_LIST AS aal')
            ->join('ASSET_ASSIGNMENT AS aa', 'aal.ass_assign_id', '=', 'aa.id')
            ->where('aa.status', AssetAssignment::STATUS_ACTIVE)
            ->pluck('aal.asset_id')
            ->toArray();

        $assetQuery = DB::connection('oracle')
            ->table('ASSET AS a')
            ->join('ASSET_CATEGORY AS c', 'a.asscat_id', '=', 'c.id')
            ->selectRaw("
                a.id,
                a.ass_code    AS asset_code,
                c.asscat_name AS asset_name,
                a.ass_price   AS asset_value,
                c.asscat_name AS category_name,
                TO_CHAR(a.inspect_date, 'DD-MM-') || TO_CHAR(a.inspect_date + INTERVAL '543' YEAR(3), 'YYYY') AS inspect_date_th
            ")
            ->where('a.ass_status', '1')
            ->orderBy('a.ass_code');

        if (!empty($visibleOrgIds)) {
            $assetQuery->whereIn('a.org_id', $visibleOrgIds);
        } else {
            $assetQuery->whereRaw('1 = 0');
        }

        if (!empty($assignedAssetIds)) {
            $assetQuery->whereNotIn('a.id', $assignedAssetIds);
        }

        $availableAssets = $assetQuery->limit(200)->get();

        return view('asset.ASS-004-assign-asset-to-department.create', [
            'pageTitle'       => 'จัดสรรครุภัณฑ์ให้หน่วยงาน',
            'userOrg'         => $userOrg,
            'availableAssets' => $availableAssets,
            'currentUser'     => $user,
        ]);
    }

    // ──────────────────────────────────────────────────────────────────────
    //  STORE
    // ──────────────────────────────────────────────────────────────────────

    public function store(Request $request): RedirectResponse|JsonResponse
    {
        $request->validate([
            'target_org_id'     => ['required', 'integer', 'min:1'],
            'target_sub_org_id' => ['nullable', 'integer', 'min:1'],
            'assigner_id'       => ['required', 'integer', 'min:1'],
            'assign_date'       => ['required', 'date'],
            'asset_ids'         => ['required', 'array', 'min:1'],
            'asset_ids.*'       => ['integer', 'min:1'],
        ]);

        $user          = Auth::user();
        $userOrgId     = (int) $user->org_id;
        $visibleOrgIds = $this->orgVisibility->visibleOrgIds($userOrgId);
        $targetOrgId   = (int) $request->input('target_org_id');
        $subOrgId      = $request->filled('target_sub_org_id') ? (int) $request->input('target_sub_org_id') : null;
        $assignerId    = (int) $request->input('assigner_id');
        $assignDate    = $request->input('assign_date');
        $assetIds      = array_map('intval', (array) $request->input('asset_ids'));

        // Scope check
        if (!in_array($targetOrgId, $visibleOrgIds, true)) {
            return back()->withErrors(['_error' => 'หน่วยงานเป้าหมายไม่อยู่ใน Scope ที่อนุญาต'])->withInput();
        }

        // Validate assets are real, status=1, and in scope
        $validAssets = DB::connection('oracle')
            ->table('ASSET')
            ->whereIn('id', $assetIds)
            ->where('ass_status', '1')
            ->whereIn('org_id', $visibleOrgIds)
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->toArray();

        if (count($validAssets) !== count($assetIds)) {
            return back()->withErrors(['_error' => 'ครุภัณฑ์บางรายการไม่ถูกต้องหรือไม่อยู่ใน Scope'])->withInput();
        }

        // Ensure none are already assigned (race condition check)
        $alreadyAssigned = DB::connection('oracle')
            ->table('ASSET_ASSIGNMENT_LIST AS aal')
            ->join('ASSET_ASSIGNMENT AS aa', 'aal.ass_assign_id', '=', 'aa.id')
            ->where('aa.status', AssetAssignment::STATUS_ACTIVE)
            ->whereIn('aal.asset_id', $validAssets)
            ->exists();

        if ($alreadyAssigned) {
            return back()->withErrors(['_error' => 'ครุภัณฑ์บางรายการถูกจัดสรรไปแล้ว กรุณาตรวจสอบใหม่'])->withInput();
        }

        try {
            DB::connection('oracle')->transaction(function () use (
                $userOrgId, $targetOrgId, $subOrgId, $assignerId, $assignDate,
                $validAssets, $user
            ) {
                $userId = (int) $user->id;

                $assignId = DB::connection('oracle')->selectOne('SELECT ASSET_ASSIGNMENT_SEQ.NEXTVAL AS nv FROM DUAL')->nv;

                DB::connection('oracle')->table('ASSET_ASSIGNMENT')->insert([
                    'id'               => $assignId,
                    'org_id'           => $userOrgId,
                    'target_org_id'    => $targetOrgId,
                    'target_sub_org_id'=> $subOrgId,
                    'assigner_id'      => $assignerId,
                    'assign_date'      => $assignDate,
                    'status'           => AssetAssignment::STATUS_ACTIVE,
                    'created_by'       => $userId,
                    'updated_by'       => $userId,
                ]);

                foreach ($validAssets as $assetId) {
                    $listId = DB::connection('oracle')->selectOne('SELECT ASSET_ASSIGNMENT_LIST_SEQ.NEXTVAL AS nv FROM DUAL')->nv;
                    DB::connection('oracle')->table('ASSET_ASSIGNMENT_LIST')->insert([
                        'id'          => $listId,
                        'ass_assign_id' => $assignId,
                        'asset_id'    => $assetId,
                        'created_by'  => $userId,
                        'updated_by'  => $userId,
                    ]);
                }
            });
        } catch (Throwable $e) {
            Log::error('AssetAssignmentController::store error', ['error' => $e->getMessage()]);
            return back()->withErrors(['_error' => 'เกิดข้อผิดพลาดในการบันทึก กรุณาลองใหม่'])->withInput();
        }

        return redirect()->route('asset.assignments.index')
            ->with('assignment_success', 'บันทึกการจัดสรรครุภัณฑ์เรียบร้อยแล้ว');
    }

    // ──────────────────────────────────────────────────────────────────────
    //  SHOW (read-only detail)
    // ──────────────────────────────────────────────────────────────────────

    public function show(int $id): View|RedirectResponse
    {
        $visibleOrgIds = $this->orgVisibility->visibleOrgIds((int) Auth::user()->org_id);
        $assignment    = $this->findAuthorized($id, $visibleOrgIds);
        if ($assignment === null) {
            return redirect()->route('asset.assignments.index')
                ->withErrors(['_error' => 'ไม่พบข้อมูลหรือไม่มีสิทธิ์เข้าถึง']);
        }

        $items = DB::connection('oracle')
            ->table('ASSET_ASSIGNMENT_LIST AS aal')
            ->join('ASSET AS a', 'aal.asset_id', '=', 'a.id')
            ->join('ASSET_CATEGORY AS c', 'a.asscat_id', '=', 'c.id')
            ->selectRaw('
                a.id          AS asset_id,
                a.ass_code    AS asset_code,
                c.asscat_name AS asset_name,
                c.asscat_name AS category_name,
                a.ass_price   AS asset_value
            ')
            ->where('aal.ass_assign_id', $id)
            ->orderBy('a.ass_code')
            ->get();

        $targetOrg    = GlbOrganization::where('org_id', $assignment->target_org_id)->first();
        $targetSubOrg = $assignment->target_sub_org_id
            ? GlbOrganization::where('org_id', $assignment->target_sub_org_id)->first()
            : null;
        $assigner = DB::connection('oracle')->selectOne('SELECT user_name FROM SYS_USER WHERE id = ?', [$assignment->assigner_id]);
        $orgName  = GlbOrganization::where('org_id', $assignment->org_id)->value('org_name') ?? '-';

        return view('asset.ASS-004-assign-asset-to-department.show', [
            'pageTitle'    => 'จัดสรรครุภัณฑ์ให้หน่วยงาน',
            'assignment'   => $assignment,
            'assets'       => $items,
            'targetOrg'    => $targetOrg,
            'targetSubOrg' => $targetSubOrg,
            'assigner'     => $assigner,
            'orgName'      => $orgName,
        ]);
    }

    // ──────────────────────────────────────────────────────────────────────
    //  EDIT FORM
    // ──────────────────────────────────────────────────────────────────────

    public function edit(int $id): View|RedirectResponse
    {
        $user          = Auth::user();
        $userOrgId     = (int) $user->org_id;
        $visibleOrgIds = $this->orgVisibility->visibleOrgIds($userOrgId);

        $assignment = $this->findAuthorized($id, $visibleOrgIds);
        if ($assignment === null) {
            return redirect()->route('asset.assignments.index')
                ->withErrors(['_error' => 'ไม่พบข้อมูลหรือไม่มีสิทธิ์เข้าถึง']);
        }

        $userOrg = GlbOrganization::where('org_id', $userOrgId)->first(['org_id', 'org_name']);

        // Currently assigned asset IDs for this record
        $currentAssetIds = DB::connection('oracle')
            ->table('ASSET_ASSIGNMENT_LIST')
            ->where('ass_assign_id', $id)
            ->pluck('asset_id')
            ->map(fn ($x) => (int) $x)
            ->toArray();

        // Asset IDs assigned by OTHER active assignments
        $otherAssignedIds = DB::connection('oracle')
            ->table('ASSET_ASSIGNMENT_LIST AS aal')
            ->join('ASSET_ASSIGNMENT AS aa', 'aal.ass_assign_id', '=', 'aa.id')
            ->where('aa.status', AssetAssignment::STATUS_ACTIVE)
            ->where('aa.id', '!=', $id)
            ->pluck('aal.asset_id')
            ->map(fn ($x) => (int) $x)
            ->toArray();

        $assetQuery = DB::connection('oracle')
            ->table('ASSET AS a')
            ->join('ASSET_CATEGORY AS c', 'a.asscat_id', '=', 'c.id')
            ->selectRaw("
                a.id,
                a.ass_code    AS asset_code,
                c.asscat_name AS asset_name,
                a.ass_price   AS asset_value,
                c.asscat_name AS category_name,
                TO_CHAR(a.inspect_date, 'DD-MM-') || TO_CHAR(a.inspect_date + INTERVAL '543' YEAR(3), 'YYYY') AS inspect_date_th
            ")
            ->where('a.ass_status', '1')
            ->orderBy('a.ass_code');

        if (!empty($visibleOrgIds)) {
            $assetQuery->whereIn('a.org_id', $visibleOrgIds);
        } else {
            $assetQuery->whereRaw('1 = 0');
        }

        // Exclude other-assigned assets, but include currently-assigned assets
        if (!empty($otherAssignedIds)) {
            $assetQuery->whereNotIn('a.id', $otherAssignedIds);
        }

        $availableAssets = $assetQuery->limit(200)->get();

        $targetOrg    = GlbOrganization::where('org_id', $assignment->target_org_id)->first();
        $targetSubOrg = $assignment->target_sub_org_id
            ? GlbOrganization::where('org_id', $assignment->target_sub_org_id)->first()
            : null;
        $assigner = DB::connection('oracle')->selectOne('SELECT id, user_name FROM SYS_USER WHERE id = ?', [$assignment->assigner_id]);

        return view('asset.ASS-004-assign-asset-to-department.edit', [
            'pageTitle'        => 'จัดสรรครุภัณฑ์ให้หน่วยงาน',
            'assignment'       => $assignment,
            'userOrg'          => $userOrg,
            'availableAssets'  => $availableAssets,
            'currentAssetIds'  => $currentAssetIds,
            'targetOrg'        => $targetOrg,
            'targetSubOrg'     => $targetSubOrg,
            'assigner'         => $assigner,
        ]);
    }

    // ──────────────────────────────────────────────────────────────────────
    //  UPDATE
    // ──────────────────────────────────────────────────────────────────────

    public function update(Request $request, int $id): RedirectResponse
    {
        $request->validate([
            'target_org_id'     => ['required', 'integer', 'min:1'],
            'target_sub_org_id' => ['nullable', 'integer', 'min:1'],
            'assigner_id'       => ['required', 'integer', 'min:1'],
            'assign_date'       => ['required', 'date'],
            'asset_ids'         => ['required', 'array', 'min:1'],
            'asset_ids.*'       => ['integer', 'min:1'],
        ]);

        $user          = Auth::user();
        $userOrgId     = (int) $user->org_id;
        $visibleOrgIds = $this->orgVisibility->visibleOrgIds($userOrgId);

        $assignment = $this->findAuthorized($id, $visibleOrgIds);
        if ($assignment === null) {
            return redirect()->route('asset.assignments.index')
                ->withErrors(['_error' => 'ไม่พบข้อมูลหรือไม่มีสิทธิ์เข้าถึง']);
        }

        $targetOrgId = (int) $request->input('target_org_id');
        $subOrgId    = $request->filled('target_sub_org_id') ? (int) $request->input('target_sub_org_id') : null;
        $assignerId  = (int) $request->input('assigner_id');
        $assignDate  = $request->input('assign_date');
        $assetIds    = array_map('intval', (array) $request->input('asset_ids'));

        if (!in_array($targetOrgId, $visibleOrgIds, true)) {
            return back()->withErrors(['_error' => 'หน่วยงานเป้าหมายไม่อยู่ใน Scope ที่อนุญาต'])->withInput();
        }

        // Validate submitted assets
        $validAssets = DB::connection('oracle')
            ->table('ASSET')
            ->whereIn('id', $assetIds)
            ->where('ass_status', '1')
            ->whereIn('org_id', $visibleOrgIds)
            ->pluck('id')
            ->map(fn ($x) => (int) $x)
            ->toArray();

        if (count($validAssets) !== count($assetIds)) {
            return back()->withErrors(['_error' => 'ครุภัณฑ์บางรายการไม่ถูกต้อง'])->withInput();
        }

        // Race-condition check: newly added assets must not be assigned elsewhere
        $currentIds = DB::connection('oracle')
            ->table('ASSET_ASSIGNMENT_LIST')
            ->where('ass_assign_id', $id)
            ->pluck('asset_id')
            ->map(fn ($x) => (int) $x)
            ->toArray();
        $newIds = array_diff($validAssets, $currentIds);

        if (!empty($newIds)) {
            $conflict = DB::connection('oracle')
                ->table('ASSET_ASSIGNMENT_LIST AS aal')
                ->join('ASSET_ASSIGNMENT AS aa', 'aal.ass_assign_id', '=', 'aa.id')
                ->where('aa.status', AssetAssignment::STATUS_ACTIVE)
                ->where('aa.id', '!=', $id)
                ->whereIn('aal.asset_id', $newIds)
                ->exists();

            if ($conflict) {
                return back()->withErrors(['_error' => 'ครุภัณฑ์บางรายการถูกจัดสรรโดย Request อื่น'])->withInput();
            }
        }

        try {
            DB::connection('oracle')->transaction(function () use (
                $id, $targetOrgId, $subOrgId, $assignerId, $assignDate, $validAssets, $user
            ) {
                $userId = (int) $user->id;

                DB::connection('oracle')->table('ASSET_ASSIGNMENT')
                    ->where('id', $id)
                    ->update([
                        'target_org_id'    => $targetOrgId,
                        'target_sub_org_id'=> $subOrgId,
                        'assigner_id'      => $assignerId,
                        'assign_date'      => $assignDate,
                        'updated_by'       => $userId,
                        'updated_at'       => DB::raw('SYSTIMESTAMP'),
                    ]);

                DB::connection('oracle')->table('ASSET_ASSIGNMENT_LIST')
                    ->where('ass_assign_id', $id)
                    ->delete();

                foreach ($validAssets as $assetId) {
                    $listId = DB::connection('oracle')->selectOne('SELECT ASSET_ASSIGNMENT_LIST_SEQ.NEXTVAL AS nv FROM DUAL')->nv;
                    DB::connection('oracle')->table('ASSET_ASSIGNMENT_LIST')->insert([
                        'id'           => $listId,
                        'ass_assign_id'=> $id,
                        'asset_id'     => $assetId,
                        'created_by'   => $userId,
                        'updated_by'   => $userId,
                    ]);
                }
            });
        } catch (Throwable $e) {
            Log::error('AssetAssignmentController::update error', ['error' => $e->getMessage()]);
            return back()->withErrors(['_error' => 'เกิดข้อผิดพลาด กรุณาลองใหม่'])->withInput();
        }

        return redirect()->route('asset.assignments.index')
            ->with('assignment_success', 'แก้ไขการจัดสรรครุภัณฑ์เรียบร้อยแล้ว');
    }

    // ──────────────────────────────────────────────────────────────────────
    //  CANCEL (soft delete: set status='0')
    // ──────────────────────────────────────────────────────────────────────

    public function cancel(Request $request, int $id): JsonResponse
    {
        $visibleOrgIds = $this->orgVisibility->visibleOrgIds((int) Auth::user()->org_id);
        $assignment    = $this->findAuthorized($id, $visibleOrgIds);

        if ($assignment === null) {
            return response()->json(['ok' => false, 'message' => 'ไม่พบข้อมูลหรือไม่มีสิทธิ์'], 403);
        }

        try {
            DB::connection('oracle')->table('ASSET_ASSIGNMENT')
                ->where('id', $id)
                ->update([
                    'status'     => AssetAssignment::STATUS_CANCELLED,
                    'updated_by' => (int) Auth::user()->id,
                    'updated_at' => DB::raw('SYSTIMESTAMP'),
                ]);
        } catch (Throwable $e) {
            Log::error('AssetAssignmentController::cancel error', ['error' => $e->getMessage()]);
            return response()->json(['ok' => false, 'message' => 'เกิดข้อผิดพลาด'], 500);
        }

        return response()->json([
            'ok'          => true,
            'message'     => 'ยกเลิกการจัดสรรเรียบร้อยแล้ว',
            'redirect_url'=> route('asset.assignments.index'),
        ]);
    }

    // ──────────────────────────────────────────────────────────────────────
    //  AJAX: search available assets (Section 2 in create/edit)
    // ──────────────────────────────────────────────────────────────────────

    public function searchAssets(Request $request): JsonResponse
    {
        $searchBy  = $request->string('search_by', 'all')->value();
        $keyword   = trim($request->string('q')->value());
        $excludeId = (int) $request->input('exclude_assignment', 0); // for edit page

        $user          = Auth::user();
        $visibleOrgIds = $this->orgVisibility->visibleOrgIds((int) $user->org_id);

        if (empty($visibleOrgIds)) {
            return response()->json(['data' => []]);
        }

        // Asset IDs already assigned in OTHER active assignments
        $assignedQuery = DB::connection('oracle')
            ->table('ASSET_ASSIGNMENT_LIST AS aal')
            ->join('ASSET_ASSIGNMENT AS aa', 'aal.ass_assign_id', '=', 'aa.id')
            ->where('aa.status', AssetAssignment::STATUS_ACTIVE)
            ->select('aal.asset_id');

        if ($excludeId > 0) {
            $assignedQuery->where('aa.id', '!=', $excludeId);
        }

        $assignedIds = $assignedQuery->pluck('asset_id')->map(fn ($x) => (int) $x)->toArray();

        $query = DB::connection('oracle')
            ->table('ASSET AS a')
            ->join('ASSET_CATEGORY AS c', 'a.asscat_id', '=', 'c.id')
            ->selectRaw("
                a.id,
                a.ass_code    AS asset_code,
                c.asscat_name AS asset_name,
                c.asscat_name AS category_name,
                a.ass_price   AS asset_value,
                TO_CHAR(a.inspect_date, 'DD-MM-') || TO_CHAR(a.inspect_date + INTERVAL '543' YEAR(3), 'YYYY') AS inspect_date_th
            ")
            ->where('a.ass_status', '1')
            ->whereIn('a.org_id', $visibleOrgIds)
            ->orderBy('a.ass_code')
            ->limit(100);

        if (!empty($assignedIds)) {
            $query->whereNotIn('a.id', $assignedIds);
        }

        if ($keyword !== '') {
            $escaped = $this->escapeLike($keyword);
            // Accept both 'field' (our own fetch) and 'type' (SearchAutocomplete component)
            $field   = $request->string('field', $request->string('type', '')->value())->value();
            if ($field === 'code') {
                $query->whereRaw('UPPER(a.ass_code) LIKE UPPER(?)', ['%' . $escaped . '%']);
            } elseif ($field === 'name') {
                $query->whereRaw('UPPER(c.asscat_name) LIKE UPPER(?)', ['%' . $escaped . '%']);
            } elseif ($field === 'category') {
                $query->whereRaw('UPPER(c.asscat_name) LIKE UPPER(?)', ['%' . $escaped . '%']);
            } else {
                $query->where(function ($q) use ($escaped): void {
                    $q->whereRaw('UPPER(a.ass_code) LIKE UPPER(?)', ['%' . $escaped . '%'])
                      ->orWhereRaw('UPPER(c.asscat_name) LIKE UPPER(?)', ['%' . $escaped . '%']);
                });
            }
        }

        $data = $query->get()->map(fn ($r) => [
            'id'             => $r->id,
            'asset_code'     => $r->asset_code ?? '-',
            'asset_name'     => $r->asset_name ?? '-',
            'category_name'  => $r->category_name ?? '-',
            'asset_value'    => $r->asset_value !== null ? (float) $r->asset_value : null,
            'inspect_date_th'=> $r->inspect_date_th ?? '-',
        ]);

        return response()->json(['data' => $data]);
    }

    // ──────────────────────────────────────────────────────────────────────
    //  Helpers
    // ──────────────────────────────────────────────────────────────────────

    private function findAuthorized(int $id, array $visibleOrgIds): ?object
    {
        if (empty($visibleOrgIds)) {
            return null;
        }

        return DB::connection('oracle')
            ->table('ASSET_ASSIGNMENT')
            ->whereIn('org_id', $visibleOrgIds)
            ->where('status', AssetAssignment::STATUS_ACTIVE)
            ->where('id', $id)
            ->first();
    }

    private function escapeLike(string $value): string
    {
        return str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $value);
    }
}
