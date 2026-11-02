<?php
$shareUrl = rawurlencode(absolute_url('noticia/' . $post['slug']));
$shareText = rawurlencode($post['title']);
$updated = $post['published_at'] && strtotime($post['updated_at']) - strtotime($post['published_at']) > 3600;
?>
<div class="layout">
    <article class="main-col single">
        <?php if ($preview): ?>
            <div class="notice">
                Pré-visualização — esta notícia está como
                <strong><?= e(is_public($post) ? 'publicada' : ($post['status'] === 'published' ? 'agendada' : mb_strtolower(POST_STATUSES[$post['status']]))) ?></strong>
                e não aparece para o público.
            </div>
        <?php endif ?>

        <header class="single-header">
            <?php if ($post['category_name']): ?>
                <a class="kicker" href="<?= url('categoria/' . $post['category_slug']) ?>"><?= e($post['category_name']) ?></a>
            <?php endif ?>
            <h1><?= e($post['title']) ?></h1>
            <?php if ($post['excerpt']): ?><p class="lead"><?= e($post['excerpt']) ?></p><?php endif ?>
            <div class="byline">
                <span>Por <a href="<?= url('autor/' . $post['author_id']) ?>"><?= e($post['author_name']) ?></a></span>
                <?php if ($post['published_at']): ?>
                    <time datetime="<?= e(date('c', strtotime($post['published_at']))) ?>"><?= e(format_date($post['published_at'], true)) ?></time>
                <?php endif ?>
                <?php if ($updated): ?><span>Atualizado em <?= e(format_date($post['updated_at'], true)) ?></span><?php endif ?>
                <span><?= reading_time($post['content']) ?> min de leitura</span>
                <?php if ($canEdit): ?><a class="edit-link" href="<?= url('admin/noticias/' . $post['id'] . '/editar') ?>">Editar</a><?php endif ?>
            </div>
        </header>

        <?php if ($post['featured_image']): ?>
            <figure class="featured">
                <img src="<?= e(upload_url($post['featured_image'])) ?>" alt="<?= e($post['image_caption'] ?: $post['title']) ?>">
                <?php if ($post['image_caption']): ?><figcaption><?= e($post['image_caption']) ?></figcaption><?php endif ?>
            </figure>
        <?php endif ?>

        <div class="post-content">
            <?= $post['content'] /* HTML já sanitizado ao salvar */ ?>
        </div>

        <?php if ($tags): ?>
            <div class="tags">
                <?php foreach ($tags as $tag): ?>
                    <a href="<?= url('tag/' . $tag['slug']) ?>">#<?= e($tag['name']) ?></a>
                <?php endforeach ?>
            </div>
        <?php endif ?>

        <div class="share">
            <span>Compartilhe:</span>
            <a href="https://wa.me/?text=<?= $shareText ?>%20<?= $shareUrl ?>" target="_blank" rel="noopener">WhatsApp</a>
            <a href="https://twitter.com/intent/tweet?text=<?= $shareText ?>&amp;url=<?= $shareUrl ?>" target="_blank" rel="noopener">X</a>
            <a href="https://www.facebook.com/sharer/sharer.php?u=<?= $shareUrl ?>" target="_blank" rel="noopener">Facebook</a>
            <a href="https://www.linkedin.com/sharing/share-offsite/?url=<?= $shareUrl ?>" target="_blank" rel="noopener">LinkedIn</a>
        </div>

        <?php if ($related): ?>
            <section class="block related">
                <h2 class="section-title">Leia também</h2>
                <div class="grid grid-3">
                    <?php foreach ($related as $item): ?>
                        <?= render('site/partials/card', ['post' => $item, 'variant' => 'vertical']) ?>
                    <?php endforeach ?>
                </div>
            </section>
        <?php endif ?>
    </article>
    <?= render('site/partials/sidebar') ?>
</div>
