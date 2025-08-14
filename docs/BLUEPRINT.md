# BLUEPRINT – mapa projektu

## Moduły
- GRY
- LOSOWANIA
- STATYSTYKI (schematy S1/S2; wyniki)
- STRATEGIE (SIMPLE)
- ZAKŁADY (serie + kupony + baseline)
- WYNIKI (bet_results)
- HOME
- USTAWIENIA (OpenAPI klucze)

## Najważniejsze tabele
- games
- draw_results
- settings
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
