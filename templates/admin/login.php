<h1><?= e(setting('site_name', 'Painel')) ?></h1>
<p class="muted">Entre para acessar o painel.</p>

<?php if ($error): ?>
    <div class="alert alert-error"><?= e($error) ?></div>
<?php endif ?>
<?php foreach (get_flashes() as [$type, $message]): ?>
    <div class="alert alert-<?= e($type) ?>"><?= e($message) ?></div>
<?php endforeach ?>

<form method="post" action="<?= url('admin/login') ?>" class="form">
    <?= csrf_field() ?>
    <label>E-mail
        <input type="email" name="email" required autofocus value="<?= e($email) ?>" autocomplete="username">
    </label>
    <label>Senha
        <input type="password" name="password" required autocomplete="current-password">
    </label>
    <button type="submit" class="btn btn-primary btn-block">Entrar</button>
</form>
<p class="center"><a href="<?= url('/') ?>">← Voltar ao site</a></p>
