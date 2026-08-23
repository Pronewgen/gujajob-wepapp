<?php

namespace App\Services;

class FiscalYearService
{
    /** Current Thai fiscal year (BE). FY ends Sep 30. Oct 1 starts the next year. */
    public static function current(): int
    {
        $month = (int) date('n');
        $year  = (int) date('Y');
        return $month >= 10 ? $year + 544 : $year + 543;
    }

    public static function isCurrent(int $fiscalYear): bool
    {
        return $fiscalYear === static::current();
    }

    /**
     * CE date range for a given BE fiscal year.
     * FY 2569 → start: 2025-10-01, end: 2026-09-30
     */
    public static function dateRange(int $fiscalYear): array
    {
        $ceYear = $fiscalYear - 543;
        return [
            'start' => ($ceYear - 1) . '-10-01',
            'end'   => $ceYear . '-09-30',
        ];
    }

    /** Recent fiscal years descending (most recent first). */
    public static function availableYears(int $count = 5): array
    {
        $current = static::current();
        $years   = [];
        for ($i = 0; $i < $count; $i++) {
            $years[] = $current - $i;
        }
        return $years;
    }
}
