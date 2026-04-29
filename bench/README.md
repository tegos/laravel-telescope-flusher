# Bench: `telescope:clear` vs `telescope:flush`

Reproduction harness for the disk + speed comparison referenced in
[the article](https://dev.to/tegos/PLACEHOLDER).

## What it does

- Creates a real Telescope schema (entries + tags + monitoring, with the same
  `ON DELETE CASCADE` foreign key that production Telescope uses).
- Seeds N rows of realistic ~2 KB JSON content per entry, ~3 tag rows per entry.
- Adds the secondary indexes + FK after the bulk insert (faster seed).
- Measures both the logical size (`information_schema.tables`) and the actual
  on-disk `.ibd` files inside the MySQL container.
- Runs each cleanup strategy and re-measures.

## Run it

```bash
docker compose up -d
docker compose exec app composer install

# Full race: clear → prune → flush, re-seeding 1M rows between runs.
# Heads up: the `clear` pass alone took ~150 minutes on MySQL 8.0 default config.
./bench/run_all.sh 1000000

# Flush-only pass (faster — useful if you just want to verify TRUNCATE+OPTIMIZE).
./bench/run_flush.sh 1000000
```

Reference output is in [`run.log`](run.log) (partial — clear pass + before-state)
and [`results-flush.txt`](results-flush.txt) (full flush pass).

## Headline numbers (1,000,000 entries / 3,000,000 tags, MySQL 8.0)

| Step                       | `telescope:clear`      | `telescope:flush` |
|----------------------------|------------------------|-------------------|
| Wall time                  | **9025 s** (≈150 min)  | **1.21 s**        |
| Logical size after         | 128 KB                 | 128 KB            |
| `.ibd` files on disk after | **3.1 GB** (unchanged) | **428 KB**        |

The `info_schema` row makes both look identical. Only `ls -lah` on the `.ibd`
files reveals what InnoDB actually freed.

## Files

| File               | Purpose                                                           |
|--------------------|-------------------------------------------------------------------|
| `bench.php`        | Standalone PDO script: setup, seed, finalize, measure, cleanup    |
| `run_all.sh`       | Full clear → prune → flush race                                   |
| `run_flush.sh`     | Single flush pass (seed + finalize + flush + measure)             |
| `run.log`          | Partial output from the full race (clear pass)                    |
| `results-flush.txt`| Output of the flush pass                                          |
