<?php

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Core\View;
use App\Services\PatientService;
use App\Services\ClinicalService;
use App\Services\PreRegistrationService;
use App\Services\SettingsService;

final class DashboardController
{
    public function index(Request $request): void
    {
        $patients = new PatientService();
        $clinical = new ClinicalService();
        $pre = new PreRegistrationService();
        $settings = new SettingsService();

        $counts = $patients->countByStatus();
        $hour = (int) date('G');
        $greeting = $hour < 12 ? 'Buenos días' : ($hour < 20 ? 'Buenas tardes' : 'Buenas noches');

        Response::send(View::render('dashboard/index', [
            'greeting' => $greeting,
            'doctor' => $settings->get('doctor_name', ''),
            'active' => $counts['ACTIVE'],
            'inactive' => $counts['INACTIVE'],
            'today' => $clinical->todayCount(),
            'pending' => $pre->countPending(),
            'recent' => $patients->recent(),
        ]));
    }
}