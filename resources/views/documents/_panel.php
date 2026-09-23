<?php /** Panel de documentos del paciente. Espera $pid, $documents y $encounterId opcional. */ ?>
<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px;flex-wrap:wrap;gap:10px">
    <h3 style="margin:0">Documentos</h3>
    <a class="btn" href="/pacientes/<?= $pid ?>">← Volver</a>
</div>

<div class="card" style="margin-bottom:16px">
    <h3>Subir documento</h3>
    <form method="post" action="/pacientes/<?= $pid ?>/documentos" enctype="multipart/form-data">
        <div class="form-grid cols-3">
            <div class="field">
                <label>Archivo *</label>
                <input class="input" type="file" name="document" accept=".pdf,.jpg,.jpeg,.png" required>
            </div>
            <div class="field">
                <label>Categoría</label>
                <select class="select" name="category">
                    <option value="">General</option>
                    <option>INFORME</option>
                    <option>RECETA</option>
                    <option>ESTUDIO</option>
                    <option>CONSENTIMIENTO</option>
                    <option>OTRO</option>
                </select>
            </div>
            <div class="field">
                <label>Descripción</label>
                <input class="input" name="description" placeholder="Opcional">
            </div>
        </div>
        <?php if (isset($encounterId) && $encounterId): ?>
            <input type="hidden" name="encounter_id" value="<?= (int) $encounterId ?>">
            <p class="sub" style="margin:8px 0;font-size:0.8rem;color:var(--muted)">Se vinculará a la consulta actual.</p>
        <?php endif; ?>
        <button class="btn btn-primary" style="margin-top:10px" type="submit">Subir</button>
        <span class="sub" style="margin-left:10px;font-size:0.78rem;color:var(--muted)">PDF · JPG · PNG · máx. 15 MB</span>
    </form>
</div>

<?php if (!$documents): ?>
    <div class="card empty">
        <div class="big">Sin documentos</div>
        Sube informes, recetas o estudios con el formulario superior.
    </div>
<?php else: ?>
    <div class="table-wrap card" style="padding:0;overflow:auto">
        <table class="table">
            <thead>
                <tr><th>Archivo</th><th>Categoría</th><th>Fecha</th><th>Tamaño</th><th></th></tr>
            </thead>
            <tbody>
                <?php foreach ($documents as $d): ?>
                <tr>
                    <td>
                        <div style="font-weight:600"><?= \App\Core\View::e($d['original_name']) ?></div>
                        <?php if ($d['description']): ?><div style="color:var(--muted);font-size:0.8rem"><?= \App\Core\View::e($d['description']) ?></div><?php endif; ?>
                    </td>
                    <td><?= $d['category'] ? '<span class="tag tag-accent">' . \App\Core\View::e($d['category']) . '</span>' : '—' ?></td>
                    <td><?= \App\Core\View::datetime($d['created_at']) ?></td>
                    <td><?= round($d['size'] / 1024, 1) ?> KB</td>
                    <td style="white-space:nowrap">
                        <a class="btn btn-sm" href="/pacientes/<?= $pid ?>/documentos/<?= (int) $d['id'] ?>/descargar">Descargar</a>
                        <form method="post" action="/pacientes/<?= $pid ?>/documentos/<?= (int) $d['id'] ?>/eliminar" data-confirm="¿Eliminar este documento? Esta acción no podrá revertirse." style="display:inline">
                            <button class="btn btn-sm btn-danger" type="submit">Borrar</button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>