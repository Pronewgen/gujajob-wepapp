<?php

namespace App\Http\Controllers;

use App\Models\GlbOrganization;
use App\Models\Material;
use App\Models\MaterialWithdrawn;
use App\Services\FiscalYearService;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
use Throwable;

class MaterialRegisterController extends Controller
{
    public function index(Request $request): View
    {
        $searchType  = $request->string('search_type', 'code')->value();
        $keyword     = trim($request->string('keyword')->value());
        $materialId  = (int) $request->input('material_id', 0);
        $dateFrom    = $request->string('date_from')->value();
        $dateTo      = $request->string('date_to')->value();
        $sort        = $request->string('sort', '')->value();
        $direction   = strtolower($request->string('direction', 'asc')->value()) === 'desc' ? 'desc' : 'asc';
        $orgId       = $request->integer('org_id', 0);
        $currentFY   = FiscalYearService::current();
        $fiscalYear  = $request->integer('fiscal_year', $currentFY);
        if ($fiscalYear < $currentFY - 4 || $fiscalYear > $currentFY) {
            $fiscalYear = $currentFY;
        }

        // If fiscal_year is selected but no explicit date range, default to fiscal year date range
        $fyRange     = FiscalYearService::dateRange($fiscalYear);
        $effectiveDateFrom = $dateFrom !== '' ? $dateFrom : ($fiscalYear !== 0 ? $fyRange['start'] : '');
        $effectiveDateTo   = $dateTo   !== '' ? $dateTo   : ($fiscalYear !== 0 ? $fyRange['end']   : '');

        $dateError = null;
        if ($effectiveDateFrom !== '' && $effectiveDateTo !== '' && $effectiveDateFrom > $effectiveDateTo) {
            $dateError = 'วันที่เริ่มต้นต้องไม่มากกว่าวันที่สิ้นสุด';
        }

        $searched = $materialId > 0 || $keyword !== '';

        // Organizations that have procurement records
        $organizations = GlbOrganization::query()
            ->whereRaw('EXISTS (SELECT 1 FROM MATERIAL_PROCUREMENT mp WHERE mp.org_id = GLB_ORGANIZATION.org_id)')
            ->orderBy('org_name')
            ->get(['org_id', 'org_name']);

        $availableFiscalYears = FiscalYearService::availableYears(5);

        // Build material list filtered by org if specified
        $matQuery = Material::query()->orderBy('mat_code');
        if ($orgId > 0) {
            $matQuery->whereRaw(
                'EXISTS (SELECT 1 FROM MATERIAL_PROCUREMENT_LIST mpl JOIN MATERIAL_PROCUREMENT mp ON mp.id = mpl.mat_pro_id WHERE mpl.mat_id = MATERIALS.id AND mp.org_id = ?)',
                [$orgId]
            );
        }
        $allMaterials = $matQuery->paginate(10, ['id', 'mat_code', 'mat_name', 'unit'], 'mat_page')
            ->withQueryString();

        $baseProps = [
            'pageTitle'            => 'คุมทะเบียนวัสดุ',
            'searchType'           => $searchType,
            'keyword'              => $keyword,
            'materialId'           => $materialId,
            'dateFrom'             => $dateFrom,
            'dateTo'               => $dateTo,
            'dateError'            => $dateError,
            'allMaterials'         => $allMaterials,
            'sort'                 => $sort,
            'direction'            => $direction,
            'orgId'                => $orgId,
            'fiscalYear'           => $fiscalYear,
            'currentFY'            => $currentFY,
            'organizations'        => $organizations,
            'availableFiscalYears' => $availableFiscalYears,
        ];

        if (! $searched || $dateError) {
            return view('material.MAT-005-material-register.index', array_merge($baseProps, [
                'searched'      => false,
                'material'      => null,
                'multipleFound' => false,
                'materials'     => collect(),
                'summary'       => null,
                'transactions'  => [],
            ]));
        }

        $material      = null;
        $multipleFound = false;
        $materials     = collect();

        try {
            if ($materialId > 0) {
                $material = Material::query()->find($materialId);
            } else {
                $escaped = $this->escapeLike($keyword);
                $q       = Material::query()->orderBy('mat_code');

                if ($searchType === 'code') {
                    $q->whereRaw('UPPER(mat_code) LIKE UPPER(?)', ['%' . mb_strtoupper($escaped) . '%']);
                } else {
                    $q->whereRaw('UPPER(mat_name) LIKE UPPER(?)', ['%' . mb_strtoupper($escaped) . '%']);
                }

                if ($orgId > 0) {
                    $q->whereRaw(
                        'EXISTS (SELECT 1 FROM MATERIAL_PROCUREMENT_LIST mpl JOIN MATERIAL_PROCUREMENT mp ON mp.id = mpl.mat_pro_id WHERE mpl.mat_id = MATERIALS.id AND mp.org_id = ?)',
                        [$orgId]
                    );
                }

                $materials = $q->paginate(10, ['*'], 'mat_page')->withQueryString();

                if ($materials->total() === 1) {
                    $material  = $materials->first();
                    $materials = collect();
                } elseif ($materials->total() > 1) {
                    $multipleFound = true;
                }
            }
        } catch (Throwable $e) {
            Log::error('MAT-005 material lookup failed', ['error' => $e->getMessage()]);
        }

        $summary      = null;
        $transactions = [];

        if ($material !== null) {
            [
                'summary'      => $summary,
                'transactions' => $transactions,
            ] = $this->buildLedger($material, $effectiveDateFrom ?: null, $effectiveDateTo ?: null, $sort, $direction, $orgId);
        }

        return view('material.MAT-005-material-register.index', array_merge($baseProps, [
            'searched'      => true,
            'material'      => $material,
            'multipleFound' => $multipleFound,
            'materials'     => $materials,
            'summary'       => $summary,
            'transactions'  => $transactions,
        ]));
    }

    private function buildLedger(Material $material, ?string $dateFrom, ?string $dateTo, string $sort = '', string $direction = 'asc', int $orgId = 0): array
    {
        $matId = (int) $material->id;

        // Oracle analytic function computes running balance chronologically
        $orgWhereProcurement = $orgId > 0 ? ' AND mp.org_id = ?' : '';
        $orgWhereWithdrawn   = $orgId > 0 ? ' AND mw.org_id = ?' : '';

        $sql = "
            SELECT
                TO_CHAR(t.txn_date, 'YYYY-MM-DD') AS txn_date,
                t.sort_key,
                t.doc_code,
                t.reference_no,
                t.detail,
                t.operator,
                t.operator_role,
                t.in_qty,
                t.out_qty,
                SUM(t.in_qty - t.out_qty) OVER (
                    ORDER BY t.txn_date ASC, t.sort_key ASC
                    ROWS UNBOUNDED PRECEDING
                ) AS running_balance
            FROM (
                SELECT
                    mp.mat_pro_date        AS txn_date,
                    mp.mat_pro_code        AS doc_code,
                    mp.mat_pro_quotation   AS reference_no,
                    'รับวัสดุเข้าคลัง'   AS detail,
                    NULL                   AS operator,
                    'กรรมการตรวจรับ'      AS operator_role,
                    mpl.mat_amt            AS in_qty,
                    0                      AS out_qty,
                    'A'                    AS sort_key
                FROM MATERIAL_PROCUREMENT_LIST mpl
                JOIN MATERIAL_PROCUREMENT mp ON mp.id = mpl.mat_pro_id
                WHERE mpl.mat_id = ?{$orgWhereProcurement}

                UNION ALL

                SELECT
                    mw.mat_wd_date         AS txn_date,
                    mw.mat_wd_code         AS doc_code,
                    NULL                   AS reference_no,
                    'เบิกวัสดุ'           AS detail,
                    mw.mat_wd_person       AS operator,
                    'ผู้เบิกวัสดุ'        AS operator_role,
                    0                      AS in_qty,
                    mwl.wd_amount          AS out_qty,
                    'B'                    AS sort_key
                FROM MATERIAL_WITHDRAWN_LIST mwl
                JOIN MATERIAL_WITHDRAWN mw ON mw.id = mwl.mat_wd_id
                WHERE mwl.mat_id = ? AND mw.status = ?{$orgWhereWithdrawn}
            ) t
            ORDER BY t.txn_date ASC, t.sort_key ASC
        ";

        $params = [$matId];
        if ($orgId > 0) $params[] = $orgId;
        $params[] = $matId;
        $params[] = MaterialWithdrawn::STATUS_APPROVED;
        if ($orgId > 0) $params[] = $orgId;

        try {
            $rows = DB::connection('oracle')->select($sql, $params);
        } catch (Throwable $e) {
            Log::error('MAT-005 ledger query failed', ['mat_id' => $matId, 'error' => $e->getMessage()]);
            return [
                'summary'      => ['forward' => 0, 'in_total' => 0, 'out_total' => 0, 'balance' => 0],
                'transactions' => [],
            ];
        }

        $forwardBalance = 0.0;
        $inTotal        = 0.0;
        $outTotal       = 0.0;
        $displayRows    = [];

        foreach ($rows as $row) {
            $txnDate        = (string) ($row->txn_date       ?? '');
            $inQty          = (float)  ($row->in_qty         ?? 0);
            $outQty         = (float)  ($row->out_qty        ?? 0);
            $runningBalance = (float)  ($row->running_balance ?? 0);

            if ($dateFrom !== null && $txnDate < $dateFrom) {
                $forwardBalance += $inQty - $outQty;
                continue;
            }

            if ($dateTo !== null && $txnDate > $dateTo) {
                continue;
            }

            $inTotal  += $inQty;
            $outTotal += $outQty;

            $displayRows[] = [
                'date'          => $this->formatThaiDate($txnDate),
                'txn_date_raw'  => $txnDate,
                'doc_code'      => (string) ($row->doc_code      ?? ''),
                'reference_no'  => (string) ($row->reference_no  ?? ''),
                'detail'        => (string) ($row->detail        ?? ''),
                'operator'      => (string) ($row->operator      ?? ''),
                'operator_role' => (string) ($row->operator_role ?? ''),
                'in_qty'        => $inQty  > 0 ? number_format($inQty,  0) : '-',
                'in_qty_raw'    => $inQty,
                'out_qty'       => $outQty > 0 ? number_format($outQty, 0) : '-',
                'out_qty_raw'   => $outQty,
                'balance'       => number_format($runningBalance, 0),
                'balance_raw'   => $runningBalance,
            ];
        }

        // Sort display rows by requested column (server-side, before view)
        $sortMap = [
            'txn_date'        => 'txn_date_raw',
            'doc_code'        => 'doc_code',
            'in_qty_raw'      => 'in_qty_raw',
            'out_qty_raw'     => 'out_qty_raw',
            'running_balance' => 'balance_raw',
        ];

        if ($sort !== '' && isset($sortMap[$sort])) {
            $sortKey = $sortMap[$sort];
            $dir     = $direction;
            usort($displayRows, static function (array $a, array $b) use ($sortKey, $dir): int {
                $va = $a[$sortKey];
                $vb = $b[$sortKey];
                if ($va == $vb) {
                    return 0;
                }
                $cmp = ($va < $vb) ? -1 : 1;
                return $dir === 'desc' ? -$cmp : $cmp;
            });
        }

        $summary = [
            'forward'   => (int) round($forwardBalance),
            'in_total'  => (int) round($inTotal),
            'out_total' => (int) round($outTotal),
            'balance'   => (int) round($forwardBalance + $inTotal - $outTotal),
        ];

        $txnPage = max(1, (int) request()->input('txn_page', 1));
        $perPage  = 20;
        $total    = count($displayRows);
        $items    = array_slice($displayRows, ($txnPage - 1) * $perPage, $perPage);

        $transactions = (new LengthAwarePaginator($items, $total, $perPage, $txnPage, [
            'path'     => request()->url(),
            'pageName' => 'txn_page',
        ]))->withQueryString();

        return ['summary' => $summary, 'transactions' => $transactions];
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

    private function escapeLike(string $value): string
    {
        return str_replace(['%', '_', '\\'], ['\\%', '\\_', '\\\\'], $value);
    }
}
