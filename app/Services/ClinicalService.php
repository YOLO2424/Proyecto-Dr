<?php

namespace App\Services;

use App\Core\Database;
use PDO;

final class ClinicalService
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::connection();
    }

    public function encounters(string $patientId): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT * FROM clinical_encounters WHERE patient_id = :pid ORDER BY encounter_date DESC, id DESC'
        );
        $stmt->execute([':pid' => $patientId]);
        $rows = $stmt->fetchAll();
        foreach ($rows as &$r) {
            $r['diagnoses'] = $this->encounterDiagnoses($r['id']);
            $r['treatments'] = $this->encounterTreatments($r['id']);
            $r['notes'] = $this->encounterNotes($r['id']);
            $r['documents'] = $this->encounterDocuments($r['id']);
        }
        return $rows;
    }

    public function encounter(int $id): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM clinical_encounters WHERE id = :id');
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch();
        if (!$row) {
            return null;
        }
        $row['patient'] = (new PatientService())->get($row['patient_id']);
        $row['diagnoses'] = $this->encounterDiagnoses($id);
        $row['treatments'] = $this->encounterTreatments($id);
        $row['notes'] = $this->encounterNotes($id);
        $row['documents'] = $this->encounterDocuments($id);
        return $row;
    }

    public function createEncounter(string $patientId, array $data): int
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO clinical_encounters (patient_id, encounter_date, reason, subjective, objective, plan)
             VALUES (:pid, :ed, :reason, :subj, :obj, :plan)'
        );
        $stmt->execute([
            ':pid' => $patientId,
            ':ed' => $data['encounter_date'],
            ':reason' => $data['reason'] ?? null,
            ':subj' => $data['subjective'] ?? null,
            ':obj' => $data['objective'] ?? null,
            ':plan' => $data['plan'] ?? null,
        ]);
        $id = (int) $this->pdo->lastInsertId();
        (new AuditService())->log('CONSULTA.CREADA', 'clinical_encounters', $id, ['patient_id' => $patientId, 'fecha' => $data['encounter_date']]);

        if (!empty($data['diagnosis_description'])) {
            $this->addDiagnosis($id, $patientId, [
                'code' => $data['diagnosis_code'] ?? null,
                'description' => $data['diagnosis_description'],
                'status' => 'ACTIVE',
                'notes' => null,
            ]);
        }
        if (!empty($data['treatment_description'])) {
            $this->addTreatment($id, $patientId, [
                'description' => $data['treatment_description'],
                'dose' => $data['treatment_dose'] ?? null,
                'frequency' => $data['treatment_frequency'] ?? null,
                'duration' => $data['treatment_duration'] ?? null,
                'notes' => null,
            ]);
        }
        if (!empty($data['note_content'])) {
            $this->addNote($id, $patientId, $data['note_type'] ?? 'OBSERVACION', $data['note_content']);
        }
        return $id;
    }

    public function updateEncounter(int $id, array $data): void
    {
        $existing = $this->encounter($id);
        if (!$existing) {
            throw new \RuntimeException('Consulta no encontrada');
        }
        $this->storeVersion('encounter', $id, $existing);
        $stmt = $this->pdo->prepare(
            "UPDATE clinical_encounters SET encounter_date = :ed, reason = :reason, subjective = :subj,
                objective = :obj, plan = :plan, updated_at = datetime('now','localtime') WHERE id = :id"
        );
        $stmt->execute([
            ':ed' => $data['encounter_date'],
            ':reason' => $data['reason'] ?? null,
            ':subj' => $data['subjective'] ?? null,
            ':obj' => $data['objective'] ?? null,
            ':plan' => $data['plan'] ?? null,
            ':id' => $id,
        ]);
        (new AuditService())->log('CONSULTA.ACTUALIZADA', 'clinical_encounters', $id, ['patient_id' => $existing['patient_id']]);
    }

    public function addDiagnosis(int $encounterId, string $patientId, array $data): void
    {
        $stmt = $this->pdo->prepare(
            "INSERT INTO diagnoses (encounter_id, patient_id, code, description, status, notes)
             VALUES (:eid, :pid, :code, :desc, :st, :notes)"
        );
        $stmt->execute([
            ':eid' => $encounterId,
            ':pid' => $patientId,
            ':code' => $data['code'] ?? null,
            ':desc' => $data['description'],
            ':st' => $data['status'] ?? 'ACTIVE',
            ':notes' => $data['notes'] ?? null,
        ]);
        (new AuditService())->log('DIAGNOSTICO.CREADO', 'diagnoses', (int) $this->pdo->lastInsertId(), ['patient_id' => $patientId]);
    }

    public function updateDiagnosis(int $encounterId, int $diagnosisId, array $data): void
    {
        $existing = $this->findDiagnosis($diagnosisId);
        if (!$existing) {
            throw new \RuntimeException('Diagnóstico no encontrado');
        }
        $this->storeVersion('diagnosis', $diagnosisId, $existing);
        $stmt = $this->pdo->prepare(
            "UPDATE diagnoses SET code = :code, description = :desc, status = :st, notes = :notes, updated_at = datetime('now','localtime')
             WHERE id = :id"
        );
        $stmt->execute([
            ':code' => $data['code'] ?? null,
            ':desc' => $data['description'],
            ':st' => $data['status'] ?? 'ACTIVE',
            ':notes' => $data['notes'] ?? null,
            ':id' => $diagnosisId,
        ]);
        (new AuditService())->log('DIAGNOSTICO.ACTUALIZADO', 'diagnoses', $diagnosisId, ['patient_id' => $existing['patient_id']]);
    }

    public function deleteDiagnosis(int $diagnosisId): void
    {
        $existing = $this->findDiagnosis($diagnosisId);
        if ($existing) {
            $this->storeVersion('diagnosis', $diagnosisId, $existing);
        }
        $this->pdo->prepare('DELETE FROM diagnoses WHERE id = :id')->execute([':id' => $diagnosisId]);
    }

    public function addTreatment(int $encounterId, string $patientId, array $data): void
    {
        $stmt = $this->pdo->prepare(
            "INSERT INTO treatments (encounter_id, patient_id, description, dose, frequency, duration, notes)
             VALUES (:eid, :pid, :desc, :dose, :freq, :dur, :notes)"
        );
        $stmt->execute([
            ':eid' => $encounterId,
            ':pid' => $patientId,
            ':desc' => $data['description'],
            ':dose' => $data['dose'] ?? null,
            ':freq' => $data['frequency'] ?? null,
            ':dur' => $data['duration'] ?? null,
            ':notes' => $data['notes'] ?? null,
        ]);
        (new AuditService())->log('TRATAMIENTO.CREADO', 'treatments', (int) $this->pdo->lastInsertId(), ['patient_id' => $patientId]);
    }

    public function updateTreatment(int $treatmentId, array $data): void
    {
        $existing = $this->findTreatment($treatmentId);
        if (!$existing) {
            throw new \RuntimeException('Tratamiento no encontrado');
        }
        $this->storeVersion('treatment', $treatmentId, $existing);
        $stmt = $this->pdo->prepare(
            "UPDATE treatments SET description = :desc, dose = :dose, frequency = :freq, duration = :dur, notes = :notes, updated_at = datetime('now','localtime')
             WHERE id = :id"
        );
        $stmt->execute([
            ':desc' => $data['description'],
            ':dose' => $data['dose'] ?? null,
            ':freq' => $data['frequency'] ?? null,
            ':dur' => $data['duration'] ?? null,
            ':notes' => $data['notes'] ?? null,
            ':id' => $treatmentId,
        ]);
    }

    public function deleteTreatment(int $treatmentId): void
    {
        $existing = $this->findTreatment($treatmentId);
        if ($existing) {
            $this->storeVersion('treatment', $treatmentId, $existing);
        }
        $this->pdo->prepare('DELETE FROM treatments WHERE id = :id')->execute([':id' => $treatmentId]);
    }

    public function addNote(int $encounterId, string $patientId, string $type, string $content): void
    {
        $stmt = $this->pdo->prepare(
            "INSERT INTO clinical_notes (encounter_id, patient_id, note_type, content) VALUES (:eid, :pid, :nt, :c)"
        );
        $stmt->execute([':eid' => $encounterId, ':pid' => $patientId, ':nt' => $type, ':c' => $content]);
        (new AuditService())->log('NOTA.CREADA', 'clinical_notes', (int) $this->pdo->lastInsertId(), ['patient_id' => $patientId]);
    }

    public function deleteNote(int $noteId): void
    {
        $this->pdo->prepare('DELETE FROM clinical_notes WHERE id = :id')->execute([':id' => $noteId]);
    }

    private function findDiagnosis(int $id): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM diagnoses WHERE id = :id');
        $stmt->execute([':id' => $id]);
        return $stmt->fetch() ?: null;
    }

    private function findTreatment(int $id): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM treatments WHERE id = :id');
        $stmt->execute([':id' => $id]);
        return $stmt->fetch() ?: null;
    }

    private function encounterDiagnoses(int $encounterId): array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM diagnoses WHERE encounter_id = :eid ORDER BY id');
        $stmt->execute([':eid' => $encounterId]);
        return $stmt->fetchAll();
    }

    private function encounterTreatments(int $encounterId): array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM treatments WHERE encounter_id = :eid ORDER BY id');
        $stmt->execute([':eid' => $encounterId]);
        return $stmt->fetchAll();
    }

    private function encounterNotes(int $encounterId): array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM clinical_notes WHERE encounter_id = :eid ORDER BY id');
        $stmt->execute([':eid' => $encounterId]);
        return $stmt->fetchAll();
    }

    private function encounterDocuments(int $encounterId): array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM documents WHERE encounter_id = :eid ORDER BY id DESC');
        $stmt->execute([':eid' => $encounterId]);
        return $stmt->fetchAll();
    }

    public function versions(string $entityType, int $entityId): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT * FROM clinical_record_versions WHERE entity_type = :et AND entity_id = :eid ORDER BY version DESC'
        );
        $stmt->execute([':et' => $entityType, ':eid' => $entityId]);
        $out = [];
        foreach ($stmt->fetchAll() as $row) {
            $row['data'] = json_decode($row['data_json'], true);
            $out[] = $row;
        }
        return $out;
    }

    /**
     * Versiones de una consulta: la propia y las de sus diagnosticos/tratamientos.
     */
    public function encounterVersions(int $encounterId): array
    {
        $diagnoses = $this->encounterDiagnoses($encounterId);
        $treatments = $this->encounterTreatments($encounterId);

        $clauses = ["entity_type = 'encounter' AND entity_id = ?"];
        $values = [$encounterId];
        foreach ($diagnoses as $d) {
            $clauses[] = "entity_type = 'diagnosis' AND entity_id = ?";
            $values[] = $d['id'];
        }
        foreach ($treatments as $t) {
            $clauses[] = "entity_type = 'treatment' AND entity_id = ?";
            $values[] = $t['id'];
        }

        $sql = 'SELECT * FROM clinical_record_versions WHERE ' . implode(' OR ', $clauses) . ' ORDER BY id DESC';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($values);
        $out = [];
        foreach ($stmt->fetchAll() as $row) {
            $row['data'] = json_decode($row['data_json'], true);
            $out[] = $row;
        }
        return $out;
    }

    public function todayCount(): int
    {
        $stmt = $this->pdo->prepare("SELECT COUNT(*) AS c FROM clinical_encounters WHERE date(encounter_date) = date('now','localtime')");
        return (int) $stmt->fetch()['c'];
    }

    private function storeVersion(string $entityType, int $entityId, array $data): void
    {
        $stmt = $this->pdo->prepare(
            'SELECT COALESCE(MAX(version), 0) + 1 AS v FROM clinical_record_versions WHERE entity_type = :et AND entity_id = :eid'
        );
        $stmt->execute([':et' => $entityType, ':eid' => $entityId]);
        $version = (int) $stmt->fetch()['v'];

        $stmt = $this->pdo->prepare(
            "INSERT INTO clinical_record_versions (entity_type, entity_id, version, data_json) VALUES (:et, :eid, :v, :data)"
        );
        $stmt->execute([
            ':et' => $entityType,
            ':eid' => $entityId,
            ':v' => $version,
            ':data' => json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
        ]);
    }
}