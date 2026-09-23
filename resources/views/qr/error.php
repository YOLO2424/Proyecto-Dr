<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>No encontrado</title>
<link rel="stylesheet" href="/assets/css/app.css">
</head>
<body>
<div class="external-wrap">
    <div class="external-card" style="text-align:center">
        <div style="font-size:2.4rem">◌</div>
        <h1><?= \App\Core\View::e($message ?? 'Página no encontrada') ?></h1>
        <div class="hint" style="text-align:center">El recurso no existe en este sistema.</div>
        <div style="margin-top:16px;color:var(--muted);font-size:0.82rem">Puedes cerrar esta página.</div>
    </div>
</div>
</body>
</html>