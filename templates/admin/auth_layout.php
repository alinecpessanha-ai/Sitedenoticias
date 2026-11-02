<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="icon" href="data:image/svg+xml,%3Csvg xmlns=%27http://www.w3.org/2000/svg%27 viewBox=%270 0 32 32%27%3E%3Crect width=%2732%27 height=%2732%27 rx=%276%27 fill=%27%23c4161c%27/%3E%3Ctext x=%2716%27 y=%2723%27 font-size=%2720%27 font-family=%27Georgia%27 fill=%27white%27 text-anchor=%27middle%27%3EN%3C/text%3E%3C/svg%3E">
    <meta name="robots" content="noindex">
    <title><?= e($title ?? 'Painel') ?> — CMS</title>
    <link rel="stylesheet" href="<?= asset('css/admin.css') ?>">
</head>
<body class="auth-page">
<main class="auth-box">
    <?= $content ?>
</main>
</body>
</html>
