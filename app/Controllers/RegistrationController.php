<?php

namespace App\Controllers;

use App\Core\View;
use App\Core\Request;
use App\Core\Response;
use App\Services\PreRegistrationService;
use App\Services\QrService;

final class RegistrationController
{
    /**
     * Formulario externo (solo crea solicitud PENDING).
     */
    public function externalForm(Request $request, string $token): void
    {
        $service = new PreRegistrationService();
        $registration = $service->findByToken($token);
        if (!$registration) {
            Response::send(View::render('preregistration/status', [
                'type' => 'error',
                'title' => 'Formulario no encontrado',
                'message' => 'El enlace de registro no es válido.',
            ]), 404);
        }
        if ($registration['status'] !== 'PENDING') {
            Response::send(View::render('preregistration/status', [
                'type' => 'info',
                'title' => $registration['status'] === 'APPROVED' ? 'Solicitud aprobada' : 'Solicitud rechazada',
                'message' => $registration['status'] === 'APPROVED'
                    ? 'Tu registro ya fue aprobado por el médico.'
                    : 'Tu solicitud fue rechazada' . ($registration['rejection_reason'] ? ': ' . $registration['rejection_reason'] : '.'),
            ]));
        }

        Response::send(View::render('preregistration/form', [
            'token' => $token,
            'registration' => $registration,
        ]));
    }

    public function externalSubmit(Request $request, string $token): void
    {
        $service = new PreRegistrationService();
        try {
            $service->submit($token, $request->all());
        } catch (\RuntimeException $e) {
            Response::send(View::render('preregistration/status', [
                'type' => 'error',
                'title' => 'No se pudo enviar',
                'message' => $e->getMessage(),
            ]));
        }
        Response::send(View::render('preregistration/status', [
            'type' => 'success',
            'title' => 'Solicitud enviada',
            'message' => 'Tus datos fueron recibidos. Quedan pendientes de aprobación por el médico.',
        ]));
    }

    /**
     * Devuelve un nuevo token de pre-registro (para imprimir el QR).
     */
    public function newToken(Request $request): void
    {
        $service = new PreRegistrationService();
        $created = $service->createRequest();
        $base = trim((string) (new \App\Services\SettingsService())->get('registration_url', '/registro'));
        if ($base === '' || str_starts_with($base, '/')) {
            $host = $_SERVER['HTTP_HOST'] ?? '';
            if ($host !== '') {
                $base = 'http://' . $host . rtrim($base, '/');
            }
        }
        Response::json([
            'token' => $created['token'],
            'url' => rtrim($base, '/') . '/' . $created['token'],
        ]);
    }

    /**
     * Consola del medico para revisar y aprobar/rechazar.
     */
    public function adminIndex(Request $request): void
    {
        $service = new PreRegistrationService();
        Response::send(View::render('preregistration/admin', [
            'pending' => $service->pending(),
            'processed' => $service->processed(),
        ]));
    }

    public function approve(Request $request, string $id): void
    {
        try {
            $result = (new PreRegistrationService())->approve((int) $id);
        } catch (\Throwable $e) {
            Response::redirectWith('/registros', 'error', $e->getMessage());
        }
        Response::redirectWith('/pacientes/' . $result['patient_id'], 'success', 'Pre-registro aprobado. Paciente ' . $result['patient_id'] . ' creado con QR.');
    }

    public function reject(Request $request, string $id): void
    {
        (new PreRegistrationService())->reject((int) $id, $request->input('reason', 'No aprobado por el consultorio.'));
        Response::redirectWith('/registros', 'success', 'Solicitud rechazada.');
    }
}