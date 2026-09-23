<?php

namespace App\Services;

use App\Core\Database;
use PDO;

final class SettingsService
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::connection();
    }

    public function all(): array
    {
        $rows = $this->pdo->query('SELECT key, value FROM settings ORDER BY key')->fetchAll();
        $out = [];
        foreach ($rows as $row) {
            $out[$row['key']] = $row['value'];
        }
        return $out;
    }

    public function get(string $key, ?string $default = null): ?string
    {
        $stmt = $this->pdo->prepare('SELECT value FROM settings WHERE key = :k');
        $stmt->execute([':k' => $key]);
        $row = $stmt->fetch();
        return $row['value'] ?? $default;
    }

    public function set(string $key, string $value): void
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO settings (key, value) VALUES (:k, :v)
             ON CONFLICT(key) DO UPDATE SET value = excluded.value'
        );
        $stmt->execute([':k' => $key, ':v' => $value]);
    }
}