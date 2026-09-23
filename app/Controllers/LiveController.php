<?php

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Services\PatientService;
use App\Services\ClinicalService;
use App\Services\PreRegistrationService;

final class LiveController
{
    /**
     * Contadores en vivo para actualizar la interfaz sin recargar la pagina.
     */
    public function estado(Request $request): void
    {
        $counts = (new PatientService())->countByStatus();
        Response::json([
            'pending' => (int) (new PreRegistrationService())->countPending(),
            'active' => (int) ($counts['ACTIVE'] ?? 0),
            'inactive' => (int) ($counts['INACTIVE'] ?? 0),
            'today' => (int) (new ClinicalService())->todayCount(),
            'server_time' => date('H:i:s'),
        ]);
    }
}