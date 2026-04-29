#!/usr/bin/env bash
set -euo pipefail

N="${1:-1000000}"
RESULTS="/home/tegos/work/laravel-telescope-flusher/bench/results-flush.txt"
RUN="docker compose exec -T app php /app/bench/bench.php"
LSCMD="docker compose exec -T mysql ls -lah /var/lib/mysql/telescope_test/"

cd /home/tegos/work/laravel-telescope-flusher

{
    echo "==================== telescope:flush (TRUNCATE all + OPTIMIZE TABLE) ===================="
    echo "[reset]"
    $RUN drop
    $RUN setup
    echo "[seed N=${N}]"
    $RUN seed "$N"
    echo "[finalize: add indexes + FK]"
    $RUN finalize
    echo "[before — info_schema]"
    $RUN measure
    echo "[before — disk .ibd files]"
    $LSCMD
    echo "[run: flush]"
    $RUN flush
    echo "[after — info_schema]"
    $RUN measure
    echo "[after — disk .ibd files]"
    $LSCMD
    echo "DONE"
} 2>&1 | tee "$RESULTS"
