<?php

namespace App\Console\Commands;

use App\Models\AssetAssignment;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class SyncAssetStatus extends Command
{
    protected $signature   = 'asset:sync-status {--dry-run : Preview without updating}';
    protected $description = 'Sync ASSET.ASS_STATUS for records that were received but still show status 1';

    public function handle(): int
    {
        $dryRun = $this->option('dry-run');

        // Find received assets still carrying status '1'
        $rows = DB::connection('oracle')
            ->table('ASSET AS a')
            ->join('ASSET_ASSIGNMENT_LIST AS aal', 'aal.asset_id', '=', 'a.id')
            ->join('ASSET_ASSIGNMENT AS aa', 'aa.id', '=', 'aal.ass_assign_id')
            ->where('a.ass_status', '1')
            ->where('aa.status', AssetAssignment::STATUS_RECEIVED)
            ->whereNotNull('a.ass_trans_date')
            ->selectRaw('a.id, a.remain_price, a.ass_status')
            ->get();

        if ($rows->isEmpty()) {
            $this->info('Nothing to sync — all received assets already have the correct status.');
            return 0;
        }

        $toNormal  = $rows->filter(fn ($r) => (float) $r->remain_price !== 1.0);
        $toDispose = $rows->filter(fn ($r) => (float) $r->remain_price === 1.0);

        $this->line("Records to update → ปกติ (2):          {$toNormal->count()}");
        $this->line("Records to update → พร้อมจำหน่าย (3): {$toDispose->count()}");

        if ($dryRun) {
            $this->warn('Dry-run mode — no changes written.');
            return 0;
        }

        DB::connection('oracle')->transaction(function () use ($toNormal, $toDispose): void {
            if ($toNormal->isNotEmpty()) {
                $ids = $toNormal->pluck('id')->toArray();
                DB::connection('oracle')->table('ASSET')
                    ->whereIn('id', $ids)
                    ->update(['ass_status' => '2', 'updated_at' => now()]);
            }

            if ($toDispose->isNotEmpty()) {
                $ids = $toDispose->pluck('id')->toArray();
                DB::connection('oracle')->table('ASSET')
                    ->whereIn('id', $ids)
                    ->update(['ass_status' => '3', 'updated_at' => now()]);
            }
        });

        $this->info("Synced {$rows->count()} asset(s) successfully.");
        return 0;
    }
}
