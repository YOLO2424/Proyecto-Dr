<?php

namespace App\Services;

use App\Core\Database;
use PDO;

/**
 * Gestiona la identidad permanente del paciente.
 * Invariante: un patient_id jamas se reutiliza, ni siquiera tras un borrado.
 */
final class IdentityService
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::connection();
    }

    /**
     * Reserva y devuelve el siguiente patient_id (PAC-XXXXXXXX) dentro
     * de una transaccion ya iniciada por el llamador. Requiere transaccion
     * abierta con BEGIN IMMEDIATE para serializar escritores.
     */
    public function nextIdInTransaction(): string
    {
        $config = require dirname(__DIR__, 2) . '/config/config.php';
        $prefix = $config['identity']['prefix'];
        $digits = (int) $config['identity']['digits'];

        $stmt = $this->pdo->query('SELECT COALESCE(MAX(sequence_id), 0) + 1 AS next FROM patient_identity_registry');
        $next = (int) $stmt->fetch()['next'];

        $id = $prefix . str_pad((string) $next, $digits, '0', STR_PAD_LEFT);

        $insert = $this->pdo->prepare(
            'INSERT INTO patient_identity_registry (patient_id) VALUES (:id)'
        );
        $insert->execute([':id' => $id]);

        return $id;
    }

    /**
     * Marca la identidad como DELETED. Nunca elimina la fila.
     */
    public function markDeleted(string $patientId): void
    {
        $stmt = $this->pdo->prepare(
            "UPDATE patient_identity_registry SET status = 'DELETED' WHERE patient_id = :id"
        );
        $stmt->execute([':id' => $patientId]);
    }

    /**
     * Marca la identidad como ACTIVE (al reactivar o restaurar).
     */
    public function markActive(string $patientId): void
    {
        $stmt = $this->pdo->prepare(
            "UPDATE patient_identity_registry SET status = 'ACTIVE' WHERE patient_id = :id AND status = 'DELETED'"
        );
        $stmt->execute([':id' => $patientId]);
    }

    public function status(string $patientId): ?string
    {
        $stmt = $this->pdo->prepare(
            'SELECT status FROM patient_identity_registry WHERE patient_id = :id'
        );
        $stmt->execute([':id' => $patientId]);
        $row = $stmt->fetch();
        return $row['status'] ?? null;
    }
}