<div class="topbar">
    <div>
        <h1>Configuración</h1>
        <div class="sub">Datos del consultorio y comportamiento del sistema.</div>
    </div>
    <a class="btn" href="/">← Inicio</a>
</div>

<form method="post" action="/configuracion">
    <div class="card form-section">
        <h4>Consultorio</h4>
        <div class="form-grid cols-2">
            <div class="field">
                <label>Nombre del consultorio</label>
                <input class="input" name="clinic_name" value="<?= \App\Core\View::e($settings['clinic_name'] ?? '') ?>">
            </div>
            <div class="field">
                <label>Nombre del médico</label>
                <input class="input" name="doctor_name" value="<?= \App\Core\View::e($settings['doctor_name'] ?? '') ?>">
            </div>
        </div>
    </div>

    <div class="card form-section">
        <h4>Pre-registro</h4>
        <div class="form-grid cols-2">
            <div class="field">
                <label>Ruta del formulario externo</label>
                <input class="input" name="registration_url" value="<?= \App\Core\View::e($settings['registration_url'] ?? '/registro') ?>">
                <label style="margin-top:6px;font-weight:400;color:var(--muted);font-size:0.78rem">El QR de pre-registro codifica esta ruta + el token.</label>
            </div>
        </div>
    </div>

    <div class="card form-section">
        <h4>Respaldo</h4>
        <div class="form-grid cols-2">
            <div class="field">
                <label>Retención de backups (días)</label>
                <input class="input" type="number" min="1" name="backup_retention_days" value="<?= \App\Core\View::e($settings['backup_retention_days'] ?? '30') ?>">
            </div>
        </div>
    </div>

    <button class="btn btn-primary" type="submit">Guardar configuración</button>
</form>

<div class="card" style="margin-top:20px">
    <h3>Reglas del sistema</h3>
    <ul style="color:var(--muted);font-size:0.88rem;padding-left:18px;line-height:1.9">
        <li>El ID del paciente (PAC-…) es único, permanente y <b>nunca se reutiliza</b>, aunque el paciente se elimine.</li>
        <li>El QR es reemplazable y revocable; solo contiene un token opaco, nunca datos clínicos.</li>
        <li>La historia clínica es cronológica y fechada; las modificaciones versionan el estado anterior.</li>
        <li>El pre-registro externo solo crea solicitudes pendientes de aprobación.</li>
        <li>Los backups los genera el sistema (snapshot consistente); evita copiar el archivo .sqlite a mano.</li>
    </ul>
</div>