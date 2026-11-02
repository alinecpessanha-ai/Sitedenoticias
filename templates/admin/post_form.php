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

        <div class="editor" data-editor data-upload-url="<?= url('admin/midia/enviar') ?>">
            <div class="editor-toolbar" role="toolbar" aria-label="Formatação">
                <button type="button" data-cmd="bold" title="Negrito"><b>N</b></button>
                <button type="button" data-cmd="italic" title="Itálico"><i>I</i></button>
                <button type="button" data-cmd="underline" title="Sublinhado"><u>S</u></button>
                <span class="sep"></span>
                <button type="button" data-cmd="formatBlock" data-value="p" title="Parágrafo">¶</button>
                <button type="button" data-cmd="formatBlock" data-value="h2" title="Subtítulo">H2</button>
                <button type="button" data-cmd="formatBlock" data-value="h3" title="Intertítulo">H3</button>
                <button type="button" data-cmd="formatBlock" data-value="blockquote" title="Citação">“ ”</button>
                <span class="sep"></span>
                <button type="button" data-cmd="insertUnorderedList" title="Lista">• Lista</button>
                <button type="button" data-cmd="insertOrderedList" title="Lista numerada">1. Lista</button>
                <span class="sep"></span>
                <button type="button" data-cmd="link" title="Inserir link">Link</button>
                <button type="button" data-cmd="unlink" title="Remover link">Sem link</button>
                <button type="button" data-cmd="image" title="Inserir imagem">Imagem</button>
                <button type="button" data-cmd="video" title="Inserir vídeo do YouTube ou Vimeo">Vídeo</button>
                <span class="sep"></span>
                <button type="button" data-cmd="removeFormat" title="Limpar formatação">Limpar</button>
                <button type="button" data-cmd="source" title="Editar HTML">&lt;/&gt;</button>
            </div>
            <div class="editor-area post-content" contenteditable="true" aria-label="Conteúdo da notícia"></div>
            <textarea name="content" class="editor-source" hidden><?= e($post['content']) ?></textarea>
            <input type="file" accept="image/jpeg,image/png,image/gif,image/webp" class="editor-file" hidden>
        </div>
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
                    <img src="<?= e(upload_url($post['featured_image'])) ?>" alt="">
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
