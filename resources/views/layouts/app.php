<?php
/** @var string $content */
$path = '/' . ltrim(rawurldecode(parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/'), '/');
$pendingCount = \App\Services\PreRegistrationService::countPendingStatic();
$flash = $flash ?? ($_SESSION['flash'] ?? null);
unset($_SESSION['flash']);

$nav = [
    'Inicio' => ['/', 'icono-inicio'],
    'Pacientes' => ['/pacientes', 'icono-pacientes'],
    'Pre-registros' => ['/registros', 'icono-registro'],
    'Backups' => ['/backups', 'icono-backup'],
    'Configuración' => ['/configuracion', 'icono-config'],
    'Auditoría' => ['/auditoria', 'icono-auditoria'],
];
function isActive(string $href, string $current): bool
{
    if ($href === '/') {
        return $current === '/';
    }
    return str_starts_with($current, $href);
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0">
<meta name="robots" content="noindex, nofollow">
<title><?= \App\Core\View::e($title ?? 'Sistema Clínico') ?> · <?= \App\Core\View::e($clinicName ?? 'Consultorio') ?></title>
<link rel="stylesheet" href="/assets/css/app.css">
</head>
<body>
<div class="container">

    <aside class="sidebar">
        <div class="brand"><span class="dot"></span> Sistema clínico</div>
        <div class="nav-group">Principal</div>
        <a href="/" class="nav-link <?= isActive('/', $path) ? 'active' : '' ?>">
            <span class="icono">◨</span> Inicio
        </a>
        <a href="/pacientes" class="nav-link <?= isActive('/pacientes', $path) ? 'active' : '' ?>">
            <span class="icono">◈</span> Pacientes
        </a>
        <a href="/registros" class="nav-link <?= isActive('/registros', $path) ? 'active' : '' ?>">
            <span class="icono">◧</span> Pre-registros
            <?php if ($pendingCount > 0): ?><span class="badge"><?= $pendingCount ?></span><?php endif; ?>
        </a>
        <div class="nav-group">Administración</div>
        <a href="/backups" class="nav-link <?= isActive('/backups', $path) ? 'active' : '' ?>">
            <span class="icono">❖</span> Backups
        </a>
        <a href="/auditoria" class="nav-link <?= isActive('/auditoria', $path) ? 'active' : '' ?>">
            <span class="icono">❏</span> Auditoría
        </a>
        <a href="/configuracion" class="nav-link <?= isActive('/configuracion', $path) ? 'active' : '' ?>">
            <span class="icono">⚙</span> Configuración
        </a>
        <div class="footer">
            <?= \App\Core\View::e($clinicName ?? 'Consultorio') ?><br>
            v<?= \App\Core\View::e($appVersion ?? '1.0.0') ?>
        </div>
    </aside>

    <main class="main">
        <?php if ($flash): ?>
            <div class="flash <?= \App\Core\View::e($flash['type']) ?>"><?= \App\Core\View::e($flash['message']) ?></div>
        <?php endif; ?>
        <?= $content ?>
    </main>
</div>
<script src="/assets/js/app.js"></script>
</body>
</html>