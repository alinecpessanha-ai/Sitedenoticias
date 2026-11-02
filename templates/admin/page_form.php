<?php $isNew = empty($page['id']); ?>
<div class="page-head">
    <h1><?= e($title) ?></h1>
    <?php if (!$isNew): ?>
        <a class="btn" href="<?= static_page_url($page) ?>" target="_blank">
            <?= $page['status'] === 'published' ? 'Ver no site ↗' : 'Pré-visualizar ↗' ?>
        </a>
    <?php endif ?>
</div>

<?php foreach ($errors as $error): ?>
    <div class="alert alert-error"><?= e($error) ?></div>
<?php endforeach ?>

<form method="post" action="<?= url('admin/paginas/salvar') ?>" class="post-form">
    <?= csrf_field() ?>
    <input type="hidden" name="id" value="<?= (int) $page['id'] ?>">

    <div class="post-form-main">
        <input class="title-input" type="text" name="title" required maxlength="200" placeholder="Título da página"
               value="<?= e($page['title']) ?>" autofocus>

        <?= render('admin/partials/editor', ['name' => 'content', 'value' => $page['content'], 'label' => 'Conteúdo da página']) ?>
    </div>

    <aside class="post-form-side">
        <section class="panel">
            <h2>Publicação</h2>
            <label>Status
                <select name="status">
                    <?php foreach (PAGE_STATUSES as $value => $label): ?>
                        <option value="<?= $value ?>" <?= $page['status'] === $value ? 'selected' : '' ?>><?= e($label) ?></option>
                    <?php endforeach ?>
                </select>
            </label>
            <button type="submit" class="btn btn-primary btn-block">Salvar</button>
        </section>

        <section class="panel">
            <h2>Configurações da página</h2>
            <label>Modelo
                <select name="template">
                    <?php foreach (PAGE_TEMPLATES as $value => $label): ?>
                        <option value="<?= $value ?>" <?= $page['template'] === $value ? 'selected' : '' ?>><?= e($label) ?></option>
                    <?php endforeach ?>
                </select>
            </label>
            <p class="muted small">O modelo “Contato” mostra um formulário abaixo do texto. As mensagens chegam em <a href="<?= url('admin/mensagens') ?>">Mensagens</a>.</p>
            <label>Endereço (slug)
                <input type="text" name="slug" value="<?= e($page['slug']) ?>" placeholder="gerado a partir do título">
            </label>
            <label class="checkbox"><input type="checkbox" name="show_in_menu" value="1" <?= $page['show_in_menu'] ? 'checked' : '' ?>> Mostrar no rodapé do site</label>
            <label>Ordem no rodapé
                <input type="number" name="position" value="<?= (int) $page['position'] ?>">
            </label>
        </section>
    </aside>
</form>

<script src="<?= asset('js/editor.js') ?>"></script>
