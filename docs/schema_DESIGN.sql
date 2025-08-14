--
# 4) (Opcjonalnie) „Kanoniczna” struktura jako punkt odniesienia
-- === GŁÓWNE TABELE ===


CREATE TABLE IF NOT EXISTS games (
  id              INT UNSIGNED NOT NULL AUTO_INCREMENT,
  slug            VARCHAR(40)  NOT NULL UNIQUE,
  name            VARCHAR(100) NOT NULL,
  description     TEXT NULL,
  price_per_bet   DECIMAL(8,2) NOT NULL DEFAULT 0.00,  -- "Cena 1 zakładu"
  logo_path       VARCHAR(255) NULL,                   -- /assets/img/...
  range_a_min     INT NULL, range_a_max INT NULL,
  range_b_min     INT NULL, range_b_max INT NULL,
  active          TINYINT(1) NOT NULL DEFAULT 1,
  created_at      DATETIME NULL,
  updated_at      DATETIME NULL,
  PRIMARY KEY (id),
  KEY idx_active (active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS settings (
  id         INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `key`      VARCHAR(120) NOT NULL UNIQUE,    -- np. lotto.api.secret
  `value`    TEXT NULL,
  updated_at DATETIME NULL,
  PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS draw_results (
  id              BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  game_id         INT UNSIGNED NOT NULL,
  draw_system_id  INT UNSIGNED NOT NULL,
  draw_date       DATE NULL,
  draw_time       TIME NULL,
  numbers_a       TEXT NULL,  -- CSV: "1,2,3"
  numbers_b       TEXT NULL,
  source          ENUM('csv','api') NOT NULL DEFAULT 'csv',
  raw_json        LONGTEXT NULL,
  created_at      DATETIME NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uq_game_draw (game_id, draw_system_id),
  KEY idx_game_date (game_id, draw_date),
  CONSTRAINT fk_draws_game FOREIGN KEY (game_id) REFERENCES games(id)
    ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- === STATYSTYKI ===

CREATE TABLE IF NOT EXISTS stat_schemas (
  id              INT UNSIGNED NOT NULL AUTO_INCREMENT,
  game_id         INT UNSIGNED NOT NULL,
  scheme          ENUM('S1','S2') NOT NULL,   -- S1: x≥y, S2: topK
  name            VARCHAR(120) NULL,          -- NAZWA WŁASNA (nowe)
  x_a INT NULL, y_a INT NULL,
  x_b INT NULL, y_b INT NULL,
  k_a INT NULL, k_b INT NULL,
  from_draw_system_id INT NULL,               -- start okna (jeśli ręczny)
  window_size     INT NULL,                   -- opcja pomocnicza
  series_end_at   DATETIME NULL,              -- opcja pomocnicza
  created_at      DATETIME NULL,
  PRIMARY KEY (id),
  KEY idx_game (game_id),
  CONSTRAINT fk_stat_schemas_game FOREIGN KEY (game_id) REFERENCES games(id)
    ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS stat_results (
  id                   BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  game_id              INT UNSIGNED NOT NULL,
  schema_id            INT UNSIGNED NOT NULL,
  from_draw_system_id  INT UNSIGNED NOT NULL, -- okno kończy się na tym losowaniu
  met_draw_system_id   INT UNSIGNED NOT NULL, -- „spełniono przy”
  hot_a TEXT NULL, cold_a TEXT NULL,
  hot_b TEXT NULL, cold_b TEXT NULL,
  created_at DATETIME NULL,
  PRIMARY KEY (id),
  KEY idx_schema_from (schema_id, from_draw_system_id),
  CONSTRAINT fk_stat_results_schema FOREIGN KEY (schema_id) REFERENCES stat_schemas(id)
    ON UPDATE CASCADE ON DELETE CASCADE,
  CONSTRAINT fk_stat_results_game FOREIGN KEY (game_id) REFERENCES games(id)
    ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- === STRATEGIE ===

CREATE TABLE IF NOT EXISTS strategies (
  id                   BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  game_id              INT UNSIGNED NOT NULL,
  schema_id            INT UNSIGNED NULL,
  stat_result_id       BIGINT UNSIGNED NOT NULL,
  stype                VARCHAR(20) NOT NULL DEFAULT 'SIMPLE',
  from_draw_system_id  INT UNSIGNED NOT NULL,
  next_draw_system_id  INT UNSIGNED NOT NULL,
  -- rekomendacje
  recommend_a_json     JSON NULL,
  recommend_b_json     JSON NULL,
  -- metryki A
  hot_count_a  INT NULL, cold_count_a INT NULL,
  hot_even_a   INT NULL, hot_odd_a  INT NULL,
  cold_even_a  INT NULL, cold_odd_a INT NULL,
  hits_hot_a   TEXT NULL, hits_cold_a TEXT NULL,
  -- metryki B
  hot_count_b  INT NULL, cold_count_b INT NULL,
  hot_even_b   INT NULL, hot_odd_b  INT NULL,
  cold_even_b  INT NULL, cold_odd_b INT NULL,
  hits_hot_b   TEXT NULL, hits_cold_b TEXT NULL,
  created_at   DATETIME NULL,
  PRIMARY KEY (id),
  KEY idx_game_next (game_id, next_draw_system_id),
  KEY idx_stat (stat_result_id),
  CONSTRAINT fk_strategies_stat FOREIGN KEY (stat_result_id) REFERENCES stat_results(id)
    ON UPDATE CASCADE ON DELETE CASCADE,
  CONSTRAINT fk_strategies_game FOREIGN KEY (game_id) REFERENCES games(id)
    ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- === ZAKŁADY ===

CREATE TABLE IF NOT EXISTS bet_batches (
  id                       BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  game_id                  INT UNSIGNED NOT NULL,
  stype                    VARCHAR(20) NOT NULL DEFAULT 'SIMPLE',
  schema_id                INT UNSIGNED NULL,
  strategy_id_from         BIGINT UNSIGNED NULL,
  strategy_id_to           BIGINT UNSIGNED NULL,
  last_n                   INT NULL,
  per_strategy             INT NOT NULL DEFAULT 1,
  include_random_baseline  TINYINT(1) NOT NULL DEFAULT 0,
  status                   ENUM('running','done','error') NOT NULL DEFAULT 'running',
  total_strategies         INT NULL,
  processed_strategies     INT NOT NULL DEFAULT 0,
  total_tickets            INT NULL,
  processed_tickets        INT NOT NULL DEFAULT 0,
  error_msg                TEXT NULL,
  created_at               DATETIME NULL,
  started_at               DATETIME NULL,
  finished_at              DATETIME NULL,
  PRIMARY KEY (id),
  KEY idx_game_status (game_id, status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS bet_tickets (
  id                   BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  batch_id             BIGINT UNSIGNED NOT NULL,
  game_id              INT UNSIGNED NOT NULL,
  strategy_id          BIGINT UNSIGNED NOT NULL,
  is_baseline          TINYINT(1) NOT NULL DEFAULT 0,
  k_a INT NULL, k_b INT NULL,
  hot_count_a INT NULL, cold_count_a INT NULL,
  hot_count_b INT NULL, cold_count_b INT NULL,
  numbers_a TEXT NULL, numbers_b TEXT NULL,
  next_draw_system_id INT UNSIGNED NOT NULL, -- „check”
  created_at DATETIME NULL,
  PRIMARY KEY (id),
  KEY idx_batch (batch_id),
  KEY idx_strategy (strategy_id),
  KEY idx_next (next_draw_system_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS bet_results (
  id                          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  ticket_id                   BIGINT UNSIGNED NOT NULL,
  batch_id                    BIGINT UNSIGNED NOT NULL,
  game_id                     INT UNSIGNED NOT NULL,
  strategy_id                 BIGINT UNSIGNED NOT NULL,
  is_baseline                 TINYINT(1) NOT NULL DEFAULT 0,
  next_draw_system_id         INT UNSIGNED NOT NULL,
  evaluation_draw_system_id   INT UNSIGNED NOT NULL, -- „check + 1”
  hits_a INT NULL, hits_b INT NULL,
  k_a INT NULL,   k_b INT NULL,
  win_amount DECIMAL(12,2) NULL,
  win_factor DECIMAL(8,4) NULL,
  win_currency VARCHAR(8) NULL,
  prize_label VARCHAR(50) NULL,
  is_winner TINYINT(1) NOT NULL DEFAULT 0,
  created_at DATETIME NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uq_ticket_eval (ticket_id, evaluation_draw_system_id),
  KEY idx_eval (evaluation_draw_system_id),
  KEY idx_game (game_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
