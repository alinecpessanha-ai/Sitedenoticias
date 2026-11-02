<?php
$isNew = empty($post['id']);
$publishedValue = $post['published_at'] ? date('Y-m-d\TH:i', strtotime($post['published_at'])) : '';
$statusOptions = can('publish') ? POST_STATUSES : array_intersect_key(POST_STATUSES, ['draft' => 1, 'pending' => 1]);
?>
<div class="page-head">
    <h1><?= e($title) ?></h1>
    <?php if (!$isNew): ?>
        <a class="btn" href="<?= url('noticia/' . $post['slug']) ?>" target="_blank">
            <?= is_public($post) ? 'Ver no site ↗' : 'Pré-visualizar ↗' ?>
        </a>
    <?php endif ?>
</div>

<?php foreach ($errors as $error): ?>
    <div class="alert alert-error"><?= e($error) ?></div>
<?php endforeach ?>

<form method="post" action="<?= url('admin/noticias/salvar') ?>" enctype="multipart/form-data" class="post-form">
    <?= csrf_field() ?>
    <input type="hidden" name="id" value="<?= (int) $post['id'] ?>">

    <div class="post-form-main">
        <input class="title-input" type="text" name="title" required maxlength="250" placeholder="Título da notícia"
               value="<?= e($post['title']) ?>" autofocus>

        <label>Linha fina (resumo exibido abaixo do título)
            <textarea name="excerpt" rows="2" maxlength="500" placeholder="Um resumo curto da notícia"><?= e($post['excerpt']) ?></textarea>
        </label>

        <?= render('admin/partials/editor', ['name' => 'content', 'value' => $post['content'], 'label' => 'Conteúdo da notícia']) ?>
    </div>

    <aside class="post-form-side">
        <section class="panel">
            <h2>Publicação</h2>
            <label>Status
                <select name="status">
                    <?php foreach ($statusOptions as $value => $label): ?>
                        <option value="<?= $value ?>" <?= $post['status'] === $value ? 'selected' : '' ?>><?= e($label) ?></option>
                    <?php endforeach ?>
                </select>
            </label>
            <?php if (!can('publish')): ?>
                <p class="muted small">Como autor, envie a notícia “para revisão” e um editor fará a publicação.</p>
            <?php endif ?>
            <label>Data de publicação
                <input type="datetime-local" name="published_at" value="<?= e($publishedValue) ?>">
            </label>
            <p class="muted small">Deixe vazio para publicar agora. Uma data futura agenda a publicação.</p>
            <?php if (can('publish')): ?>
                <label class="checkbox"><input type="checkbox" name="is_featured" value="1" <?= $post['is_featured'] ? 'checked' : '' ?>> Destaque na página inicial</label>
            <?php endif ?>
            <button type="submit" class="btn btn-primary btn-block">Salvar</button>
        </section>

        <section class="panel">
            <h2>Organização</h2>
            <label>Categoria
                <select name="category_id">
                    <option value="">Sem categoria</option>
                    <?php foreach (all_categories() as $cat): ?>
                        <option value="<?= $cat['id'] ?>" <?= (int) $post['category_id'] === (int) $cat['id'] ? 'selected' : '' ?>><?= e($cat['name']) ?></option>
                    <?php endforeach ?>
                </select>
            </label>
            <label>Tags (separadas por vírgula)
                <input type="text" name="tags" value="<?= e($tags) ?>" placeholder="eleições, prefeitura">
            </label>
            <label>Endereço (slug)
                <input type="text" name="slug" value="<?= e($post['slug']) ?>" placeholder="gerado a partir do título">
            </label>
        </section>

        <section class="panel">
            <h2>Imagem destacada</h2>
            <div class="featured-preview" data-featured-preview>
                <?php if ($post['featured_image']): ?>
                    <img src="<?= e(image_url($post['featured_image'], 400)) ?>" alt="">
                <?php endif ?>
            </div>
            <?php if ($post['featured_image']): ?>
                <label class="checkbox"><input type="checkbox" name="remove_image" value="1"> Remover imagem</label>
            <?php endif ?>
            <label>Enviar nova imagem
                <input type="file" name="featured_image" accept="image/jpeg,image/png,image/gif,image/webp" data-featured-file>
            </label>
            <input type="hidden" name="featured_media" value="" data-featured-media>
            <button type="button" class="btn btn-block" data-media-picker data-source="<?= url('admin/midia?formato=json') ?>">Escolher da biblioteca</button>
            <label>Legenda / crédito
                <input type="text" name="image_caption" maxlength="250" value="<?= e($post['image_caption']) ?>" placeholder="Foto: Fulano/Agência">
            </label>
        </section>
    </aside>
</form>

<dialog class="media-dialog" data-media-dialog>
    <div class="media-dialog-head">
        <h2>Biblioteca de mídia</h2>
        <button type="button" class="link-button" data-close>Fechar</button>
    </div>
    <div class="media-grid" data-media-grid></div>
</dialog>

<script src="<?= asset('js/editor.js') ?>"></script>
