<?php
$me = current_user();
$path = request_path();
$menu = [
    ['admin', 'Painel', true, fn ($p) => $p === '/admin'],
    ['admin/noticias', 'Notícias', true, fn ($p) => str_starts_with($p, '/admin/noticias')],
    ['admin/categorias', 'Categorias', can('manage_categories'), fn ($p) => str_starts_with($p, '/admin/categorias')],
    ['admin/midia', 'Mídia', true, fn ($p) => str_starts_with($p, '/admin/midia')],
    ['admin/usuarios', 'Usuários', can('manage_users'), fn ($p) => str_starts_with($p, '/admin/usuarios')],
    ['admin/configuracoes', 'Configurações', can('manage_settings'), fn ($p) => str_starts_with($p, '/admin/configuracoes')],
];
?>
<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="icon" href="data:image/svg+xml,%3Csvg xmlns=%27http://www.w3.org/2000/svg%27 viewBox=%270 0 32 32%27%3E%3Crect width=%2732%27 height=%2732%27 rx=%276%27 fill=%27%23c4161c%27/%3E%3Ctext x=%2716%27 y=%2723%27 font-size=%2720%27 font-family=%27Georgia%27 fill=%27white%27 text-anchor=%27middle%27%3EN%3C/text%3E%3C/svg%3E">
    <meta name="robots" content="noindex">
    <meta name="csrf-token" content="<?= e(csrf_token()) ?>">
    <title><?= e($title ?? 'Painel') ?> — <?= e(setting('site_name', 'CMS')) ?></title>
    <link rel="stylesheet" href="<?= asset('css/admin.css') ?>">
</head>
<body>
<div class="admin">
    <aside class="admin-sidebar">
        <a class="admin-brand" href="<?= url('admin') ?>"><?= e(setting('site_name', 'CMS')) ?></a>
        <nav>
            <?php foreach ($menu as [$href, $label, $visible, $isActive]): if (!$visible) continue; ?>
                <a href="<?= url($href) ?>" class="<?= $isActive($path) ? 'active' : '' ?>"><?= e($label) ?></a>
            <?php endforeach ?>
        </nav>
        <a class="btn btn-primary btn-block new-post" href="<?= url('admin/noticias/nova') ?>">+ Nova notícia</a>
    </aside>

    <div class="admin-main">
        <header class="admin-topbar">
            <a href="<?= url('/') ?>" target="_blank">Ver site ↗</a>
            <?php if ($me): ?>
                <div class="user-menu">
                    <a href="<?= url('admin/perfil') ?>"><?= e($me['name']) ?></a>
                    <span class="badge"><?= e(ROLES[$me['role']]) ?></span>
                    <form method="post" action="<?= url('admin/sair') ?>">
                        <?= csrf_field() ?>
                        <button type="submit" class="link-button">Sair</button>
                    </form>
                </div>
            <?php endif ?>
        </header>

        <div class="admin-content">
            <?php foreach (get_flashes() as [$type, $message]): ?>
                <div class="alert alert-<?= e($type) ?>"><?= e($message) ?></div>
            <?php endforeach ?>
            <?= $content ?>
        </div>
    </div>
</div>
<script src="<?= asset('js/admin.js') ?>"></script>
</body>
</html>
