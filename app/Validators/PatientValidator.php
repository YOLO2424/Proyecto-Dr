<?php

namespace App\Validators;

final class PatientValidator
{
    public static function validate(array $data): array
    {
        $errors = [];

        if (empty(trim($data['first_name'] ?? ''))) {
            $errors['first_name'] = 'El nombre es obligatorio.';
        }
        if (empty(trim($data['last_name'] ?? ''))) {
            $errors['last_name'] = 'El apellido es obligatorio.';
        }

        $birth = $data['birth_date'] ?? '';
        if ($birth !== '' && $birth !== null && !self::validDate($birth)) {
            $errors['birth_date'] = 'Formato de fecha de nacimiento inválido (DD/MM/AAAA).';
        }

        $gender = $data['gender'] ?? '';
        if ($gender !== '' && !in_array($gender, ['M', 'F', 'O'], true)) {
            $errors['gender'] = 'Sexo inválido.';
        }

        $bg = $data['blood_group'] ?? '';
        if ($bg !== '' && !in_array($bg, ['O+', 'O-', 'A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'DESCONOCIDO'], true)) {
            $errors['blood_group'] = 'Grupo sanguíneo inválido.';
        }

        return $errors;
    }

    public static function validateEncounterDate(string $date): bool
    {
        return self::validDate($date);
    }

    private static function validDate(string $value): bool
    {
        $m = [];
        if (!preg_match('#^(\d{2})/(\d{2})/(\d{4})$#', trim($value), $m)) {
            return false;
        }
        return checkdate((int) $m[2], (int) $m[1], (int) $m[3]);
    }

    /**
     * Normaliza DD/MM/AAAA a un valor ordenable AAAAMMDD o devuelve el input.
     */
    public static function normalizeDate(?string $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }
        if (preg_match('#^(\d{2})/(\d{2})/(\d{4})$#', trim($value), $m)) {
            return $m[3] . '-' . $m[2] . '-' . $m[1];
        }
        return $value;
    }

    public static function denormalizeDate(?string $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }
        if (preg_match('#^(\d{4})-(\d{2})-(\d{2})$#', $value, $m)) {
            return $m[3] . '/' . $m[2] . '/' . $m[1];
        }
        return $value;
    }
}