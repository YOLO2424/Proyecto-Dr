<?php

namespace App\Services;

use App\Core\Database;
use PDO;

/**
 * Tokens opacos para pacientes y pre-registros.
 * El QR contiene unicamente el token: nunca datos clinicos.
 */
final class QrService
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::connection();
    }

    public function generateToken(int $bytes = 24): string
    {
        return bin2hex(random_bytes($bytes));
    }

    /**
     * Crea el token activo para un paciente. Devuelve el token opaco.
     */
    public function issuePatientToken(string $patientId, ?string $reason = null): string
    {
        $token = $this->generateToken();
        $stmt = $this->pdo->prepare(
            "INSERT INTO patient_qr_tokens (patient_id, token, reason) VALUES (:pid, :tok, :reason)"
        );
        $stmt->execute([':pid' => $patientId, ':tok' => $token, ':reason' => $reason]);
        return $token;
    }

    /**
     * Revoca el token activo actual (sin tocar el patient_id).
     */
    public function revokeActiveToken(string $patientId, ?string $reason = 'ROTACION'): void
    {
        $stmt = $this->pdo->prepare(
            "UPDATE patient_qr_tokens SET status = 'REVOKED', revoked_at = datetime('now','localtime'), reason = :reason
             WHERE patient_id = :pid AND status = 'ACTIVE'"
        );
        $stmt->execute([':pid' => $patientId, ':reason' => $reason]);
    }

    /**
     * Rota el QR: revoca el actual y emite uno nuevo.
     */
    public function rotateToken(string $patientId): string
    {
        $this->revokeActiveToken($patientId, 'ROTACION');
        return $this->issuePatientToken($patientId, 'ROTACION');
    }

    public function activeToken(string $patientId): ?array
    {
        $stmt = $this->pdo->prepare(
            "SELECT * FROM patient_qr_tokens WHERE patient_id = :pid AND status = 'ACTIVE' ORDER BY id DESC LIMIT 1"
        );
        $stmt->execute([':pid' => $patientId]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function history(string $patientId): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT * FROM patient_qr_tokens WHERE patient_id = :pid ORDER BY id DESC'
        );
        $stmt->execute([':pid' => $patientId]);
        return $stmt->fetchAll();
    }

    /**
     * Resuelve un token completo (PATIENT:xxxx...) a paciente.
     */
    public function resolvePatientToken(string $fullToken): ?array
    {
        $config = require dirname(__DIR__, 2) . '/config/config.php';
        $prefix = $config['qr']['prefix'];
        if (strncmp($fullToken, $prefix, strlen($prefix)) !== 0) {
            return null;
        }
        $token = substr($fullToken, strlen($prefix));

        $stmt = $this->pdo->prepare(
            "SELECT q.*, p.patient_id, p.first_name, p.last_name, p.status AS patient_status
             FROM patient_qr_tokens q
             JOIN patients p ON p.patient_id = q.patient_id
             WHERE q.token = :tok AND q.status = 'ACTIVE'
             LIMIT 1"
        );
        $stmt->execute([':tok' => $token]);
        return $stmt->fetch() ?: null;
    }

    /**
     * Crea el token para una solicitud de pre-registro.
     */
    public function issueRegistrationToken(): string
    {
        return $this->generateToken(16);
    }
}