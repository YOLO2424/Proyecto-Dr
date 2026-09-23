<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="robots" content="noindex, nofollow">
<title>Registro de paciente</title>
<link rel="stylesheet" href="/assets/css/app.css">
</head>
<body>
<div class="external-wrap">
    <div class="external-card">
        <h1>Pre-registro del paciente</h1>
        <div class="hint">Completa tus datos. Esta solicitud queda <b>pendiente</b> de aprobación por el médico; no crea un expediente automáticamente.</div>

        <form method="post" action="/registro/<?= \App\Core\View::e($token) ?>">
            <div class="form-grid">
                <div class="field">
                    <label>Nombre(s) *</label>
                    <input class="input" name="first_name" required>
                </div>
                <div class="field">
                    <label>Apellidos *</label>
                    <input class="input" name="last_name" required>
                </div>
                <div class="field">
                    <label>Documento de identidad</label>
                    <input class="input" name="national_id" placeholder="DNI / CURP / pasaporte">
                </div>
                <div class="form-grid cols-2">
                    <div class="field">
                        <label>Fecha de nacimiento</label>
                        <input class="input" name="birth_date" placeholder="DD/MM/AAAA">
                    </div>
                    <div class="field">
                        <label>Sexo</label>
                        <select class="select" name="gender">
                            <option value="">—</option>
                            <option value="M">Masculino</option>
                            <option value="F">Femenino</option>
                            <option value="O">Otro</option>
                        </select>
                    </div>
                </div>
                <div class="field">
                    <label>Teléfono</label>
                    <input class="input" name="phone" placeholder="10 dígitos">
                </div>
                <div class="field">
                    <label>Correo electrónico</label>
                    <input class="input" type="email" name="email">
                </div>
                <div class="field">
                    <label>Ciudad</label>
                    <input class="input" name="city">
                </div>
                <hr style="border:none;border-top:1px dashed var(--border);margin:6px 0">
                <div class="field">
                    <label>Contacto de emergencia · Nombre</label>
                    <input class="input" name="emergency_name">
                </div>
                <div class="form-grid cols-2">
                    <div class="field">
                        <label>Parentesco</label>
                        <input class="input" name="emergency_relationship">
                    </div>
                    <div class="field">
                        <label>Teléfono</label>
                        <input class="input" name="emergency_phone">
                    </div>
                </div>
            </div>
            <button class="btn btn-primary btn-block" style="margin-top:18px" type="submit">Enviar solicitud</button>
        </form>
    </div>
</div>
</body>
</html>