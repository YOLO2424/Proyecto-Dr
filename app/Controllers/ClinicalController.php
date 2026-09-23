<?php

namespace App\Controllers;

use App\Core\View;
use App\Core\Request;
use App\Core\Response;
use App\Services\ClinicalService;
use App\Services\PatientService;
use App\Services\SettingsService;
use App\Services\PdfService;
use App\Validators\PatientValidator;

final class ClinicalController
{
    public function index(Request $request, string $patientId): void
    {
        $patient = (new PatientService())->get($patientId);
        if (!$patient) {
            Response::redirectWith('/pacientes', 'error', 'Paciente no encontrado.');
        }
        Response::send(View::render('clinical/index', [
            'patient' => $patient,
            'encounters' => (new ClinicalService())->encounters($patientId),
        ]));
    }

    public function create(Request $request, string $patientId): void
    {
        $patient = (new PatientService())->get($patientId);
        if (!$patient) {
            Response::redirectWith('/pacientes', 'error', 'Paciente no encontrado.');
        }
        Response::send(View::render('clinical/form', [
            'patient' => $patient,
            'today' => date('d/m/Y'),
        ]));
    }

    public function store(Request $request, string $patientId): void
    {
        $data = $request->all();
        if (!PatientValidator::validateEncounterDate((string) ($data['encounter_date'] ?? ''))) {
            Response::redirectWith('/pacientes/' . $patientId . '/consultas/nueva', 'error', 'Fecha de atención inválida.');
        }
        $data['encounter_date'] = PatientValidator::normalizeDate($data['encounter_date']);

        $encounterId = (new ClinicalService())->createEncounter($patientId, $data);
        Response::redirectWith('/consultas/' . $encounterId, 'success', 'Consulta registrada.');
    }

    public function show(Request $request, string $encounterId): void
    {
        $service = new ClinicalService();
        $encounter = $service->encounter((int) $encounterId);
        if (!$encounter) {
            Response::redirectWith('/pacientes', 'error', 'Consulta no encontrada.');
        }
        Response::send(View::render('clinical/show', [
            'patient' => $encounter['patient'],
            'encounter' => $encounter,
            'versions' => $service->encounterVersions((int) $encounterId),
        ]));
    }

    public function update(Request $request, string $encounterId): void
    {
        $data = $request->all();
        $data['encounter_date'] = PatientValidator::normalizeDate($data['encounter_date'] ?? '');
        (new ClinicalService())->updateEncounter((int) $encounterId, $data);
        Response::redirectWith('/consultas/' . $encounterId, 'success', 'Consulta actualizada.');
    }

    public function addDiagnosis(Request $request, string $encounterId): void
    {
        $enc = (new ClinicalService())->encounter((int) $encounterId);
        if (!$enc) {
            Response::redirectWith('/pacientes', 'error', 'Consulta no encontrada.');
        }
        (new ClinicalService())->addDiagnosis((int) $encounterId, $enc['patient_id'], $request->all());
        Response::redirectWith('/consultas/' . $encounterId, 'success', 'Diagnóstico agregado.');
    }

    public function updateDiagnosis(Request $request, string $encounterId, string $diagId): void
    {
        try {
            (new ClinicalService())->updateDiagnosis((int) $encounterId, (int) $diagId, $request->all());
            Response::redirectWith('/consultas/' . $encounterId, 'success', 'Diagnóstico actualizado (versión guardada).');
        } catch (\Throwable $e) {
            Response::redirectWith('/consultas/' . $encounterId, 'error', $e->getMessage());
        }
    }

    public function addTreatment(Request $request, string $encounterId): void
    {
        $enc = (new ClinicalService())->encounter((int) $encounterId);
        if (!$enc) {
            Response::redirectWith('/pacientes', 'error', 'Consulta no encontrada.');
        }
        (new ClinicalService())->addTreatment((int) $encounterId, $enc['patient_id'], $request->all());
        Response::redirectWith('/consultas/' . $encounterId, 'success', 'Tratamiento agregado.');
    }

    public function updateTreatment(Request $request, string $encounterId, string $treatId): void
    {
        try {
            (new ClinicalService())->updateTreatment((int) $treatId, $request->all());
            Response::redirectWith('/consultas/' . $encounterId, 'success', 'Tratamiento actualizado (versión guardada).');
        } catch (\Throwable $e) {
            Response::redirectWith('/consultas/' . $encounterId, 'error', $e->getMessage());
        }
    }

    public function addNote(Request $request, string $encounterId): void
    {
        $enc = (new ClinicalService())->encounter((int) $encounterId);
        if (!$enc) {
            Response::redirectWith('/pacientes', 'error', 'Consulta no encontrada.');
        }
        (new ClinicalService())->addNote((int) $encounterId, $enc['patient_id'], $request->input('note_type', 'OBSERVACION'), $request->input('note_content', ''));
        Response::redirectWith('/consultas/' . $encounterId, 'success', 'Nota agregada.');
    }

    public function informePdf(Request $request, string $encounterId): void
    {
        $this->renderClinicalPdf((int) $encounterId, 'informe');
    }

    public function recetaPdf(Request $request, string $encounterId): void
    {
        $this->renderClinicalPdf((int) $encounterId, 'receta');
    }

    private function renderClinicalPdf(int $encounterId, string $type): void
    {
        $service = new ClinicalService();
        $settings = new SettingsService();
        $encounter = $service->encounter($encounterId);
        if (!$encounter) {
            Response::redirectWith('/pacientes', 'error', 'Consulta no encontrada.');
        }
        $patient = $encounter['patient'];

        $pdf = new PdfService();
        $pdf->addPage();
        $w = $pdf->contentWidth();
        $y = $pdf->pageHeight() - 50;

        // Encabezado
        $pdf->text(56, $y, $settings->get('clinic_name', 'Consultorio Médico'), 16, 'bold');
        $y -= 20;
        $pdf->text(56, $y, $settings->get('doctor_name', ''), 11);
        $y -= 24;
        $pdf->line(56, $y, 56 + $w, $y);
        $y -= 24;

        $pdf->text(56, $y, $type === 'receta' ? 'RECETA MÉDICA' : 'INFORME CLÍNICO', 13, 'bold');
        $y -= 28;

        // Datos del paciente
        $pdf->rect(56, $y - 8, $w, 74, '0.97 0.97 0.97');
        $pdf->text(64, $y + 14, 'Paciente:  ' . $patient['first_name'] . ' ' . $patient['last_name'], 11, 'bold');
        $pdf->text(64, $y, 'ID:  ' . $patient['patient_id'], 10);
        $pdf->text(360, $y + 14, 'Fecha:  ' . (new \DateTime($encounter['encounter_date']))->format('d/m/Y'), 10);
        $pdf->text(360, $y, 'Motivo:  ' . ($encounter['reason'] ?? '—'), 10);
        $y -= 42;

        $pdf->text(56, $y, $type === 'receta' ? 'Tratamiento indicado' : 'Contenido', 12, 'bold');
        $y -= 24;

        if ($type === 'receta') {
            foreach ($encounter['treatments'] as $t) {
                $label = $t['description'] . (($t['dose'] || $t['frequency']) ? '  —  ' . trim(($t['dose'] ?? '') . ' · ' . ($t['frequency'] ?? ''), ' ·') : '');
                if (($t['duration'] ?? '') !== '') {
                    $label .= '  (' . $t['duration'] . ')';
                }
                $y = $pdf->paragraph(56, $y, $label, $w, 10, 16);
            }
        } else {
            $y = $pdf->paragraph(56, $y, $encounter['subjective'] ? 'Subjetivo: ' . $encounter['subjective'] : '', $w, 10, 14);
            $y -= 6;
            $y = $pdf->paragraph(56, $y, $encounter['objective'] ? 'Objetivo: ' . $encounter['objective'] : '', $w, 10, 14);
            $y -= 6;
            $y = $pdf->paragraph(56, $y, $encounter['plan'] ? 'Plan: ' . $encounter['plan'] : '', $w, 10, 14);
            $y -= 12;
            $pdf->text(56, $y, 'Diagnósticos', 11, 'bold');
            $y -= 16;
            foreach ($encounter['diagnoses'] as $d) {
                $y = $pdf->paragraph(56, $y, '· ' . $d['description'], $w - 20, 10, 14);
            }
            $y -= 10;
            $pdf->text(56, $y, 'Tratamiento', 11, 'bold');
            $y -= 16;
            foreach ($encounter['treatments'] as $t) {
                $y = $pdf->paragraph(56, $y, '· ' . $t['description'], $w - 20, 10, 14);
            }
        }

        $y -= 30;
        $pdf->line(56, $y, 220, $y);
        $y -= 16;
        $pdf->text(56, $y, 'Firma  ·  ' . $settings->get('doctor_name', ''), 10);

        Response::send($pdf->build(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="' . $type . '_' . $patient['patient_id'] . '.pdf"',
        ]);
    }
}