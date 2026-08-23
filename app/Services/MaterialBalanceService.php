<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

/**
 * MaterialBalanceService
 *
 * Computes current material balance from transaction tables.
 *
 * Source of Truth (selected by priority):
 *   1. MATERIAL_INVENTORY — exists but currently has 0 rows;
 *      NOT populated by MAT-002, so it cannot serve as the source.
 *   2. Aggregation from individual transaction tables (chosen).
 *
 * Formula:
 *   balance =
 *       + Σ MATERIAL_PROCUREMENT_LIST.mat_amt        (all procurement = stock in)
 *       − Σ MATERIAL_WITHDRAWN_LIST.wd_amount        (approved withdrawals, status = 2)
 *       + Σ MATERIAL_INSPECTION_LIST.isp_amount      (confirmed transfers; list rows exist
 *                                                     only after confirmation)
 *
 * No double-counting: MATERIAL_INVENTORY (empty) is NOT included.
 * No ledger table exists; each transaction source is queried exactly once.
 */
class MaterialBalanceService
{
    /** STATUS value in MATERIAL_WITHDRAWN that means "approved" */
    private const WD_STATUS_APPROVED = 2;

    /**
     * Compute balances for multiple materials in a single batch query.
     *
     * @param  int[]    $matIds               Material IDs to compute balance for.
     * @param  int|null $excludeInspectionId  When the current document is already
     *                                        confirmed (has list rows), pass its
     *                                        inspection ID here so its contribution
     *                                        is excluded, giving the balance
     *                                        *before* this transfer.
     * @return array<int, int>  mat_id => balance (only mat_ids that have at least
     *                          one transaction row are returned; others are absent
     *                          → caller shows "–" for missing keys).
     */
    public function getBalances(array $matIds, ?int $excludeInspectionId = null): array
    {
        if (empty($matIds)) {
            return [];
        }

        $matIds = array_values(array_unique(array_map('intval', $matIds)));
        $count  = count($matIds);
        $ph     = implode(',', array_fill(0, $count, '?'));

        // Optional exclusion clause for the transfer sub-query
        $transferExclude = ($excludeInspectionId !== null)
            ? 'AND mat_isp_id != ?'
            : '';

        $sql = "
            SELECT mat_id, SUM(qty_change) AS balance
            FROM (
                -- Stock IN: procurement (all records; no STATUS column on MATERIAL_PROCUREMENT)
                SELECT mat_id, mat_amt AS qty_change
                FROM MATERIAL_PROCUREMENT_LIST
                WHERE mat_id IN ($ph)

                UNION ALL

                -- Stock OUT: approved withdrawals (MATERIAL_WITHDRAWN.status = 2)
                SELECT mwl.mat_id, -mwl.wd_amount AS qty_change
                FROM MATERIAL_WITHDRAWN_LIST mwl
                INNER JOIN MATERIAL_WITHDRAWN mw ON mw.id = mwl.mat_wd_id
                WHERE mwl.mat_id IN ($ph)
                  AND mw.status = ?

                UNION ALL

                -- Stock IN: confirmed transfers
                -- (MATERIAL_INSPECTION_LIST rows only exist for confirmed documents)
                SELECT mat_id, isp_amount AS qty_change
                FROM MATERIAL_INSPECTION_LIST
                WHERE mat_id IN ($ph)
                  $transferExclude
            ) t
            GROUP BY mat_id
        ";

        // Parameter order matches the three sub-queries
        $params = array_merge(
            $matIds,                          // procurement WHERE mat_id IN
            $matIds,                          // withdrawal WHERE mat_id IN
            [self::WD_STATUS_APPROVED],       // withdrawal status
            $matIds,                          // transfer WHERE mat_id IN
            $excludeInspectionId !== null ? [(int) $excludeInspectionId] : []
        );

        $rows = DB::connection('oracle')->select($sql, $params);

        $result = [];
        foreach ($rows as $row) {
            $result[(int) $row->mat_id] = (int) $row->balance;
        }

        return $result;
    }
}
