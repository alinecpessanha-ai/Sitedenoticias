<h1>Instalação do CMS</h1>
<p class="muted">Preencha os dados abaixo para criar o seu portal e a conta de administrador.</p>

<?php foreach ($errors as $error): ?>
    <div class="alert alert-error"><?= e($error) ?></div>
<?php endforeach ?>

<form method="post" class="form">
    <?= csrf_field() ?>
    <fieldset>
        <legend>O site</legend>
        <label>Nome do site
            <input type="text" name="site_name" required value="<?= e($old['site_name'] ?? '') ?>" placeholder="Ex.: Diário da Cidade">
        </label>
        <label>Descrição (slogan)
            <input type="text" name="site_description" value="<?= e($old['site_description'] ?? '') ?>" placeholder="Ex.: Notícias de Niterói e região">
        </label>
    </fieldset>
    <fieldset>
        <legend>Administrador</legend>
        <label>Seu nome
            <input type="text" name="name" required value="<?= e($old['name'] ?? '') ?>">
        </label>
        <label>E-mail
            <input type="email" name="email" required value="<?= e($old['email'] ?? '') ?>">
        </label>
        <label>Senha (mínimo 8 caracteres)
            <input type="password" name="password" required minlength="8" autocomplete="new-password">
        </label>
    </fieldset>
    <button type="submit" class="btn btn-primary btn-block">Instalar</button>
</form>
