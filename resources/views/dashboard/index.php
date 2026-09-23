<div class="topbar">
    <div>
        <h1><?= \App\Core\View::e($greeting . ($doctor ? ', ' . $doctor : '')) ?></h1>
        <div class="sub">Resumen del consultorio · <?= date('l d \d\e F \d\e Y') ?></div>
    </div>
    <div style="display:flex;gap:8px;flex-wrap:wrap">
        <a class="btn btn-primary" href="/pacientes/nuevo">+ Nuevo paciente</a>
        <a class="btn" href="/pacientes?filter=ACTIVE">Escanear / buscar QR</a>
    </div>
</div>

<div class="stat-grid" style="margin-bottom:22px">
    <div class="stat"><div class="value"><?= (int) $active ?></div><div class="label">Pacientes activos</div></div>
    <div class="stat"><div class="value"><?= (int) $today ?></div><div class="label">Consultas hoy</div></div>
    <div class="stat"><div class="value"><?= (int) $pending ?></div><div class="label">Pre-registros pendientes</div></div>
    <div class="stat"><div class="value"><?= (int) $inactive ?></div><div class="label">Inactivos</div></div>
</div>

<div class="card" style="margin-bottom:18px">
    <div class="head" style="display:flex;justify-content:space-between;align-items:center;margin-bottom:12px">
        <h3 style="margin:0">Pacientes recientes</h3>
        <a href="/pacientes" class="btn btn-sm btn-ghost">Ver todos →</a>
    </div>
    <?php if (!$recent): ?>
        <div class="empty">
            <div class="big">Sin pacientes todavía</div>
            Crea el primer paciente con el botón <b>+ Nuevo paciente</b>.
        </div>
    <?php else: ?>
        <?php foreach ($recent as $p): ?>
            <a class="patient-row" href="/pacientes/<?= \App\Core\View::e($p['patient_id']) ?>" style="display:flex">
                <div class="avatar"><?= \App\Core\View::e(mb_strtoupper(mb_substr($p['first_name'], 0, 1))) ?></div>
                <div class="grow">
                    <div class="name"><?= \App\Core\View::e($p['last_name'] . ' ' . $p['first_name']) ?></div>
                    <div class="id"><?= \App\Core\View::e($p['patient_id']) ?>
                        <?= $p['status'] === 'INACTIVE' ? '<span class="tag tag-warn">INACTIVO</span>' : '' ?>
                    </div>
                </div>
                <div class="meta"><?= $p['last_encounter'] ? ('Última consulta · ' . \App\Core\View::date($p['last_encounter'])) : 'Sin consultas' ?></div>
            </a>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<div class="card">
    <h3>Acciones rápidas</h3>
    <div style="display:flex;gap:10px;flex-wrap:wrap">
        <a class="btn" href="/registros">Revisar pre-registros (<?= (int) $pending ?>)</a>
        <a class="btn" href="/backups">Crear backup</a>
    </div>
    <p class="sub" style="margin-top:10px;color:var(--muted);font-size:0.85rem">
        Consejo: genera un backup al menos una vez por semana. Los ID de paciente (PAC-0000…) no se reutilizan jamás.
    </p>
</div>