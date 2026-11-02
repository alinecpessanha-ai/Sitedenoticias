<h1>Olá, <?= e(explode(' ', current_user()['name'])[0]) ?>!</h1>

<div class="stats">
    <a class="stat" href="<?= url('admin/noticias?status=published') ?>"><strong><?= $stats['published'] ?></strong><span>Publicadas</span></a>
    <a class="stat" href="<?= url('admin/noticias?status=scheduled') ?>"><strong><?= $stats['scheduled'] ?></strong><span>Agendadas</span></a>
    <a class="stat" href="<?= url('admin/noticias?status=pending') ?>"><strong><?= $stats['pending'] ?></strong><span>Aguardando revisão</span></a>
    <a class="stat" href="<?= url('admin/noticias?status=draft') ?>"><strong><?= $stats['draft'] ?></strong><span>Rascunhos</span></a>
    <div class="stat"><strong><?= number_format($stats['views'], 0, ',', '.') ?></strong><span>Visualizações</span></div>
    <?php if ($stats['messages'] !== null): ?>
        <a class="stat" href="<?= url('admin/mensagens?filtro=nao-lidas') ?>"><strong><?= $stats['messages'] ?></strong><span>Mensagens não lidas</span></a>
    <?php endif ?>
</div>

<div class="columns">
    <section class="panel">
        <h2>Atividade recente</h2>
        <?php if ($recent): ?>
            <table class="table">
                <tbody>
                <?php foreach ($recent as $post): ?>
                    <tr>
                        <td><a href="<?= url('admin/noticias/' . $post['id'] . '/editar') ?>"><?= e($post['title']) ?></a>
                            <div class="muted small"><?= e($post['author_name']) ?> · <?= e(short_date($post['updated_at'])) ?></div></td>
                        <td class="right"><?= render('admin/partials/status', ['post' => $post]) ?></td>
                    </tr>
                <?php endforeach ?>
                </tbody>
            </table>
        <?php else: ?>
            <p class="muted">Nada por aqui ainda. <a href="<?= url('admin/noticias/nova') ?>">Escreva a primeira notícia</a>.</p>
        <?php endif ?>
    </section>

    <div>
        <?php if ($pendingList): ?>
            <section class="panel">
                <h2>Para revisar</h2>
                <ul class="plain-list">
                    <?php foreach ($pendingList as $post): ?>
                        <li><a href="<?= url('admin/noticias/' . $post['id'] . '/editar') ?>"><?= e($post['title']) ?></a>
                            <span class="muted small">por <?= e($post['author_name']) ?></span></li>
                    <?php endforeach ?>
                </ul>
            </section>
        <?php endif ?>
        <section class="panel">
            <h2>Mais lidas</h2>
            <?php if ($popular): ?>
                <ol class="plain-list">
                    <?php foreach ($popular as $post): ?>
                        <li><a href="<?= post_url($post) ?>" target="_blank"><?= e($post['title']) ?></a>
                            <span class="muted small"><?= number_format((int) $post['views'], 0, ',', '.') ?> visualizações</span></li>
                    <?php endforeach ?>
                </ol>
            <?php else: ?>
                <p class="muted">Sem dados ainda.</p>
            <?php endif ?>
        </section>
    </div>
</div>
