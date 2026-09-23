<?php

namespace App\Services;

use App\Core\Database;
use PDO;

final class PatientService
{
    private PDO $pdo;

    private IdentityService $identity;

    private QrService $qr;

    public function __construct()
    {
        $this->pdo = Database::connection();
        $this->identity = new IdentityService();
        $this->qr = new QrService();
    }

    /**
     * Flujo de creacion: transaccion que reserva identidad en el registro
     * permanente, crea el expediente y emite el primer QR.
     */
    public function create(array $patientData, array $contact, array $emergency, array $insurance): array
    {
        $this->pdo->beginTransaction();
        try {
            $patientId = $this->identity->nextIdInTransaction();

            $now = date('Y-m-d H:i:s');
            $stmt = $this->pdo->prepare(
                'INSERT INTO patients
                    (patient_id, first_name, last_name, national_id, birth_date, gender, marital_status, nationality,
                     place_of_birth, blood_group, previous_surgeries, observations, created_at, updated_at)
                 VALUES
                    (:pid, :fn, :ln, :nid, :bd, :g, :ms, :nat, :pob, :bg, :surg, :obs, :created, :updated)'
            );
            $stmt->execute([
                ':pid' => $patientId,
                ':fn' => trim($patientData['first_name'] ?? ''),
                ':ln' => trim($patientData['last_name'] ?? ''),
                ':nid' => $patientData['national_id'] ?: null,
                ':bd' => $patientData['birth_date'] ?: null,
                ':g' => $patientData['gender'] ?: null,
                ':ms' => $patientData['marital_status'] ?: null,
                ':nat' => $patientData['nationality'] ?: null,
                ':pob' => $patientData['place_of_birth'] ?: null,
                ':bg' => $patientData['blood_group'] ?: null,
                ':surg' => $patientData['previous_surgeries'] ?: null,
                ':obs' => $patientData['observations'] ?: null,
                ':created' => $now,
                ':updated' => $now,
            ]);

            $this->saveContact($patientId, $contact);
            if (!empty(array_filter($emergency))) {
                $this->saveEmergencyContact($patientId, $emergency);
            }
            $this->saveInsurance($patientId, $insurance);

            $token = $this->qr->issuePatientToken($patientId, 'INICIAL');

            $this->pdo->commit();
        } catch (\Throwable $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw $e;
        }

        (new AuditService())->log('PACIENTE.CREADO', 'patients', null, [
            'patient_id' => $patientId,
            'qr_emitido' => true,
        ]);

        return ['patient_id' => $patientId, 'qr_token' => $token];
    }

    /**
     * Crea un paciente a partir de un pre-registro aprobado.
     */
    public function createFromRegistration(array $requestData, string $registrationToken): array
    {
        $defaults = [
            'first_name' => $requestData['first_name'] ?? '',
            'last_name' => $requestData['last_name'] ?? '',
            'national_id' => $requestData['national_id'] ?? null,
            'birth_date' => $requestData['birth_date'] ?? null,
            'gender' => $requestData['gender'] ?? null,
            'marital_status' => null,
            'nationality' => null,
            'place_of_birth' => null,
            'blood_group' => null,
            'previous_surgeries' => null,
            'observations' => null,
        ];
        $contact = [
            'phone_primary' => $requestData['phone'] ?? null,
            'phone_secondary' => $requestData['phone_secondary'] ?? null,
            'email' => $requestData['email'] ?? null,
            'address' => $requestData['address'] ?? null,
            'city' => $requestData['city'] ?? null,
            'region' => null,
            'postal_code' => null,
        ];
        $emergency = [
            'full_name' => $requestData['emergency_name'] ?? null,
            'relationship' => $requestData['emergency_relationship'] ?? null,
            'phone' => $requestData['emergency_phone'] ?? null,
            'alt_phone' => null,
        ];
        $insurance = ['provider' => null, 'policy_number' => null, 'holder_name' => null, 'holder_relationship' => null, 'company' => null];

        $result = $this->create($defaults, $contact, $emergency, $insurance);

        $stmt = $this->pdo->prepare(
            "UPDATE registration_requests SET status = 'APPROVED', decided_at = datetime('now','localtime'), patient_id = :pid
             WHERE token = :tok"
        );
        $stmt->execute([':pid' => $result['patient_id'], ':tok' => $registrationToken]);

        return $result;
    }

    public function update(string $patientId, array $patientData, array $contact, array $emergency, array $insurance): void
    {
        if ($this->get($patientId) === null) {
            throw new \RuntimeException('Paciente no encontrado');
        }

        $stmt = $this->pdo->prepare(
            "UPDATE patients SET first_name = :fn, last_name = :ln, national_id = :nid, birth_date = :bd, gender = :g,
                marital_status = :ms, nationality = :nat, place_of_birth = :pob, blood_group = :bg,
                previous_surgeries = :surg, observations = :obs, updated_at = datetime('now','localtime')
             WHERE patient_id = :pid"
        );
        $stmt->execute([
            ':fn' => trim($patientData['first_name'] ?? ''),
            ':ln' => trim($patientData['last_name'] ?? ''),
            ':nid' => $patientData['national_id'] ?: null,
            ':bd' => $patientData['birth_date'] ?: null,
            ':g' => $patientData['gender'] ?: null,
            ':ms' => $patientData['marital_status'] ?: null,
            ':nat' => $patientData['nationality'] ?: null,
            ':pob' => $patientData['place_of_birth'] ?: null,
            ':bg' => $patientData['blood_group'] ?: null,
            ':surg' => $patientData['previous_surgeries'] ?: null,
            ':obs' => $patientData['observations'] ?: null,
            ':pid' => $patientId,
        ]);

        $this->saveContact($patientId, $contact);
        $this->saveEmergencyContact($patientId, $emergency);
        $this->saveInsurance($patientId, $insurance);

        (new AuditService())->log('PACIENTE.ACTUALIZADO', 'patients', null, ['patient_id' => $patientId]);
    }

    private function saveContact(string $patientId, array $data): void
    {
        $filled = array_filter($data, fn ($v) => $v !== null && $v !== '');
        if (empty($filled)) {
            return;
        }
        $stmt = $this->pdo->prepare(
            'INSERT INTO patient_contacts (patient_id, phone_primary, phone_secondary, email, address, city, region, postal_code)
             VALUES (:pid, :p1, :p2, :em, :addr, :city, :reg, :pc)
             ON CONFLICT(patient_id) DO UPDATE SET
                phone_primary = excluded.phone_primary, phone_secondary = excluded.phone_secondary,
                email = excluded.email, address = excluded.address, city = excluded.city,
                region = excluded.region, postal_code = excluded.postal_code'
        );
        $stmt->execute([
            ':pid' => $patientId,
            ':p1' => $data['phone_primary'] ?? null,
            ':p2' => $data['phone_secondary'] ?? null,
            ':em' => $data['email'] ?? null,
            ':addr' => $data['address'] ?? null,
            ':city' => $data['city'] ?? null,
            ':reg' => $data['region'] ?? null,
            ':pc' => $data['postal_code'] ?? null,
        ]);
    }

    private function saveEmergencyContact(string $patientId, array $data): void
    {
        $this->pdo->prepare('DELETE FROM patient_emergency_contacts WHERE patient_id = :pid')
            ->execute([':pid' => $patientId]);
        if (empty(array_filter($data))) {
            return;
        }
        $stmt = $this->pdo->prepare(
            'INSERT INTO patient_emergency_contacts (patient_id, full_name, relationship, phone, alt_phone)
             VALUES (:pid, :fn, :rel, :ph, :alt)'
        );
        $stmt->execute([
            ':pid' => $patientId,
            ':fn' => $data['full_name'] ?? null,
            ':rel' => $data['relationship'] ?? null,
            ':ph' => $data['phone'] ?? null,
            ':alt' => $data['alt_phone'] ?? null,
        ]);
    }

    private function saveInsurance(string $patientId, array $data): void
    {
        $filled = array_filter($data, fn ($v) => $v !== null && $v !== '');
        if (empty($filled)) {
            return;
        }
        $stmt = $this->pdo->prepare(
            'INSERT INTO patient_insurance (patient_id, provider, policy_number, holder_name, holder_relationship, company)
             VALUES (:pid, :pr, :pn, :hn, :hr, :co)
             ON CONFLICT(patient_id) DO UPDATE SET
                provider = excluded.provider, policy_number = excluded.policy_number, holder_name = excluded.holder_name,
                holder_relationship = excluded.holder_relationship, company = excluded.company'
        );
        $stmt->execute([
            ':pid' => $patientId,
            ':pr' => $data['provider'] ?? null,
            ':pn' => $data['policy_number'] ?? null,
            ':hn' => $data['holder_name'] ?? null,
            ':hr' => $data['holder_relationship'] ?? null,
            ':co' => $data['company'] ?? null,
        ]);
    }

    public function get(string $patientId): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM patients WHERE patient_id = :pid');
        $stmt->execute([':pid' => $patientId]);
        return $stmt->fetch() ?: null;
    }

    public function contacts(string $patientId): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM patient_contacts WHERE patient_id = :pid');
        $stmt->execute([':pid' => $patientId]);
        return $stmt->fetch() ?: null;
    }

    public function emergencyContacts(string $patientId): array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM patient_emergency_contacts WHERE patient_id = :pid ORDER BY id');
        $stmt->execute([':pid' => $patientId]);
        return $stmt->fetchAll();
    }

    public function insurance(string $patientId): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM patient_insurance WHERE patient_id = :pid');
        $stmt->execute([':pid' => $patientId]);
        return $stmt->fetch() ?: null;
    }

    public function allergies(string $patientId): array
    {
        return $this->listSimple('patient_allergies', $patientId);
    }

    public function conditions(string $patientId): array
    {
        return $this->listSimple('patient_conditions', $patientId);
    }

    public function medications(string $patientId): array
    {
        return $this->listSimple('patient_medications', $patientId);
    }

    private function listSimple(string $table, string $patientId): array
    {
        $stmt = $this->pdo->prepare("SELECT * FROM {$table} WHERE patient_id = :pid ORDER BY id DESC");
        $stmt->execute([':pid' => $patientId]);
        return $stmt->fetchAll();
    }

    public function addAllergy(string $patientId, array $data): void
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO patient_allergies (patient_id, allergen, reaction, severity, notes)
             VALUES (:pid, :alg, :rea, :sev, :notes)'
        );
        $stmt->execute([':pid' => $patientId, ':alg' => $data['allergen'], ':rea' => $data['reaction'] ?? null, ':sev' => $data['severity'] ?? null, ':notes' => $data['notes'] ?? null]);
        (new AuditService())->log('ALERGIA.AGREGADA', 'patient_allergies', null, ['patient_id' => $patientId]);
    }

    public function deleteAllergy(int $id): void
    {
        $this->pdo->prepare('DELETE FROM patient_allergies WHERE id = :id')->execute([':id' => $id]);
    }

    public function addCondition(string $patientId, array $data): void
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO patient_conditions (patient_id, condition_name, category, diagnosis_date, notes)
             VALUES (:pid, :cn, :cat, :dd, :notes)'
        );
        $stmt->execute([':pid' => $patientId, ':cn' => $data['condition_name'], ':cat' => $data['category'] ?? null, ':dd' => $data['diagnosis_date'] ?? null, ':notes' => $data['notes'] ?? null]);
    }

    public function deleteCondition(int $id): void
    {
        $this->pdo->prepare('DELETE FROM patient_conditions WHERE id = :id')->execute([':id' => $id]);
    }

    public function addMedication(string $patientId, array $data): void
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO patient_medications (patient_id, name, dose, frequency, started_at, status, notes)
             VALUES (:pid, :n, :d, :f, :sa, :st, :notes)'
        );
        $stmt->execute([':pid' => $patientId, ':n' => $data['name'], ':d' => $data['dose'] ?? null, ':f' => $data['frequency'] ?? null, ':sa' => $data['started_at'] ?? null, ':st' => $data['status'] ?? 'CURRENT', ':notes' => $data['notes'] ?? null]);
    }

    public function deleteMedication(int $id): void
    {
        $this->pdo->prepare('DELETE FROM patient_medications WHERE id = :id')->execute([':id' => $id]);
    }

    /**
     * Desactiva (INACTIVE): conserva todo, deja de mostrarse en listados normales.
     */
    public function deactivate(string $patientId): void
    {
        $stmt = $this->pdo->prepare(
            "UPDATE patients SET status = 'INACTIVE', updated_at = datetime('now','localtime') WHERE patient_id = :pid"
        );
        $stmt->execute([':pid' => $patientId]);
        (new AuditService())->log('PACIENTE.DESACTIVADO', 'patients', null, ['patient_id' => $patientId]);
    }

    public function reactivate(string $patientId): void
    {
        $this->identity->markActive($patientId);
        $stmt = $this->pdo->prepare(
            "UPDATE patients SET status = 'ACTIVE', deleted_at = NULL, updated_at = datetime('now','localtime') WHERE patient_id = :pid"
        );
        $stmt->execute([':pid' => $patientId]);
        (new AuditService())->log('PACIENTE.REACTIVADO', 'patients', null, ['patient_id' => $patientId]);
    }

    /**
     * Eliminacion administrativa (DELETED). La identidad queda reservada para siempre.
     */
    public function delete(string $patientId): void
    {
        $stmt = $this->pdo->prepare(
            "UPDATE patients SET status = 'DELETED', deleted_at = datetime('now','localtime'), updated_at = datetime('now','localtime')
             WHERE patient_id = :pid"
        );
        $stmt->execute([':pid' => $patientId]);
        $this->identity->markDeleted($patientId);
        (new AuditService())->log('PACIENTE.ELIMINADO', 'patients', null, ['patient_id' => $patientId]);
    }

    public function restore(string $patientId): void
    {
        $this->identity->markActive($patientId);
        $stmt = $this->pdo->prepare(
            "UPDATE patients SET status = 'ACTIVE', deleted_at = NULL, updated_at = datetime('now','localtime') WHERE patient_id = :pid"
        );
        $stmt->execute([':pid' => $patientId]);
        (new AuditService())->log('PACIENTE.RESTAURADO', 'patients', null, ['patient_id' => $patientId]);
    }

    /**
     * Busqueda por nombre, patient_id o documento. Sin parametros lista activos.
     */
    public function search(?string $q = null, string $filter = 'ACTIVE', int $limit = 50): array
    {
        $sql = 'SELECT p.*, (SELECT MAX(e.encounter_date) FROM clinical_encounters e WHERE e.patient_id = p.patient_id) AS last_encounter
                FROM patients p WHERE 1=1';
        $params = [];

        if ($filter === 'ACTIVE') {
            $sql .= " AND p.status IN ('ACTIVE','INACTIVE')";
        } elseif ($filter === 'DELETED') {
            $sql .= " AND p.status = 'DELETED'";
        } elseif ($filter === 'INACTIVE') {
            $sql .= " AND p.status = 'INACTIVE'";
        } elseif ($filter === 'ALL') {
            // todos
        } else {
            $sql .= " AND p.status IN ('ACTIVE','INACTIVE')";
        }

        if ($q !== null && $q !== '') {
            $sql .= ' AND (p.first_name LIKE :q1 OR p.last_name LIKE :q2 OR p.patient_id LIKE :q3)';
            $params[':q1'] = '%' . $q . '%';
            $params[':q2'] = '%' . $q . '%';
            $params[':q3'] = '%' . $q . '%';
        }

        $sql .= ' ORDER BY p.last_name COLLATE NOCASE, p.first_name COLLATE NOCASE LIMIT :limit';
        $stmt = $this->pdo->prepare($sql);
        foreach ($params as $k => $v) {
            $stmt->bindValue($k, $v);
        }
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function countByStatus(): array
    {
        $rows = $this->pdo->query("SELECT status, COUNT(*) AS total FROM patients GROUP BY status")->fetchAll();
        $out = ['ACTIVE' => 0, 'INACTIVE' => 0, 'DELETED' => 0];
        foreach ($rows as $r) {
            $out[$r['status']] = (int) $r['total'];
        }
        return $out;
    }

    public function recent(int $limit = 8): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT p.*, (SELECT MAX(e.encounter_date) FROM clinical_encounters e WHERE e.patient_id = p.patient_id) AS last_encounter
             FROM patients p WHERE p.status IN (\'ACTIVE\',\'INACTIVE\')
             ORDER BY p.created_at DESC LIMIT :limit'
        );
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function lastEncounter(string $patientId): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT * FROM clinical_encounters WHERE patient_id = :pid ORDER BY encounter_date DESC, id DESC LIMIT 1'
        );
        $stmt->execute([':pid' => $patientId]);
        return $stmt->fetch() ?: null;
    }

    public function nextEncounterDate(string $patientId): ?string
    {
        $row = $this->lastEncounter($patientId);
        return $row['encounter_date'] ?? null;
    }
}