<?php
$siteName = setting('site_name', 'Meu Portal');
$siteDescription = setting('site_description');
$pageTitle = isset($title) ? $title . ' — ' . $siteName : $siteName;
$metaDescription = !empty($description) ? $description : $siteDescription;
$activeCategory = $activeCategory ?? null;
$socials = array_filter([
    'Instagram' => setting('social_instagram'),
    'X'         => setting('social_x'),
    'Facebook'  => setting('social_facebook'),
    'YouTube'   => setting('social_youtube'),
]);
?>
<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="icon" href="data:image/svg+xml,%3Csvg xmlns=%27http://www.w3.org/2000/svg%27 viewBox=%270 0 32 32%27%3E%3Crect width=%2732%27 height=%2732%27 rx=%276%27 fill=%27%23c4161c%27/%3E%3Ctext x=%2716%27 y=%2723%27 font-size=%2720%27 font-family=%27Georgia%27 fill=%27white%27 text-anchor=%27middle%27%3EN%3C/text%3E%3C/svg%3E">
    <title><?= e($pageTitle) ?></title>
    <meta name="description" content="<?= e($metaDescription) ?>">
    <?php if (!empty($robots)): ?><meta name="robots" content="<?= e($robots) ?>"><?php endif ?>
    <meta property="og:site_name" content="<?= e($siteName) ?>">
    <meta property="og:title" content="<?= e($title ?? $siteName) ?>">
    <meta property="og:description" content="<?= e($metaDescription) ?>">
    <meta property="og:type" content="<?= e($og_type ?? 'website') ?>">
    <meta property="og:url" content="<?= e(absolute_url(request_path())) ?>">
    <?php if (!empty($og_image)): ?>
        <meta property="og:image" content="<?= e($og_image) ?>">
        <meta name="twitter:card" content="summary_large_image">
    <?php endif ?>
    <link rel="canonical" href="<?= e(absolute_url(request_path())) ?>">
    <link rel="alternate" type="application/rss+xml" title="<?= e($siteName) ?>" href="<?= url('feed.xml') ?>">
    <link rel="stylesheet" href="<?= asset('css/site.css') ?>">
</head>
<body>
<header class="site-header">
    <div class="topbar">
        <div class="container topbar-inner">
            <span class="today"><?= e(ucfirst(weekday()) . ', ' . format_date(now())) ?></span>
            <form class="search-form" action="<?= url('busca') ?>" method="get" role="search">
                <input type="search" name="q" placeholder="Buscar notícias…" value="<?= e($searchTerm ?? '') ?>" aria-label="Buscar notícias">
                <button type="submit">Buscar</button>
            </form>
        </div>
    </div>
    <div class="container brand">
        <a class="logo" href="<?= url('/') ?>"><?= e($siteName) ?></a>
        <?php if ($siteDescription): ?><p class="tagline"><?= e($siteDescription) ?></p><?php endif ?>
    </div>
    <nav class="main-nav" aria-label="Seções">
        <div class="container nav-inner">
            <a href="<?= url('/') ?>" class="<?= request_path() === '/' ? 'active' : '' ?>">Início</a>
            <?php foreach (all_categories() as $cat): ?>
                <a href="<?= url('categoria/' . $cat['slug']) ?>" class="<?= (int) $activeCategory === (int) $cat['id'] ? 'active' : '' ?>"><?= e($cat['name']) ?></a>
            <?php endforeach ?>
        </div>
    </nav>
</header>

<main class="container site-main">
    <?= $content ?>
</main>

<footer class="site-footer">
    <div class="container footer-inner">
        <div>
            <a class="logo logo-small" href="<?= url('/') ?>"><?= e($siteName) ?></a>
            <p><?= e(setting('footer_text', '© ' . date('Y') . ' ' . $siteName . '. Todos os direitos reservados.')) ?></p>
        </div>
        <div class="footer-links">
            <?php foreach ($socials as $label => $link): ?>
                <a href="<?= e($link) ?>" target="_blank" rel="noopener"><?= e($label) ?></a>
            <?php endforeach ?>
            <a href="<?= url('feed.xml') ?>">RSS</a>
            <a href="<?= url('admin') ?>">Painel</a>
        </div>
    </div>
</footer>
</body>
</html>
