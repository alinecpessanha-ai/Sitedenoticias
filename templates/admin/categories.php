<h1>Categorias</h1>

<div class="columns columns-form">
    <section class="panel">
        <h2><?= $editing ? 'Editar categoria' : 'Nova categoria' ?></h2>
        <form method="post" action="<?= url('admin/categorias/salvar') ?>" class="form">
            <?= csrf_field() ?>
            <input type="hidden" name="id" value="<?= (int) ($editing['id'] ?? 0) ?>">
            <label>Nome
                <input type="text" name="name" required maxlength="100" value="<?= e($editing['name'] ?? '') ?>" placeholder="Ex.: Política">
            </label>
            <label>Endereço (slug)
                <input type="text" name="slug" value="<?= e($editing['slug'] ?? '') ?>" placeholder="gerado automaticamente">
            </label>
            <label>Descrição
                <textarea name="description" rows="3" maxlength="300"><?= e($editing['description'] ?? '') ?></textarea>
            </label>
            <label>Ordem no menu
                <input type="number" name="position" value="<?= (int) ($editing['position'] ?? 0) ?>">
            </label>
            <button type="submit" class="btn btn-primary"><?= $editing ? 'Salvar alterações' : 'Criar categoria' ?></button>
            <?php if ($editing): ?><a href="<?= url('admin/categorias') ?>" class="btn">Cancelar</a><?php endif ?>
        </form>
    </section>

    <section>
        <?php if ($categories): ?>
            <table class="table">
                <thead><tr><th>Nome</th><th>Slug</th><th class="right">Notícias</th><th class="right">Ordem</th><th></th></tr></thead>
                <tbody>
                <?php foreach ($categories as $cat): ?>
                    <tr>
                        <td><strong><?= e($cat['name']) ?></strong>
                            <?php if ($cat['description']): ?><div class="muted small"><?= e($cat['description']) ?></div><?php endif ?></td>
                        <td><code><?= e($cat['slug']) ?></code></td>
                        <td class="right"><?= (int) $cat['post_count'] ?></td>
                        <td class="right"><?= (int) $cat['position'] ?></td>
                        <td class="actions">
                            <a href="<?= url('admin/categorias?editar=' . $cat['id']) ?>">Editar</a>
                            <form method="post" action="<?= url('admin/categorias/' . $cat['id'] . '/excluir') ?>" data-confirm="Excluir a categoria “<?= e($cat['name']) ?>”? As notícias dela ficarão sem categoria.">
                                <?= csrf_field() ?>
                                <button type="submit" class="link-button danger">Excluir</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach ?>
                </tbody>
            </table>
        <?php else: ?>
            <p class="muted">Nenhuma categoria criada.</p>
        <?php endif ?>
    </section>
</div>
