<?php
if (!function_exists('pretty_numbers')) {
    /**
     * Formatuje CSV liczb: usuwa wiodące zera, sortuje rosnąco i łączy przecinkami.
     *
     * @param string|null $csv "1,09,3, 12"
     * @return string "1, 3, 9, 12"
     */
    function pretty_numbers(?string $csv): string
    {
        if (!$csv) return '';
        $arr = array_map('trim', explode(',', $csv));
        $arr = array_filter($arr, static fn($v) => $v !== '');
        $arr = array_map('intval', $arr);        // "01" -> 1
        sort($arr, SORT_NUMERIC);                // rosnąco
        return $arr ? implode(', ', $arr) : '';
    }
}
