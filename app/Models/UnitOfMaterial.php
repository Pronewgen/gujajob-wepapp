<?php

namespace App\Models;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Helper for unit-of-measure values.
 *
 * UNIT_OF_MATERIAL does not exist in this Oracle schema.
 * Units are sourced from distinct values already recorded in
 * MATERIALS.UNIT and ASSET_CATEGORY.ASSCAT_UNIT.
 * ASSCAT_UNIT stores the unit name directly as a VARCHAR2 string.
 */
class UnitOfMaterial
{
    /** Returns sorted distinct unit name strings from both master tables. */
    public static function allNames(): Collection
    {
        $rows = DB::connection('oracle')->select("
            SELECT DISTINCT u AS unit_name FROM (
                SELECT UNIT        AS u FROM MATERIALS        WHERE UNIT        IS NOT NULL
                UNION
                SELECT ASSCAT_UNIT AS u FROM ASSET_CATEGORY  WHERE ASSCAT_UNIT IS NOT NULL
            ) ORDER BY 1
        ");

        return collect($rows)->pluck('unit_name');
    }
}
