<?php $isEdit = $isEdit ?? false; ?>
<?php $action = $isEdit ? '/pacientes/' . \App\Core\View::e($patient['patient_id']) . '/editar' : '/pacientes/nuevo'; ?>
<?php $title = $isEdit ? 'Editar paciente' : 'Nuevo paciente'; ?>
<div class="topbar">
    <div>
        <h1><?= $title ?></h1>
        <div class="sub"><?= $isEdit ? \App\Core\View::e($patient['patient_id']) . ' · identidad inmutable' : 'Se asignará automáticamente un ID permanente (PAC-…) y un QR inicial.' ?></div>
    </div>
    <a class="btn" href="<?= $isEdit ? '/pacientes/' . \App\Core\View::e($patient['patient_id']) : '/pacientes' ?>">← Volver</a>
</div>

<?php if (!empty($errors)): ?>
    <div class="flash error">
        <?php foreach ($errors as $e): ?><div>• <?= \App\Core\View::e($e) ?></div><?php endforeach; ?>
    </div>
<?php endif; ?>

<form method="post" action="<?= $action ?>">
    <div class="card form-section">
        <h4>Identificación</h4>
        <div class="form-grid cols-2">
            <div class="field">
                <label>Nombre *</label>
                <input class="input" name="first_name" value="<?= \App\Core\View::e($patient['first_name']) ?>" required>
            </div>
            <div class="field">
                <label>Apellidos *</label>
                <input class="input" name="last_name" value="<?= \App\Core\View::e($patient['last_name']) ?>" required>
            </div>
            <div class="field">
                <label>Documento de identidad</label>
                <input class="input" name="national_id" value="<?= \App\Core\View::e($patient['national_id'] ?? '') ?>" placeholder="DNI / CURP / pasaporte">
            </div>
            <div class="field">
                <label>Fecha de nacimiento</label>
                <input class="input" name="birth_date" placeholder="DD/MM/AAAA" value="<?= \App\Core\View::e(\App\Validators\PatientValidator::denormalizeDate($patient['birth_date'])) ?>">
            </div>
            <div class="field">
                <label>Sexo</label>
                <select class="select" name="gender">
                    <option value="">—</option>
                    <option value="M" <?= $patient['gender'] === 'M' ? 'selected' : '' ?>>Masculino</option>
                    <option value="F" <?= $patient['gender'] === 'F' ? 'selected' : '' ?>>Femenino</option>
                    <option value="O" <?= $patient['gender'] === 'O' ? 'selected' : '' ?>>Otro</option>
                </select>
            </div>
            <div class="field">
                <label>Estado civil</label>
                <input class="input" name="marital_status" value="<?= \App\Core\View::e($patient['marital_status']) ?>">
            </div>
            <div class="field">
                <label>Nacionalidad</label>
                <input class="input" name="nationality" value="<?= \App\Core\View::e($patient['nationality']) ?>">
            </div>
            <div class="field">
                <label>Lugar de nacimiento</label>
                <input class="input" name="place_of_birth" value="<?= \App\Core\View::e($patient['place_of_birth']) ?>">
            </div>
        </div>
    </div>

    <div class="card form-section">
        <h4>Contacto</h4>
        <div class="form-grid cols-2">
            <div class="field">
                <label>Teléfono principal</label>
                <input class="input" name="phone_primary" value="<?= \App\Core\View::e($contact['phone_primary'] ?? '') ?>">
            </div>
            <div class="field">
                <label>Teléfono secundario</label>
                <input class="input" name="phone_secondary" value="<?= \App\Core\View::e($contact['phone_secondary'] ?? '') ?>">
            </div>
            <div class="field">
                <label>Correo electrónico</label>
                <input class="input" type="email" name="email" value="<?= \App\Core\View::e($contact['email'] ?? '') ?>">
            </div>
            <div class="field">
                <label>Dirección</label>
                <input class="input" name="address" value="<?= \App\Core\View::e($contact['address'] ?? '') ?>">
            </div>
            <div class="field">
                <label>Ciudad</label>
                <input class="input" name="city" value="<?= \App\Core\View::e($contact['city'] ?? '') ?>">
            </div>
            <div class="field">
                <label>Estado / región</label>
                <input class="input" name="region" value="<?= \App\Core\View::e($contact['region'] ?? '') ?>">
            </div>
            <div class="field">
                <label>Código postal</label>
                <input class="input" name="postal_code" value="<?= \App\Core\View::e($contact['postal_code'] ?? '') ?>">
            </div>
        </div>
    </div>

    <div class="card form-section">
        <h4>Contacto de emergencia</h4>
        <div class="form-grid cols-2">
            <div class="field">
                <label>Nombre</label>
                <input class="input" name="emergency_name" value="<?= \App\Core\View::e($emergency['full_name'] ?? '') ?>">
            </div>
            <div class="field">
                <label>Parentesco</label>
                <input class="input" name="emergency_relationship" value="<?= \App\Core\View::e($emergency['relationship'] ?? '') ?>">
            </div>
            <div class="field">
                <label>Teléfono</label>
                <input class="input" name="emergency_phone" value="<?= \App\Core\View::e($emergency['phone'] ?? '') ?>">
            </div>
            <div class="field">
                <label>Teléfono alternativo</label>
                <input class="input" name="emergency_alt_phone" value="<?= \App\Core\View::e($emergency['alt_phone'] ?? '') ?>">
            </div>
        </div>
    </div>

    <div class="card form-section">
        <h4>Información administrativa · Seguro</h4>
        <div class="form-grid cols-2">
            <div class="field">
                <label>Seguro / aseguradora</label>
                <input class="input" name="insurance_provider" value="<?= \App\Core\View::e($insurance['provider'] ?? '') ?>">
            </div>
            <div class="field">
                <label>Número de póliza</label>
                <input class="input" name="insurance_policy" value="<?= \App\Core\View::e($insurance['policy_number'] ?? '') ?>">
            </div>
            <div class="field">
                <label>Titular</label>
                <input class="input" name="insurance_holder" value="<?= \App\Core\View::e($insurance['holder_name'] ?? '') ?>">
            </div>
            <div class="field">
                <label>Parentesco con el titular</label>
                <input class="input" name="insurance_relation" value="<?= \App\Core\View::e($insurance['holder_relationship'] ?? '') ?>">
            </div>
            <div class="field">
                <label>Empresa</label>
                <input class="input" name="insurance_company" value="<?= \App\Core\View::e($insurance['company'] ?? '') ?>">
            </div>
        </div>
    </div>

    <div class="card form-section">
        <h4>Información clínica básica</h4>
        <div class="form-grid cols-2">
            <div class="field">
                <label>Grupo sanguíneo</label>
                <select class="select" name="blood_group">
                    <option value="">—</option>
                    <?php foreach (['O+', 'O-', 'A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'DESCONOCIDO'] as $bg): ?>
                        <option value="<?= $bg ?>" <?= $patient['blood_group'] === $bg ? 'selected' : '' ?>><?= $bg ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="field">
                <label>Cirugías previas</label>
                <input class="input" name="previous_surgeries" value="<?= \App\Core\View::e($patient['previous_surgeries']) ?>">
            </div>
            <div class="field" style="grid-column:1/-1">
                <label>Observaciones</label>
                <textarea class="input" name="observations" rows="3"><?= \App\Core\View::e($patient['observations']) ?></textarea>
            </div>
        </div>
        <p class="sub" style="margin-top:12px;color:var(--muted);font-size:0.8rem">
            Las alergias, condiciones/antecedentes y medicamentos se gestionan desde la ficha del paciente.
        </p>
    </div>

    <div style="display:flex;gap:10px;margin-top:6px">
        <button class="btn btn-primary" type="submit"><?= $isEdit ? 'Guardar cambios' : 'Crear paciente' ?></button>
        <a class="btn" href="<?= $isEdit ? '/pacientes/' . \App\Core\View::e($patient['patient_id']) : '/pacientes' ?>">Cancelar</a>
    </div>
</form>