<?php
$tab = $_GET['tab'] ?? 'resumen';
$pid = \App\Core\View::e($patient['patient_id']);
$payload = 'PATIENT:' . ($qr['token'] ?? '');
$statusTag = $patient['status'] === 'DELETED'
    ? '<span class="tag tag-danger">ELIMINADO</span>'
    : ($patient['status'] === 'INACTIVE'
        ? '<span class="tag tag-warn">INACTIVO</span>'
        : '<span class="tag tag-ok">ACTIVO</span>');
?>
<div class="topbar">
    <div>
        <h1><?= \App\Core\View::e($patient['last_name'] . ' ' . $patient['first_name']) ?> <?= $statusTag ?></h1>
        <div class="id" style="color:var(--accent);font-weight:600"><?= $pid ?>
            <?php if ($patient['status'] !== 'DELETED' && !empty($qr)): ?>
                · <a href="/pacientes/<?= $pid ?>/qr/tarjeta" target="_blank" style="font-weight:500">Tarjeta QR</a>
            <?php endif; ?>
        </div>
    </div>
    <div style="display:flex;gap:8px;flex-wrap:wrap">
        <?php if ($patient['status'] === 'DELETED'): ?>
            <form method="post" action="/pacientes/<?= $pid ?>/restaurar" data-confirm="¿Restaurar al paciente y reactivar su expediente?">
                <button class="btn btn-primary" type="submit">Restaurar</button>
            </form>
        <?php else: ?>
            <a class="btn" href="/pacientes/<?= $pid ?>/consultas/nueva">+ Consulta</a>
            <a class="btn" href="/pacientes/<?= $pid ?>/editar">Editar</a>
            <?php if ($patient['status'] === 'INACTIVE'): ?>
                <form method="post" action="/pacientes/<?= $pid ?>/reactivar">
                    <button class="btn" type="submit">Reactivar</button>
                </form>
            <?php else: ?>
                <form method="post" action="/pacientes/<?= $pid ?>/desactivar" data-confirm="¿Desactivar al paciente? Conservará su ID, historial y QR.">
                    <button class="btn" type="submit">Desactivar</button>
                </form>
            <?php endif; ?>
            <form method="post" action="/pacientes/<?= $pid ?>/eliminar" data-confirm="⚠️ Eliminación administrativa. El ID <?= $patient['patient_id'] ?> quedará reservado para siempre. ¿Continuar?">
                <button class="btn btn-danger" type="submit">Eliminar</button>
            </form>
        <?php endif; ?>
    </div>
</div>

<div class="tabs">
    <a href="/pacientes/<?= $pid ?>?tab=resumen" class="<?= $tab === 'resumen' ? 'active' : '' ?>">Resumen</a>
    <a href="/pacientes/<?= $pid ?>?tab=consultas" class="<?= $tab === 'consultas' ? 'active' : '' ?>">Consultas</a>
    <a href="/pacientes/<?= $pid ?>?tab=documentos" class="<?= $tab === 'documentos' ? 'active' : '' ?>">Documentos</a>
    <a href="/pacientes/<?= $pid ?>?tab=informacion" class="<?= $tab === 'informacion' ? 'active' : '' ?>">Información</a>
    <?php if ($patient['status'] !== 'DELETED'): ?>
        <a href="/pacientes/<?= $pid ?>?tab=qr" class="<?= $tab === 'qr' ? 'active' : '' ?>">QR</a>
    <?php endif; ?>
</div>

<?php if ($tab === 'resumen'): ?>
    <div class="grid" style="grid-template-columns:1fr;margin-bottom:18px">
        <div class="card">
            <h3>Resumen clínico</h3>
            <div class="grid" style="grid-template-columns:repeat(auto-fit,minmax(220px,1fr))">
                <div>
                    <dl class="kv">
                        <dt>Fecha de nacimiento</dt><dd><?= \App\Core\View::date($patient['birth_date']) ?></dd>
                        <dt>Sexo</dt><dd><?= $patient['gender'] ? ['M' => 'Masculino', 'F' => 'Femenino', 'O' => 'Otro'][$patient['gender']] : '—' ?></dd>
                        <dt>Grupo sanguíneo</dt><dd><?= \App\Core\View::e($patient['blood_group'] ?: '—') ?></dd>
                    </dl>
                </div>
                <div>
                    <dl class="kv">
                        <dt>Última consulta</dt>
                        <dd><?php $lastEnc = $encounters[0] ?? null; ?><?= $lastEnc ? \App\Core\View::date($lastEnc['encounter_date']) : 'Ninguna registrada' ?></dd>
                        <dt>Teléfono</dt><dd><?= \App\Core\View::e($contact['phone_primary'] ?? '—') ?></dd>
                        <dt>Correo</dt><dd><?= \App\Core\View::e($contact['email'] ?? '—') ?></dd>
                    </dl>
                </div>
                <div>
                    <dl class="kv">
                        <dt>Alergias</dt><dd><?= count($allergies) ? ('<span class="tag tag-danger">' . count($allergies) . ' registradas</span>') : 'Sin alergias' ?></dd>
                        <dt>Condiciones / antecedentes</dt><dd><?= count($conditions) . ' registro(s)' ?></dd>
                        <dt>Medicamentos actuales</dt><dd><?= count(array_filter($medications, fn ($m) => $m['status'] === 'CURRENT')) . ' activo(s)' ?></dd>
                    </dl>
                </div>
            </div>
        </div>
    </div>

    <div class="grid">
        <div class="card">
            <h3>Alergias</h3>
            <form method="post" action="/pacientes/<?= $pid ?>/alergias" class="form-grid cols-2" style="margin-bottom:14px">
                <input class="input" name="allergen" placeholder="Alergeno *" required>
                <input class="input" name="reaction" placeholder="Reacción">
                <select class="select" name="severity">
                    <option value="">Severidad</option>
                    <option>LEVE</option><option>MODERADA</option><option>SEVERA</option>
                </select>
                <button class="btn btn-primary" type="submit">Agregar</button>
            </form>
            <?php foreach ($allergies as $a): ?>
                <div class="patient-row" style="box-shadow:none">
                    <div class="grow">
                        <div class="name"><?= \App\Core\View::e($a['allergen']) ?>
                            <?php if ($a['severity']): ?><span class="tag <?= $a['severity'] === 'SEVERA' ? 'tag-danger' : 'tag-warn' ?>"><?= \App\Core\View::e($a['severity']) ?></span><?php endif; ?>
                        </div>
                        <div class="meta"><?= \App\Core\View::e($a['reaction'] ?: '') ?></div>
                    </div>
                    <form method="post" action="/pacientes/<?= $pid ?>/alergias/<?= (int) $a['id'] ?>/eliminar">
                        <button class="btn btn-sm btn-danger" type="submit">Quitar</button>
                    </form>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="card">
            <h3>Condiciones y antecedentes</h3>
            <form method="post" action="/pacientes/<?= $pid ?>/condiciones" class="form-grid" style="margin-bottom:14px">
                <input class="input" name="condition_name" placeholder="Enfermedad, antecedente o cirugía *" required>
                <div class="form-grid cols-2">
                    <select class="select" name="category">
                        <option value="">Categoría</option>
                        <option>CONOCIDA</option><option>ANTECEDENTE</option><option>CIRUGIA</option>
                    </select>
                    <input class="input" name="diagnosis_date" placeholder="Fecha (DD/MM/AAAA)">
                </div>
                <button class="btn btn-primary" type="submit">Agregar</button>
            </form>
            <?php foreach ($conditions as $c): ?>
                <div class="patient-row" style="box-shadow:none">
                    <div class="grow">
                        <div class="name"><?= \App\Core\View::e($c['condition_name']) ?></div>
                        <div class="meta"><?= \App\Core\View::e($c['category'] ?: '') ?> <?= $c['diagnosis_date'] ? '· ' . \App\Core\View::date($c['diagnosis_date']) : '' ?></div>
                    </div>
                    <form method="post" action="/pacientes/<?= $pid ?>/condiciones/<?= (int) $c['id'] ?>/eliminar">
                        <button class="btn btn-sm btn-danger" type="submit">Quitar</button>
                    </form>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="card">
            <h3>Medicamentos</h3>
            <form method="post" action="/pacientes/<?= $pid ?>/medicamentos" class="form-grid cols-2" style="margin-bottom:14px">
                <input class="input" name="name" placeholder="Medicamento *" required>
                <input class="input" name="dose" placeholder="Dosis">
                <input class="input" name="frequency" placeholder="Frecuencia">
                <input class="input" name="started_at" placeholder="Inicio (DD/MM/AAAA)">
                <button class="btn btn-primary" type="submit">Agregar</button>
            </form>
            <?php foreach ($medications as $m): ?>
                <div class="patient-row" style="box-shadow:none">
                    <div class="grow">
                        <div class="name"><?= \App\Core\View::e($m['name']) ?>
                            <?php if ($m['status'] === 'CURRENT'): ?><span class="tag tag-ok">ACTIVO</span><?php else: ?><span class="tag">SUSPENDIDO</span><?php endif; ?>
                        </div>
                        <div class="meta"><?= \App\Core\View::e(trim(($m['dose'] ?? '') . ($m['frequency'] ? ' · ' . $m['frequency'] : ''))) ?></div>
                    </div>
                    <form method="post" action="/pacientes/<?= $pid ?>/medicamentos/<?= (int) $m['id'] ?>/eliminar">
                        <button class="btn btn-sm btn-danger" type="submit">Quitar</button>
                    </form>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

<?php elseif ($tab === 'consultas'): ?>
    <?php require __DIR__ . '/../clinical/_list.php'; ?>

<?php elseif ($tab === 'documentos'): ?>
    <?php require __DIR__ . '/../documents/_panel.php'; ?>

<?php elseif ($tab === 'informacion'): ?>
    <div class="grid" style="grid-template-columns:1fr">
        <div class="card">
            <h3>Identificación</h3>
            <dl class="kv" style="grid-template-columns:repeat(auto-fit,minmax(200px,1fr))">
                <div><dt>ID paciente</dt><dd><?= $pid ?></dd></div>
                <div><dt>Nombre</dt><dd><?= \App\Core\View::e($patient['first_name']) ?></dd></div>
                <div><dt>Apellidos</dt><dd><?= \App\Core\View::e($patient['last_name']) ?></dd></div>
                <div><dt>Documento</dt><dd><?= \App\Core\View::e($patient['national_id'] ?? '—') ?></dd></div>
                <div><dt>Nacimiento</dt><dd><?= \App\Core\View::date($patient['birth_date']) ?></dd></div>
                <div><dt>Sexo</dt><dd><?= $patient['gender'] ? ['M' => 'Masculino', 'F' => 'Femenino', 'O' => 'Otro'][$patient['gender']] : '—' ?></dd></div>
                <div><dt>Estado civil</dt><dd><?= \App\Core\View::e($patient['marital_status'] ?: '—') ?></dd></div>
                <div><dt>Nacionalidad</dt><dd><?= \App\Core\View::e($patient['nationality'] ?: '—') ?></dd></div>
                <div><dt>Lugar de nacimiento</dt><dd><?= \App\Core\View::e($patient['place_of_birth'] ?: '—') ?></dd></div>
            </dl>
        </div>
        <div class="card">
            <h3>Contacto</h3>
            <dl class="kv">
                <div><dt>Teléfono principal</dt><dd><?= \App\Core\View::e($contact['phone_primary'] ?? '—') ?></dd></div>
                <div><dt>Teléfono secundario</dt><dd><?= \App\Core\View::e($contact['phone_secondary'] ?? '—') ?></dd></div>
                <div><dt>Correo</dt><dd><?= \App\Core\View::e($contact['email'] ?? '—') ?></dd></div>
                <div><dt>Dirección</dt><dd><?= \App\Core\View::e(trim(($contact['address'] ?? '') . ', ' . ($contact['city'] ?? '') . ', ' . ($contact['region'] ?? '') . ' ' . ($contact['postal_code'] ?? ''), ' ,')) ?: '—' ?></dd></div>
            </dl>
        </div>
        <?php if ($emergency): ?>
        <div class="card">
            <h3>Contacto de emergencia</h3>
            <?php foreach ($emergency as $e): ?>
            <dl class="kv">
                <div><dt>Nombre</dt><dd><?= \App\Core\View::e($e['full_name'] ?: '—') ?></dd></div>
                <div><dt>Parentesco</dt><dd><?= \App\Core\View::e($e['relationship'] ?: '—') ?></dd></div>
                <div><dt>Teléfono</dt><dd><?= \App\Core\View::e($e['phone'] ?: '—') ?><?= $e['alt_phone'] ? ' / ' . \App\Core\View::e($e['alt_phone']) : '' ?></dd></div>
            </dl>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
        <?php if (!empty(array_filter($insurance ?? []))): ?>
        <div class="card">
            <h3>Seguro</h3>
            <dl class="kv">
                <div><dt>Aseguradora</dt><dd><?= \App\Core\View::e($insurance['provider'] ?? '—') ?></dd></div>
                <div><dt>Póliza</dt><dd><?= \App\Core\View::e($insurance['policy_number'] ?? '—') ?></dd></div>
                <div><dt>Titular</dt><dd><?= \App\Core\View::e($insurance['holder_name'] ?? '—') ?><?= $insurance['holder_relationship'] ? ' (' . \App\Core\View::e($insurance['holder_relationship']) . ')' : '' ?></dd></div>
                <div><dt>Empresa</dt><dd><?= \App\Core\View::e($insurance['company'] ?? '—') ?></dd></div>
            </dl>
        </div>
        <?php endif; ?>
        <div class="card">
            <h3>Registro</h3>
            <dl class="kv">
                <div><dt>Creado</dt><dd><?= \App\Core\View::datetime($patient['created_at']) ?></dd></div>
                <div><dt>Última actualización</dt><dd><?= \App\Core\View::datetime($patient['updated_at']) ?></dd></div>
                <div><dt>Desactivado / eliminado</dt><dd><?= \App\Core\View::datetime($patient['deleted_at']) ?></dd></div>
            </dl>
        </div>
    </div>

<?php elseif ($tab === 'qr' && $patient['status'] !== 'DELETED'): ?>
    <div class="grid" style="grid-template-columns:1fr">
        <div class="card">
            <h3>QR del paciente</h3>
            <p class="sub" style="color:var(--muted);font-size:0.88rem;margin-bottom:14px">
                El QR contiene <b>únicamente</b> un token opaco (sin identificador directo ni datos clínicos).
                Puedes rotarlo o revocarlo sin cambiar el ID <?= $pid ?> del paciente.
            </p>
            <?php if ($qr): ?>
                <div class="box" style="display:grid;place-items:center;padding:16px;border:1px solid var(--border);border-radius:12px;background:#fff;margin-bottom:12px">
                    <img src="/qr/imagen?t=<?= rawurlencode($payload ?? ('PATIENT:' . $qr['token'])) ?>" alt="QR del paciente" width="220" height="220">
                </div>
                <div style="display:flex;gap:8px;flex-wrap:wrap;margin-bottom:14px">
                    <a class="btn btn-primary" href="/pacientes/<?= $pid ?>/qr/tarjeta" target="_blank">Imprimir tarjeta</a>
                    <form method="post" action="/pacientes/<?= $pid ?>/qr/regenerar" data-confirm="¿Rotar el QR? El QR actual quedará revocado y se emitirá uno nuevo. El ID no cambia.">
                        <button class="btn" type="submit">Rotar QR</button>
                    </form>
                    <form method="post" action="/pacientes/<?= $pid ?>/qr/revocar" data-confirm="¿Revocar el QR activo? Dejará de funcionar hasta que emitas uno nuevo.">
                        <button class="btn btn-danger" type="submit">Revocar</button>
                    </form>
                </div>
            <?php else: ?>
                <div class="empty">
                    <div class="big">Sin QR activo</div>
                    <form method="post" action="/pacientes/<?= $pid ?>/qr/regenerar">
                        <button class="btn btn-primary" style="margin-top:10px" type="submit">Emitir nuevo QR</button>
                    </form>
                </div>
            <?php endif; ?>
        </div>
    </div>
<?php endif; ?>