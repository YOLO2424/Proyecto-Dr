<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="robots" content="noindex, nofollow">
<title><?= \App\Core\View::e($title) ?></title>
<link rel="stylesheet" href="/assets/css/app.css">
</head>
<body>
<div class="external-wrap">
    <div class="external-card" style="text-align:center">
        <div style="font-size:2.2rem;margin-bottom:12px">
            <?= $type === 'success' ? '✓' : ($type === 'error' ? '✕' : 'ℹ') ?>
        </div>
        <h1><?= \App\Core\View::e($title) ?></h1>
        <div class="hint" style="text-align:center"><?= \App\Core\View::e($message) ?></div>
        <div style="margin-top:16px;color:var(--muted);font-size:0.82rem">Puedes cerrar esta página.</div>
    </div>
</div>
</body>
</html>