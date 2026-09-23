<?php

namespace App\Controllers;

use App\Core\View;
use App\Core\Request;
use App\Core\Response;
use App\Services\QrService;
use App\Services\PatientService;
use App\Services\AuditService;
use App\Services\SettingsService;

final class QRController
{
    /**
     * Resolucion del QR de paciente escaneado por el medico.
     * Contenido esperado: PATIENT:<token opaco>.
     */
    public function resolve(Request $request, string $token): void
    {
        $full = $request->query('t', 'PATIENT:' . $token);
        $result = (new QrService())->resolvePatientToken($full);
        if (!$result) {
            Response::send(View::render('qr/error', [
                'message' => 'El código QR no corresponde a un paciente activo del sistema. Puede haber sido revocado o ser inválido.',
            ]), 404);
        }
        if ($result['patient_status'] === 'DELETED') {
            Response::send(View::render('qr/error', [
                'message' => 'El paciente asociado a este código fue eliminado del sistema.',
            ]), 404);
        }
        (new AuditService())->log('QR.ESCANEADO', 'patients', null, ['patient_id' => $result['patient_id']]);
        Response::redirect('/u/' . $result['patient_id']);
    }

    /**
     * Rota el QR: revoca el actual y emite uno nuevo. La identidad no cambia.
     */
    public function regenerate(Request $request, string $patientId): void
    {
        $patient = (new PatientService())->get($patientId);
        if (!$patient) {
            Response::redirectWith('/pacientes', 'error', 'Paciente no encontrado.');
        }
        $token = (new QrService())->rotateToken($patientId);
        (new AuditService())->log('QR.ROTADO', 'patients', null, ['patient_id' => $patientId]);
        Response::redirectWith('/pacientes/' . $patientId, 'success', 'QR regenerado. Imprime la nueva tarjeta.');
    }

    public function revoke(Request $request, string $patientId): void
    {
        $patient = (new PatientService())->get($patientId);
        if (!$patient) {
            Response::redirectWith('/pacientes', 'error', 'Paciente no encontrado.');
        }
        (new QrService())->revokeActiveToken($patientId, 'REVOCADO_MANUAL');
        (new AuditService())->log('QR.REVOCADO', 'patients', null, ['patient_id' => $patientId]);
        Response::redirectWith('/pacientes/' . $patientId, 'success', 'QR revocado.');
    }

    /**
     * Renderiza el QR como PNG a partir del payload.
     */
    public function image(Request $request): void
    {
        $payload = (string) $request->query('t', '');
        if ($payload === '') {
            http_response_code(400);
            exit;
        }
        error_reporting(E_ALL & ~E_DEPRECATED);
        $qrcode = dirname(__DIR__, 2) . '/vendor/qrcode/qrlib.php';
        require $qrcode;
        ob_start();
        \QRcode::png($payload, null, QR_ECLEVEL_L, 10, 2);
        $png = ob_get_clean();
        header('Content-Type: image/png');
        header('Content-Length: ' . strlen($png));
        echo $png;
        exit;
    }

    /**
     * Tarjeta imprimible con el QR y datos de identificacion (sin datos clinicos).
     */
    public function card(Request $request, string $patientId): void
    {
        $patient = (new PatientService())->get($patientId);
        if (!$patient) {
            Response::redirectWith('/pacientes', 'error', 'Paciente no encontrado.');
        }
        $qr = (new QrService())->activeToken($patientId);

        $config = require dirname(__DIR__, 2) . '/config/config.php';
        $payload = $config['qr']['prefix'] . $qr['token'];
        $settings = new SettingsService();

        Response::send(View::render('patients/qrcard', [
            'patient' => $patient,
            'qr' => $qr,
            'payload' => $payload,
            'clinic' => $settings->get('clinic_name', 'Consultorio'),
        ]));

        return;
    }
}