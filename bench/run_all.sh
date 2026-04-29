#!/usr/bin/env bash
set -euo pipefail

N="${1:-1000000}"
RESULTS="/home/tegos/work/laravel-telescope-flusher/bench/results.txt"
RUN="docker compose exec -T app php /app/bench/bench.php"

cd /home/tegos/work/laravel-telescope-flusher

run_one() {
    local label="$1"
    local cmd="$2"
    {
        echo "==================== ${label} ===================="
        echo "[reset]"
        $RUN drop
        $RUN setup
        echo "[seed N=${N}]"
        $RUN seed "$N"
        echo "[finalize: add indexes + FK]"
        $RUN finalize
        echo "[before]"
        $RUN measure
        echo "[run: ${cmd}]"
        $RUN "$cmd"
        echo "[after]"
        $RUN measure
        echo
    } 2>&1 | tee -a "$RESULTS"
}

: > "$RESULTS"

run_one "telescope:clear (chunked DELETE 1000 on entries+monitoring)" clear
run_one "telescope:prune --hours=0 (chunked DELETE WHERE created_at on entries)" prune
run_one "telescope:flush (TRUNCATE all + OPTIMIZE TABLE)" flush

echo "DONE" | tee -a "/home/tegos/work/laravel-telescope-flusher/bench/results.txt"
