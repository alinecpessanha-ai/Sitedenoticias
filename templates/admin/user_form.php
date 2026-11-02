<?php $isNew = empty($user['id']); ?>
<h1><?= e($title) ?></h1>

<?php foreach ($errors as $error): ?>
    <div class="alert alert-error"><?= e($error) ?></div>
<?php endforeach ?>

<form method="post" action="<?= url('admin/usuarios/salvar') ?>" class="form panel narrow">
    <?= csrf_field() ?>
    <input type="hidden" name="id" value="<?= (int) $user['id'] ?>">
    <?php if ($isProfile): ?><input type="hidden" name="perfil" value="1"><?php endif ?>

    <label>Nome
        <input type="text" name="name" required maxlength="100" value="<?= e($user['name']) ?>">
    </label>
    <label>E-mail
        <input type="email" name="email" required maxlength="150" value="<?= e($user['email']) ?>">
    </label>
    <?php if (!$isProfile): ?>
        <label>Papel
            <select name="role">
                <?php foreach (ROLES as $value => $label): ?>
                    <option value="<?= $value ?>" <?= $user['role'] === $value ? 'selected' : '' ?>><?= e($label) ?></option>
                <?php endforeach ?>
            </select>
        </label>
    <?php endif ?>
    <label>Minibiografia (aparece na página do autor)
        <textarea name="bio" rows="3" maxlength="500"><?= e($user['bio']) ?></textarea>
    </label>
    <label><?= $isNew ? 'Senha (mínimo 8 caracteres)' : 'Nova senha (deixe vazio para manter a atual)' ?>
        <input type="password" name="password" <?= $isNew ? 'required' : '' ?> minlength="8" autocomplete="new-password">
    </label>
    <button type="submit" class="btn btn-primary">Salvar</button>
    <?php if (!$isProfile): ?><a class="btn" href="<?= url('admin/usuarios') ?>">Cancelar</a><?php endif ?>
</form>
