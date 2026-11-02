<?php if (!$hero): ?>
    <div class="empty-state">
        <h1>Nenhuma notícia publicada ainda</h1>
        <p>Acesse o <a href="<?= url('admin') ?>">painel administrativo</a> para publicar a primeira notícia.</p>
    </div>
<?php return; endif ?>

<section class="hero">
    <div class="hero-main">
        <?= render('site/partials/card', ['post' => $hero[0], 'variant' => 'hero']) ?>
    </div>
    <?php if (count($hero) > 1): ?>
        <div class="hero-side">
            <?php foreach (array_slice($hero, 1) as $post): ?>
                <?= render('site/partials/card', ['post' => $post, 'variant' => 'vertical']) ?>
            <?php endforeach ?>
        </div>
    <?php endif ?>
</section>

<div class="layout">
    <div class="main-col">
        <?php if ($latest): ?>
            <section class="block">
                <h2 class="section-title">Últimas notícias</h2>
                <div class="post-list">
                    <?php foreach ($latest as $post): ?>
                        <?= render('site/partials/card', ['post' => $post, 'variant' => 'horizontal']) ?>
                    <?php endforeach ?>
                </div>
                <a class="more-link" href="<?= url('noticias') ?>">Ver todas as notícias →</a>
            </section>
        <?php endif ?>

        <?php foreach ($sections as $section): ?>
            <section class="block">
                <h2 class="section-title">
                    <a href="<?= url('categoria/' . $section['category']['slug']) ?>"><?= e($section['category']['name']) ?></a>
                </h2>
                <div class="grid">
                    <?php foreach ($section['posts'] as $post): ?>
                        <?= render('site/partials/card', ['post' => $post, 'variant' => 'vertical']) ?>
                    <?php endforeach ?>
                </div>
            </section>
        <?php endforeach ?>
    </div>
    <?= render('site/partials/sidebar') ?>
</div>
