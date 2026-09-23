<div class="topbar">
    <div>
        <h1>Pre-registros</h1>
        <div class="sub">Las solicitudes externas nunca crean expedientes directamente. Deben aprobarse aquí.</div>
    </div>
    <a class="btn" href="/">← Inicio</a>
</div>

<div class="card" style="margin-bottom:18px">
    <h3>Generar QR de pre-registro</h3>
    <p class="sub" style="font-size:0.85rem;color:var(--muted);margin-bottom:10px">
        El QR lleva al formulario externo. Imprime el QR y colócalo a la vista de los pacientes.
    </p>
    <div class="search" style="gap:10px">
        <button class="btn btn-primary" data-generate-regqr type="button">Generar QR de registro</button>
        <div id="regqr-area" style="display:none">
            <div class="box" style="display:inline-block;padding:12px;border:1px solid var(--border);border-radius:12px;background:#fff">
                <img id="regqr-img" src="" alt="QR de registro" width="180" height="180">
                <div style="font-size:0.78rem;color:var(--muted);margin-top:6px;word-break:break-all" id="regqr-payload"></div>
            </div>
        </div>
    </div>
</div>

<h2 style="font-size:1.05rem;margin:18px 0 10px">Pendientes (<?= count($pending) ?>)</h2>
<?php if (!$pending): ?>
    <div class="card empty"><div class="big">Sin solicitudes pendientes</div></div>
<?php else: ?>
    <?php foreach ($pending as $r): $d = $r['data']; ?>
        <div class="card" style="margin-bottom:12px">
            <div class="head" style="display:flex;justify-content:space-between;gap:10px;flex-wrap:wrap">
                <div>
                    <b><?= \App\Core\View::e(trim(($d['last_name'] ?? '') . ' ' . ($d['first_name'] ?? ''))) ?: 'Sin nombre' ?></b>
                    <span class="tag tag-warn">PENDIENTE</span>
                    <div class="sub" style="font-size:0.8rem;color:var(--muted)">Recibida <?= \App\Core\View::datetime($r['created_at']) ?> · token <?= \App\Core\View::e(mb_substr($r['token'], 0, 10)) ?>…</div>
                </div>
            </div>
            <dl class="kv" style="grid-template-columns:repeat(auto-fit,minmax(200px,1fr));margin:10px 0">
                <div><dt>Documento</dt><dd><?= \App\Core\View::e($d['national_id'] ?? '—') ?></dd></div>
                <div><dt>Nacimiento</dt><dd><?= \App\Core\View::e($d['birth_date'] ?? '—') ?></dd></div>
                <div><dt>Teléfono</dt><dd><?= \App\Core\View::e($d['phone'] ?? '—') ?></dd></div>
                <div><dt>Correo</dt><dd><?= \App\Core\View::e($d['email'] ?? '—') ?></dd></div>
                <div><dt>Emergencia</dt><dd><?= trim(($d['emergency_name'] ?? '') . ' · ' . ($d['emergency_phone'] ?? ''), ' · ') ?: '—' ?></dd></div>
            </dl>
            <div style="display:flex;gap:8px;flex-wrap:wrap;align-items:center">
                <form method="post" action="/registros/<?= (int) $r['id'] ?>/aprobar">
                    <button class="btn btn-primary" type="submit">Aprobar y crear expediente</button>
                </form>
                <form method="post" action="/registros/<?= (int) $r['id'] ?>/rechazar">
                    <input class="input" name="reason" placeholder="Motivo del rechazo (opcional)" style="width:180px;padding:6px 10px;font-size:0.8rem">
                    <button class="btn btn-danger btn-sm" type="submit">Rechazar</button>
                </form>
            </div>
        </div>
    <?php endforeach; ?>
<?php endif; ?>

<h2 style="font-size:1.05rem;margin:22px 0 10px">Procesadas</h2>
<?php if (!$processed): ?>
    <div class="card empty"><div class="big">Ninguna procesada aún</div></div>
<?php else: ?>
    <div class="card" style="padding:0;overflow:auto">
        <table class="table">
            <thead><tr><th>Paciente</th><th>Estado</th><th>Archivo</th><th>Resultado</th></tr></thead>
            <tbody>
                <?php foreach ($processed as $r): $d = $r['data']; ?>
                <tr>
                    <td><?= \App\Core\View::e(trim(($d['last_name'] ?? '') . ' ' . ($d['first_name'] ?? ''))) ?></td>
                    <td><?= $r['status'] === 'APPROVED' ? '<span class="tag tag-ok">APROBADO</span>' : '<span class="tag tag-danger">RECHAZADO</span>' ?></td>
                    <td><?= \App\Core\View::datetime($r['decided_at']) ?></td>
                    <td>
                        <?= $r['status'] === 'APPROVED' && $r['patient_id'] ? '<a href="/pacientes/' . \App\Core\View::e($r['patient_id']) . '">' . \App\Core\View::e($r['patient_id']) . '</a>' : \App\Core\View::e($r['rejection_reason'] ?: '—') ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>

<script>
document.querySelector('[data-generate-regqr]').addEventListener('click', function () {
    fetch('/registros/qr/nuevo-token')
        .then(r => r.json())
        .then(function (data) {
            if (data.url) {
                var box = document.getElementById('regqr-area');
                box.style.display = 'inline-block';
                document.getElementById('regqr-img').src = '/qr/imagen?t=' + encodeURIComponent(data.url);
                document.getElementById('regqr-payload').textContent = data.url;
            }
        });
});
</script>