<div class="layout">
    <article class="main-col single page">
        <?php if ($preview): ?>
            <div class="notice">Pré-visualização — esta página está como <strong>rascunho</strong> e não aparece para o público.</div>
        <?php endif ?>

        <header class="single-header">
            <h1><?= e($page['title']) ?></h1>
            <?php if (can('manage_pages')): ?>
                <a class="edit-link" href="<?= url('admin/paginas/' . $page['id'] . '/editar') ?>">Editar página</a>
            <?php endif ?>
        </header>

        <div class="post-content">
            <?= responsive_content($page['content']) /* HTML já sanitizado ao salvar */ ?>
        </div>

        <?php if ($page['template'] === 'contact'): ?>
            <section class="contact" id="contato">
                <?php foreach (get_flashes() as [$type, $message]): ?>
                    <div class="alert alert-<?= e($type) ?>" role="status"><?= e($message) ?></div>
                <?php endforeach ?>
                <?php if ($errors): ?>
                    <div class="alert alert-error" role="alert">
                        <?php foreach ($errors as $error): ?><div><?= e($error) ?></div><?php endforeach ?>
                    </div>
                <?php endif ?>

                <form method="post" action="<?= static_page_url($page) ?>#contato" class="contact-form">
                    <?= csrf_field() ?>
                    <div class="contact-row">
                        <label>Nome
                            <input type="text" name="name" required maxlength="100" value="<?= e($old['name'] ?? '') ?>" autocomplete="name">
                        </label>
                        <label>E-mail
                            <input type="email" name="email" required maxlength="150" value="<?= e($old['email'] ?? '') ?>" autocomplete="email">
                        </label>
                    </div>
                    <label>Assunto
                        <input type="text" name="subject" maxlength="150" value="<?= e($old['subject'] ?? '') ?>">
                    </label>
                    <label>Mensagem
                        <textarea name="body" required minlength="10" maxlength="5000" rows="6"><?= e($old['body'] ?? '') ?></textarea>
                    </label>
                    <!-- Campo armadilha contra spam: fica invisível para pessoas -->
                    <div class="hp" aria-hidden="true">
                        <label>Não preencha este campo <input type="text" name="website" tabindex="-1" autocomplete="off"></label>
                    </div>
                    <button type="submit">Enviar mensagem</button>
                </form>
            </section>
        <?php endif ?>
    </article>
    <?= render('site/partials/sidebar') ?>
</div>
