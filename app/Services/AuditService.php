<?php

namespace App\Services;

use App\Core\Database;
use PDO;

final class AuditService
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::connection();
    }

    public function log(string $action, ?string $entityType = null, ?int $entityId = null, mixed $details = null): void
    {
        $detailsJson = is_string($details) ? $details : json_encode($details, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'local';
        $stmt = $this->pdo->prepare(
            'INSERT INTO audit_log (actor, action, entity_type, entity_id, details, ip)
             VALUES (:actor, :action, :type, :eid, :details, :ip)'
        );
        $stmt->execute([
            ':actor' => 'SYSTEM',
            ':action' => $action,
            ':type' => $entityType,
            ':eid' => $entityId,
            ':details' => $detailsJson,
            ':ip' => $ip,
        ]);
    }

    public function recent(int $limit = 100): array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM audit_log ORDER BY id DESC LIMIT :limit');
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }
}