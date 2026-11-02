<?php
$tabs = ['pending' => 'Pendentes', 'approved' => 'Aprovados', 'spam' => 'Spam', 'todos' => 'Todos'];
$total = array_sum($counts);
?>
<h1>Comentários</h1>

<nav class="tabs">
    <?php foreach ($tabs as $key => $label): ?>
        <?php $n = $key === 'todos' ? $total : (int) ($counts[$key] ?? 0); ?>
        <a href="<?= url('admin/comentarios?status=' . $key) ?>" class="<?= $status === $key ? 'active' : '' ?>"><?= e($label) ?> <span class="muted">(<?= $n ?>)</span></a>
    <?php endforeach ?>
</nav>

<form class="filters" method="get">
    <input type="hidden" name="status" value="<?= e($status) ?>">
    <input type="search" name="q" value="<?= e($search) ?>" placeholder="Buscar no texto, nome ou e-mail…">
    <button class="btn" type="submit">Buscar</button>
</form>

<?php if ($comments): ?>
    <form method="post" action="<?= url('admin/comentarios/acao') ?>" id="moderacao">
        <?= csrf_field() ?>
        <input type="hidden" name="return_status" value="<?= e($status) ?>">
        <input type="hidden" name="return_q" value="<?= e($search) ?>">
        <div class="bulk-bar">
            <label class="checkbox"><input type="checkbox" data-check-all> Selecionar todos</label>
            <select name="action" aria-label="Ação em lote">
                <option value="">Ação em lote…</option>
                <option value="approve">Aprovar</option>
                <option value="pending">Devolver para pendentes</option>
                <option value="spam">Marcar como spam</option>
                <option value="delete">Excluir definitivamente</option>
            </select>
            <button type="submit" class="btn">Aplicar</button>
        </div>
    </form>

    <div class="comment-queue">
        <?php foreach ($comments as $c): ?>
            <article class="panel queue-item status-border-<?= e($c['status']) ?>">
                <div class="queue-head">
                    <label class="checkbox"><input type="checkbox" name="ids[]" value="<?= (int) $c['id'] ?>" form="moderacao" aria-label="Selecionar comentário de <?= e($c['author_name']) ?>"></label>
                    <div>
                        <strong><?= e($c['author_name']) ?></strong>
                        <?php if ($c['user_id']): ?><span class="badge">Redação</span><?php endif ?>
                        <span class="muted small"><?= e($c['author_email']) ?> · IP <?= e($c['ip']) ?></span>
                        <div class="muted small">
                            <?= e(short_date($c['created_at'])) ?> em
                            <a href="<?= url('noticia/' . $c['post_slug']) ?>#comentarios" target="_blank"><?= e($c['post_title']) ?></a>
                            <?php if ($c['parent_name']): ?> · resposta a <?= e($c['parent_name']) ?><?php endif ?>
                        </div>
                    </div>
                    <span class="status status-c-<?= e($c['status']) ?>"><?= e(COMMENT_STATUSES[$c['status']]) ?></span>
                </div>

                <div class="queue-body"><?= nl2br(e($c['body'])) ?></div>

                <div class="queue-actions">
                    <?php if ($c['status'] !== 'approved'): ?>
                        <button type="submit" form="moderacao" name="single" value="approve:<?= (int) $c['id'] ?>" class="btn btn-small btn-approve">Aprovar</button>
                    <?php else: ?>
                        <button type="submit" form="moderacao" name="single" value="pending:<?= (int) $c['id'] ?>" class="btn btn-small">Despublicar</button>
                    <?php endif ?>
                    <?php if ($c['status'] !== 'spam'): ?>
                        <button type="submit" form="moderacao" name="single" value="spam:<?= (int) $c['id'] ?>" class="btn btn-small">Spam</button>
                    <?php else: ?>
                        <button type="submit" form="moderacao" name="single" value="pending:<?= (int) $c['id'] ?>" class="btn btn-small">Não é spam</button>
                    <?php endif ?>
                    <button type="submit" form="moderacao" name="single" value="delete:<?= (int) $c['id'] ?>" class="btn btn-small danger"
                            data-confirm-click="Excluir este comentário definitivamente?">Excluir</button>

                    <?php if ($c['status'] !== 'spam'): ?>
                        <details class="reply-box">
                            <summary class="btn btn-small">Responder</summary>
                            <form method="post" action="<?= url('admin/comentarios/' . $c['id'] . '/responder') ?>">
                                <?= csrf_field() ?>
                                <input type="hidden" name="return_status" value="<?= e($status) ?>">
                                <textarea name="body" rows="3" required maxlength="<?= COMMENT_MAX_LENGTH ?>" placeholder="Resposta da redação (aparece como “<?= e(current_user()['name']) ?>”)"></textarea>
                                <button type="submit" class="btn btn-primary btn-small">Publicar resposta</button>
                                <?php if ($c['status'] === 'pending'): ?><span class="muted small">Responder também aprova o comentário.</span><?php endif ?>
                            </form>
                        </details>
                    <?php endif ?>
                </div>
            </article>
        <?php endforeach ?>
    </div>
    <?= render('site/partials/pagination', ['pagination' => $pagination]) ?>
<?php else: ?>
    <p class="muted">
        <?= $status === 'pending' ? 'Nenhum comentário aguardando moderação. 🎉' : 'Nenhum comentário encontrado.' ?>
    </p>
<?php endif ?>
