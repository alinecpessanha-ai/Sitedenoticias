<div class="page-head">
    <div>
        <h1>Histórico de alterações</h1>
        <p class="muted"><?= e($post['title']) ?></p>
    </div>
    <a class="btn" href="<?= url('admin/noticias/' . $post['id'] . '/editar') ?>">← Voltar para a notícia</a>
</div>

<?php if ($revisions): ?>
    <ol class="timeline">
        <?php foreach ($revisions as $i => $rev): ?>
            <li class="<?= $i === 0 ? 'current' : '' ?>">
                <div class="timeline-head">
                    <strong><?= e($rev['user_name'] ?? 'Usuário excluído') ?></strong>
                    <span class="muted"><?= e(format_date($rev['created_at'], true)) ?></span>
                    <?php if ($i === 0): ?><span class="badge">Versão atual</span><?php endif ?>
                </div>
                <?php if ($rev['note']): ?>
                    <div class="timeline-note"><?= e($rev['note']) ?></div>
                <?php endif ?>
                <?php if ($rev['changes']): ?>
                    <div class="muted small">Alterou: <?= e(mb_strtolower(implode(', ', $rev['changes']))) ?></div>
                <?php endif ?>
                <div class="timeline-actions">
                    <a href="<?= url('admin/noticias/' . $post['id'] . '/historico/' . $rev['id']) ?>">
                        <?= isset($revisions[$i + 1]) ? 'Ver o que mudou' : 'Ver esta versão' ?>
                    </a>
                    <?php if ($i > 0): ?>
                        <a href="<?= url('admin/noticias/' . $post['id'] . '/historico/' . $rev['id'] . '?com=atual') ?>">Comparar com a atual</a>
                        <form method="post" action="<?= url('admin/noticias/' . $post['id'] . '/historico/' . $rev['id'] . '/restaurar') ?>"
                              data-confirm="Restaurar esta versão? O conteúdo atual continua guardado no histórico.">
                            <?= csrf_field() ?>
                            <button type="submit" class="link-button">Restaurar</button>
                        </form>
                    <?php endif ?>
                </div>
            </li>
        <?php endforeach ?>
    </ol>
    <p class="muted small">São guardadas as <?= REVISIONS_KEEP ?> versões mais recentes de cada notícia.</p>
<?php else: ?>
    <p class="muted">Nenhuma versão registrada ainda.</p>
<?php endif ?>
