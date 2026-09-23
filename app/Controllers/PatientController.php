<?php

namespace App\Controllers;

use App\Core\View;
use App\Core\Request;
use App\Core\Response;
use App\Services\PatientService;
use App\Services\QrService;
use App\Validators\PatientValidator;

final class PatientController
{
    /**
     * Listado y busqueda. filter=ACTIVE|INACTIVE|DELETED|ALL. q=texto.
     */
    public function index(Request $request): void
    {
        $service = new PatientService();
        $q = $request->query('q');
        $filter = $request->query('filter', 'ACTIVE');

        $data = [
            'patients' => $service->search($q, $filter),
            'q' => $q ?? '',
            'filter' => $filter,
            'counts' => $service->countByStatus(),
        ];
        Response::send(View::render('patients/index', $data));
    }

    public function create(Request $request): void
    {
        Response::send(View::render('patients/form', [
            'patient' => [
                'first_name' => '', 'last_name' => '', 'national_id' => '', 'birth_date' => '', 'gender' => '',
                'marital_status' => '', 'nationality' => '', 'place_of_birth' => '',
                'blood_group' => '', 'previous_surgeries' => '', 'observations' => '',
            ],
            'contact' => ['phone_primary' => '', 'phone_secondary' => '', 'email' => '', 'address' => '', 'city' => '', 'region' => '', 'postal_code' => ''],
            'emergency' => ['full_name' => '', 'relationship' => '', 'phone' => '', 'alt_phone' => ''],
            'insurance' => ['provider' => '', 'policy_number' => '', 'holder_name' => '', 'holder_relationship' => '', 'company' => ''],
            'errors' => [],
            'isEdit' => false,
        ]));
    }

    public function store(Request $request): void
    {
        $data = $request->all();
        $errors = PatientValidator::validate($data);
        if (!empty($errors)) {
            Response::redirectWith('/pacientes/nuevo', 'error', 'Revisa los campos marcados.');
        }

        try {
            $service = new PatientService();
            $created = $service->create(
                $this->patientData($data),
                $this->contactData($data),
                $this->emergencyData($data),
                $this->insuranceData($data),
            );
        } catch (\Throwable $e) {
            Response::redirectWith('/pacientes/nuevo', 'error', 'No se pudo crear el paciente: ' . $e->getMessage());
        }

        Response::redirectWith('/pacientes/' . $created['patient_id'], 'success', 'Paciente creado. ID asignado: ' . $created['patient_id']);
    }

    public function show(Request $request, string $id): void
    {
        $service = new PatientService();
        $patient = $service->get($id);
        if (!$patient) {
            Response::redirectWith('/pacientes', 'error', 'Paciente no encontrado.');
        }

        Response::send(View::render('patients/show', [
            'patient' => $patient,
            'contact' => $service->contacts($id) ?: [],
            'emergency' => $service->emergencyContacts($id),
            'insurance' => $service->insurance($id) ?: [],
            'allergies' => $service->allergies($id),
            'conditions' => $service->conditions($id),
            'medications' => $service->medications($id),
            'qr' => (new QrService())->activeToken($id),
            'documents' => (new \App\Services\DocumentService())->forPatient($id),
            'encounters' => (new \App\Services\ClinicalService())->encounters($id),
        ]));
    }

    public function edit(Request $request, string $id): void
    {
        $service = new PatientService();
        $patient = $service->get($id);
        if (!$patient) {
            Response::redirectWith('/pacientes', 'error', 'Paciente no encontrado.');
        }

        Response::send(View::render('patients/form', [
            'patient' => $patient,
            'contact' => $service->contacts($id) ?: [],
            'emergency' => $service->emergencyContacts($id)[0] ?? [],
            'insurance' => $service->insurance($id) ?: [],
            'errors' => [],
            'isEdit' => true,
        ]));
    }

    public function update(Request $request, string $id): void
    {
        $data = $request->all();
        $service = new PatientService();
        if (!$service->get($id)) {
            Response::redirectWith('/pacientes', 'error', 'Paciente no encontrado.');
        }
        try {
            $service->update($id, $this->patientData($data), $this->contactData($data), $this->emergencyData($data), $this->insuranceData($data));
        } catch (\Throwable $e) {
            Response::redirectWith('/pacientes/' . $id . '/editar', 'error', 'No se pudo actualizar: ' . $e->getMessage());
        }
        Response::redirectWith('/pacientes/' . $id, 'success', 'Paciente actualizado.');
    }

    public function deactivate(Request $request, string $id): void
    {
        $service = new PatientService();
        $service->deactivate($id);
        Response::redirectWith('/pacientes/' . $id, 'success', 'Paciente desactivado.');
    }

    public function reactivate(Request $request, string $id): void
    {
        $service = new PatientService();
        $service->reactivate($id);
        Response::redirectWith('/pacientes/' . $id, 'success', 'Paciente reactivado.');
    }

    public function delete(Request $request, string $id): void
    {
        $service = new PatientService();
        $service->delete($id);
        Response::redirectWith('/pacientes?filter=DELETED', 'success', 'Paciente eliminado. El ID ' . $id . ' queda reservado para siempre.');
    }

    public function restore(Request $request, string $id): void
    {
        $service = new PatientService();
        $service->restore($id);
        Response::redirectWith('/pacientes/' . $id, 'success', 'Paciente restaurado.');
    }

    public function addAllergy(Request $request, string $id): void
    {
        (new PatientService())->addAllergy($id, $request->all());
        Response::redirectWith('/pacientes/' . $id, 'success', 'Alergia registrada.');
    }

    public function deleteAllergy(Request $request, string $id, string $allergyId): void
    {
        (new PatientService())->deleteAllergy((int) $allergyId);
        Response::redirectWith('/pacientes/' . $id, 'success', 'Alergia eliminada.');
    }

    public function addCondition(Request $request, string $id): void
    {
        (new PatientService())->addCondition($id, $request->all());
        Response::redirectWith('/pacientes/' . $id, 'success', 'Condición / antecedente registrado.');
    }

    public function deleteCondition(Request $request, string $id, string $conditionId): void
    {
        (new PatientService())->deleteCondition((int) $conditionId);
        Response::redirectWith('/pacientes/' . $id, 'success', 'Condición eliminada.');
    }

    public function addMedication(Request $request, string $id): void
    {
        (new PatientService())->addMedication($id, $request->all());
        Response::redirectWith('/pacientes/' . $id, 'success', 'Medicamento registrado.');
    }

    public function deleteMedication(Request $request, string $id, string $medId): void
    {
        (new PatientService())->deleteMedication((int) $medId);
        Response::redirectWith('/pacientes/' . $id, 'success', 'Medicamento eliminado.');
    }

    private function patientData(array $d): array
    {
        return [
            'first_name' => trim($d['first_name'] ?? ''),
            'last_name' => trim($d['last_name'] ?? ''),
            'national_id' => trim($d['national_id'] ?? '') ?: null,
            'birth_date' => PatientValidator::normalizeDate($d['birth_date'] ?? null),
            'gender' => $d['gender'] ?? null,
            'marital_status' => $d['marital_status'] ?? null,
            'nationality' => $d['nationality'] ?? null,
            'place_of_birth' => $d['place_of_birth'] ?? null,
            'blood_group' => $d['blood_group'] ?? null,
            'previous_surgeries' => $d['previous_surgeries'] ?? null,
            'observations' => $d['observations'] ?? null,
        ];
    }

    private function contactData(array $d): array
    {
        return [
            'phone_primary' => $d['phone_primary'] ?? null,
            'phone_secondary' => $d['phone_secondary'] ?? null,
            'email' => $d['email'] ?? null,
            'address' => $d['address'] ?? null,
            'city' => $d['city'] ?? null,
            'region' => $d['region'] ?? null,
            'postal_code' => $d['postal_code'] ?? null,
        ];
    }

    private function emergencyData(array $d): array
    {
        return [
            'full_name' => $d['emergency_name'] ?? null,
            'relationship' => $d['emergency_relationship'] ?? null,
            'phone' => $d['emergency_phone'] ?? null,
            'alt_phone' => $d['emergency_alt_phone'] ?? null,
        ];
    }

    private function insuranceData(array $d): array
    {
        return [
            'provider' => $d['insurance_provider'] ?? null,
            'policy_number' => $d['insurance_policy'] ?? null,
            'holder_name' => $d['insurance_holder'] ?? null,
            'holder_relationship' => $d['insurance_relation'] ?? null,
            'company' => $d['insurance_company'] ?? null,
        ];
    }
}