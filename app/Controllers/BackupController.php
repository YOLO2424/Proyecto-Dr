<?php

namespace App\Controllers;

use App\Core\View;
use App\Core\Request;
use App\Core\Response;
use App\Services\BackupService;

final class BackupController
{
    public function index(Request $request): void
    {
        Response::send(View::render('backups/index', [
            'backups' => (new BackupService())->list(),
        ]));
    }

    public function create(Request $request): void
    {
        try {
            $name = (new BackupService())->create('manual');
        } catch (\Throwable $e) {
            Response::redirectWith('/backups', 'error', 'No se pudo crear el respaldo: ' . $e->getMessage());
        }
        Response::redirectWith('/backups', 'success', 'Respaldo creado: ' . $name);
    }

    public function download(Request $request, string $name): void
    {
        $service = new BackupService();
        $file = $service->path($name);
        if (!$file) {
            Response::redirectWith('/backups', 'error', 'Respaldo no encontrado.');
        }
        header('Content-Type: application/zip');
        header('Content-Disposition: attachment; filename="' . basename($name) . '"');
        header('Content-Length: ' . filesize($file));
        readfile($file);
        exit;
    }

    public function delete(Request $request, string $name): void
    {
        (new BackupService())->delete($name);
        Response::redirectWith('/backups', 'success', 'Respaldo eliminado.');
    }
}