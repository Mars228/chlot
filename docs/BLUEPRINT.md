# BLUEPRINT – mapa projektu

## Moduły / SEKCJE
- HOME
- GRY
- LOSOWANIA
- STATYSTYKI (schematy S1/S2; wyniki)
- STRATEGIE (SIMPLE)
- ZAKŁADY (serie + kupony + baseline)
- WYNIKI (bet_results)
- USTAWIENIA (OpenAPI klucze)

## Najważniejsze tabele
- games
- settings
- draw_results
- stat_schemas (NOWE pole: name VARCHAR(120))
- stat_results
- strategies
- bet_batches (… + processed_strategies, processed_tickets, error_msg)
- bet_tickets
- bet_results

## Flow danych
draw_results → (stat_schemas) → stat_results → strategies → bet_batches/bet_tickets → bet_results

## Konwencje
- layout: layouts/adminlte + $content
- daty w listach: dd/mm/yyyy
- liczby A/B: CSV rosnąco, bez zer wiodących
- stałe nazwy dla głównych metod (index; create; save; edit; update; delete)
- nazwy głównych plików view: *name*_index, *name*_edit, *name*_create
- nazwy kontrolerów, modeli, tabel i column w bazie po angielsku, ewentualne opisy i wyjaśnienia robimy po polsku