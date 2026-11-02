<div class="page-head">
    <h1>Mídia</h1>
</div>

<form method="post" action="<?= url('admin/midia/enviar') ?>" enctype="multipart/form-data" class="upload-form panel">
    <?= csrf_field() ?>
    <label>Enviar imagem (JPG, PNG, GIF ou WEBP)
        <input type="file" name="file" accept="image/jpeg,image/png,image/gif,image/webp" required>
    </label>
    <button type="submit" class="btn btn-primary">Enviar</button>
</form>

<?php if ($items): ?>
    <div class="media-grid">
        <?php foreach ($items as $item): ?>
            <figure class="media-item">
                <a href="<?= e(upload_url($item['path'])) ?>" target="_blank"><img src="<?= e(upload_url($item['path'])) ?>" alt="" loading="lazy"></a>
                <figcaption>
                    <span class="truncate" title="<?= e($item['original_name']) ?>"><?= e($item['original_name']) ?></span>
                    <span class="muted small"><?= (int) $item['width'] ?>×<?= (int) $item['height'] ?> · <?= e(format_bytes((int) $item['size'])) ?></span>
                    <input type="text" readonly value="<?= e(absolute_url('uploads/' . $item['path'])) ?>" onclick="this.select()" aria-label="Endereço da imagem">
                    <form method="post" action="<?= url('admin/midia/' . $item['id'] . '/excluir') ?>" data-confirm="Excluir esta imagem? Notícias que a usam ficarão com a imagem quebrada.">
                        <?= csrf_field() ?>
                        <button type="submit" class="link-button danger">Excluir</button>
                    </form>
                </figcaption>
            </figure>
        <?php endforeach ?>
    </div>
    <?= render('site/partials/pagination', ['pagination' => $pagination]) ?>
<?php else: ?>
    <p class="muted">Nenhuma imagem enviada ainda.</p>
<?php endif ?>
