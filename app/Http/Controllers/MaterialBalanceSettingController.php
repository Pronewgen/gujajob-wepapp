<?php

namespace App\Http\Controllers;

use App\Models\GlbOrganization;
use App\Models\Material;
use App\Services\FiscalYearService;
use App\Services\MaterialBalanceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
use Throwable;

class MaterialBalanceSettingController extends Controller
{
    public function __construct(private MaterialBalanceService $balanceService) {}

    public function index(Request $request): View
    {
        $searchBy    = $request->string('search_by', 'name')->value();
        $keyword     = trim($request->string('keyword')->value());
        $sort        = $request->string('sort', '')->value();
        $direction   = strtolower($request->string('direction', 'asc')->value()) === 'desc' ? 'desc' : 'asc';
        $orgId       = $request->integer('org_id', 0);
        $currentFY   = FiscalYearService::current();
        $fiscalYear  = $request->integer('fiscal_year', $currentFY);
        // Clamp to available years
        if ($fiscalYear < $currentFY - 4 || $fiscalYear > $currentFY) {
            $fiscalYear = $currentFY;
        }
        $isEditable  = FiscalYearService::isCurrent($fiscalYear);

        $allowedSorts = [
            'fiscal_year' => null,
            'code'        => 'mat_code',
            'name'        => 'mat_name',
            'balance'     => null,
            'avg_price'   => null,
        ];

        // Real balance aggregated from transaction tables
        $balanceSubSql = "(SELECT NVL(SUM(delta),0) FROM (
            SELECT mat_amt AS delta FROM MATERIAL_PROCUREMENT_LIST WHERE mat_id = MATERIALS.id
            UNION ALL
            SELECT -mwl.wd_amount FROM MATERIAL_WITHDRAWN_LIST mwl
                JOIN MATERIAL_WITHDRAWN mw ON mw.id = mwl.mat_wd_id
                WHERE mwl.mat_id = MATERIALS.id AND mw.status = 2
            UNION ALL
            SELECT isp_amount FROM MATERIAL_INSPECTION_LIST WHERE mat_id = MATERIALS.id
        ))";

        $query = Material::query()
            ->select('MATERIALS.*')
            ->addSelect(DB::raw("{$balanceSubSql} AS current_balance"));

        // Filter by organization via procurement records
        if ($orgId > 0) {
            $query->whereRaw(
                'EXISTS (SELECT 1 FROM MATERIAL_PROCUREMENT_LIST mpl JOIN MATERIAL_PROCUREMENT mp ON mp.id = mpl.mat_pro_id WHERE mpl.mat_id = MATERIALS.id AND mp.org_id = ?)',
                [$orgId]
            );
        } else {
            $query->whereRaw('EXISTS (SELECT 1 FROM MATERIAL_PROCUREMENT_LIST mpl WHERE mpl.mat_id = MATERIALS.id)');
        }

        if ($keyword !== '') {
            $escaped = $this->escapeLike($keyword);
            if ($searchBy === 'code') {
                $query->whereRaw('UPPER(mat_code) LIKE UPPER(?)', ['%' . mb_strtoupper($escaped) . '%']);
            } else {
                $query->whereRaw('UPPER(mat_name) LIKE UPPER(?)', ['%' . mb_strtoupper($escaped) . '%']);
            }
        }

        if ($sort === 'balance') {
            $query->orderByRaw("{$balanceSubSql} {$direction} NULLS LAST");
        } elseif ($sort === 'avg_price') {
            $avgPriceSql = "(SELECT SUM(mpl2.mat_amt * mpl2.mat_price) / NULLIF(SUM(mpl2.mat_amt), 0) FROM MATERIAL_PROCUREMENT_LIST mpl2 WHERE mpl2.mat_id = MATERIALS.id)";
            $query->orderByRaw("{$avgPriceSql} {$direction} NULLS LAST");
        } elseif ($sort === 'fiscal_year') {
            // fiscal_year is the same for all rows (from filter), so secondary sort by code
            $query->orderBy('mat_code', $direction);
        } else {
            $sortColumn = (array_key_exists($sort, $allowedSorts) && $allowedSorts[$sort])
                ? $allowedSorts[$sort]
                : 'mat_code';
            $query->orderBy($sortColumn, $direction);
        }

        $materials = $query->paginate(10)->withQueryString();
        $matIds    = $materials->pluck('id')->map('intval')->toArray();
        $avgPrices = $this->computeAvgPrices($matIds);

        // Organizations that have procurement records
        $organizations = GlbOrganization::query()
            ->whereRaw('EXISTS (SELECT 1 FROM MATERIAL_PROCUREMENT mp WHERE mp.org_id = GLB_ORGANIZATION.org_id)')
            ->orderBy('org_name')
            ->get(['org_id', 'org_name']);

        $availableFiscalYears = FiscalYearService::availableYears(5);

        return view('material.MAT-006-record-balance-setting.index', [
            'pageTitle'            => 'บันทึกการตั้งยอดคงเหลือ',
            'materials'            => $materials,
            'avgPrices'            => $avgPrices,
            'fiscalYear'           => $fiscalYear,
            'currentFY'            => $currentFY,
            'isEditable'           => $isEditable,
            'searchBy'             => $searchBy,
            'keyword'              => $keyword,
            'sort'                 => $sort,
            'direction'            => $direction,
            'orgId'                => $orgId,
            'organizations'        => $organizations,
            'availableFiscalYears' => $availableFiscalYears,
            'bulkUpdateUrl'        => route('material.balance.bulk-update'),
        ]);
    }

    public function bulkUpdate(Request $request): JsonResponse
    {
        $currentFY = FiscalYearService::current();

        $fiscalYear = (int) $request->input('fiscal_year', 0);
        if ($fiscalYear !== $currentFY) {
            return response()->json([
                'success' => false,
                'error'   => 'ไม่สามารถแก้ไขข้อมูลของปีงบประมาณที่ผ่านมาได้',
            ], 422);
        }

        $orgId = (int) $request->input('organization_id', 0);
        $items = $request->input('items', []);

        if (! is_array($items) || count($items) === 0) {
            return response()->json(['success' => false, 'error' => 'ไม่มีรายการที่จะบันทึก'], 422);
        }

        // Validate no duplicate material_id
        $matIds = array_column($items, 'material_id');
        if (count($matIds) !== count(array_unique($matIds))) {
            return response()->json(['success' => false, 'error' => 'มีรายการวัสดุซ้ำกันใน Payload'], 422);
        }

        // Validate each item
        foreach ($items as $item) {
            if (! isset($item['material_id']) || ! is_numeric($item['material_id'])) {
                return response()->json(['success' => false, 'error' => 'ข้อมูล material_id ไม่ถูกต้อง'], 422);
            }
            if (! isset($item['balance']) || ! is_numeric($item['balance']) || (float) $item['balance'] < 0) {
                return response()->json(['success' => false, 'error' => 'ยอดคงเหลือต้องเป็นตัวเลขที่ไม่ติดลบ'], 422);
            }
        }

        try {
            DB::connection('oracle')->transaction(function () use ($items, $orgId): void {
                foreach ($items as $item) {
                    $matId  = (int) $item['material_id'];
                    $invAmt = (float) $item['balance'];

                    // Verify material exists
                    $mat = Material::query()->find($matId);
                    if (! $mat) {
                        throw new \RuntimeException("ไม่พบวัสดุ ID: {$matId}");
                    }

                    $existing = DB::connection('oracle')
                        ->table('MATERIAL_INVENTORY')
                        ->where('mat_id', $matId)
                        ->first();

                    if ($existing) {
                        DB::connection('oracle')
                            ->table('MATERIAL_INVENTORY')
                            ->where('mat_id', $matId)
                            ->update(['inv_amt' => $invAmt, 'updated_at' => now(), 'updated_by' => 1]);
                    } else {
                        $resolvedOrgId = $orgId > 0 ? $orgId : (int) (
                            DB::connection('oracle')
                                ->table('MATERIAL_PROCUREMENT')
                                ->join('MATERIAL_PROCUREMENT_LIST', 'MATERIAL_PROCUREMENT.id', '=', 'MATERIAL_PROCUREMENT_LIST.mat_pro_id')
                                ->where('MATERIAL_PROCUREMENT_LIST.mat_id', $matId)
                                ->orderBy('MATERIAL_PROCUREMENT.id')
                                ->value('MATERIAL_PROCUREMENT.org_id') ?? 1
                        );
                        $nextId = (int) DB::connection('oracle')->selectOne('SELECT NVL(MAX(id),0)+1 AS next_id FROM MATERIAL_INVENTORY')->next_id;
                        DB::connection('oracle')
                            ->table('MATERIAL_INVENTORY')
                            ->insert([
                                'id'         => $nextId,
                                'org_id'     => $resolvedOrgId,
                                'mat_id'     => $matId,
                                'inv_amt'    => $invAmt,
                                'inv_price'  => 0,
                                'created_by' => 1,
                                'updated_by' => 1,
                                'created_at' => now(),
                                'updated_at' => now(),
                            ]);
                    }
                }
            });
        } catch (Throwable $e) {
            Log::error('MAT-006 bulkUpdate failed', ['error' => $e->getMessage()]);
            return response()->json(['success' => false, 'error' => 'บันทึกไม่สำเร็จ: ' . $e->getMessage()], 500);
        }

        return response()->json(['success' => true, 'count' => count($items)]);
    }

    public function updateBalance(Request $request, string $materialCode): JsonResponse
    {
        $material = Material::query()->where('mat_code', $materialCode)->first();
        if (! $material) {
            return response()->json(['success' => false, 'error' => 'ไม่พบวัสดุ'], 404);
        }

        $balance = $request->input('balance');
        if (! is_numeric($balance) || (float) $balance < 0) {
            return response()->json(['success' => false, 'error' => 'ยอดคงเหลือต้องเป็นตัวเลขที่ไม่ติดลบ']);
        }

        $matId   = (int) $material->id;
        $invAmt  = (float) $balance;

        try {
            $existing = DB::connection('oracle')
                ->table('MATERIAL_INVENTORY')
                ->where('mat_id', $matId)
                ->first();

            if ($existing) {
                DB::connection('oracle')
                    ->table('MATERIAL_INVENTORY')
                    ->where('mat_id', $matId)
                    ->update(['inv_amt' => $invAmt]);
            } else {
                $orgId = (int) (DB::connection('oracle')
                    ->table('MATERIAL_PROCUREMENT')
                    ->join('MATERIAL_PROCUREMENT_LIST', 'MATERIAL_PROCUREMENT.id', '=', 'MATERIAL_PROCUREMENT_LIST.mat_pro_id')
                    ->where('MATERIAL_PROCUREMENT_LIST.mat_id', $matId)
                    ->orderBy('MATERIAL_PROCUREMENT.id')
                    ->value('MATERIAL_PROCUREMENT.org_id') ?? 1);
                $nextId = (int) DB::connection('oracle')->selectOne('SELECT NVL(MAX(id),0)+1 AS next_id FROM MATERIAL_INVENTORY')->next_id;
                DB::connection('oracle')
                    ->table('MATERIAL_INVENTORY')
                    ->insert([
                        'id'         => $nextId,
                        'org_id'     => $orgId,
                        'mat_id'     => $matId,
                        'inv_amt'    => $invAmt,
                        'inv_price'  => 0,
                        'created_by' => 1,
                        'updated_by' => 1,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
            }
        } catch (Throwable $e) {
            Log::error('MAT-006 updateBalance failed', ['mat_id' => $matId, 'error' => $e->getMessage()]);
            return response()->json(['success' => false, 'error' => 'บันทึกไม่สำเร็จ กรุณาลองใหม่']);
        }

        return response()->json(['success' => true, 'balance' => $invAmt]);
    }

    public function edit(Request $request, string $materialCode): View
    {
        $material = Material::query()->where('mat_code', $materialCode)->first();
        if (! $material) {
            abort(404);
        }

        $matId = (int) $material->id;

        try {
            $rows = DB::connection('oracle')->select("
                SELECT
                    TO_CHAR(mp.mat_pro_date, 'YYYY-MM-DD') AS receive_date,
                    mp.mat_pro_code AS receive_no,
                    mpl.mat_amt AS receive_quantity,
                    mpl.mat_price AS unit_price
                FROM MATERIAL_PROCUREMENT_LIST mpl
                JOIN MATERIAL_PROCUREMENT mp ON mp.id = mpl.mat_pro_id
                WHERE mpl.mat_id = :mat_id
                ORDER BY mp.mat_pro_date ASC, mpl.id ASC
            ", ['mat_id' => $matId]);
        } catch (Throwable $e) {
            Log::error('MAT-007 lots query failed', ['mat_id' => $matId, 'error' => $e->getMessage()]);
            $rows = [];
        }

        $avgPrices = $this->computeAvgPrices([$matId]);

        $lots = array_map(function ($row) {
            return [
                'receive_date'     => $this->formatThaiDate((string) ($row->receive_date ?? '')),
                'receive_no'       => (string) ($row->receive_no ?? ''),
                'receive_quantity' => (float) ($row->receive_quantity ?? 0),
                'unit_price'       => number_format((float) ($row->unit_price ?? 0), 2),
                'balance_quantity' => 0,
            ];
        }, $rows);

        $record = [
            'material_code' => $material->mat_code,
            'material_name' => $material->mat_name,
            'department'    => '-',
            'unit'          => $material->unit ?? '-',
            'average_price' => number_format($avgPrices[$matId] ?? 0, 2),
        ];

        return view('material.MAT-006-record-balance-setting.edit', [
            'pageTitle' => 'แก้ไขข้อมูลการตั้งยอดคงเหลือ',
            'record'    => $record,
            'lots'      => $lots,
        ]);
    }

    private function formatThaiDate(string $date): string
    {
        if ($date === '') {
            return '-';
        }

        $parts = explode('-', $date);

        if (count($parts) !== 3) {
            return $date;
        }

        [$y, $m, $d] = $parts;

        return sprintf('%s-%s-%d', $d, $m, (int) $y + 543);
    }

    private function computeAvgPrices(array $matIds): array
    {
        if (empty($matIds)) {
            return [];
        }

        $ph  = implode(',', array_fill(0, count($matIds), '?'));
        $sql = "
            SELECT mat_id,
                   SUM(mat_amt * mat_price) / NULLIF(SUM(mat_amt), 0) AS avg_price
            FROM MATERIAL_PROCUREMENT_LIST
            WHERE mat_id IN ({$ph})
            GROUP BY mat_id
        ";

        try {
            $rows = DB::connection('oracle')->select($sql, $matIds);
        } catch (Throwable $e) {
            Log::error('MAT-006 avg price query failed', ['error' => $e->getMessage()]);
            return [];
        }

        $result = [];
        foreach ($rows as $row) {
            $result[(int) $row->mat_id] = round((float) ($row->avg_price ?? 0), 2);
        }

        return $result;
    }

    private function escapeLike(string $value): string
    {
        return str_replace(['%', '_', '\\'], ['\\%', '\\_', '\\\\'], $value);
    }
}
