<div class="layout">
    <div class="main-col">
        <header class="page-header">
            <h1><?= e($heading) ?></h1>
            <?php if (!empty($subheading)): ?><p><?= e($subheading) ?></p><?php endif ?>
        </header>

        <?php if ($posts): ?>
            <div class="post-list">
                <?php foreach ($posts as $post): ?>
                    <?= render('site/partials/card', ['post' => $post, 'variant' => 'horizontal']) ?>
                <?php endforeach ?>
            </div>
            <?= render('site/partials/pagination', ['pagination' => $pagination]) ?>
        <?php elseif (isset($searchTerm) && $searchTerm === ''): ?>
            <form class="search-big" action="<?= url('busca') ?>" method="get">
                <input type="search" name="q" placeholder="O que você procura?" autofocus>
                <button type="submit">Buscar</button>
            </form>
        <?php else: ?>
            <p class="muted">Nenhuma notícia encontrada.</p>
        <?php endif ?>
    </div>
    <?= render('site/partials/sidebar') ?>
</div>
