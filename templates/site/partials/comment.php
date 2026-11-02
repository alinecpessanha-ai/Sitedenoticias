<?php
/** Um comentário. Variáveis: $c (comentário), $canReply, $pending (bool) */
$pending = $pending ?? false;
?>
<article class="comment<?= $pending ? ' comment-pending' : '' ?>" id="comentario-<?= (int) $c['id'] ?>">
    <div class="avatar" style="--hue: <?= avatar_hue($c['author_name']) ?>" aria-hidden="true"><?= e(initials($c['author_name'])) ?></div>
    <div class="comment-main">
        <header class="comment-head">
            <strong><?= e($c['author_name']) ?></strong>
            <?php if (!empty($c['user_id'])): ?><span class="staff-badge">Redação</span><?php endif ?>
            <time datetime="<?= e(date('c', strtotime($c['created_at']))) ?>"><?= e(format_date($c['created_at'], true)) ?></time>
        </header>
        <?php if ($pending): ?>
            <p class="pending-note">Seu comentário aguarda moderação e só você o vê por enquanto.</p>
        <?php endif ?>
        <div class="comment-body"><?= nl2br(e($c['body'])) ?></div>
        <?php if (!empty($canReply)): ?>
            <a class="reply-link" href="?responder=<?= (int) $c['id'] ?>#comentar"
               data-reply="<?= (int) $c['id'] ?>" data-reply-name="<?= e($c['author_name']) ?>">Responder</a>
        <?php endif ?>
    </div>
</article>
