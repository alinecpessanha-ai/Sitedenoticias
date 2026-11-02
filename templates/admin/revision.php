<?php
$historyUrl = url('admin/noticias/' . $post['id'] . '/historico');
$who = e($revision['user_name'] ?? 'Usuário excluído') . ', ' . e(format_date($revision['created_at'], true));
$shortFields = ['featured_image', 'category_id', 'tags', 'status', 'published_at'];
?>
<div class="page-head">
    <div>
        <h1><?= $againstCurrent ? 'Versão antiga × versão atual' : ($old ? 'O que mudou nesta versão' : 'Primeira versão registrada') ?></h1>
        <p class="muted"><?= e($post['title']) ?></p>
    </div>
    <a class="btn" href="<?= $historyUrl ?>">← Histórico</a>
</div>

<div class="panel compare-head">
    <?php if ($againstCurrent): ?>
        <div><span class="legend legend-del">Antes</span> versão de <?= $who ?></div>
        <div><span class="legend legend-ins">Depois</span> versão atual da notícia</div>
    <?php elseif ($old): ?>
        <div><span class="legend legend-del">Antes</span> versão de <?= e($old['user_name'] ?? 'Usuário excluído') ?>, <?= e(format_date($old['created_at'], true)) ?></div>
        <div><span class="legend legend-ins">Depois</span> versão de <?= $who ?></div>
    <?php else: ?>
        <div>Versão de <?= $who ?>. Não há versão anterior para comparar.</div>
    <?php endif ?>
    <?php if ($revision['note']): ?><div class="muted small"><?= e($revision['note']) ?></div><?php endif ?>

    <?php if (!$isLatest || $againstCurrent): ?>
        <form method="post" action="<?= url('admin/noticias/' . $post['id'] . '/historico/' . $revision['id'] . '/restaurar') ?>"
              data-confirm="Restaurar a versão de <?= e(short_date($revision['created_at'])) ?>? O conteúdo atual continua guardado no histórico.">
            <?= csrf_field() ?>
            <button type="submit" class="btn btn-primary">Restaurar a versão de <?= e(short_date($revision['created_at'])) ?></button>
        </form>
        <p class="muted small">Restaurar traz de volta título, linha fina, texto, imagem, categoria e tags. O status e a data de publicação não mudam.</p>
    <?php endif ?>
</div>

<?php if (!$changes): ?>
    <p class="muted">Nenhuma diferença: o conteúdo é idêntico.</p>
<?php endif ?>

<?php foreach ($changes as $field): ?>
    <section class="panel diff-field">
        <h2><?= e(REVISION_FIELDS[$field]) ?></h2>

        <?php if (!$old): ?>
            <?php if ($field === 'content'): ?>
                <div class="diff-content">
                    <?php foreach (content_lines((string) $new['content']) as $line): ?><p><?= e($line) ?></p><?php endforeach ?>
                </div>
            <?php elseif ($field === 'featured_image' && $new['featured_image']): ?>
                <img class="diff-image" src="<?= e(image_url($new['featured_image'], 400)) ?>" alt="">
            <?php else: ?>
                <p><?= e(revision_display_value($field, $new[$field])) ?></p>
            <?php endif ?>

        <?php elseif ($field === 'content'): ?>
            <div class="diff-content">
                <?php foreach (diff_content_blocks((string) $old['content'], (string) $new['content']) as $block): ?>
                    <?php if ($block['type'] === 'skip'): ?>
                        <p class="diff-skip">… <?= $block['count'] ?> parágrafo(s) sem alteração …</p>
                    <?php else: ?>
                        <p class="diff-<?= $block['type'] ?>"><?= $block['html'] /* já escapado em diff_content_blocks */ ?></p>
                    <?php endif ?>
                <?php endforeach ?>
            </div>

        <?php elseif ($field === 'featured_image'): ?>
            <div class="diff-images">
                <figure><figcaption class="legend legend-del">Antes</figcaption>
                    <?php if ($old['featured_image']): ?><img class="diff-image" src="<?= e(image_url($old['featured_image'], 400)) ?>" alt=""><?php else: ?><p>Sem imagem</p><?php endif ?>
                </figure>
                <figure><figcaption class="legend legend-ins">Depois</figcaption>
                    <?php if ($new['featured_image']): ?><img class="diff-image" src="<?= e(image_url($new['featured_image'], 400)) ?>" alt=""><?php else: ?><p>Sem imagem</p><?php endif ?>
                </figure>
            </div>

        <?php elseif (in_array($field, $shortFields, true)): ?>
            <p class="diff-short">
                <del><?= e(revision_display_value($field, $old[$field])) ?></del>
                <span aria-hidden="true">→</span>
                <ins><?= e(revision_display_value($field, $new[$field])) ?></ins>
            </p>

        <?php else: ?>
            <p class="diff-text"><?= diff_words_html((string) $old[$field], (string) $new[$field]) /* já escapado */ ?></p>
        <?php endif ?>
    </section>
<?php endforeach ?>
