<div style="max-width:430px;margin:6vh auto 0;padding:0 12px">
    <div class="card" style="padding:28px">
        <div class="brand" style="padding:0 0 12px">Acceso de Soporte/TI</div>
        <p class="sub" style="color:var(--muted);font-size:0.88rem;margin-bottom:18px">
            Crear, descargar o eliminar backups requiere la clave del personal autorizado.
        </p>

        <?php if (!$configured): ?>
            <div class="flash error">⚠️ La clave de Soporte/TI aún no está configurada.
                Configúrala en <a href="/configuracion">Configuración</a> para habilitar los backups.</div>
        <?php endif; ?>

        <form method="post" action="/soporte">
            <div class="field">
                <label>Clave de Soporte/TI</label>
                <input class="input" type="password" name="pin" autocomplete="off" autofocus required>
            </div>
            <button class="btn btn-primary btn-block" type="submit">Ingresar</button>
        </form>
        <a class="btn btn-ghost btn-block" href="/" style="margin-top:10px">← Volver al inicio</a>
    </div>
</div>