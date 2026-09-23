<?php

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Core\View;
use App\Services\DocumentService;

final class DocumentController
{
    public function store(Request $request, string $patientId): void
    {
        $file = $request->file('document');
        $encounterId = $request->input('encounter_id');
        $category = $request->input('category');
        $description = $request->input('description');

        if (!$file || $file['error'] !== UPLOAD_ERR_OK) {
            Response::redirectWith('/pacientes/' . $patientId . '?tab=documentos', 'error', 'Selecciona un archivo para subir.');
        }

        try {
            (new DocumentService())->store($patientId, $encounterId ? (int) $encounterId : null, $file, $category, $description);
        } catch (\RuntimeException $e) {
            Response::redirectWith('/pacientes/' . $patientId . '?tab=documentos', 'error', $e->getMessage());
        }
        Response::redirectWith('/pacientes/' . $patientId . '?tab=documentos', 'success', 'Documento subido.');
    }

    public function download(Request $request, string $patientId, string $docId): void
    {
        $service = new DocumentService();
        $doc = $service->get((int) $docId);
        if (!$doc || $doc['patient_id'] !== $patientId) {
            Response::redirectWith('/pacientes/' . $patientId . '?tab=documentos', 'error', 'Documento no encontrado.');
        }
        $path = $service->path($doc);
        if (!is_file($path)) {
            Response::redirectWith('/pacientes/' . $patientId . '?tab=documentos', 'error', 'El archivo físico no existe. Crea un backup / revisa storage/documents.');
        }
        header('Content-Description: File Transfer');
        header('Content-Type: ' . $doc['mime_type']);
        header('Content-Disposition: attachment; filename="' . basename($doc['original_name']) . '"');
        header('Content-Length: ' . filesize($path));
        readfile($path);
        exit;
    }

    public function delete(Request $request, string $patientId, string $docId): void
    {
        try {
            (new DocumentService())->delete((int) $docId);
        } catch (\RuntimeException $e) {
            Response::redirectWith('/pacientes/' . $patientId . '?tab=documentos', 'error', $e->getMessage());
        }
        Response::redirectWith('/pacientes/' . $patientId . '?tab=documentos', 'success', 'Documento eliminado.');
    }
}