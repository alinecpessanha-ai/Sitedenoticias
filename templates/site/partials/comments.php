<?php
/**
 * Seção de comentários da notícia.
 * Variáveis: $post, $comments, $commentCount, $closedReason, $myPending, $commentErrors, $commentOld
 */
if ($closedReason === 'disabled') {
    return; // comentários desligados no site inteiro
}
$open = $closedReason === null;
$replyTo = (int) ($commentOld['parent_id'] ?? ($_GET['responder'] ?? 0));
$replyName = '';
foreach ($comments as $thread) {
    if ((int) $thread['id'] === $replyTo) {
        $replyName = $thread['author_name'];
    }
}
if ($replyName === '') {
    $replyTo = 0;
}
?>
<section class="comments" id="comentarios">
    <h2 class="section-title">
        <?= $commentCount ? $commentCount . ' ' . ($commentCount === 1 ? 'comentário' : 'comentários') : 'Comentários' ?>
    </h2>

    <?php foreach (get_flashes() as [$type, $message]): ?>
        <div class="alert alert-<?= e($type) ?>" role="status"><?= e($message) ?></div>
    <?php endforeach ?>

    <?php if ($comments || $myPending): ?>
        <ol class="comment-list">
            <?php foreach ($comments as $thread): ?>
                <li>
                    <?= render('site/partials/comment', ['c' => $thread, 'canReply' => $open]) ?>
                    <?php if ($thread['replies']): ?>
                        <ol class="comment-replies">
                            <?php foreach ($thread['replies'] as $reply): ?>
                                <li><?= render('site/partials/comment', ['c' => $reply, 'canReply' => false]) ?></li>
                            <?php endforeach ?>
                        </ol>
                    <?php endif ?>
                </li>
            <?php endforeach ?>
            <?php foreach ($myPending as $mine): ?>
                <li><?= render('site/partials/comment', ['c' => $mine, 'pending' => true]) ?></li>
            <?php endforeach ?>
        </ol>
    <?php elseif ($open): ?>
        <p class="muted">Ninguém comentou ainda. Seja a primeira pessoa!</p>
    <?php endif ?>

    <?php if ($open): ?>
        <form method="post" action="<?= url('noticia/' . $post['slug'] . '/comentar') ?>" class="contact-form comment-form" id="comentar">
            <h3>Deixe seu comentário</h3>
            <?php if ($commentErrors): ?>
                <div class="alert alert-error" role="alert">
                    <?php foreach ($commentErrors as $error): ?><div><?= e($error) ?></div><?php endforeach ?>
                </div>
            <?php endif ?>
            <?= csrf_field() ?>
            <input type="hidden" name="parent_id" value="<?= $replyTo ?: '' ?>" data-parent-input>
            <p class="replying" data-replying <?= $replyTo ? '' : 'hidden' ?>>
                Respondendo a <strong data-replying-name><?= e($replyName) ?></strong>
                · <a href="<?= post_url($post) ?>#comentar" data-cancel-reply>cancelar</a>
            </p>
            <label>Comentário
                <textarea name="body" required minlength="<?= COMMENT_MIN_LENGTH ?>" maxlength="<?= COMMENT_MAX_LENGTH ?>" rows="5"><?= e($commentOld['body'] ?? '') ?></textarea>
            </label>
            <div class="contact-row">
                <label>Nome
                    <input type="text" name="name" required maxlength="80" value="<?= e($commentOld['name'] ?? '') ?>" autocomplete="name">
                </label>
                <label>E-mail <span class="muted small">(não será publicado)</span>
                    <input type="email" name="email" required maxlength="150" value="<?= e($commentOld['email'] ?? '') ?>" autocomplete="email">
                </label>
            </div>
            <div class="hp" aria-hidden="true">
                <label>Não preencha este campo <input type="text" name="website" tabindex="-1" autocomplete="off"></label>
            </div>
            <p class="muted small">Os comentários passam por moderação antes de aparecer. Seja respeitoso: ofensas e spam não serão publicados.</p>
            <button type="submit">Publicar comentário</button>
        </form>
        <script src="<?= asset('js/comments.js') ?>" defer></script>
    <?php else: ?>
        <p class="muted"><?= e($closedReason) ?></p>
    <?php endif ?>
</section>
