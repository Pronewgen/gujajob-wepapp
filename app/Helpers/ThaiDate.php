<?php

if (! function_exists('thai_date')) {
    /**
     * Format a date as DD-MM-BBBB (Buddhist Era year).
     *
     * @param  \Carbon\Carbon|\DateTime|string|null  $date
     * @return string  e.g. "15-01-2567"  or "-" for null/empty
     */
    function thai_date(mixed $date): string
    {
        if (! $date) {
            return '-';
        }

        $carbon = $date instanceof \Carbon\Carbon
            ? $date
            : \Carbon\Carbon::parse($date);

        return $carbon->format('d-m-') . ($carbon->year + 543);
    }
}
