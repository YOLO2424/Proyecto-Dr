<?php

namespace App\Services;

use App\Core\Database;
use PDO;

final class DocumentService
{
    private PDO $pdo;

    private array $config;

    private string $docsDir;

    public function __construct()
    {
        $this->pdo = Database::connection();
        $this->config = require dirname(__DIR__, 2) . '/config/config.php';
        $this->docsDir = $this->config['paths']['documents'];
    }

    /**
     * Valida y almacena un archivo. Quarantene y hash.
     */
    public function store(string $patientId, ?int $encounterId, array $file, ?string $category, ?string $description): array
    {
        if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            throw new \RuntimeException('No se recibió un archivo válido.');
        }
        if ($file['size'] > $this->config['documents']['max_size']) {
            throw new \RuntimeException('El archivo supera el tamaño máximo permitido (15 MB).');
        }

        $mime = mime_content_type($file['tmp_name']) ?: $file['type'];
        if (!in_array($mime, $this->config['documents']['allowed'], true)) {
            $this->quarantine($file);
            throw new \RuntimeException('Tipo de archivo no permitido. Solo PDF, JPG o PNG.');
        }

        $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
        if (!in_array(strtolower($ext), $this->config['documents']['extensions'], true)) {
            $this->quarantine($file);
            throw new \RuntimeException('Extensión no permitida.');
        }

        $hash = hash_file('sha256', $file['tmp_name']);
        $stored = $patientId . '_' . bin2hex(random_bytes(8)) . '.' . strtolower($ext);
        $dest = $this->docsDir . '/' . $stored;

        if (!move_uploaded_file($file['tmp_name'], $dest)) {
            throw new \RuntimeException('No se pudo guardar el archivo.');
        }

        $stmt = $this->pdo->prepare(
            'INSERT INTO documents (patient_id, encounter_id, original_name, stored_name, mime_type, size, category, description, sha256)
             VALUES (:pid, :eid, :oname, :sname, :mime, :size, :cat, :desc, :sha)'
        );
        $stmt->execute([
            ':pid' => $patientId,
            ':eid' => $encounterId ?: null,
            ':oname' => $file['name'],
            ':sname' => $stored,
            ':mime' => $mime,
            ':size' => $file['size'],
            ':cat' => $category ?: null,
            ':desc' => $description ?: null,
            ':sha' => $hash,
        ]);

        (new AuditService())->log('DOCUMENTO.SUBIDO', 'documents', (int) $this->pdo->lastInsertId(), [
            'patient_id' => $patientId,
            'original' => $file['name'],
            'size' => $file['size'],
        ]);

        return ['id' => (int) $this->pdo->lastInsertId(), 'stored_name' => $stored];
    }

    public function forPatient(string $patientId): array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM documents WHERE patient_id = :pid ORDER BY id DESC');
        $stmt->execute([':pid' => $patientId]);
        return $stmt->fetchAll();
    }

    public function get(int $id): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM documents WHERE id = :id');
        $stmt->execute([':id' => $id]);
        return $stmt->fetch() ?: null;
    }

    public function path($doc): string
    {
        return $this->docsDir . '/' . $doc['stored_name'];
    }

    public function delete(int $id): void
    {
        $doc = $this->get($id);
        if (!$doc) {
            throw new \RuntimeException('Documento no encontrado');
        }
        $path = $this->path($doc);
        $stmt = $this->pdo->prepare('DELETE FROM documents WHERE id = :id');
        $stmt->execute([':id' => $id]);
        if (is_file($path)) {
            unlink($path);
        }
        (new AuditService())->log('DOCUMENTO.ELIMINADO', 'documents', $id, ['patient_id' => $doc['patient_id']]);
    }

    private function quarantine(array $file): void
    {
        $dir = $this->config['paths']['quarantine'];
        if (!is_dir($dir)) {
            mkdir($dir, 0775, true);
        }
        $dest = $dir . '/' . basename($file['name']) . '_' . bin2hex(random_bytes(4));
        if (is_uploaded_file($file['tmp_name'])) {
            move_uploaded_file($file['tmp_name'], $dest);
        } else {
            copy($file['tmp_name'], $dest);
        }
        (new AuditService())->log('DOCUMENTO.CUARENTENA', 'documents', null, ['original' => $file['name'] ?? '']);
    }
}