<?php

namespace App\Controllers;

use App\Core\View;
use App\Core\Request;
use App\Core\Response;
use App\Services\SupportAuth;
use App\Services\AuditService;

final class SoporteController
{
    /**
     * Puerta de acceso de Soporte/TI para las operaciones de backup.
     */
    public function loginForm(Request $request): void
    {
        if (SupportAuth::isGranted()) {
            Response::redirect('/backups');
        }
        Response::send(View::render('soporte/login', [
            'configured' => SupportAuth::isConfigured(),
        ]));
    }

    public function verify(Request $request): void
    {
        $pin = (string) $request->input('pin', '');
        if (SupportAuth::attempt($pin)) {
            SupportAuth::login();
            (new AuditService())->log('SOPORTE.ACCESO_CONCEDIDO', 'backups');
            Response::redirectWith('/backups', 'success', 'Acceso de Soporte/TI concedido.');
        }

        (new AuditService())->log('SOPORTE.ACCESO_DENEGADO', 'backups');
        Response::redirectWith('/soporte', 'error', 'Clave de Soporte/TI incorrecta.');
    }

    public function logout(Request $request): void
    {
        SupportAuth::logout();
        (new AuditService())->log('SOPORTE.SESION_CERRADA', 'backups');
        Response::redirect('/');
    }
}