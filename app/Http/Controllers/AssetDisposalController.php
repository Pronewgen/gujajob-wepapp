<?php

namespace App\Http\Controllers;

use App\Models\GlbOrganization;
use App\Services\OrganizationVisibilityService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Throwable;

class AssetDisposalController extends Controller
{
    private const PER_PAGE = 10;

    // selling_approval_status: NULL/0 = รอการอนุมัติ, 1 = อนุมัติ, 2 = ไม่อนุมัติ
    private const STATUS_MAP = [
        0 => ['label' => 'รอการอนุมัติ', 'type' => 'pending'],
        1 => ['label' => 'อนุมัติ',       'type' => 'approved'],
        2 => ['label' => 'ไม่อนุมัติ',    'type' => 'rejected'],
    ];

    // REASON column is VARCHAR2(1); codes are the single-char keys stored in Oracle.
    private const REASON_OPTIONS = [
        'C' => 'ชำรุดเสียหาย',
        'E' => 'หมดอายุการใช้งาน',
        'N' => 'ไม่มีความจำเป็นต้องใช้งาน',
        'O' => 'อื่นๆ',
    ];

    public function __construct(private readonly OrganizationVisibilityService $orgVisibility) {}

    // ──────────────────────────────────────────────────────────────────────
    //  LIST
    // ──────────────────────────────────────────────────────────────────────

    public function index(Request $request): View
    {
        $searchBy  = $request->input('search_by', 'all');
        $keyword   = trim($request->string('keyword')->value());
        $status    = $request->input('status', '');
        $sort      = $request->input('sort', '');
        $direction = strtolower($request->input('direction', 'asc')) === 'desc' ? 'desc' : 'asc';

        $visibleOrgIds = $this->orgVisibility->visibleOrgIds((int) Auth::user()->org_id);

        $query = DB::connection('oracle')
            ->table('ASSET_SELLING AS s')
            ->leftJoin('GLB_ORGANIZATION AS o', 'o.org_id', '=', 's.req_org_id')
            ->selectRaw("
                s.id,
                s.selling_code,
                s.selling_req_date,
                CASE WHEN s.selling_req_date IS NOT NULL THEN
                    TO_CHAR(s.selling_req_date,'DD-MM-')||TO_CHAR(s.selling_req_date+INTERVAL '543' YEAR(3),'YYYY')
                END AS req_date_th,
                o.org_name AS req_org_name,
                s.reason,
                s.remarks,
                s.selling_approval_status
            ");

        if (empty($visibleOrgIds)) {
            $query->whereRaw('1=0');
        } else {
            $query->whereIn('s.req_org_id', $visibleOrgIds);
        }

        // Keyword search – whitelist-guarded
        if ($keyword !== '') {
            $escaped = $this->escapeLike($keyword);
            $searchFieldMap = [
                'request_no' => 's.selling_code',
                'org_name'   => 'o.org_name',
            ];
            if (isset($searchFieldMap[$searchBy])) {
                $col = $searchFieldMap[$searchBy];
                $query->whereRaw("UPPER($col) LIKE UPPER(?) ESCAPE '\\'", ["%{$escaped}%"]);
            } else {
                // 'all' – partial match across doc number and org name
                $query->where(function ($q) use ($escaped) {
                    $q->whereRaw("UPPER(s.selling_code) LIKE UPPER(?) ESCAPE '\\'", ["%{$escaped}%"])
                      ->orWhereRaw("UPPER(o.org_name)    LIKE UPPER(?) ESCAPE '\\'", ["%{$escaped}%"]);
                });
            }
        }

        // Status filter
        if ($status === 'pending') {
            $query->where(function ($q) {
                $q->whereNull('s.selling_approval_status')
                  ->orWhere('s.selling_approval_status', 0);
            });
        } elseif ($status === 'approved') {
            $query->where('s.selling_approval_status', 1);
        } elseif ($status === 'rejected') {
            $query->where('s.selling_approval_status', 2);
        }

        // Sorting – whitelist-guarded, with deterministic secondary key
        $sortMap = [
            'request_no'   => 's.selling_code',
            'request_date' => 's.selling_req_date',
            'org_name'     => 'o.org_name',
            'status'       => 's.selling_approval_status',
        ];
        if (isset($sortMap[$sort])) {
            $query->orderByRaw("{$sortMap[$sort]} {$direction}, s.id ASC");
        } else {
            $query->orderByRaw('s.selling_req_date DESC NULLS LAST, s.id ASC');
        }

        $disposals = $query->paginate(self::PER_PAGE)->withQueryString();

        // Attach resolved status label/type to each row
        $disposals->getCollection()->transform(function ($item) {
            $key  = (int) ($item->selling_approval_status ?? 0);
            $info = self::STATUS_MAP[$key] ?? self::STATUS_MAP[0];
            $item->status_label = $info['label'];
            $item->status_type  = $info['type'];
            return $item;
        });

        return view('asset.ASS-006-request-asset-disposal.index', [
            'pageTitle' => 'แจ้งขอจำหน่ายครุภัณฑ์',
            'disposals' => $disposals,
            'searchBy'  => $searchBy,
            'keyword'   => $keyword,
            'status'    => $status,
            'sort'      => $sort,
            'direction' => $direction,
        ]);
    }

    // ──────────────────────────────────────────────────────────────────────
    //  DETAIL
    // ──────────────────────────────────────────────────────────────────────

    public function show(int $id): View
    {
        $disposal = DB::connection('oracle')
            ->table('ASSET_SELLING AS s')
            ->leftJoin('GLB_ORGANIZATION AS rorg', 'rorg.org_id', '=', 's.req_org_id')
            ->leftJoin('GLB_ORGANIZATION AS aorg', 'aorg.org_id', '=', 's.app_org_id')
            ->selectRaw("
                s.id,
                s.selling_code,
                s.selling_req_date,
                CASE WHEN s.selling_req_date IS NOT NULL THEN
                    TO_CHAR(s.selling_req_date,'DD-MM-')||TO_CHAR(s.selling_req_date+INTERVAL '543' YEAR(3),'YYYY')
                END AS req_date_th,
                rorg.org_name AS req_org_name,
                aorg.org_name AS app_org_name,
                s.reason,
                s.remarks,
                s.selling_approval_status,
                s.selling_approval_date,
                CASE WHEN s.selling_approval_date IS NOT NULL THEN
                    TO_CHAR(s.selling_approval_date,'DD-MM-')||TO_CHAR(s.selling_approval_date+INTERVAL '543' YEAR(3),'YYYY')
                END AS approval_date_th,
                s.reject_reason,
                s.buyer
            ")
            ->where('s.id', $id)
            ->first();

        abort_if(! $disposal, 404);

        $items = DB::connection('oracle')
            ->table('ASSET_SELLING_LIST AS sl')
            ->join('ASSET AS a', 'a.id', '=', 'sl.ass_id')
            ->leftJoin('ASSET_CATEGORY AS c', 'c.id', '=', 'a.asscat_id')
            ->selectRaw("
                sl.id,
                sl.selling_min_price,
                sl.selling_real_price,
                a.ass_code,
                a.ass_price,
                c.asscat_name
            ")
            ->where('sl.selling_id', $id)
            ->orderByRaw('sl.id ASC')
            ->get();

        $statusKey  = (int) ($disposal->selling_approval_status ?? 0);
        $statusInfo = self::STATUS_MAP[$statusKey] ?? self::STATUS_MAP[0];

        return view('asset.ASS-006-request-asset-disposal.show', [
            'pageTitle'  => 'รายละเอียดการแจ้งขอจำหน่ายครุภัณฑ์',
            'disposal'   => $disposal,
            'items'      => $items,
            'statusInfo' => $statusInfo,
        ]);
    }

    private function escapeLike(string $value): string
    {
        return str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $value);
    }

    // ──────────────────────────────────────────────────────────────────────
    //  CREATE FORM
    // ──────────────────────────────────────────────────────────────────────

    public function create(): View
    {
        $user    = Auth::user();
        $userOrg = GlbOrganization::where('org_id', (int) $user->org_id)
                       ->first(['org_id', 'org_name']);

        return view('asset.ASS-006-request-asset-disposal.create', [
            'pageTitle'     => 'แจ้งขอจำหน่ายครุภัณฑ์',
            'nextRequestNo' => $this->generateSellingCode(),
            'userOrg'       => $userOrg,
            'reasonOptions' => self::REASON_OPTIONS,
        ]);
    }

    // ──────────────────────────────────────────────────────────────────────
    //  STORE (Header + Lines in a single transaction)
    // ──────────────────────────────────────────────────────────────────────

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'selling_req_date' => ['required', 'date'],
            'reason'           => ['required', 'string', 'size:1', Rule::in(array_keys(self::REASON_OPTIONS))],
            'remarks'          => ['nullable', 'string', 'max:500'],
            'asset_ids'        => ['required', 'array', 'min:1'],
            'asset_ids.*'      => ['integer', 'min:1'],
        ]);

        $user          = Auth::user();
        $userId        = (int) $user->id;
        $orgId         = (int) $user->org_id;
        $visibleOrgIds = $this->orgVisibility->visibleOrgIds($orgId);
        $assetIds      = array_unique(array_map('intval', (array) $request->input('asset_ids')));
        $reqDate       = $request->input('selling_req_date'); // Y-m-d from js-date-picker
        $reason        = $request->input('reason');
        $remarks       = $request->filled('remarks') ? trim($request->input('remarks')) : null;

        // Validate assets belong to visible scope and are active
        $validAssets = DB::connection('oracle')
            ->table('ASSET')
            ->whereIn('id', $assetIds)
            ->where('ass_status', '1')
            ->whereIn('org_id', $visibleOrgIds)
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->toArray();

        if (count($validAssets) !== count($assetIds)) {
            return back()->withErrors(['_error' => 'ครุภัณฑ์บางรายการไม่ถูกต้องหรือไม่อยู่ใน Scope ที่อนุญาต'])->withInput();
        }

        // Guard: assets must not be in an active/pending disposal
        $alreadyInDisposal = DB::connection('oracle')
            ->table('ASSET_SELLING_LIST AS sl')
            ->join('ASSET_SELLING AS s', 'sl.selling_id', '=', 's.id')
            ->whereIn('sl.ass_id', $validAssets)
            ->where(function ($q) {
                $q->whereNull('s.selling_approval_status')
                  ->orWhere('s.selling_approval_status', 0)
                  ->orWhere('s.selling_approval_status', 1);
            })
            ->exists();

        if ($alreadyInDisposal) {
            return back()->withErrors(['_error' => 'ครุภัณฑ์บางรายการอยู่ในรายการแจ้งจำหน่ายอยู่แล้ว'])->withInput();
        }

        try {
            DB::connection('oracle')->transaction(function () use (
                $userId, $orgId, $reqDate, $reason, $remarks, $validAssets
            ) {
                $now      = now();
                $newId    = (int) DB::connection('oracle')->selectOne('SELECT ASSET_SELLING_SEQ.NEXTVAL AS id FROM DUAL')->id;
                $sellCode = $this->generateSellingCode();

                DB::connection('oracle')->table('ASSET_SELLING')->insert([
                    'id'                      => $newId,
                    'selling_code'            => $sellCode,
                    'selling_req_date'        => $reqDate,
                    'reason'                  => $reason,
                    'remarks'                 => $remarks,
                    'req_org_id'              => $orgId,
                    'selling_approval_status' => 0,
                    'created_by'              => $userId,
                    'updated_by'              => $userId,
                    'created_at'              => $now,
                    'updated_at'              => $now,
                ]);

                foreach ($validAssets as $assetId) {
                    $listId = (int) DB::connection('oracle')->selectOne('SELECT ASSET_SELLING_LIST_SEQ.NEXTVAL AS id FROM DUAL')->id;
                    DB::connection('oracle')->table('ASSET_SELLING_LIST')->insert([
                        'id'         => $listId,
                        'selling_id' => $newId,
                        'ass_id'     => $assetId,
                        'created_by' => $userId,
                        'updated_by' => $userId,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]);
                }
            });
        } catch (Throwable $e) {
            Log::error('AssetDisposalController::store failed', ['error' => $e->getMessage()]);
            return back()->withErrors(['_error' => 'เกิดข้อผิดพลาดขณะบันทึก กรุณาลองใหม่'])->withInput();
        }

        return redirect()->route('asset.disposals.index')
            ->with('success', 'บันทึกการแจ้งขอจำหน่ายเรียบร้อย');
    }

    // ──────────────────────────────────────────────────────────────────────
    //  ASSET SEARCH (for create form draft list)
    // ──────────────────────────────────────────────────────────────────────

    public function searchAssets(Request $request): JsonResponse
    {
        $field         = $request->string('field', '')->value();
        $keyword       = trim($request->string('q', '')->value());
        $limit         = min((int) $request->input('limit', 20), 50);
        $visibleOrgIds = $this->orgVisibility->visibleOrgIds((int) Auth::user()->org_id);

        if (empty($visibleOrgIds)) {
            return response()->json(['data' => []]);
        }

        // Exclude assets already in an active/pending disposal request
        $inDisposalIds = DB::connection('oracle')
            ->table('ASSET_SELLING_LIST AS sl')
            ->join('ASSET_SELLING AS s', 'sl.selling_id', '=', 's.id')
            ->where(function ($q) {
                $q->whereNull('s.selling_approval_status')
                  ->orWhere('s.selling_approval_status', 0)
                  ->orWhere('s.selling_approval_status', 1);
            })
            ->pluck('sl.ass_id')
            ->map(fn ($id) => (int) $id)
            ->toArray();

        $query = DB::connection('oracle')
            ->table('ASSET AS a')
            ->join('ASSET_CATEGORY AS c', 'a.asscat_id', '=', 'c.id')
            ->selectRaw("a.id, a.ass_code, c.asscat_name AS asset_name, a.ass_price")
            ->where('a.ass_status', '1')
            ->whereIn('a.org_id', $visibleOrgIds)
            ->orderBy('a.ass_code')
            ->limit($limit);

        if (!empty($inDisposalIds)) {
            $query->whereNotIn('a.id', $inDisposalIds);
        }

        if ($keyword !== '') {
            $escaped = $this->escapeLike($keyword);
            $colMap  = ['code' => 'a.ass_code', 'name' => 'c.asscat_name'];
            if (isset($colMap[$field])) {
                $col = $colMap[$field];
                $query->whereRaw("UPPER({$col}) LIKE UPPER(?) ESCAPE '\\'", ["%{$escaped}%"]);
            } else {
                $query->where(function ($q) use ($escaped) {
                    $q->whereRaw("UPPER(a.ass_code)    LIKE UPPER(?) ESCAPE '\\'", ["%{$escaped}%"])
                      ->orWhereRaw("UPPER(c.asscat_name) LIKE UPPER(?) ESCAPE '\\'", ["%{$escaped}%"]);
                });
            }
        }

        $rows = $query->get()->map(fn ($r) => [
            'id'         => (int) $r->id,
            'ass_code'   => $r->ass_code ?? '',
            'asset_name' => $r->asset_name ?? '',
            'ass_price'  => $r->ass_price !== null ? number_format((float) $r->ass_price, 2) : '-',
        ]);

        return response()->json(['data' => $rows]);
    }

    // ──────────────────────────────────────────────────────────────────────
    //  HELPERS
    // ──────────────────────────────────────────────────────────────────────

    /**
     * Generate next selling_code in format SL{BBBB}{NNN} (9 chars, Thai year).
     * Called at create() for preview and inside store() transaction for the real code.
     */
    private function generateSellingCode(): string
    {
        $thaiYear = (int) date('Y') + 543; // 2026 → 2569
        $prefix   = 'SL' . $thaiYear;

        $maxRunning = DB::connection('oracle')
            ->table('ASSET_SELLING')
            ->where('selling_code', 'like', $prefix . '%')
            ->get(['selling_code'])
            ->reduce(function (int $carry, object $r): int {
                if (preg_match('/^SL\d{4}(\d{3})$/', (string) $r->selling_code, $m) === 1) {
                    return max($carry, (int) $m[1]);
                }
                return $carry;
            }, 0);

        return $prefix . str_pad((string) ($maxRunning + 1), 3, '0', STR_PAD_LEFT);
    }
}
