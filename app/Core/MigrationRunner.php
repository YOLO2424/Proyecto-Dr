<?php

namespace App\Core;

use PDO;
use PDOException;

final class MigrationRunner
{
    public static function run(bool $verbose = false): array
    {
        $config = require dirname(__DIR__, 2) . '/config/config.php';
        $dir = $config['paths']['migrations'];
        $pdo = Database::connection();

        $pdo->exec('CREATE TABLE IF NOT EXISTS schema_migrations (
            version TEXT PRIMARY KEY,
            applied_at TEXT NOT NULL DEFAULT (datetime(\'now\', \'localtime\'))
        );');

        $applied = $pdo->query('SELECT version FROM schema_migrations')->fetchAll(PDO::FETCH_COLUMN);

        $files = glob($dir . '/*.sql');
        sort($files);

        $result = [];
        foreach ($files as $file) {
            $version = basename($file);
            if (in_array($version, $applied, true)) {
                continue;
            }
            $sql = file_get_contents($file);
            try {
                $pdo->beginTransaction();
                $pdo->exec($sql);
                $stmt = $pdo->prepare('INSERT INTO schema_migrations (version) VALUES (?)');
                $stmt->execute([$version]);
                $pdo->commit();
                $result[] = $version;
                if ($verbose) {
                    echo "[OK] " . $version . PHP_EOL;
                }
            } catch (PDOException $e) {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }
                throw new \RuntimeException('Migración fallida ' . $version . ': ' . $e->getMessage());
            }
        }
        return $result;
    }
}