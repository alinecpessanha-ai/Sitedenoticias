<?php if (!$hero): ?>
    <div class="empty-state">
        <h2>Nenhuma notícia publicada ainda</h2>
        <p>Acesse o <a href="<?= url('admin') ?>">painel administrativo</a> para publicar a primeira notícia.</p>
    </div>
<?php return; endif ?>

<?php if ($ticker): ?>
    <section class="ticker" aria-label="Últimas notícias">
        <span class="ticker-label">Últimas</span>
        <ul class="ticker-list">
            <?php foreach ($ticker as $item): ?>
                <li>
                    <time datetime="<?= e(date('c', strtotime($item['published_at']))) ?>"><?= e(time_ago($item['published_at'])) ?></time>
                    <a href="<?= post_url($item) ?>"><?= e($item['title']) ?></a>
                </li>
            <?php endforeach ?>
        </ul>
    </section>
<?php endif ?>

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
