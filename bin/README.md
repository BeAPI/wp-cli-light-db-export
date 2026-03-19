# `truncate-light-db-tables.sh`

Bash helper that **truncates** the same logical tables that `wp light_db export` treats as **structure-only** (no row data in the dump). It uses the **`mysql` client only**—no PHP, no WP-CLI.

The filter list and matching rules are kept aligned with `src/wp-cli-light-db-export.php` (`extract_no_data_tables`, same idea as PHP `strpos` on the full table name, including any table prefix).

## What the script does

1. **Connects** to MySQL (`mysql` over TCP to `MYSQL_HOST` / `MYSQL_PORT` with the given user and password).
2. **Lists** every **base table** in the target schema via `information_schema.TABLES` (`TABLE_TYPE = 'BASE TABLE'`). This matches the scope you get from WP-CLI when exporting “all tables” for a single database.
3. **Selects** tables whose name **contains** at least one **filter substring** (default list mirrors `$tables_to_filter` in the PHP command; optional extra substrings via `TABLES_TO_FILTER`).
4. **Runs** `TRUNCATE TABLE` on each selected table, wrapped in:
   - `SET FOREIGN_KEY_CHECKS = 0` … `SET FOREIGN_KEY_CHECKS = 1`  
   so InnoDB foreign keys are less likely to block truncates (same session only).
5. **Identifiers** are quoted with backticks; embedded backticks in names are doubled.

If no table matches, the script exits successfully and prints that nothing was truncated.

## Requirements

- `mysql` client in `PATH`
- Bash (script avoids Bash 4–only features for compatibility with older macOS defaults)
- Network access to the server if `MYSQL_HOST` is not local

## Usage

```bash
chmod +x bin/truncate-light-db-tables.sh
./bin/truncate-light-db-tables.sh <database> <user> <password>
```

Dry-run (print `TRUNCATE` SQL only, no writes):

```bash
./bin/truncate-light-db-tables.sh --dry-run <database> <user> <password>
# or: -n
```

Help:

```bash
./bin/truncate-light-db-tables.sh --help
```

### Environment variables

| Variable | Default | Purpose |
|----------|---------|---------|
| `MYSQL_HOST` | `127.0.0.1` | Server host |
| `MYSQL_PORT` | `3306` | Server port |
| `TABLES_TO_FILTER` | _(empty)_ | Comma-separated **extra** substrings (like WP-CLI `--tables-to-filter`; **adds** to the built-in list, does not replace it) |
| `DRY_RUN` | _(unset)_ | Set to `1` for the same behavior as `--dry-run` (CLI flag preferred) |

Examples:

```bash
./bin/truncate-light-db-tables.sh --dry-run mydb myuser 'secret'
```

```bash
DRY_RUN=1 ./bin/truncate-light-db-tables.sh mydb myuser 'secret'
```

```bash
TABLES_TO_FILTER=postmeta,posts MYSQL_HOST=db.example.com ./bin/truncate-light-db-tables.sh mydb myuser 'secret'
```

## Relationship to `wp light_db export`

| `wp light_db export` | This script |
|------------------------|------------|
| Writes a dump: filtered tables with **no data**, others **with data** | **Deletes all rows** in filtered tables only (structure remains) |
| Uses WP-CLI / `mysqldump` | Uses **`mysql`** interactive client with `TRUNCATE` |

Use this when you want to **shrink** or **reset** log/telemetry-style tables on a server while keeping schema, in line with what you would omit from a “light” export.

## Security note

Passing the password as the third argument forwards it to `mysql -p…`, which can expose the password in process listings. For production, prefer MySQL option files (`~/.my.cnf` with restricted permissions), credential helpers, or other mechanisms your environment provides.

## Maintaining the filter list

The **default** substring list is duplicated in this script for standalone use. When you change `$tables_to_filter` in `src/wp-cli-light-db-export.php`, update the `DEFAULT_FILTERS` array in `truncate-light-db-tables.sh` so behavior stays consistent.
