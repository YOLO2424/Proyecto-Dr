<?php

namespace App\Controllers;

use App\Core\View;
use App\Core\Request;
use App\Core\Response;
use App\Services\SettingsService;
use App\Services\AuditService;
use App\Services\BackupService;
use App\Services\QrService;
use App\Services\SupportAuth;

final class SettingsController
{
    public function index(Request $request): void
    {
        Response::send(View::render('settings/index', [
            'settings' => (new SettingsService())->all(),
            'backupConfig' => [
                'retention_days' => (new SettingsService())->get('backup_retention_days', '30'),
            ],
            'supportConfigured' => SupportAuth::isConfigured(),
        ]));
    }

    public function update(Request $request): void
    {
        $service = new SettingsService();
        $data = $request->all();

        foreach (['clinic_name', 'doctor_name', 'registration_url', 'backup_retention_days'] as $key) {
            if (array_key_exists($key, $data)) {
                $service->set($key, trim((string) $data[$key]));
            }
        }

        $pinChanged = false;
        $pin = (string) ($data['support_pin'] ?? '');
        if ($pin !== '') {
            if (strlen($pin) < 4) {
                Response::redirectWith('/configuracion', 'error', 'La clave de Soporte/TI debe tener al menos 4 caracteres.');
            }
            $service->set('support_pin_hash', password_hash($pin, PASSWORD_DEFAULT));
            $service->set('support_pin_updated_at', date('Y-m-d H:i:s'));
            $pinChanged = true;
        }

        if (!empty($request->input('action')) && $request->input('action') === 'qr_registro') {
            $prefix = trim((string) $request->input('qr_prefix', 'PATIENT:'));
            $service->set('qr_prefix', $prefix);
        }

        (new AuditService())->log('CONFIGURACION.ACTUALIZADA', 'settings', null, ['pin_soporte' => $pinChanged]);
        Response::redirectWith('/configuracion', 'success', 'Configuración guardada.');
    }

    public function audit(Request $request): void
    {
        Response::send(View::render('settings/audit', [
            'entries' => (new AuditService())->recent((int) ($request->query('limit', '200') ?? 200)),
        ]));
    }
}