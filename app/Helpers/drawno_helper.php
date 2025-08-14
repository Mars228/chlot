<?php
/**
 * Zwraca lokalny numer losowania na podstawie transformacji w $game['draw_no_transform_json'].
 * Wspierane:
 *   {"type":"offset","b":-205}  => local = external + b
 * Jeśli brak transformacji albo brak $external, zwraca $external bez zmian.
 */
function normalize_draw_no(array $game, ?int $external): ?int
{
    if ($external === null) return null;
    $raw = $game['draw_no_transform_json'] ?? null;
    if (!$raw) return $external;

    $cfg = json_decode((string)$raw, true);
    if (!is_array($cfg)) return $external;

    if (($cfg['type'] ?? '') === 'offset') {
        $b = (int)($cfg['b'] ?? 0);
        return $external + $b;
    }

    // miejsce na przyszłe typy transformacji (np. "affine": a*x+b)
    return $external;
}
