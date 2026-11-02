<div class="page-head">
    <h1>Usuários</h1>
    <a class="btn btn-primary" href="<?= url('admin/usuarios/novo') ?>">+ Novo usuário</a>
</div>

<table class="table">
    <thead><tr><th>Nome</th><th>E-mail</th><th>Papel</th><th class="right">Notícias</th><th>Desde</th><th></th></tr></thead>
    <tbody>
    <?php foreach ($users as $user): ?>
        <tr>
            <td><strong><?= e($user['name']) ?></strong></td>
            <td><?= e($user['email']) ?></td>
            <td><span class="badge"><?= e(ROLES[$user['role']]) ?></span></td>
            <td class="right"><?= (int) $user['post_count'] ?></td>
            <td><?= e(format_date($user['created_at'])) ?></td>
            <td class="actions">
                <a href="<?= url('admin/usuarios/' . $user['id'] . '/editar') ?>">Editar</a>
                <?php if ((int) $user['id'] !== (int) current_user()['id']): ?>
                    <form method="post" action="<?= url('admin/usuarios/' . $user['id'] . '/excluir') ?>" data-confirm="Excluir <?= e($user['name']) ?>? As notícias dessa pessoa serão transferidas para você.">
                        <?= csrf_field() ?>
                        <button type="submit" class="link-button danger">Excluir</button>
                    </form>
                <?php endif ?>
            </td>
        </tr>
    <?php endforeach ?>
    </tbody>
</table>

<section class="panel roles-help">
    <h2>Papéis</h2>
    <ul class="plain-list">
        <li><strong>Administrador:</strong> acesso total, incluindo usuários e configurações.</li>
        <li><strong>Editor:</strong> publica e edita qualquer notícia, gerencia categorias e mídia.</li>
        <li><strong>Autor:</strong> escreve as próprias notícias e as envia para revisão.</li>
    </ul>
</section>
