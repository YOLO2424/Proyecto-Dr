<?php

namespace App\Services;

use App\Core\Database;
use PDO;

/**
 * Pre-registro externo: el formulario externo solo crea una solicitud
 * PENDING. Jamas un expediente operativo hasta que el medico la apruebe.
 */
final class PreRegistrationService
{
    private PDO $pdo;

    private QrService $qr;

    public function __construct()
    {
        $this->pdo = Database::connection();
        $this->qr = new QrService();
    }

    /**
     * Genera una solicitud vacia de pre-registro (PENDING) con su token.
     */
    public function createRequest(): array
    {
        $token = $this->qr->issueRegistrationToken();
        $stmt = $this->pdo->prepare(
            "INSERT INTO registration_requests (token, data_json) VALUES (:tok, '{}')"
        );
        $stmt->execute([':tok' => $token]);
        return ['id' => (int) $this->pdo->lastInsertId(), 'token' => $token];
    }

    public function findByToken(string $token): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM registration_requests WHERE token = :tok');
        $stmt->execute([':tok' => $token]);
        $row = $stmt->fetch();
        if (!$row) {
            return null;
        }
        $row['data'] = json_decode($row['data_json'], true) ?: [];
        return $row;
    }

    /**
     * El usuario externo completa los datos. Devuelve true si quedo PENDING.
     */
    public function submit(string $token, array $data): array
    {
        $request = $this->findByToken($token);
        if (!$request) {
            throw new \RuntimeException('Solicitud de registro no encontrada.');
        }
        if ($request['status'] !== 'PENDING') {
            throw new \RuntimeException('Esta solicitud ya fue procesada por el médico.');
        }

        $clean = $this->sanitize($data);
        $stmt = $this->pdo->prepare('UPDATE registration_requests SET data_json = :data WHERE token = :tok');
        $stmt->execute([':data' => json_encode($clean, JSON_UNESCAPED_UNICODE), ':tok' => $token]);
        (new AuditService())->log('PRE-REGISTRO.RECIBIDO', 'registration_requests', $request['id'], []);

        return $clean;
    }

    /**
     * Aprobacion por el medico: crea el expediente y genera PAC + QR.
     */
    public function approve(int $id): array
    {
        $request = $this->pdo->prepare('SELECT * FROM registration_requests WHERE id = :id');
        $request->execute([':id' => $id]);
        $row = $request->fetch();
        if (!$row) {
            throw new \RuntimeException('Solicitud no encontrada');
        }
        if ($row['status'] !== 'PENDING') {
            throw new \RuntimeException('La solicitud ya fue procesada.');
        }

        $data = json_decode($row['data_json'], true) ?: [];
        $patientService = new PatientService();
        $result = $patientService->createFromRegistration($data, $row['token']);

        return $result;
    }

    public function reject(int $id, ?string $reason = null): void
    {
        $stmt = $this->pdo->prepare(
            "UPDATE registration_requests SET status = 'REJECTED', decided_at = datetime('now','localtime'), rejection_reason = :reason WHERE id = :id"
        );
        $stmt->execute([':id' => $id, ':reason' => $reason]);
        (new AuditService())->log('PRE-REGISTRO.RECHAZADO', 'registration_requests', $id, ['motivo' => $reason]);
    }

    public function pending(int $limit = 50): array
    {
        $stmt = $this->pdo->prepare("SELECT * FROM registration_requests WHERE status = 'PENDING' ORDER BY id DESC LIMIT :limit");
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        $rows = $stmt->fetchAll();
        foreach ($rows as &$r) {
            $r['data'] = json_decode($r['data_json'], true) ?: [];
        }
        return $rows;
    }

    public function processed(int $limit = 50): array
    {
        $stmt = $this->pdo->prepare("SELECT * FROM registration_requests WHERE status != 'PENDING' ORDER BY id DESC LIMIT :limit");
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        $rows = $stmt->fetchAll();
        foreach ($rows as &$r) {
            $r['data'] = json_decode($r['data_json'], true) ?: [];
        }
        return $rows;
    }

    public function countPending(): int
    {
        $stmt = $this->pdo->query("SELECT COUNT(*) AS c FROM registration_requests WHERE status = 'PENDING'");
        return (int) $stmt->fetch()['c'];
    }

    public static function countPendingStatic(): int
    {
        return (new self())->countPending();
    }

    private function sanitize(array $data): array
    {
        $allowed = ['first_name', 'last_name', 'national_id', 'birth_date', 'gender', 'phone', 'phone_secondary', 'email', 'address', 'city', 'emergency_name', 'emergency_relationship', 'emergency_phone'];
        $out = [];
        foreach ($allowed as $key) {
            if (isset($data[$key])) {
                $out[$key] = is_string($data[$key]) ? trim($data[$key]) : $data[$key];
            }
        }
        return $out;
    }
}