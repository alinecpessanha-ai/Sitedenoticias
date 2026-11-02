<?php $replySubject = 'Re: ' . ($message['subject'] ?: 'Seu contato com ' . setting('site_name', 'o site')); ?>
<div class="page-head">
    <h1><?= e($message['subject'] ?: '(sem assunto)') ?></h1>
    <a class="btn" href="<?= url('admin/mensagens') ?>">← Voltar</a>
</div>

<section class="panel narrow">
    <dl class="message-meta">
        <dt>De</dt><dd><?= e($message['name']) ?> &lt;<a href="mailto:<?= e($message['email']) ?>"><?= e($message['email']) ?></a>&gt;</dd>
        <dt>Recebida em</dt><dd><?= e(format_date($message['created_at'], true)) ?></dd>
        <?php if ($message['page_title']): ?><dt>Pela página</dt><dd><?= e($message['page_title']) ?></dd><?php endif ?>
    </dl>
    <div class="message-body"><?= nl2br(e($message['body'])) ?></div>

    <div class="message-actions">
        <a class="btn btn-primary" href="mailto:<?= e($message['email']) ?>?subject=<?= rawurlencode($replySubject) ?>">Responder por e-mail</a>
        <form method="post" action="<?= url('admin/mensagens/' . $message['id'] . '/nao-lida') ?>">
            <?= csrf_field() ?>
            <button type="submit" class="btn">Marcar como não lida</button>
        </form>
        <form method="post" action="<?= url('admin/mensagens/' . $message['id'] . '/excluir') ?>" data-confirm="Excluir esta mensagem?">
            <?= csrf_field() ?>
            <button type="submit" class="btn danger">Excluir</button>
        </form>
    </div>
</section>
