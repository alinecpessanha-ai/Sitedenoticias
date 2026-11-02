<?php $popular = most_read(5); ?>
<aside class="sidebar">
    <?php if ($popular): ?>
        <section class="widget">
            <h2 class="section-title">Mais lidas</h2>
            <ol class="most-read">
                <?php foreach ($popular as $item): ?>
                    <li><a href="<?= post_url($item) ?>"><?= e($item['title']) ?></a></li>
                <?php endforeach ?>
            </ol>
        </section>
    <?php endif ?>
    <?php $cats = all_categories(); if ($cats): ?>
        <section class="widget">
            <h2 class="section-title">Editorias</h2>
            <ul class="category-list">
                <?php foreach ($cats as $cat): ?>
                    <li><a href="<?= url('categoria/' . $cat['slug']) ?>"><?= e($cat['name']) ?></a></li>
                <?php endforeach ?>
            </ul>
        </section>
    <?php endif ?>
</aside>
