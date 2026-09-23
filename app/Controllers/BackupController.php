<?php

namespace App\Controllers;

use App\Core\View;
use App\Core\Request;
use App\Core\Response;
use App\Services\BackupService;
use App\Services\SupportAuth;
use App\Services\AuditService;

final class BackupController
{
    /**
     * Los backups solo los puede operar personal autorizado (Soporte/TI).
     */
    private function requireSupport(): void
    {
        if (!SupportAuth::isGranted()) {
            Response::redirect('/soporte');
        }
    }

    public function index(Request $request): void
    {
        $this->requireSupport();
        Response::send(View::render('backups/index', [
            'backups' => (new BackupService())->list(),
        ]));
    }

    public function create(Request $request): void
    {
        $this->requireSupport();
        try {
            $name = (new BackupService())->create('manual');
        } catch (\Throwable $e) {
            Response::redirectWith('/backups', 'error', 'No se pudo crear el respaldo: ' . $e->getMessage());
        }
        Response::redirectWith('/backups', 'success', 'Respaldo creado: ' . $name);
    }

    public function download(Request $request, string $name): void
    {
        $this->requireSupport();
        $service = new BackupService();
        $file = $service->path($name);
        if (!$file) {
            Response::redirectWith('/backups', 'error', 'Respaldo no encontrado.');
        }
        (new AuditService())->log('BACKUP.DESCARGA', 'backups', null, ['archivo' => basename($name)]);
        Response::download($file, basename($name));
    }

    public function delete(Request $request, string $name): void
    {
        $this->requireSupport();
        (new BackupService())->delete($name);
        (new AuditService())->log('BACKUP.ELIMINADO', 'backups', null, ['archivo' => $name]);
        Response::redirectWith('/backups', 'success', 'Respaldo eliminado.');
    }
}