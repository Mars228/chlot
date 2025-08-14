#!/usr/bin/env bash
set -euo pipefail

DB_NAME="${1:-lotteryg}"
DB_USER="${DB_USER:-root}"
DB_PASS="${DB_PASS:-}"
DB_HOST="${DB_HOST:-127.0.0.1}"
DB_PORT="${DB_PORT:-3306}"

OUT_FILE="$(git rev-parse --show-toplevel)/docs/schema.sql"

echo "→ Export struktury DB '${DB_NAME}' do ${OUT_FILE}"

# --no-data         : tylko DDL (CREATE TABLE, INDEX itp.)
# --skip-comments   : bez timestampów i komentarzy
# --skip-dump-date  : powtarzalny diff
# --single-transaction : spójny snapshot
# --set-gtid-purged=OFF : dla MySQL 8
MYSQL_PWD="${DB_PASS}" mysqldump \
  -h "${DB_HOST}" -P "${DB_PORT}" -u "${DB_USER}" \
  --no-data --skip-comments --skip-dump-date \
  --single-transaction --set-gtid-purged=OFF \
  "${DB_NAME}" > "${OUT_FILE}"

echo "✓ Zapisano: ${OUT_FILE}"
