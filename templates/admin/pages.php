<div class="page-head">
    <h1>Páginas</h1>
    <a class="btn btn-primary" href="<?= url('admin/paginas/nova') ?>">+ Nova página</a>
</div>

<p class="muted">Páginas fixas, como Quem somos, Contato e Expediente. Elas ficam na raiz do site (ex.: <code><?= e(absolute_url('quem-somos')) ?></code>) e podem aparecer no rodapé.</p>

<?php if ($pages): ?>
    <table class="table">
        <thead>
        <tr><th>Título</th><th>Endereço</th><th>Modelo</th><th>Status</th><th>No rodapé</th><th class="right">Ordem</th><th></th></tr>
        </thead>
        <tbody>
        <?php foreach ($pages as $page): ?>
            <tr>
                <td><a href="<?= url('admin/paginas/' . $page['id'] . '/editar') ?>"><strong><?= e($page['title']) ?></strong></a></td>
                <td><code>/<?= e($page['slug']) ?></code></td>
                <td><?= e(PAGE_TEMPLATES[$page['template']]) ?></td>
                <td><span class="status status-<?= e($page['status']) ?>"><?= e(PAGE_STATUSES[$page['status']]) ?></span></td>
                <td><?= $page['show_in_menu'] ? 'Sim' : 'Não' ?></td>
                <td class="right"><?= (int) $page['position'] ?></td>
                <td class="actions">
                    <a href="<?= static_page_url($page) ?>" target="_blank">Ver</a>
                    <form method="post" action="<?= url('admin/paginas/' . $page['id'] . '/excluir') ?>" data-confirm="Excluir a página “<?= e($page['title']) ?>”? Esta ação não pode ser desfeita.">
                        <?= csrf_field() ?>
                        <button type="submit" class="link-button danger">Excluir</button>
                    </form>
                </td>
            </tr>
        <?php endforeach ?>
        </tbody>
    </table>
<?php else: ?>
    <p class="muted">Nenhuma página criada.</p>
<?php endif ?>
