<?php /** Tarjeta imprimible: identidad + QR (sin datos clinicos). */ ?>
<div class="topbar print-actions">
    <div>
        <h1>Tarjeta del paciente</h1>
        <div class="sub">Imprimir · El QR solo identifica, no contiene datos clínicos</div>
    </div>
    <button class="btn btn-primary" data-print type="button">Imprimir / Guardar PDF</button>
</div>

<div class="card qr-card">
    <div class="clinic"><?= \App\Core\View::e($clinic) ?></div>
    <div style="font-size:1.3rem;font-weight:650"><?= \App\Core\View::e($patient['last_name'] . ' ' . $patient['first_name']) ?></div>
    <div class="pid"><?= \App\Core\View::e($patient['patient_id']) ?></div>
    <div class="box">
        <img src="/qr/imagen?t=<?= rawurlencode($payload) ?>" alt="QR" width="320" height="320" style="image-rendering:pixelated">
    </div>
    <?php if (!empty($patient['birth_date'])): ?>
        <div style="margin-top:14px;color:var(--muted)">Nacimiento: <?= \App\Core\View::date($patient['birth_date']) ?></div>
    <?php endif; ?>
</div>
<div class="print-actions">
    <a class="btn" href="/pacientes/<?= \App\Core\View::e($patient['patient_id']) ?>?tab=qr">← Volver</a>
</div>