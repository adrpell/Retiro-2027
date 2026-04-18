<?php

class MigrationRunner {
    public static function runPending(PDO $pdo, string $migrationDir): void {
        self::ensureMigrationsTable($pdo);
        $applied = self::appliedVersions($pdo);
        $files = glob(rtrim($migrationDir, '/\\') . '/*.php') ?: [];
        sort($files, SORT_STRING);
        foreach ($files as $file) {
            $migration = require $file;
            if (!is_array($migration) || empty($migration['version']) || !is_callable($migration['up'] ?? null)) {
                continue;
            }
            $version = (string)$migration['version'];
            if (isset($applied[$version])) {
                continue;
            }

            $transactional = !empty($migration['transactional']);
            $startedTransaction = false;
            if ($transactional) {
                try {
                    $pdo->beginTransaction();
                    $startedTransaction = true;
                } catch (Throwable $e) {
                    $startedTransaction = false;
                }
            }

            try {
                $migration['up']($pdo, new self());
                $stmt = $pdo->prepare('INSERT INTO schema_migrations (version, description) VALUES (?, ?)');
                $stmt->execute([$version, (string)($migration['description'] ?? '')]);
                $applied[$version] = true;
                if ($startedTransaction && $pdo->inTransaction()) {
                    $pdo->commit();
                }
            } catch (Throwable $e) {
                if ($startedTransaction && $pdo->inTransaction()) {
                    $pdo->rollBack();
                }
                throw $e;
            }
        }
    }

    public static function ensureMigrationsTable(PDO $pdo): void {
        $pdo->exec("CREATE TABLE IF NOT EXISTS schema_migrations (
            version VARCHAR(120) PRIMARY KEY,
            description VARCHAR(255) NULL,
            applied_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    }

    public static function appliedVersions(PDO $pdo): array {
        $rows = $pdo->query('SELECT version FROM schema_migrations')->fetchAll(PDO::FETCH_COLUMN) ?: [];
        return array_fill_keys(array_map('strval', $rows), true);
    }

    public function tableExists(PDO $pdo, string $table): bool {
        $stmt = $pdo->prepare('SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = ?');
        $stmt->execute([$table]);
        return (int)$stmt->fetchColumn() > 0;
    }

    public function columnExists(PDO $pdo, string $table, string $column): bool {
        $stmt = $pdo->prepare('SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = ? AND column_name = ?');
        $stmt->execute([$table, $column]);
        return (int)$stmt->fetchColumn() > 0;
    }

    public function indexExists(PDO $pdo, string $table, string $index): bool {
        $stmt = $pdo->prepare('SELECT COUNT(*) FROM information_schema.statistics WHERE table_schema = DATABASE() AND table_name = ? AND index_name = ?');
        $stmt->execute([$table, $index]);
        return (int)$stmt->fetchColumn() > 0;
    }
}
