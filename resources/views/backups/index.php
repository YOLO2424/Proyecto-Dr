<div class="topbar">
    <div>
        <h1>Backups</h1>
        <div class="sub">Los respaldos son generados por el sistema (a prueba de WAL). No copies el archivo .sqlite a mano.</div>
    </div>
    <div style="display:flex;gap:8px;flex-wrap:wrap">
        <form method="post" action="/soporte/salir" data-confirm="¿Cerrar el acceso de Soporte/TI?" style="display:inline">
            <button class="btn btn-sm" type="submit">Cerrar acceso</button>
        </form>
        <a class="btn" href="/">← Inicio</a>
    </div>
</div>

<div class="card" style="margin-bottom:18px;text-align:center">
    <h3>Crear respaldo</h3>
    <p class="sub" style="font-size:0.85rem;color:var(--muted);margin-bottom:14px">
        Incluye base de datos (snapshot consistente) + todos los documentos. Se retienen según configuración.
    </p>
    <form method="post" action="/backups">
        <button class="btn btn-primary" type="submit">Crear backup ZIP</button>
    </form>
</div>

<div class="card" style="padding:0;overflow:auto">
    <table class="table">
        <thead><tr><th>Archivo</th><th>Fecha</th><th>Tamaño</th><th></th></tr></thead>
        <tbody>
            <?php if (!$backups): ?>
            <tr><td colspan="4"><div class="empty"><div class="big">Sin respaldos todavía</div>Crea tu primer backup ahora mismo.</div></td></tr>
            <?php else: ?>
                <?php foreach ($backups as $b): ?>
                <tr>
                    <td style="font-weight:600"><?= \App\Core\View::e($b['name']) ?></td>
                    <td><?= \App\Core\View::datetime($b['created_at']) ?></td>
                    <td><?= round($b['size'] / 1024 / 1024, 2) ?> MB</td>
                    <td style="white-space:nowrap;text-align:right">
                        <a class="btn btn-sm" href="/backups/<?= rawurlencode($b['name']) ?>">Descargar</a>
                        <form method="post" action="/backups/<?= rawurlencode($b['name']) ?>/eliminar" data-confirm="¿Eliminar este respaldo?" style="display:inline">
                            <button class="btn btn-sm btn-danger" type="submit">Borrar</button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>