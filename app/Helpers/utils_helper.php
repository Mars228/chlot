<?php
if (!function_exists('array_index_by_id')) {
    function array_index_by_id(array $rows): array {
        $out = [];
        foreach ($rows as $r) {
            if (isset($r['id'])) $out[$r['id']] = $r;
        }
        return $out;
    }
}

# alias kompatybilności – jeśli gdzieś zostało "index_by_id(...)"
if (!function_exists('index_by_id')) {
    function index_by_id(array $rows): array {
        return array_index_by_id($rows);
    }
}
