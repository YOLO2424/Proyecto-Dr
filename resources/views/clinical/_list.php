<?php /** Listado de consultas (tambien usado como partial en la ficha). */ ?>
<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px;flex-wrap:wrap;gap:10px">
    <h3 style="margin:0">Historial de consultas</h3>
    <a class="btn btn-primary" href="/pacientes/<?= $pid ?>">← Volver</a>
</div>

<?php if (!$encounters): ?>
    <div class="card empty">
        <div class="big">Sin consultas registradas</div>
        Registra la primera consulta para comenzar el historial clínico.
        <div style="margin-top:14px"><a class="btn btn-primary" href="/pacientes/<?= $pid ?>/consultas/nueva">+ Nueva consulta</a></div>
    </div>
<?php else: ?>
    <div style="margin-bottom:14px"><a class="btn btn-primary" href="/pacientes/<?= $pid ?>/consultas/nueva">+ Nueva consulta</a></div>
    <?php foreach ($encounters as $e): ?>
        <a class="entry" href="/consultas/<?= (int) $e['id'] ?>" style="display:block">
            <div class="head">
                <div class="title">📅 Consulta · <?= \App\Core\View::date($e['encounter_date']) ?></div>
                <div class="meta" style="color:var(--muted);font-size:0.82rem">Registrada <?= \App\Core\View::datetime($e['recorded_at']) ?></div>
            </div>
            <div class="meta" style="color:var(--muted)">
                <?= $e['reason'] ? 'Motivo: ' . \App\Core\View::e($e['reason']) . ' · ' : '' ?>
                <?= count($e['diagnoses']) ?> diagnóstico(s) · <?= count($e['treatments']) ?> tratamiento(s)
            </div>
        </a>
    <?php endforeach; ?>
<?php endif; ?>