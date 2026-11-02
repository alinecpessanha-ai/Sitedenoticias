<?php
/** @var array $post */
$variant = $variant ?? 'vertical'; // vertical | horizontal | hero | compact
$showExcerpt = $showExcerpt ?? in_array($variant, ['hero', 'horizontal'], true);
?>
<article class="card card-<?= e($variant) ?>">
    <?php if ($variant !== 'compact'): ?>
        <a class="card-media" href="<?= post_url($post) ?>" tabindex="-1" aria-hidden="true">
            <?php if ($post['featured_image']): ?>
                <img src="<?= e(upload_url($post['featured_image'])) ?>" alt="" loading="lazy">
            <?php else: ?>
                <span class="placeholder"><?= e(mb_substr($post['category_name'] ?? $post['title'], 0, 1)) ?></span>
            <?php endif ?>
        </a>
    <?php endif ?>
    <div class="card-body">
        <?php if (!empty($post['category_name'])): ?>
            <a class="kicker" href="<?= url('categoria/' . $post['category_slug']) ?>"><?= e($post['category_name']) ?></a>
        <?php endif ?>
        <h3 class="card-title"><a href="<?= post_url($post) ?>"><?= e($post['title']) ?></a></h3>
        <?php if ($showExcerpt && $post['excerpt']): ?>
            <p class="card-excerpt"><?= e($post['excerpt']) ?></p>
        <?php endif ?>
        <time class="card-date" datetime="<?= e(date('c', strtotime($post['published_at']))) ?>"><?= e(format_date($post['published_at'], true)) ?></time>
    </div>
</article>
