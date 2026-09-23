<?php

namespace App\Core;

final class App
{
    public static function boot(): void
    {
        $config = require dirname(__DIR__, 2) . '/config/config.php';
        if (!empty($config['app']['timezone'])) {
            date_default_timezone_set($config['app']['timezone']);
        }
        ini_set('display_errors', !empty($config['app']['debug']) ? '1' : '0');
        ini_set('log_errors', '1');
        ini_set('error_log', $config['paths']['logs'] . '/php_errors.log');
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        foreach ([
            'database', 'documents', 'backups', 'quarantine', 'logs', 'cache',
        ] as $dir) {
            if (!is_dir($config['paths'][$dir])) {
                mkdir($config['paths'][$dir], 0775, true);
            }
        }
        MigrationRunner::run();
    }

    public static function handle(): void
    {
        self::boot();

        $request = Request::capture();
        $router = new Router();

        // ---- Inicio / dashboard
        $router->get('/', 'DashboardController', 'index');

        // ---- Pacientes
        $router->get('/pacientes', 'PatientController', 'index');
        $router->get('/pacientes/nuevo', 'PatientController', 'create');
        $router->post('/pacientes/nuevo', 'PatientController', 'store');
        $router->get('/pacientes/([A-Z0-9-]+)', 'PatientController', 'show');
        $router->get('/pacientes/([A-Z0-9-]+)/editar', 'PatientController', 'edit');
        $router->post('/pacientes/([A-Z0-9-]+)/editar', 'PatientController', 'update');
        $router->post('/pacientes/([A-Z0-9-]+)/desactivar', 'PatientController', 'deactivate');
        $router->post('/pacientes/([A-Z0-9-]+)/reactivar', 'PatientController', 'reactivate');
        $router->post('/pacientes/([A-Z0-9-]+)/eliminar', 'PatientController', 'delete');
        $router->post('/pacientes/([A-Z0-9-]+)/restaurar', 'PatientController', 'restore');
        $router->post('/pacientes/([A-Z0-9-]+)/alergias', 'PatientController', 'addAllergy');
        $router->post('/pacientes/([A-Z0-9-]+)/alergias/(\d+)/eliminar', 'PatientController', 'deleteAllergy');
        $router->post('/pacientes/([A-Z0-9-]+)/condiciones', 'PatientController', 'addCondition');
        $router->post('/pacientes/([A-Z0-9-]+)/condiciones/(\d+)/eliminar', 'PatientController', 'deleteCondition');
        $router->post('/pacientes/([A-Z0-9-]+)/medicamentos', 'PatientController', 'addMedication');
        $router->post('/pacientes/([A-Z0-9-]+)/medicamentos/(\d+)/eliminar', 'PatientController', 'deleteMedication');

        // ---- Historia clinica (consultas)
        $router->get('/pacientes/([A-Z0-9-]+)/consultas', 'ClinicalController', 'index');
        $router->get('/pacientes/([A-Z0-9-]+)/consultas/nueva', 'ClinicalController', 'create');
        $router->post('/pacientes/([A-Z0-9-]+)/consultas', 'ClinicalController', 'store');
        $router->get('/consultas/(\d+)', 'ClinicalController', 'show');
        $router->post('/consultas/(\d+)/actualizar', 'ClinicalController', 'update');
        $router->post('/consultas/(\d+)/diagnostico', 'ClinicalController', 'addDiagnosis');
        $router->post('/consultas/(\d+)/diagnostico/(\d+)/editar', 'ClinicalController', 'updateDiagnosis');
        $router->post('/consultas/(\d+)/tratamiento', 'ClinicalController', 'addTreatment');
        $router->post('/consultas/(\d+)/tratamiento/(\d+)/editar', 'ClinicalController', 'updateTreatment');
        $router->post('/consultas/(\d+)/nota', 'ClinicalController', 'addNote');
        $router->get('/consultas/(\d+)/informe', 'ClinicalController', 'informePdf');
        $router->get('/consultas/(\d+)/receta', 'ClinicalController', 'recetaPdf');

        // ---- QR
        $router->get('/qr/imagen', 'QRController', 'image');
        $router->get('/qr/paciente/([a-zA-Z0-9]+)', 'QRController', 'resolve');
        $router->post('/pacientes/([A-Z0-9-]+)/qr/regenerar', 'QRController', 'regenerate');
        $router->post('/pacientes/([A-Z0-9-]+)/qr/revocar', 'QRController', 'revoke');
        $router->get('/pacientes/([A-Z0-9-]+)/qr/tarjeta', 'QRController', 'card');

        // ---- Pre-registro externo
        $router->get('/registro/([a-zA-Z0-9]+)', 'RegistrationController', 'externalForm');
        $router->post('/registro/([a-zA-Z0-9]+)', 'RegistrationController', 'externalSubmit');

        // ---- Ficha publica del paciente (acceso por QR; sin datos clinicos)
        $router->get('/u/([A-Z0-9-]+)', 'PatientController', 'publicShow');

        // ---- Administracion de pre-registros
        $router->get('/registros', 'RegistrationController', 'adminIndex');
        $router->get('/registros/qr/nuevo-token', 'RegistrationController', 'newToken');
        $router->post('/registros/(\d+)/aprobar', 'RegistrationController', 'approve');
        $router->post('/registros/(\d+)/rechazar', 'RegistrationController', 'reject');

        // ---- Documentos
        $router->post('/pacientes/([A-Z0-9-]+)/documentos', 'DocumentController', 'store');
        $router->get('/pacientes/([A-Z0-9-]+)/documentos/(\d+)/descargar', 'DocumentController', 'download');
        $router->post('/pacientes/([A-Z0-9-]+)/documentos/(\d+)/eliminar', 'DocumentController', 'delete');

        // ---- Backups (requieren acceso de Soporte/TI)
        $router->get('/soporte', 'SoporteController', 'loginForm');
        $router->post('/soporte', 'SoporteController', 'verify');
        $router->post('/soporte/salir', 'SoporteController', 'logout');
        $router->get('/backups', 'BackupController', 'index');
        $router->post('/backups', 'BackupController', 'create');
        $router->get('/backups/([a-zA-Z0-9._-]+)', 'BackupController', 'download');
        $router->post('/backups/([a-zA-Z0-9._-]+)/eliminar', 'BackupController', 'delete');

        // ---- Estado en vivo (sin recarga de pagina)
        $router->get('/live/estado', 'LiveController', 'estado');

        // ---- Configuracion y auditoria
        $router->get('/configuracion', 'SettingsController', 'index');
        $router->post('/configuracion', 'SettingsController', 'update');
        $router->get('/auditoria', 'SettingsController', 'audit');

        $router->dispatch($request);
    }
}