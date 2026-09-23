<div class="topbar">
    <div>
        <h1>Pacientes</h1>
        <div class="sub"><?= (int) ($counts['ACTIVE'] + $counts['INACTIVE']) ?> registrados · <?= (int) $counts['DELETED'] ?> eliminados (IDs reservados)</div>
    </div>
    <a class="btn btn-primary" href="/pacientes/nuevo">+ Nuevo paciente</a>
</div>

<form class="search" method="get" action="/pacientes" style="margin-bottom:16px">
    <input class="input" type="search" name="q" value="<?= \App\Core\View::e($q) ?>" placeholder="Buscar por nombre, documento o ID…">
    <select class="select" name="filter" style="width:auto">
        <option value="ACTIVE" <?= $filter === 'ACTIVE' ? 'selected' : '' ?>>Activos e inactivos</option>
        <option value="INACTIVE" <?= $filter === 'INACTIVE' ? 'selected' : '' ?>>Solo inactivos</option>
        <option value="DELETED" <?= $filter === 'DELETED' ? 'selected' : '' ?>>Solo eliminados</option>
    </select>
    <button class="btn" type="submit">Buscar</button>
</form>

<?php if (!$patients): ?>
    <div class="card empty">
        <div class="big">No hay resultados</div>
        <?= $q ? 'Ningún paciente coincide con "' . \App\Core\View::e($q) . '".' : 'Aún no hay pacientes en esta vista.' ?>
    </div>
<?php endif; ?>

<?php foreach ($patients as $p): ?>
    <?php
        $role = $p['status'];
        $tag = $p['status'] === 'DELETED' ? '<span class="tag tag-danger">ELIMINADO</span>'
            : ($p['status'] === 'INACTIVE' ? '<span class="tag tag-warn">INACTIVO</span>'
            : '<span class="tag tag-ok">ACTIVO</span>');
    ?>
    <div class="patient-row">
        <div class="avatar"><?= \App\Core\View::e(mb_strtoupper(mb_substr($p['first_name'] ?? '?', 0, 1))) ?></div>
        <div class="grow">
            <div class="name"><?= \App\Core\View::e($p['last_name'] . ' ' . $p['first_name']) ?></div>
            <div class="id"><?= \App\Core\View::e($p['patient_id']) ?> <?= $tag ?></div>
            <div class="meta">Nacimiento: <?= \App\Core\View::date($p['birth_date']) ?> · <?= $p['last_encounter'] ? 'Última consulta ' . \App\Core\View::date($p['last_encounter']) : 'Sin consultas' ?></div>
        </div>
        <div style="display:flex;gap:8px;flex-wrap:wrap">
            <?php if ($p['status'] === 'DELETED'): ?>
                <form method="post" action="/pacientes/<?= \App\Core\View::e($p['patient_id']) ?>/restaurar">
                    <button class="btn btn-sm" type="submit">Restaurar</button>
                </form>
            <?php else: ?>
                <a class="btn btn-sm" href="/pacientes/<?= \App\Core\View::e($p['patient_id']) ?>">Ver</a>
            <?php endif; ?>
        </div>
    </div>
<?php endforeach; ?>