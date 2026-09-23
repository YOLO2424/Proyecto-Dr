<div class="topbar">
    <div>
        <h1>Auditoría</h1>
        <div class="sub">Trazabilidad de acciones sobre el expediente.</div>
    </div>
    <a class="btn" href="/">← Inicio</a>
</div>

<div class="card" style="padding:0;overflow:auto">
    <table class="table">
        <thead><tr><th>Fecha</th><th>Acción</th><th>Entidad</th><th>Detalles</th></tr></thead>
        <tbody>
            <?php if (!$entries): ?>
            <tr><td colspan="4"><div class="empty"><div class="big">Sin eventos registrados</div></div></td></tr>
            <?php else: ?>
                <?php foreach ($entries as $e): ?>
                <tr>
                    <td style="white-space:nowrap;font-size:0.82rem"><?= \App\Core\View::datetime($e['created_at']) ?></td>
                    <td><span class="tag tag-accent"><?= \App\Core\View::e($e['action']) ?></span></td>
                    <td style="font-size:0.82rem">
                        <?= \App\Core\View::e($e['entity_type'] ?? '—') ?>
                        <?php if ($e['entity_type'] === 'patients' && $e['details']): ?>
                            <?php $det = json_decode($e['details'], true); ?>
                            <br><?= \App\Core\View::e($det['patient_id'] ?? '') ?>
                        <?php endif; ?>
                    </td>
                    <td style="font-size:0.78rem;color:var(--muted);max-width:420px;word-break:break-word"><?= \App\Core\View::e($e['details']) ?></td>
                </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>