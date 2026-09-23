<?php $pid = \App\Core\View::e($patient['patient_id']); ?>
<div class="topbar">
    <div>
        <h1>Nueva consulta</h1>
        <div class="sub"><?= \App\Core\View::e($patient['last_name'] . ' ' . $patient['first_name']) ?> · <?= $pid ?></div>
    </div>
    <a class="btn" href="/pacientes/<?= $pid ?>?tab=consultas">← Volver</a>
</div>

<form method="post" action="/pacientes/<?= $pid ?>/consultas">
    <div class="card form-section">
        <h4>Fecha de atención</h4>
        <div class="form-grid cols-2">
            <div class="field">
                <label>Fecha de atención *</label>
                <input class="input" name="encounter_date" value="<?= \App\Core\View::e($today ?? date('d/m/Y')) ?>" placeholder="DD/MM/AAAA" required>
            </div>
            <div class="field">
                <label>Motivo de consulta</label>
                <input class="input" name="reason" placeholder="Motivo principal">
            </div>
        </div>
    </div>

    <div class="card form-section">
        <h4>Notas clínicas (SOAP)</h4>
        <div class="form-grid">
            <div class="field">
                <label>Subjetivo — síntomas referidos</label>
                <textarea class="input" name="subjective" rows="3"></textarea>
            </div>
            <div class="field">
                <label>Objetivo — signos y exploración</label>
                <textarea class="input" name="objective" rows="3"></textarea>
            </div>
            <div class="field">
                <label>Plan</label>
                <textarea class="input" name="plan" rows="3"></textarea>
            </div>
        </div>
    </div>

    <div class="card form-section">
        <h4>Diagnóstico inicial</h4>
        <div class="form-grid cols-3">
            <div class="field">
                <label>Código (opcional)</label>
                <input class="input" name="diagnosis_code" placeholder="CIE-10">
            </div>
            <div class="field">
                <label>Descripción</label>
                <input class="input" name="diagnosis_description" placeholder="p. ej. Gastritis aguda">
            </div>
        </div>
    </div>

    <div class="card form-section">
        <h4>Tratamiento inicial</h4>
        <div class="form-grid cols-3">
            <div class="field">
                <label>Tratamiento / medicamento</label>
                <input class="input" name="treatment_description" placeholder="p. ej. Omeprazol 20 mg">
            </div>
            <div class="field">
                <label>Dosis</label>
                <input class="input" name="treatment_dose" placeholder="p. ej. 1 cápsula">
            </div>
            <div class="field">
                <label>Frecuencia</label>
                <input class="input" name="treatment_frequency" placeholder="p. ej. cada 24 h">
            </div>
            <div class="field">
                <label>Duración</label>
                <input class="input" name="treatment_duration" placeholder="p. ej. 14 días">
            </div>
        </div>
    </div>

    <div class="card form-section">
        <h4>Nota adicional</h4>
        <div class="form-grid cols-2">
            <div class="field">
                <label>Tipo</label>
                <select class="select" name="note_type">
                    <option value="OBSERVACION">Observación</option>
                    <option value="INFORME">Informe</option>
                    <option value="RECETA">Receta</option>
                    <option value="OTRO">Otro</option>
                </select>
            </div>
            <div class="field">
                <label>Contenido</label>
                <textarea class="input" name="note_content" rows="2" placeholder="Opcional"></textarea>
            </div>
        </div>
    </div>

    <div style="display:flex;gap:10px">
        <button class="btn btn-primary" type="submit">Registrar consulta</button>
        <a class="btn" href="/pacientes/<?= $pid ?>?tab=consultas">Cancelar</a>
    </div>
</form>