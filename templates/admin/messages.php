<h1>Mensagens</h1>

<nav class="tabs">
    <a href="<?= url('admin/mensagens') ?>" class="<?= $filter !== 'nao-lidas' ? 'active' : '' ?>">Todas</a>
    <a href="<?= url('admin/mensagens?filtro=nao-lidas') ?>" class="<?= $filter === 'nao-lidas' ? 'active' : '' ?>">Não lidas</a>
</nav>

<?php if ($messages): ?>
    <table class="table messages-table">
        <thead><tr><th>De</th><th>Assunto</th><th>Recebida em</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($messages as $msg): ?>
            <tr class="<?= $msg['is_read'] ? '' : 'unread' ?>">
                <td>
                    <a href="<?= url('admin/mensagens/' . $msg['id']) ?>"><?= e($msg['name']) ?></a>
                    <div class="muted small"><?= e($msg['email']) ?></div>
                </td>
                <td>
                    <a href="<?= url('admin/mensagens/' . $msg['id']) ?>"><?= e($msg['subject'] ?: '(sem assunto)') ?></a>
                    <div class="muted small"><?= e(mb_strimwidth($msg['body'], 0, 90, '…')) ?></div>
                </td>
                <td class="nowrap"><?= e(short_date($msg['created_at'])) ?></td>
                <td class="actions">
                    <form method="post" action="<?= url('admin/mensagens/' . $msg['id'] . '/excluir') ?>" data-confirm="Excluir a mensagem de <?= e($msg['name']) ?>?">
                        <?= csrf_field() ?>
                        <button type="submit" class="link-button danger">Excluir</button>
                    </form>
                </td>
            </tr>
        <?php endforeach ?>
        </tbody>
    </table>
    <?= render('site/partials/pagination', ['pagination' => $pagination]) ?>
<?php else: ?>
    <p class="muted"><?= $filter === 'nao-lidas' ? 'Nenhuma mensagem não lida.' : 'Nenhuma mensagem recebida ainda.' ?></p>
<?php endif ?>
