<?php
$tabs = ['' => 'Todas', 'published' => 'Publicadas', 'scheduled' => 'Agendadas', 'pending' => 'Em revisão', 'draft' => 'Rascunhos'];
?>
<div class="page-head">
    <h1>Notícias</h1>
    <a class="btn btn-primary" href="<?= url('admin/noticias/nova') ?>">+ Nova notícia</a>
</div>

<nav class="tabs">
    <?php foreach ($tabs as $key => $label): ?>
        <a href="<?= url('admin/noticias') . ($key ? '?status=' . $key : '') ?>" class="<?= $status === $key ? 'active' : '' ?>"><?= e($label) ?></a>
    <?php endforeach ?>
</nav>

<form class="filters" method="get">
    <?php if ($status): ?><input type="hidden" name="status" value="<?= e($status) ?>"><?php endif ?>
    <input type="search" name="q" value="<?= e($search) ?>" placeholder="Buscar pelo título…">
    <select name="categoria">
        <option value="">Todas as categorias</option>
        <?php foreach (all_categories() as $cat): ?>
            <option value="<?= $cat['id'] ?>" <?= $categoryId === (int) $cat['id'] ? 'selected' : '' ?>><?= e($cat['name']) ?></option>
        <?php endforeach ?>
    </select>
    <button class="btn" type="submit">Filtrar</button>
</form>

<?php if ($posts): ?>
    <table class="table">
        <thead>
        <tr><th>Título</th><th>Categoria</th><th>Autor</th><th>Status</th><th>Data</th><th class="right">Visualizações</th><th></th></tr>
        </thead>
        <tbody>
        <?php foreach ($posts as $post): ?>
            <tr>
                <td>
                    <?php if ($post['is_featured']): ?><span title="Destaque">★</span><?php endif ?>
                    <?php if (can('edit_post', $post)): ?>
                        <a href="<?= url('admin/noticias/' . $post['id'] . '/editar') ?>"><strong><?= e($post['title']) ?></strong></a>
                    <?php else: ?>
                        <strong><?= e($post['title']) ?></strong>
                    <?php endif ?>
                </td>
                <td><?= e($post['category_name'] ?? '—') ?></td>
                <td><?= e($post['author_name']) ?></td>
                <td><?= render('admin/partials/status', ['post' => $post]) ?></td>
                <td class="nowrap"><?= e(short_date($post['published_at'] ?? $post['updated_at'])) ?></td>
                <td class="right"><?= number_format((int) $post['views'], 0, ',', '.') ?></td>
                <td class="actions">
                    <a href="<?= post_url($post) ?>" target="_blank">Ver</a>
                    <?php if (can('delete_post', $post)): ?>
                        <form method="post" action="<?= url('admin/noticias/' . $post['id'] . '/excluir') ?>" data-confirm="Excluir a notícia “<?= e($post['title']) ?>”? Esta ação não pode ser desfeita.">
                            <?= csrf_field() ?>
                            <button type="submit" class="link-button danger">Excluir</button>
                        </form>
                    <?php endif ?>
                </td>
            </tr>
        <?php endforeach ?>
        </tbody>
    </table>
    <?= render('site/partials/pagination', ['pagination' => $pagination]) ?>
<?php else: ?>
    <p class="muted">Nenhuma notícia encontrada.</p>
<?php endif ?>
