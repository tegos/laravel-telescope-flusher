<?php

declare(strict_types=1);

/**
 * Standalone benchmark for telescope cleanup strategies.
 *
 * Run inside the package's docker app container:
 *   docker compose exec -T app php /app/bench/bench.php <cmd>
 *
 * Subcommands:
 *   setup     create telescope tables
 *   seed N    seed N entries (+ ~3*N tags + monitoring) with realistic content
 *   measure   print row counts + InnoDB allocated size for telescope tables
 *   clear     simulate `telescope:clear`: chunked DELETE 1000 on entries+monitoring
 *   prune     simulate `telescope:prune --hours=0`: chunked DELETE WHERE created_at < NOW() on entries
 *   flush     simulate `telescope:flush`: TRUNCATE all + OPTIMIZE TABLE telescope_entries
 *   drop      drop telescope tables
 */

const DSN  = 'mysql:host=mysql;port=3306;dbname=telescope_test;charset=utf8mb4';
const USER = 'telescope';
const PASS = 'secret';

function pdo(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        $pdo = new PDO(DSN, USER, PASS, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_EMULATE_PREPARES => true,
            PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => true,
        ]);
    }
    return $pdo;
}

function setup(): void {
    // Minimal schema during seed — secondary indexes + FK added in finalize() for fast bulk insert.
    $sql = [
        "DROP TABLE IF EXISTS telescope_entries_tags",
        "DROP TABLE IF EXISTS telescope_monitoring",
        "DROP TABLE IF EXISTS telescope_entries",
        "CREATE TABLE telescope_entries (
            sequence BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            uuid CHAR(36) NOT NULL,
            batch_id CHAR(36) NOT NULL,
            family_hash VARCHAR(255) NULL,
            should_display_on_index TINYINT(1) NOT NULL DEFAULT 1,
            type VARCHAR(20) NOT NULL,
            content LONGTEXT NOT NULL,
            created_at DATETIME NULL,
            PRIMARY KEY (sequence),
            UNIQUE KEY uuid (uuid)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
        "CREATE TABLE telescope_entries_tags (
            entry_uuid CHAR(36) NOT NULL,
            tag VARCHAR(255) NOT NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
        "CREATE TABLE telescope_monitoring (
            tag VARCHAR(255) NOT NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
    ];
    foreach ($sql as $q) pdo()->exec($q);
    echo "tables created\n";
}

function finalize(): void {
    $pdo = pdo();
    $tStart = microtime(true);
    $pdo->exec("ALTER TABLE telescope_entries ADD KEY family_hash (family_hash), ADD KEY created_at (created_at)");
    $pdo->exec("ALTER TABLE telescope_entries_tags ADD KEY entry_uuid_tag (entry_uuid, tag), ADD KEY tag (tag)");
    $pdo->exec("ALTER TABLE telescope_entries_tags ADD CONSTRAINT fk_entry_uuid FOREIGN KEY (entry_uuid) REFERENCES telescope_entries (uuid) ON DELETE CASCADE");
    echo sprintf("indexes + FK added in %.1fs\n", microtime(true) - $tStart);
}

function dropAll(): void {
    pdo()->exec("DROP TABLE IF EXISTS telescope_entries_tags");
    pdo()->exec("DROP TABLE IF EXISTS telescope_monitoring");
    pdo()->exec("DROP TABLE IF EXISTS telescope_entries");
    echo "tables dropped\n";
}

/** Build a realistic ~2KB JSON content blob mimicking a telescope query/job entry. */
function fakeContent(int $i): string {
    $sql = "SELECT `products`.* FROM `products`
        INNER JOIN `product_supplier` ON `product_supplier`.`product_id` = `products`.`id`
        WHERE `product_supplier`.`supplier_id` = ? AND `products`.`is_active` = 1
        AND `products`.`updated_at` > ? ORDER BY `products`.`id` ASC LIMIT 1000 OFFSET " . ($i % 50000);
    $bindings = [random_int(1, 25), date('Y-m-d H:i:s', time() - random_int(0, 86400 * 30))];
    $payload = [
        'connection' => 'mysql',
        'bindings'   => $bindings,
        'sql'        => $sql,
        'time'       => round(random_int(5, 500) / 10, 2),
        'slow'       => false,
        'file'       => '/var/www/app/Jobs/PriceImport/ProcessPriceImportChunkJob.php',
        'line'       => random_int(40, 220),
        'hash'       => bin2hex(random_bytes(16)),
        'tags'       => ['supplier:' . random_int(1, 25), 'job:price-import', 'chunk:' . $i],
        'extra'      => str_repeat('x', 1400),
    ];
    return json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
}

function uuidv4(): string {
    $d = random_bytes(16);
    $d[6] = chr((ord($d[6]) & 0x0f) | 0x40);
    $d[8] = chr((ord($d[8]) & 0x3f) | 0x80);
    return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($d), 4));
}

function seed(int $n): void {
    $pdo = pdo();
    $pdo->exec("SET autocommit=0");
    $pdo->exec("SET unique_checks=0");
    $pdo->exec("SET foreign_key_checks=0");

    $batchId = uuidv4();
    $chunk = 500;
    $tStart = microtime(true);

    $entryStmt = $pdo->prepare(
        "INSERT INTO telescope_entries (uuid, batch_id, family_hash, should_display_on_index, type, content, created_at) VALUES "
        . implode(',', array_fill(0, $chunk, '(?,?,?,1,?,?,?)'))
    );

    $tagPlaceholders = implode(',', array_fill(0, $chunk * 3, '(?,?)'));
    $tagStmt = $pdo->prepare("INSERT INTO telescope_entries_tags (entry_uuid, tag) VALUES " . $tagPlaceholders);

    $now = time();
    for ($i = 0; $i < $n; $i += $chunk) {
        $params = [];
        $tagParams = [];
        for ($j = 0; $j < $chunk; $j++) {
            $idx = $i + $j;
            $uuid = uuidv4();
            $createdAt = date('Y-m-d H:i:s', $now - random_int(0, 86400 * 14));
            $params[] = $uuid;
            $params[] = $batchId;
            $params[] = substr(hash('sha1', (string) $idx), 0, 32);
            $params[] = 'query';
            $params[] = fakeContent($idx);
            $params[] = $createdAt;

            $tagParams[] = $uuid; $tagParams[] = 'supplier:' . ($idx % 25 + 1);
            $tagParams[] = $uuid; $tagParams[] = 'job:price-import';
            $tagParams[] = $uuid; $tagParams[] = 'chunk:' . ($idx % 1000);
        }
        $entryStmt->execute($params);
        $tagStmt->execute($tagParams);

        if ((($i + $chunk) % 50000) === 0) {
            $pdo->commit();
            $pdo->beginTransaction();
            $elapsed = microtime(true) - $tStart;
            $rate = ($i + $chunk) / max($elapsed, 0.001);
            echo sprintf("seeded %d / %d (%.0f rows/s, %.1fs)\n", $i + $chunk, $n, $rate, $elapsed);
        }
    }
    $pdo->commit();

    // small monitoring set
    $pdo->beginTransaction();
    $monStmt = $pdo->prepare("INSERT INTO telescope_monitoring (tag) VALUES (?)");
    for ($k = 0; $k < 50; $k++) $monStmt->execute(['watch:' . $k]);
    $pdo->commit();

    $total = microtime(true) - $tStart;
    echo sprintf("seed done in %.1fs\n", $total);
}

function measure(): void {
    $pdo = pdo();
    foreach (['telescope_entries','telescope_entries_tags','telescope_monitoring'] as $t) {
        $stmt = $pdo->query("ANALYZE TABLE `$t`");
        if ($stmt) { $stmt->fetchAll(); $stmt->closeCursor(); }
    }
    $totalBytes = 0; $totalRows = 0;
    foreach (['telescope_entries','telescope_entries_tags','telescope_monitoring'] as $t) {
        $count = (int) $pdo->query("SELECT COUNT(*) FROM `$t`")->fetchColumn();
        $info  = $pdo->query("SELECT data_length+index_length AS bytes, data_free FROM information_schema.tables WHERE table_schema='telescope_test' AND table_name='$t'")->fetch(PDO::FETCH_ASSOC);
        $bytes = (int) ($info['bytes'] ?? 0);
        $free  = (int) ($info['data_free'] ?? 0);
        $totalBytes += $bytes; $totalRows += $count;
        echo sprintf("%-26s rows=%-10d size=%-10s free=%s\n", $t, $count, fmtBytes($bytes), fmtBytes($free));
    }
    echo sprintf("%-26s rows=%-10d size=%s\n", 'TOTAL', $totalRows, fmtBytes($totalBytes));
}

function fmtBytes(int $b): string {
    if ($b > 1<<30) return sprintf('%.2f GB', $b / (1<<30));
    if ($b > 1<<20) return sprintf('%.2f MB', $b / (1<<20));
    if ($b > 1<<10) return sprintf('%.2f KB', $b / (1<<10));
    return $b . ' B';
}

function benchClear(): void {
    $pdo = pdo();
    $tStart = microtime(true);
    foreach (['telescope_entries','telescope_monitoring'] as $t) {
        do {
            $stmt = $pdo->prepare("DELETE FROM `$t` LIMIT 1000");
            $stmt->execute();
            $deleted = $stmt->rowCount();
        } while ($deleted > 0);
    }
    echo sprintf("telescope:clear simulation finished in %.2fs\n", microtime(true) - $tStart);
}

function benchPrune(): void {
    $pdo = pdo();
    $tStart = microtime(true);
    $cutoff = date('Y-m-d H:i:s', time() + 3600);
    $stmt = $pdo->prepare("DELETE FROM telescope_entries WHERE created_at < ? LIMIT 1000");
    do {
        $stmt->execute([$cutoff]);
        $deleted = $stmt->rowCount();
    } while ($deleted > 0);
    echo sprintf("telescope:prune --hours=0 simulation finished in %.2fs\n", microtime(true) - $tStart);
}

function benchFlush(): void {
    $pdo = pdo();
    $tStart = microtime(true);
    $pdo->exec("SET FOREIGN_KEY_CHECKS=0");
    $pdo->exec("TRUNCATE TABLE telescope_entries");
    $pdo->exec("TRUNCATE TABLE telescope_entries_tags");
    $pdo->exec("TRUNCATE TABLE telescope_monitoring");
    $pdo->exec("SET FOREIGN_KEY_CHECKS=1");
    $pdo->exec("OPTIMIZE TABLE telescope_entries");
    echo sprintf("telescope:flush simulation finished in %.2fs\n", microtime(true) - $tStart);
}

$cmd = $argv[1] ?? 'help';
switch ($cmd) {
    case 'setup':    setup(); break;
    case 'seed':     seed((int) ($argv[2] ?? 100000)); break;
    case 'finalize': finalize(); break;
    case 'measure': measure(); break;
    case 'clear':   benchClear(); break;
    case 'prune':   benchPrune(); break;
    case 'flush':   benchFlush(); break;
    case 'drop':    dropAll(); break;
    default:
        fwrite(STDERR, "usage: bench.php setup|seed N|measure|clear|prune|flush|drop\n");
        exit(1);
}
