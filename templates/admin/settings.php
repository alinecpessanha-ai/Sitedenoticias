<h1>Configurações</h1>

<form method="post" action="<?= url('admin/configuracoes') ?>" class="form panel narrow">
    <?= csrf_field() ?>
    <fieldset>
        <legend>Geral</legend>
        <label>Nome do site
            <input type="text" name="site_name" required maxlength="100" value="<?= e(setting('site_name')) ?>">
        </label>
        <label>Descrição (slogan)
            <input type="text" name="site_description" maxlength="300" value="<?= e(setting('site_description')) ?>">
        </label>
        <label>Notícias por página nas listagens
            <input type="number" name="posts_per_page" min="1" max="50" value="<?= e(setting('posts_per_page', '10')) ?>">
        </label>
        <label>Texto do rodapé
            <input type="text" name="footer_text" maxlength="500" value="<?= e(setting('footer_text')) ?>" placeholder="© <?= date('Y') ?> Meu Portal. Todos os direitos reservados.">
        </label>
    </fieldset>
    <fieldset>
        <legend>Contato</legend>
        <label>E-mail para aviso de novas mensagens
            <input type="email" name="contact_email" value="<?= e(setting('contact_email')) ?>" placeholder="redacao@meuportal.com.br">
        </label>
        <p class="muted small">Opcional. As mensagens do formulário sempre ficam salvas em “Mensagens”; o aviso por e-mail depende de o servidor conseguir enviar e-mails.</p>
    </fieldset>
    <fieldset>
        <legend>Comentários</legend>
        <label class="checkbox"><input type="checkbox" name="comments_enabled" value="1" <?= setting('comments_enabled', '1') === '1' ? 'checked' : '' ?>> Permitir comentários no site</label>
        <label>Moderação
            <select name="comments_moderation">
                <?php foreach (COMMENT_MODERATION as $value => $label): ?>
                    <option value="<?= $value ?>" <?= setting('comments_moderation', 'all') === $value ? 'selected' : '' ?>><?= e($label) ?></option>
                <?php endforeach ?>
            </select>
        </label>
        <label>Fechar comentários depois de quantos dias da publicação
            <input type="number" name="comments_close_days" min="0" max="3650" value="<?= e(setting('comments_close_days', '0')) ?>">
        </label>
        <p class="muted small">Use 0 para nunca fechar.</p>
        <label>Palavras bloqueadas (uma por linha)
            <textarea name="comments_blocklist" rows="4" placeholder="Comentários com estas palavras no nome, e-mail ou texto vão direto para o spam"><?= e(setting('comments_blocklist')) ?></textarea>
        </label>
    </fieldset>
    <fieldset>
        <legend>Redes sociais</legend>
        <label>Instagram <input type="url" name="social_instagram" value="<?= e(setting('social_instagram')) ?>" placeholder="https://instagram.com/..."></label>
        <label>X (Twitter) <input type="url" name="social_x" value="<?= e(setting('social_x')) ?>" placeholder="https://x.com/..."></label>
        <label>Facebook <input type="url" name="social_facebook" value="<?= e(setting('social_facebook')) ?>" placeholder="https://facebook.com/..."></label>
        <label>YouTube <input type="url" name="social_youtube" value="<?= e(setting('social_youtube')) ?>" placeholder="https://youtube.com/..."></label>
    </fieldset>
    <button type="submit" class="btn btn-primary">Salvar configurações</button>
</form>
