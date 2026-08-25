<?php

namespace App\Http\Controllers;

use App\Models\Asset;
use App\Models\AssetCategory;
use App\Models\AssetImage;
use App\Models\Dealer;
use App\Models\GlbOrganization;
use App\Services\OrganizationVisibilityService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Throwable;

class AssetController extends Controller
{
    private const PER_PAGE = 10;
    private const SEARCH_COLUMN_MAP = [
        'all'  => null,
        'code' => 'a.ass_code',
        'name' => 'c.asscat_name',
        'org'  => 'org.org_name',
    ];

    private const STATUS_MAP = ['1' => '1', '3' => '3'];

    public function __construct(private readonly OrganizationVisibilityService $orgVisibility) {}

    public function index(Request $request): View
    {
        $searchBy     = $request->string('search_by', 'all')->value();
        $keyword      = trim($request->string('keyword')->value());
        $statusFilter = $request->string('status_filter', '')->value();
        $sort         = $request->string('sort', '')->value();
        $direction    = strtolower($request->string('direction', 'asc')->value()) === 'desc' ? 'desc' : 'asc';

        $visibleOrgIds = $this->orgVisibility->visibleOrgIds((int) Auth::user()->org_id);

        $allowedSorts = [
            'code'   => 'a.ass_code',
            'name'   => 'c.asscat_name',
            'status' => 'a.ass_status',
            'price'  => 'a.ass_price',
        ];

        $query = DB::connection('oracle')->table('ASSET AS a')
            ->join('ASSET_CATEGORY AS c', 'a.asscat_id', '=', 'c.id')
            ->leftJoin('GLB_ORGANIZATION AS org', 'a.org_id', '=', 'org.org_id')
            ->select([
                'a.id',
                'a.ass_code',
                'a.ass_desc',
                'a.ass_price',
                'a.remain_price',
                'a.ass_status',
                'a.inspect_date',
                DB::raw("TO_CHAR(a.inspect_date, 'DD-MM-') || TO_CHAR(a.inspect_date + INTERVAL '543' YEAR(3), 'YYYY') AS inspect_date_th"),
                'c.asscat_code',
                'c.asscat_name',
                'c.asscat_type',
                'org.org_name',
                DB::raw("(SELECT MAX(aa2.status) KEEP (DENSE_RANK LAST ORDER BY aa2.id)
                          FROM ASSET_ASSIGNMENT_LIST aal2
                          JOIN ASSET_ASSIGNMENT aa2 ON aa2.id = aal2.ass_assign_id
                          WHERE aal2.asset_id = a.id) AS aa_status"),
            ]);

        if (empty($visibleOrgIds)) {
            $query->whereRaw('1 = 0');
        } else {
            $query->whereIn('a.org_id', $visibleOrgIds);
        }

        if ($statusFilter !== '' && array_key_exists($statusFilter, self::STATUS_MAP)) {
            $query->where('a.ass_status', self::STATUS_MAP[$statusFilter]);
        }

        if ($keyword !== '') {
            $escaped = $this->escapeLike($keyword);
            if ($searchBy === 'all') {
                $query->where(function ($q) use ($escaped): void {
                    $q->whereRaw('UPPER(a.ass_code)    LIKE UPPER(?)', ['%' . $escaped . '%'])
                      ->orWhereRaw('UPPER(c.asscat_name) LIKE UPPER(?)', ['%' . $escaped . '%'])
                      ->orWhereRaw('UPPER(org.org_name)  LIKE UPPER(?)', ['%' . $escaped . '%']);
                });
            } elseif (array_key_exists($searchBy, self::SEARCH_COLUMN_MAP) && self::SEARCH_COLUMN_MAP[$searchBy] !== null) {
                $col = self::SEARCH_COLUMN_MAP[$searchBy];
                $query->whereRaw("UPPER({$col}) LIKE UPPER(?)", ['%' . $escaped . '%']);
            }
        }

        $sortExpr = array_key_exists($sort, $allowedSorts) ? $allowedSorts[$sort] : 'a.ass_code';
        $query->orderByRaw("{$sortExpr} {$direction} NULLS LAST");

        $assets = $query->paginate(self::PER_PAGE)->withQueryString();

        $userOrgId           = (int) Auth::user()->org_id;
        $forecastOrgs        = $this->getChildOrgs($userOrgId);
        $forecastCategories  = AssetCategory::query()->orderBy('asscat_name')
            ->get(['id', 'asscat_code', 'asscat_name'])
            ->map(fn ($c) => [
                'value'      => $c->id,
                'label'      => $c->asscat_name,
                'searchText' => $c->asscat_code . ' ' . $c->asscat_name,
            ])->values()->all();

        return view('asset.ASS-003-manage-asset-registration.index', [
            'pageTitle'          => 'จัดการทะเบียนครุภัณฑ์',
            'assets'             => $assets,
            'searchBy'           => $searchBy,
            'keyword'            => $keyword,
            'statusFilter'       => $statusFilter,
            'sort'               => $sort,
            'direction'          => $direction,
            'forecastCategories' => $forecastCategories,
            'forecastOrgs'       => $forecastOrgs,
        ]);
    }

    public function forecastData(Request $request): JsonResponse
    {
        $visibleOrgIds = $this->orgVisibility->visibleOrgIds((int) Auth::user()->org_id);

        if (empty($visibleOrgIds)) {
            return response()->json(['data' => [], 'summary' => ['total_count' => 0, 'total_budget' => 0.0]]);
        }

        $yearsAhead  = max(1, min(3, (int) $request->input('years_ahead', 1)));
        $filterCatId = $request->input('filter_cat_id');
        $filterOrgId = $request->input('filter_org_id');
        $filterQ     = trim((string) $request->input('q', ''));
        $currentYear = (int) date('Y');
        $endDateExpr = 'ADD_MONTHS(a.inspect_date, a.ass_lifetime * 12)';
        $endYearExpr = "EXTRACT(YEAR FROM {$endDateExpr})";

        $query = DB::connection('oracle')->table('ASSET AS a')
            ->join('ASSET_CATEGORY AS c', 'a.asscat_id', '=', 'c.id')
            ->leftJoin('GLB_ORGANIZATION AS org', 'a.org_id', '=', 'org.org_id')
            ->whereIn('a.org_id', $visibleOrgIds)
            ->whereNotNull('a.inspect_date')
            ->whereNotNull('a.ass_lifetime')
            ->where('a.ass_lifetime', '>', 0)
            ->whereRaw("{$endYearExpr} >= ?", [$currentYear])
            ->whereRaw("{$endYearExpr} <= ?", [$currentYear + $yearsAhead])
            ->select([
                'a.ass_code',
                'c.asscat_name',
                'org.org_name',
                'a.ass_lifetime',
                DB::raw("TO_CHAR(a.inspect_date, 'DD-MM-') || TO_CHAR(a.inspect_date + INTERVAL '543' YEAR(3), 'YYYY') AS inspect_date_th"),
                DB::raw("TO_CHAR({$endDateExpr}, 'DD-MM-') || TO_CHAR({$endDateExpr} + INTERVAL '543' YEAR(3), 'YYYY') AS end_date_th"),
                DB::raw("ROUND(MONTHS_BETWEEN({$endDateExpr}, SYSDATE) / 12, 1) AS years_remaining"),
                'a.ass_price',
            ])
            ->orderBy(DB::raw($endYearExpr))
            ->orderBy('a.ass_code');

        if ($filterCatId) {
            $query->where('a.asscat_id', (int) $filterCatId);
        }

        if ($filterOrgId && in_array((int) $filterOrgId, $visibleOrgIds, true)) {
            $query->where('a.org_id', (int) $filterOrgId);
        }

        if ($filterQ !== '') {
            $escaped = $this->escapeLike($filterQ);
            $query->where(function ($q) use ($escaped): void {
                $q->whereRaw('UPPER(a.ass_code)    LIKE UPPER(?)', ['%' . $escaped . '%'])
                  ->orWhereRaw('UPPER(c.asscat_name) LIKE UPPER(?)', ['%' . $escaped . '%'])
                  ->orWhereRaw('UPPER(org.org_name)  LIKE UPPER(?)', ['%' . $escaped . '%']);
            });
        }

        $rows        = $query->get();
        $totalCount  = $rows->count();
        $totalBudget = $rows->sum(fn ($r) => (float) $r->ass_price);

        $data = $rows->map(fn ($r) => [
            'code'            => $r->ass_code ?? '-',
            'name'            => $r->asscat_name ?? '-',
            'org'             => $r->org_name ?? '-',
            'lifetime'        => (int) $r->ass_lifetime,
            'inspect_date'    => $r->inspect_date_th ?? '-',
            'end_date'        => $r->end_date_th ?? '-',
            'years_remaining' => (float) $r->years_remaining,
            'price'           => (float) $r->ass_price,
        ])->values()->all();

        return response()->json([
            'data'    => $data,
            'summary' => ['total_count' => $totalCount, 'total_budget' => $totalBudget],
        ]);
    }

    public function create(): View
    {
        $user      = Auth::user();
        $userOrgId = (int) $user->org_id;

        // Resolve default org name — restores label after validation-error redirect
        $flashOld  = session('_old_input', []);
        $prevOrgId = isset($flashOld['org_id']) ? (int) $flashOld['org_id'] : null;
        if ($prevOrgId && $prevOrgId !== $userOrgId) {
            $defaultOrgName = GlbOrganization::where('org_id', $prevOrgId)->value('org_name')
                ?? GlbOrganization::where('org_id', $userOrgId)->value('org_name') ?? '';
        } else {
            $defaultOrgName = GlbOrganization::where('org_id', $userOrgId)->value('org_name') ?? '';
        }

        $categories = AssetCategory::query()->orderBy('asscat_code')->get(['id', 'asscat_code', 'asscat_name', 'asscat_type', 'asscat_group', 'asscat_unit', 'depreciation_rate']);

        return view('asset.ASS-003-manage-asset-registration.create', [
            'pageTitle'      => 'จัดการทะเบียนครุภัณฑ์',
            'categories'     => $categories,
            'defaultOrgId'   => $userOrgId,
            'defaultOrgName' => $defaultOrgName,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'ass_code'         => ['required', 'string', 'max:50'],
            'asscat_id'        => ['required', 'integer', 'exists:oracle.ASSET_CATEGORY,id'],
            'ass_desc'         => ['nullable', 'string', 'max:500'],
            'ass_model'        => ['nullable', 'string', 'max:100'],
            'ass_serail'       => ['nullable', 'string', 'max:30'],
            'ass_price'        => ['nullable', 'numeric', 'min:0'],
            'org_id'           => ['nullable', 'integer', 'min:1'],
            'ass_contact_no'   => ['nullable', 'string', 'max:20'],
            'ass_contact_date' => ['nullable', 'date'],
            'dealer_id'        => ['nullable', 'integer'],
            'inspect_date'     => ['nullable', 'date'],
            'warranty'         => ['nullable', 'integer', 'min:0'],
            'ass_lifetime'     => ['nullable', 'integer', 'min:0'],
            'remarks'          => ['nullable', 'string', 'max:500'],
            'ass_status'       => ['nullable', 'string', 'max:1'],
            'asset_images'     => ['nullable', 'array', 'max:3'],
            'asset_images.*'   => ['nullable', 'image', 'mimes:jpeg,jpg,png,gif', 'max:1024'],
        ], [
            'ass_code.required'  => 'กรุณากรอกรหัสทะเบียนครุภัณฑ์',
            'ass_code.max'       => 'รหัสทะเบียนครุภัณฑ์ต้องไม่เกิน 50 ตัวอักษร',
            'asscat_id.required' => 'กรุณาเลือกประเภทครุภัณฑ์',
            'asscat_id.exists'   => 'ประเภทครุภัณฑ์ที่เลือกไม่ถูกต้อง',
            'asset_images.*.max' => 'ไฟล์รูปภาพต้องมีขนาดไม่เกิน 1 MB ต่อไฟล์',
        ]);

        // Validate org_id is within the user's visible scope
        if (!empty($validated['org_id'])) {
            $visibleOrgIds = $this->orgVisibility->visibleOrgIds((int) Auth::user()->org_id);
            if (!in_array((int) $validated['org_id'], $visibleOrgIds, true)) {
                return back()->withInput()
                    ->withErrors(['org_id' => 'หน่วยงานที่เลือกไม่อยู่ใน Scope ที่อนุญาต']);
            }
        }

        $newCode = trim($validated['ass_code']);
        $duplicate = DB::connection('oracle')->table('ASSET')
            ->whereRaw('UPPER(ass_code) = UPPER(?)', [$newCode])
            ->exists();
        if ($duplicate) {
            return back()->withInput()
                ->withErrors(['ass_code' => 'รหัสทะเบียนครุภัณฑ์นี้มีอยู่แล้ว กรุณาใช้รหัสอื่น']);
        }

        try {
            DB::connection('oracle')->transaction(function () use ($validated, $request, $newCode): void {
                $nextId = (int) DB::connection('oracle')
                    ->selectOne('SELECT ASSET_SEQ.NEXTVAL AS next_id FROM DUAL')
                    ->next_id;
                $userId = (int) Auth::id();

                Asset::create([
                    'id'               => $nextId,
                    'asscat_id'        => (int) $validated['asscat_id'],
                    'ass_code'         => $newCode,
                    'ass_desc'         => $validated['ass_desc'] ?? null,
                    'ass_model'        => $validated['ass_model'] ?? null,
                    'ass_serail'       => $validated['ass_serail'] ?? null,
                    'ass_price'        => isset($validated['ass_price']) ? (float) $validated['ass_price'] : null,
                    'org_id'           => isset($validated['org_id']) ? (int) $validated['org_id'] : null,
                    'ass_contact_no'   => $validated['ass_contact_no'] ?? null,
                    'ass_contact_date' => $validated['ass_contact_date'] ?? null,
                    'dealer_id'        => isset($validated['dealer_id']) ? (int) $validated['dealer_id'] : null,
                    'inspect_date'     => $validated['inspect_date'] ?? null,
                    'warranty'         => isset($validated['warranty']) ? (int) $validated['warranty'] : null,
                    'ass_lifetime'     => isset($validated['ass_lifetime']) ? (int) $validated['ass_lifetime'] : null,
                    // remain_price starts equal to ass_price; no Depreciation Service formula found (PART M BLOCKER)
                    'remain_price'     => isset($validated['ass_price']) ? (float) $validated['ass_price'] : null,
                    'remarks'          => $validated['remarks'] ?? null,
                    'ass_status'       => $validated['ass_status'] ?? '1',
                    'created_by'       => $userId,
                    'updated_by'       => $userId,
                ]);

                if ($request->hasFile('asset_images')) {
                    $dir = 'asset-images/' . $nextId;
                    foreach ($request->file('asset_images') as $img) {
                        if ($img && $img->isValid()) {
                            $path = $img->store($dir, 'public');
                            if ($path) {
                                $imgId = (int) DB::connection('oracle')
                                    ->selectOne('SELECT ASSET_IMAGE_SEQ.NEXTVAL AS nv FROM DUAL')->nv;
                                DB::connection('oracle')->table('ASSET_IMAGE')->insert([
                                    'id'         => $imgId,
                                    'ass_id'     => $nextId,
                                    'ass_image'  => $path,
                                    'created_by' => $userId,
                                    'created_at' => DB::raw('SYSTIMESTAMP'),
                                    'updated_by' => $userId,
                                    'updated_at' => DB::raw('SYSTIMESTAMP'),
                                ]);
                            }
                        }
                    }
                }
            });
        } catch (Throwable $e) {
            Log::error('ASS-003 store failed', ['error' => $e->getMessage()]);
            return back()->withInput()->withErrors(['general' => 'บันทึกข้อมูลไม่สำเร็จ กรุณาลองอีกครั้ง']);
        }

        return redirect()->route('asset.registrations.index')
            ->with('asset_success', 'บันทึกข้อมูลครุภัณฑ์เรียบร้อยแล้ว');
    }

    public function show(int $id): View
    {
        $visibleOrgIds = $this->orgVisibility->visibleOrgIds((int) Auth::user()->org_id);
        $asset = Asset::with(['category', 'organization', 'dealer'])
            ->whereIn('org_id', $visibleOrgIds)
            ->findOrFail($id);

        $images = DB::connection('oracle')->table('ASSET_IMAGE')
            ->where('ass_id', $id)->orderBy('id')
            ->get(['id', 'ass_image'])
            ->filter(fn ($img) => $img->ass_image && Storage::disk('public')->exists($img->ass_image))
            ->map(fn ($img) => ['id' => $img->id, 'url' => Storage::disk('public')->url($img->ass_image)])
            ->values();

        return view('asset.ASS-003-manage-asset-registration.show', [
            'pageTitle' => 'จัดการทะเบียนครุภัณฑ์',
            'asset'     => $asset,
            'images'    => $images,
        ]);
    }

    public function edit(int $id): View
    {
        $user          = Auth::user();
        $visibleOrgIds = $this->orgVisibility->visibleOrgIds((int) $user->org_id);
        $asset         = Asset::with(['category', 'dealer', 'organization'])
            ->whereIn('org_id', $visibleOrgIds)
            ->findOrFail($id);
        $categories = AssetCategory::query()->orderBy('asscat_code')
            ->get(['id', 'asscat_code', 'asscat_name', 'asscat_type', 'asscat_group', 'asscat_unit', 'depreciation_rate']);

        // Default org: existing value, or current user's org as fallback (PART F)
        $defaultOrgId   = (int) ($asset->org_id ?? (int) $user->org_id);
        $defaultOrgName = old('_org_label', '');
        if ($defaultOrgName === '') {
            $orgRow = DB::connection('oracle')
                ->selectOne('SELECT org_name FROM GLB_ORGANIZATION WHERE org_id = ?', [$defaultOrgId]);
            $defaultOrgName = $orgRow?->org_name ?? '';
        }

        $images = DB::connection('oracle')->table('ASSET_IMAGE')
            ->where('ass_id', $id)->orderBy('id')
            ->get(['id', 'ass_image'])
            ->filter(fn ($img) => $img->ass_image && Storage::disk('public')->exists($img->ass_image))
            ->map(fn ($img) => ['id' => $img->id, 'url' => Storage::disk('public')->url($img->ass_image)])
            ->values();

        return view('asset.ASS-003-manage-asset-registration.edit', [
            'pageTitle'      => 'จัดการทะเบียนครุภัณฑ์',
            'asset'          => $asset,
            'categories'     => $categories,
            'images'         => $images,
            'defaultOrgId'   => $defaultOrgId,
            'defaultOrgName' => $defaultOrgName,
        ]);
    }

    public function update(Request $request, int $id): RedirectResponse
    {
        $visibleOrgIds = $this->orgVisibility->visibleOrgIds((int) Auth::user()->org_id);
        $asset = Asset::query()->whereIn('org_id', $visibleOrgIds)->findOrFail($id);

        $validated = $request->validate([
            'ass_code'           => ['required', 'string', 'max:50'],
            'asscat_id'          => ['required', 'integer', 'exists:oracle.ASSET_CATEGORY,id'],
            'ass_desc'           => ['nullable', 'string', 'max:500'],
            'ass_model'          => ['nullable', 'string', 'max:100'],
            'ass_serail'         => ['nullable', 'string', 'max:30'],
            'ass_price'          => ['nullable', 'numeric', 'min:0'],
            'org_id'             => ['nullable', 'integer'],
            'ass_contact_no'     => ['nullable', 'string', 'max:20'],
            'ass_contact_date'   => ['nullable', 'date'],
            'dealer_id'          => ['nullable', 'integer'],
            'inspect_date'       => ['nullable', 'date'],
            'warranty'           => ['nullable', 'integer', 'min:0'],
            'ass_lifetime'       => ['nullable', 'integer', 'min:0'],
            'remarks'            => ['nullable', 'string', 'max:500'],
            'asset_images'       => ['nullable', 'array', 'max:3'],
            'asset_images.*'     => ['nullable', 'image', 'mimes:jpeg,jpg,png,gif', 'max:1024'],
            'remove_image_ids'   => ['nullable', 'array'],
            'remove_image_ids.*' => ['integer'],
        ], [
            'ass_code.required'  => 'กรุณากรอกรหัสทะเบียนครุภัณฑ์',
            'ass_code.max'       => 'รหัสทะเบียนครุภัณฑ์ต้องไม่เกิน 50 ตัวอักษร',
            'asscat_id.required' => 'กรุณาเลือกประเภทครุภัณฑ์',
            'asscat_id.exists'   => 'ประเภทครุภัณฑ์ที่เลือกไม่ถูกต้อง',
            'asset_images.*.max' => 'ไฟล์รูปภาพต้องมีขนาดไม่เกิน 1 MB ต่อไฟล์',
        ]);

        // Check duplicate ass_code (exclude self — PART H3)
        $newCode = trim($validated['ass_code']);
        $duplicate = DB::connection('oracle')->table('ASSET')
            ->whereRaw('UPPER(ass_code) = UPPER(?)', [$newCode])
            ->where('id', '!=', $id)
            ->exists();
        if ($duplicate) {
            return back()->withInput()
                ->withErrors(['ass_code' => 'รหัสทะเบียนครุภัณฑ์นี้มีอยู่แล้ว กรุณาใช้รหัสอื่น']);
        }

        try {
            DB::connection('oracle')->transaction(function () use ($asset, $id, $validated, $request, $newCode): void {
                $userId = (int) Auth::id();

                $asset->update([
                    'ass_code'         => $newCode,
                    'asscat_id'        => (int) $validated['asscat_id'],
                    'ass_desc'         => $validated['ass_desc'] ?? null,
                    'ass_model'        => $validated['ass_model'] ?? null,
                    'ass_serail'       => $validated['ass_serail'] ?? null,
                    'ass_price'        => isset($validated['ass_price']) ? (float) $validated['ass_price'] : null,
                    'org_id'           => isset($validated['org_id']) ? (int) $validated['org_id'] : null,
                    'ass_contact_no'   => $validated['ass_contact_no'] ?? null,
                    'ass_contact_date' => $validated['ass_contact_date'] ?? null,
                    'dealer_id'        => isset($validated['dealer_id']) ? (int) $validated['dealer_id'] : null,
                    'inspect_date'     => $validated['inspect_date'] ?? null,
                    'warranty'         => isset($validated['warranty']) ? (int) $validated['warranty'] : null,
                    'ass_lifetime'     => isset($validated['ass_lifetime']) ? (int) $validated['ass_lifetime'] : null,
                    // remain_price: system-managed, no Depreciation Service formula (PART M BLOCKER)
                    'remarks'          => $validated['remarks'] ?? null,
                    // ass_status: NOT updated from this form (PART G)
                    'updated_by'       => $userId,
                ]);

                // Remove images marked for deletion (PART K/L7)
                $removeIds = array_map('intval', (array) ($validated['remove_image_ids'] ?? []));
                if (!empty($removeIds)) {
                    $toRemove = DB::connection('oracle')->table('ASSET_IMAGE')
                        ->where('ass_id', $id)->whereIn('id', $removeIds)
                        ->get(['id', 'ass_image']);
                    foreach ($toRemove as $img) {
                        if ($img->ass_image && Storage::disk('public')->exists($img->ass_image)) {
                            Storage::disk('public')->delete($img->ass_image);
                        }
                    }
                    DB::connection('oracle')->table('ASSET_IMAGE')
                        ->where('ass_id', $id)->whereIn('id', $removeIds)->delete();
                }

                // Add new uploaded images to ASSET_IMAGE (PART K)
                if ($request->hasFile('asset_images')) {
                    $dir = 'asset-images/' . $id;
                    foreach ($request->file('asset_images') as $img) {
                        if ($img && $img->isValid()) {
                            $path = $img->store($dir, 'public');
                            if ($path) {
                                $imgId = (int) DB::connection('oracle')
                                    ->selectOne('SELECT ASSET_IMAGE_SEQ.NEXTVAL AS nv FROM DUAL')->nv;
                                DB::connection('oracle')->table('ASSET_IMAGE')->insert([
                                    'id'         => $imgId,
                                    'ass_id'     => $id,
                                    'ass_image'  => $path,
                                    'created_by' => $userId,
                                    'created_at' => DB::raw('SYSTIMESTAMP'),
                                    'updated_by' => $userId,
                                    'updated_at' => DB::raw('SYSTIMESTAMP'),
                                ]);
                            }
                        }
                    }
                }
            });
        } catch (Throwable $e) {
            Log::error('ASS-003 update failed', ['id' => $id, 'error' => $e->getMessage()]);
            return back()->withInput()->withErrors(['general' => 'แก้ไขข้อมูลไม่สำเร็จ กรุณาลองอีกครั้ง']);
        }

        return redirect()->route('asset.registrations.index')
            ->with('asset_success', 'แก้ไขข้อมูลครุภัณฑ์เรียบร้อยแล้ว');
    }

    public function destroy(int $id): RedirectResponse
    {
        $visibleOrgIds = $this->orgVisibility->visibleOrgIds((int) Auth::user()->org_id);
        $asset = Asset::query()->whereIn('org_id', $visibleOrgIds)->findOrFail($id);

        try {
            $asset->delete();
        } catch (Throwable $e) {
            Log::error('ASS-003 destroy failed', ['id' => $id, 'error' => $e->getMessage()]);
            return redirect()->route('asset.registrations.index')
                ->with('asset_error', 'ไม่สามารถลบครุภัณฑ์นี้ได้ เนื่องจากมีข้อมูลที่เกี่ยวข้องอยู่');
        }

        return redirect()->route('asset.registrations.index')
            ->with('asset_success', 'ลบข้อมูลครุภัณฑ์เรียบร้อยแล้ว');
    }

    /** Returns direct child org records (not self) for the forecast dropdown. */
    private function getChildOrgs(int $userOrgId): array
    {
        if ($userOrgId <= 0) {
            return [];
        }

        try {
            $org = DB::connection('oracle')->selectOne(
                'SELECT org_id, zone_flg FROM GLB_ORGANIZATION WHERE org_id = ?',
                [$userOrgId]
            );

            if ($org === null) {
                return [];
            }

            $zoneFlg = strtoupper(trim((string) ($org->zone_flg ?? '')));

            if ($zoneFlg === 'C') {
                $rows = DB::connection('oracle')->select(
                    "SELECT org_id, org_name FROM GLB_ORGANIZATION WHERE UPPER(zone_flg)='C' AND org_id <> ? ORDER BY org_name",
                    [$userOrgId]
                );
            } elseif ($zoneFlg === 'R') {
                $rows = DB::connection('oracle')->select(
                    'SELECT org_id, org_name FROM GLB_ORGANIZATION WHERE org_org_id = ? ORDER BY org_name',
                    [$userOrgId]
                );
            } else {
                return [];
            }

            return array_map(static fn ($r) => [
                'value'      => (int) $r->org_id,
                'label'      => $r->org_name ?? '',
                'searchText' => $r->org_name ?? '',
            ], $rows);
        } catch (Throwable) {
            return [];
        }
    }

    private function escapeLike(string $value): string
    {
        return str_replace(['%', '_', '\\'], ['\\%', '\\_', '\\\\'], $value);
    }
}
