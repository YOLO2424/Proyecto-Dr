<div class="topbar">
    <div>
        <h1>Historial clínico</h1>
        <div class="sub"><?= \App\Core\View::e($patient['last_name'] . ' ' . $patient['first_name']) ?> · <?= \App\Core\View::e($patient['patient_id']) ?></div>
    </div>
    <a class="btn" href="/pacientes/<?= \App\Core\View::e($patient['patient_id']) ?>?tab=consultas">← Volver a la ficha</a>
</div>

<?php $pid = \App\Core\View::e($patient['patient_id']); ?>
<?php require __DIR__ . '/_list.php'; ?>