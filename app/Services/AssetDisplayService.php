<?php

namespace App\Services;

use App\Http\Controllers\AssetController;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class AssetDisplayService
{
    public function displayCode(object $asset): string
    {
        $categoryCode = trim((string) ($asset->asscat_code ?? ''));
        $assetCode = trim((string) ($asset->ass_code ?? ''));

        if ((string) ($asset->aa_status ?? '') !== '2') {
            return $categoryCode !== '' ? $categoryCode : '-';
        }

        if ($assetCode === '') {
            return $categoryCode !== '' ? $categoryCode : '-';
        }

        return $categoryCode !== '' ? $categoryCode . ' ' . $assetCode : $assetCode;
    }

    /** @return array{label: string, class: string} */
    public function statusInfo(string $status): array
    {
        return AssetController::assetStatusLabel($status);
    }

    /**
     * Resolve completed disposal results for a set of assets without querying from a view.
     *
     * @param  iterable<int, object>  $assets
     * @return array<int, string>
     */
    public function completedDisposalStatuses(iterable $assets): array
    {
        $assetIds = collect($assets)->pluck('id')->filter()->map(fn ($id) => (int) $id)->values();
        if ($assetIds->isEmpty()) {
            return [];
        }

        $rows = DB::connection('oracle')
            ->table('ASSET_SELLING AS s')
            ->join('ASSET_SELLING_LIST AS sl', 'sl.selling_id', '=', 's.id')
            ->whereIn('sl.ass_id', $assetIds->all())
            ->where('s.selling_approval_status', 1)
            ->groupBy('s.id', 'sl.ass_id', 's.reason', 's.buyer')
            ->selectRaw("sl.ass_id, s.reason, s.buyer, COUNT(*) AS total_items, COUNT(sl.selling_real_price) AS priced_items")
            ->get();

        $statuses = [];
        foreach ($rows as $row) {
            $completed = (int) $row->total_items > 0
                && (int) $row->total_items === (int) $row->priced_items
                && ((string) $row->reason === '3' || trim((string) ($row->buyer ?? '')) !== '');

            if ($completed) {
                $statuses[(int) $row->ass_id] = (string) $row->reason === '3' ? '6' : '4';
            }
        }

        return $statuses;
    }
}
