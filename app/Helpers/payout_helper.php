<?php
/**
 * Prosty lookup wypłat na podstawie games.payout_schema_json
 * Zwraca float (kwota lub mnożnik) albo null, gdy brak dopasowania.
 *
 * @param array      $game     rekord z tabeli games (musi mieć payout_schema_json)
 * @param int        $typedA   ile liczb typowano w puli A (np. 6 dla Lotto, 8/9/10 dla MM)
 * @param int        $hitsA    ile trafień w puli A
 * @param int|null   $typedB   ile liczb typowano w puli B (EuroJackpot = 2)
 * @param int|null   $hitsB    ile trafień w puli B
 * @return float|null
 */
function game_payout_lookup(array $game, int $typedA, int $hitsA, ?int $typedB = null, ?int $hitsB = null): ?float
{
    $raw = $game['payout_schema_json'] ?? null;
    if (!$raw) return null;
    $schema = json_decode((string)$raw, true);
    if (!is_array($schema)) return null;

    $type = $schema['type'] ?? 'single';

    if ($type === 'single') {
        // tables: { "K": { "hits" : payout } }
        $tables = $schema['tables'] ?? [];
        $kKey = (string)$typedA;
        if (!isset($tables[$kKey])) return null;
        $map = $tables[$kKey];
        $hKey = (string)$hitsA;
        return isset($map[$hKey]) ? (float)$map[$hKey] : null;
    }

    if ($type === 'dual_fixed') {
        // table: { "ha,hb" : payout } (typedA/typedB zwykle stałe)
        if ($hitsB === null) return null;
        $table = $schema['table'] ?? [];
        $key = (string)((int)$hitsA) . ',' . (string)((int)$hitsB);
        return isset($table[$key]) ? (float)$table[$key] : null;
    }

    return null;
}
