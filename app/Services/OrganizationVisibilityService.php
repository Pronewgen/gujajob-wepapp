<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Resolves which GLB_ORGANIZATION org_ids a user may see based on zone_flg.
 *
 * zone_flg = C (Central):  all orgs where zone_flg = 'C'
 * zone_flg = R (Regional): own org + direct children (org_org_id = own org_id)
 * Unknown / missing org:   empty array (fail-safe — never grants full access)
 *
 * Results are cached per service instance (request lifetime).
 */
class OrganizationVisibilityService
{
    /** @var array<int, list<int>> */
    private array $cache = [];

    /**
     * Returns the list of org_ids that the given userOrgId is authorized to see.
     * Returns an empty array when the org does not exist or has an unknown zone.
     *
     * @return int[]
     */
    public function visibleOrgIds(int $userOrgId): array
    {
        if (isset($this->cache[$userOrgId])) {
            return $this->cache[$userOrgId];
        }

        $org = DB::connection('oracle')->selectOne(
            'SELECT org_id, zone_flg FROM GLB_ORGANIZATION WHERE org_id = ?',
            [$userOrgId]
        );

        if ($org === null) {
            Log::warning('OrganizationVisibilityService: org not found in GLB_ORGANIZATION', ['org_id' => $userOrgId]);
            return $this->cache[$userOrgId] = [];
        }

        $zoneFlg = strtoupper(trim((string) ($org->zone_flg ?? '')));

        if ($zoneFlg === 'C') {
            $rows = DB::connection('oracle')->select(
                "SELECT org_id FROM GLB_ORGANIZATION WHERE UPPER(zone_flg) = 'C'"
            );
            return $this->cache[$userOrgId] = array_map(static fn($r) => (int) $r->org_id, $rows);
        }

        if ($zoneFlg === 'R') {
            // Own org plus all direct children (org_org_id = userOrgId)
            $rows = DB::connection('oracle')->select(
                'SELECT org_id FROM GLB_ORGANIZATION WHERE org_id = ? OR org_org_id = ?',
                [$userOrgId, $userOrgId]
            );
            return $this->cache[$userOrgId] = array_map(static fn($r) => (int) $r->org_id, $rows);
        }

        Log::warning('OrganizationVisibilityService: unknown zone_flg', [
            'org_id'   => $userOrgId,
            'zone_flg' => $zoneFlg,
        ]);
        return $this->cache[$userOrgId] = [];
    }
}
