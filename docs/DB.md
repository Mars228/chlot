# DB – dump i restore

Ten folder zawiera **kanoniczny zrzut struktury** bazy oraz krótkie skrypty do automatycznego eksportu DDL (bez danych).

## Pliki

- `schema.sql` – **AUTO-generowany** zrzut struktury (DDL only). Commitujemy po każdej zmianie tabel.
- `schema_DESIGN.sql` – **ręcznie pielęgnowany wzorzec** struktury (kolumny, indeksy, FK). Służy jako referencja.
- (opcjonalnie) `seed.sql` – ziarna/test-dane (jeśli kiedyś dodamy).

## Wymagania

- MySQL/MariaDB client (`mysqldump`, `mysql`)
- Uprawnienia do odczytu struktury bazy
- Zmienna/parametry z poświadczeniami (nie commitować haseł)

## Eksport STRUKTURY (DDL)

> Eksport generuje `docs/schema.sql` – **tylko definicje** (bez INSERT-ów).  
> Używamy opcji, które dają powtarzalny diff: `--skip-comments`, `--skip-dump-date`.

### Linux / macOS / WSL

```bash
# jednorazowo nadaj prawa
chmod +x scripts/export_schema.sh

# przykład użycia
DB_USER=lotto DB_PASS='***' DB_HOST=127.0.0.1 DB_PORT=3306 \
  scripts/export_schema.sh lotteryg