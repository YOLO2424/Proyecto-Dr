<?php
$pid = \App\Core\View::e($patient['patient_id']);
$eid = (int) $encounter['id'];
$enc = $encounter;
?>
<div class="topbar">
    <div>
        <h1>Consulta · <?= \App\Core\View::date($enc['encounter_date']) ?></h1>
        <div class="sub"><?= \App\Core\View::e($patient['last_name'] . ' ' . $patient['first_name']) ?> · <?= $pid ?></div>
    </div>
    <div style="display:flex;gap:8px;flex-wrap:wrap">
        <a class="btn" href="/consultas/<?= $eid ?>/informe" target="_blank">Informe PDF</a>
        <a class="btn" href="/consultas/<?= $eid ?>/receta" target="_blank">Receta PDF</a>
        <a class="btn" href="/pacientes/<?= $pid ?>?tab=consultas">← Volver</a>
    </div>
</div>

<div class="flash info">
    Fecha de atención: <b><?= \App\Core\View::date($enc['encounter_date']) ?></b> ·
    Registrada: <b><?= \App\Core\View::datetime($enc['recorded_at']) ?></b> ·
    Última actualización: <b><?= \App\Core\View::datetime($enc['updated_at']) ?></b>
</div>

<details class="card" style="margin-bottom:16px" <?= $_GET['edit'] ?? '' ? 'open' : '' ?>>
    <summary style="cursor:pointer;font-weight:650">Editar datos de la consulta</summary>
    <form method="post" action="/consultas/<?= $eid ?>/actualizar" style="margin-top:14px">
        <div class="form-grid cols-2">
            <div class="field">
                <label>Fecha de atención</label>
                <input class="input" name="encounter_date" value="<?= \App\Core\View::e(\App\Validators\PatientValidator::denormalizeDate($enc['encounter_date'])) ?>">
            </div>
            <div class="field">
                <label>Motivo</label>
                <input class="input" name="reason" value="<?= \App\Core\View::e($enc['reason']) ?>">
            </div>
            <div class="field">
                <label>Subjetivo</label>
                <textarea class="input" name="subjective" rows="2"><?= \App\Core\View::e($enc['subjective']) ?></textarea>
            </div>
            <div class="field">
                <label>Objetivo</label>
                <textarea class="input" name="objective" rows="2"><?= \App\Core\View::e($enc['objective']) ?></textarea>
            </div>
            <div class="field" style="grid-column:1/-1">
                <label>Plan</label>
                <textarea class="input" name="plan" rows="2"><?= \App\Core\View::e($enc['plan']) ?></textarea>
            </div>
        </div>
        <button class="btn btn-primary" style="margin-top:10px" type="submit">Guardar (se guarda versión anterior)</button>
    </form>
    <?php if (count($versions) > 0): ?>
        <p class="sub" style="margin-top:12px;font-size:0.78rem;color:var(--muted)"><?= count($versions) ?> versión(es) anterior(es) conservadas · ver historial al final de esta página.</p>
    <?php endif; ?>
</details>

<div class="grid" style="grid-template-columns:1fr">
    <div class="card">
        <h3>Diagnósticos</h3>
        <form method="post" action="/consultas/<?= $eid ?>/diagnostico" class="form-grid cols-3" style="margin-bottom:16px">
            <input class="input" name="code" placeholder="Código">
            <input class="input" name="description" placeholder="Descripción *" required>
            <button class="btn btn-primary" type="submit">Agregar</button>
        </form>
        <?php foreach ($enc['diagnoses'] as $d): ?>
            <?php $editDiag = (int) ($_GET['diagedit'] ?? 0) === (int) $d['id']; ?>
            <div class="patient-row" style="box-shadow:none">
                <div class="grow">
                    <div class="name"><?= \App\Core\View::e($d['description']) ?>
                        <?php if ($d['code']): ?><span class="tag tag-accent"><?= \App\Core\View::e($d['code']) ?></span><?php endif; ?>
                        <span class="tag <?= $d['status'] === 'ACTIVE' ? 'tag-warn' : ($d['status'] === 'CHRONIC' ? 'tag-accent' : 'tag-ok') ?>"><?= \App\Core\View::e($d['status']) ?></span>
                    </div>
                    <?php if ($d['notes']): ?><div class="meta"><?= \App\Core\View::e($d['notes']) ?></div><?php endif; ?>
                </div>
                <a class="btn btn-sm" href="/consultas/<?= $eid ?>?diagedit=<?= (int) $d['id'] ?>">Editar</a>
            </div>
            <?php if ($editDiag): ?>
                <form method="post" action="/consultas/<?= $eid ?>/diagnostico/<?= (int) $d['id'] ?>/editar" class="card" style="margin:-6px 0 14px;box-shadow:none;border-color:var(--accent)">
                    <div class="form-grid cols-2">
                        <div class="field"><label>Código</label><input class="input" name="code" value="<?= \App\Core\View::e($d['code']) ?>"></div>
                        <div class="field"><label>Descripción *</label><input class="input" name="description" value="<?= \App\Core\View::e($d['description']) ?>" required></div>
                        <div class="field">
                            <label>Estado</label>
                            <select class="select" name="status">
                                <option value="ACTIVE" <?= $d['status'] === 'ACTIVE' ? 'selected' : '' ?>>ACTIVE</option>
                                <option value="CHRONIC" <?= $d['status'] === 'CHRONIC' ? 'selected' : '' ?>>CHRONIC</option>
                                <option value="RESOLVED" <?= $d['status'] === 'RESOLVED' ? 'selected' : '' ?>>RESOLVED</option>
                            </select>
                        </div>
                        <div class="field"><label>Notas</label><input class="input" name="notes" value="<?= \App\Core\View::e($d['notes']) ?>"></div>
                    </div>
                    <div style="display:flex;gap:8px;margin-top:10px">
                        <button class="btn btn-primary btn-sm" type="submit">Guardar (versiona)</button>
                        <a class="btn btn-sm" href="/consultas/<?= $eid ?>">Cancelar</a>
                    </div>
                </form>
            <?php endif; ?>
        <?php endforeach; ?>
    </div>

    <div class="card">
        <h3>Tratamientos</h3>
        <form method="post" action="/consultas/<?= $eid ?>/tratamiento" class="form-grid cols-3" style="margin-bottom:16px">
            <input class="input" name="description" placeholder="Tratamiento / medicamento *" required>
            <input class="input" name="dose" placeholder="Dosis">
            <input class="input" name="frequency" placeholder="Frecuencia">
            <input class="input" name="duration" placeholder="Duración">
            <button class="btn btn-primary" type="submit">Agregar</button>
        </form>
        <?php foreach ($enc['treatments'] as $t): ?>
            <?php $editTx = (int) ($_GET['txedit'] ?? 0) === (int) $t['id']; ?>
            <div class="patient-row" style="box-shadow:none">
                <div class="grow">
                    <div class="name"><?= \App\Core\View::e($t['description']) ?></div>
                    <div class="meta"><?= \App\Core\View::e(trim(($t['dose'] ?? '') . ($t['frequency'] ? ' · ' . $t['frequency'] : '') . ($t['duration'] ? ' · ' . $t['duration'] : '') )) ?: '—' ?></div>
                </div>
                <a class="btn btn-sm" href="/consultas/<?= $eid ?>?txedit=<?= (int) $t['id'] ?>">Editar</a>
            </div>
            <?php if ($editTx): ?>
                <form method="post" action="/consultas/<?= $eid ?>/tratamiento/<?= (int) $t['id'] ?>/editar" class="card" style="margin:-6px 0 14px;box-shadow:none;border-color:var(--accent)">
                    <div class="form-grid cols-2">
                        <div class="field"><label>Descripción *</label><input class="input" name="description" value="<?= \App\Core\View::e($t['description']) ?>" required></div>
                        <div class="field"><label>Dosis</label><input class="input" name="dose" value="<?= \App\Core\View::e($t['dose']) ?>"></div>
                        <div class="field"><label>Frecuencia</label><input class="input" name="frequency" value="<?= \App\Core\View::e($t['frequency']) ?>"></div>
                        <div class="field"><label>Duración</label><input class="input" name="duration" value="<?= \App\Core\View::e($t['duration']) ?>"></div>
                    </div>
                    <div style="display:flex;gap:8px;margin-top:10px">
                        <button class="btn btn-primary btn-sm" type="submit">Guardar (versiona)</button>
                        <a class="btn btn-sm" href="/consultas/<?= $eid ?>">Cancelar</a>
                    </div>
                </form>
            <?php endif; ?>
        <?php endforeach; ?>
    </div>

    <div class="card">
        <h3>Notas</h3>
        <form method="post" action="/consultas/<?= $eid ?>/nota" class="form-grid cols-2" style="margin-bottom:16px">
            <select class="select" name="note_type">
                <option value="OBSERVACION">Observación</option>
                <option value="INFORME">Informe</option>
                <option value="RECETA">Receta</option>
                <option value="OTRO">Otro</option>
            </select>
            <textarea class="input" name="note_content" rows="2" placeholder="Contenido de la nota *" required></textarea>
            <button class="btn btn-primary" type="submit">Agregar nota</button>
        </form>
        <?php foreach ($enc['notes'] as $n): ?>
            <div class="entry">
                <div class="head">
                    <span class="tag tag-accent"><?= \App\Core\View::e($n['note_type']) ?></span>
                    <span style="font-size:0.8rem;color:var(--muted)"><?= \App\Core\View::datetime($n['created_at']) ?></span>
                </div>
                <div style="white-space:pre-wrap"><?= \App\Core\View::e($n['content']) ?></div>
            </div>
        <?php endforeach; ?>
    </div>

    <div class="card">
        <h3>Documentos de la consulta</h3>
        <?php
            $encounterId = $eid;
            $documents = $enc['documents'];
            require dirname(__DIR__) . '/documents/_panel.php';
        ?>
    </div>
</div>

<?php if (count($versions) > 0): ?>
    <div class="card" style="margin-top:18px">
        <h3>Historial de versiones de esta consulta</h3>
        <p class="sub" style="font-size:0.8rem;color:var(--muted);margin-bottom:12px">
            Las modificaciones no se sobrescriben silenciosamente: cada cambio guarda el estado anterior.
        </p>
        <?php $labels = ['encounter' => 'Consulta', 'diagnosis' => 'Diagnóstico', 'treatment' => 'Tratamiento']; ?>
        <?php foreach ($versions as $v): $label = $labels[$v['entity_type']] ?? $v['entity_type']; ?>
            <details class="entry">
                <summary style="cursor:pointer">
                    <b>v<?= (int) $v['version'] ?></b> · <?= \App\Core\View::e($label) ?> ·
                    <?= \App\Core\View::datetime($v['created_at']) ?>
                    <?php if ($v['entity_type'] === 'diagnosis'): ?> · antes: <?= \App\Core\View::e($v['data']['description'] ?? '') ?><?php endif; ?>
                    <?php if ($v['entity_type'] === 'encounter'): ?> · antes (motivo): <?= \App\Core\View::e($v['data']['reason'] ?? '') ?><?php endif; ?>
                </summary>
                <pre style="margin-top:10px;font-size:0.78rem;background:#f4f6f8;padding:10px;border-radius:8px;overflow:auto;white-space:pre-wrap"><?= \App\Core\View::e($v['data_json']) ?></pre>
            </details>
        <?php endforeach; ?>
    </div>
<?php endif; ?>