### stat_schemas
- id PK, game_id FK, scheme ENUM('S1','S2'), name VARCHAR(120)
- x_a,y_a,x_b,y_b,k_a,k_b, from_draw_system_id, window_size, series_end_at, created_at

### bet_batches
- … + processed_strategies INT, processed_tickets INT, error_msg TEXT

### bet_results
- ticket_id, batch_id, game_id, strategy_id, is_baseline
- next_draw_system_id, evaluation_draw_system_id
- hits_a,hits_b,k_a,k_b, win_amount, win_factor, win_currency, prize_label, is_winner
