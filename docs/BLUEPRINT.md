# BLUEPRINT

## Moduły
- GRY, LOSOWANIA, STATYSTYKI(S1/S2), STRATEGIE(SIMPLE), ZAKŁADY, WYNIKI, HOME, USTAWIENIA

## Tabele (skrót)
- games, draw_results, settings, stat_schemas(name!), stat_results, strategies,
  bet_batches(processed_*), bet_tickets, bet_results

## Flow
draw_results → stat_schemas → stat_results → strategies → bet_batches/tickets → bet_results

## Konwencje
- layout: layouts/adminlte + $content
- daty w UI: dd/mm/yyyy
- liczby: CSV rosnąco, bez zer wiodących