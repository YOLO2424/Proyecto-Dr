<?php
/** Ficha publica del paciente (acceso por QR). Sin datos clinicos y sin consola del doctor. */
$name = trim(($patient['last_name'] ?? '') . ' ' . ($patient['first_name'] ?? ''));
$birth = !empty($patient['birth_date']) ? \App\Core\View::date($patient['birth_date']) : '—';
$gender = ['M' => 'Masculino', 'F' => 'Femenino', 'O' => 'Otro'][$patient['gender'] ?? null] ?? '—';
$contact = $contact ?? [];
$emergency = $emergency ?? [];
$insurance = array_filter($insurance ?? []);
$hasEmergency = (bool) $emergency;
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="robots" content="noindex, nofollow">
<title>Expediente del paciente · <?= \App\Core\View::e($clinicName) ?></title>
<link rel="stylesheet" href="/assets/css/app.css">
</head>
<body>
<div class="external-wrap" style="place-items:start center">
    <div style="width:100%;max-width:680px">

        <div class="card" style="padding:26px;text-align:center;margin-bottom:14px">
            <div class="clinic" style="color:var(--muted);font-size:0.85rem;margin-bottom:6px"><?= \App\Core\View::e($clinicName) ?></div>
            <h1 style="font-size:1.4rem;letter-spacing:-0.02em"><?= \App\Core\View::e($name) ?></h1>
            <div class="pid" style="font-weight:700;color:var(--accent);margin-top:4px"><?= \App\Core\View::e($patient['patient_id']) ?></div>
            <div class="hint" style="font-size:0.82rem;margin-top:12px">Presenta este código o tu ID al personal del consultorio.</div>
        </div>

        <div class="card" style="margin-bottom:14px">
            <h3 style="font-size:0.95rem;margin-bottom:12px">Identificación</h3>
            <dl class="kv" style="grid-template-columns:repeat(auto-fit,minmax(180px,1fr))">
                <div><dt>Documento</dt><dd><?= \App\Core\View::e($patient['national_id'] ?? '—') ?></dd></div>
                <div><dt>Fecha de nacimiento</dt><dd><?= $birth ?></dd></div>
                <div><dt>Sexo</dt><dd><?= $gender ?></dd></div>
            </dl>
        </div>

        <div class="card" style="margin-bottom:14px">
            <h3 style="font-size:0.95rem;margin-bottom:12px">Contacto</h3>
            <dl class="kv" style="grid-template-columns:repeat(auto-fit,minmax(180px,1fr))">
                <div><dt>Teléfono principal</dt><dd><?= \App\Core\View::e($contact['phone_primary'] ?? '—') ?></dd></div>
                <div><dt>Teléfono secundario</dt><dd><?= \App\Core\View::e($contact['phone_secondary'] ?? '—') ?></dd></div>
                <div><dt>Correo</dt><dd><?= \App\Core\View::e($contact['email'] ?? '—') ?></dd></div>
                <div><dt>Dirección</dt><dd><?= \App\Core\View::e(trim(($contact['address'] ?? '') . ', ' . ($contact['city'] ?? '') . ', ' . ($contact['region'] ?? '') . ' ' . ($contact['postal_code'] ?? ''), ' ,')) ?: '—' ?></dd></div>
            </dl>
        </div>

        <?php if ($hasEmergency): ?>
        <div class="card" style="margin-bottom:14px">
            <h3 style="font-size:0.95rem;margin-bottom:12px">Contacto de emergencia</h3>
            <dl class="kv" style="grid-template-columns:repeat(auto-fit,minmax(180px,1fr))">
                <div><dt>Nombre</dt><dd><?= \App\Core\View::e($emergency[0]['full_name'] ?? '—') ?></dd></div>
                <div><dt>Parentesco</dt><dd><?= \App\Core\View::e($emergency[0]['relationship'] ?? '—') ?></dd></div>
                <div><dt>Teléfono</dt><dd><?= \App\Core\View::e(trim(($emergency[0]['phone'] ?? '') . (($emergency[0]['alt_phone'] ?? '') ? ' / ' . $emergency[0]['alt_phone'] : ''))) ?: '—' ?></dd></div>
            </dl>
        </div>
        <?php endif; ?>

        <?php if (!empty($insurance)): ?>
        <div class="card" style="margin-bottom:14px">
            <h3 style="font-size:0.95rem;margin-bottom:12px">Seguro</h3>
            <dl class="kv" style="grid-template-columns:repeat(auto-fit,minmax(180px,1fr))">
                <div><dt>Aseguradora</dt><dd><?= \App\Core\View::e($insurance['provider'] ?? '—') ?></dd></div>
                <div><dt>Póliza</dt><dd><?= \App\Core\View::e($insurance['policy_number'] ?? '—') ?></dd></div>
                <div><dt>Titular</dt><dd><?= \App\Core\View::e(trim(($insurance['holder_name'] ?? '') . (($insurance['holder_relationship'] ?? '') ? ' (' . $insurance['holder_relationship'] . ')' : ''))) ?: '—' ?></dd></div>
                <div><dt>Empresa</dt><dd><?= \App\Core\View::e($insurance['company'] ?? '—') ?></dd></div>
            </dl>
        </div>
        <?php endif; ?>

        <p class="sub" style="text-align:center;color:var(--muted);font-size:0.78rem;padding:0 10px">
            Este expediente registra solo datos de identificación y contacto, sin historia clínica.
        </p>
    </div>
</div>
</body>
</html>