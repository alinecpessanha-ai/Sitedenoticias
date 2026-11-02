<?php
/**
 * Rotas do painel administrativo (/admin).
 */

const ADMIN_LAYOUT = 'admin/layout';
const AUTH_LAYOUT = 'admin/auth_layout';

/* ======================================================================
 * Instalação (primeiro acesso)
 * ==================================================================== */

route('GET', '/admin/instalar', function () {
    if (has_users()) {
        redirect('/admin/login');
    }
    view('admin/setup', ['title' => 'Instalação', 'errors' => [], 'old' => []], AUTH_LAYOUT);
});

route('POST', '/admin/instalar', function () {
    if (has_users()) {
        redirect('/admin/login');
    }
    verify_csrf();

    $old = [
        'site_name' => input('site_name'),
        'site_description' => input('site_description'),
        'name' => input('name'),
        'email' => input('email'),
    ];
    $password = (string) ($_POST['password'] ?? '');

    $errors = [];
    if ($old['site_name'] === '') {
        $errors[] = 'Informe o nome do site.';
    }
    if ($old['name'] === '') {
        $errors[] = 'Informe o seu nome.';
    }
    if (!filter_var($old['email'], FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Informe um e-mail válido.';
    }
    if (mb_strlen($password) < 8) {
        $errors[] = 'A senha deve ter pelo menos 8 caracteres.';
    }
    if ($errors) {
        view('admin/setup', ['title' => 'Instalação', 'errors' => $errors, 'old' => $old], AUTH_LAYOUT);
        return;
    }

    db()->beginTransaction();
    q('INSERT INTO users (name, email, password_hash, role, created_at) VALUES (?, ?, ?, ?, ?)', [
        $old['name'], $old['email'], password_hash($password, PASSWORD_DEFAULT), 'admin', now(),
    ]);
    $userId = last_id();
    save_setting('site_name', $old['site_name']);
    save_setting('site_description', $old['site_description']);
    save_setting('posts_per_page', '10');

    q('INSERT INTO categories (name, slug, description, created_at) VALUES (?, ?, ?, ?)', [
        'Geral', 'geral', 'Notícias gerais', now(),
    ]);
    $categoryId = last_id();

    q('INSERT INTO posts (title, slug, excerpt, content, category_id, author_id, status, is_featured, published_at, created_at, updated_at)
       VALUES (?, ?, ?, ?, ?, ?, ?, 1, ?, ?, ?)', [
        'Bem-vindo ao seu novo portal de notícias',
        'bem-vindo-ao-seu-novo-portal-de-noticias',
        'Esta é a primeira notícia do site. Edite ou apague pelo painel administrativo.',
        '<p>Parabéns! O seu CMS está funcionando.</p><h2>Próximos passos</h2><ul><li>Crie as categorias do seu portal (Política, Esportes, Cultura...).</li><li>Cadastre a sua equipe de editores e autores.</li><li>Publique a primeira notícia de verdade!</li></ul>',
        $categoryId, $userId, 'published', now(), now(), now(),
    ]);
    db()->commit();

    login_user(['id' => $userId]);
    flash('success', 'Instalação concluída! Bem-vindo ao painel.');
    redirect('/admin');
});

/* ======================================================================
 * Login / logout
 * ==================================================================== */

route('GET', '/admin/login', function () {
    if (!has_users()) {
        redirect('/admin/instalar');
    }
    if (current_user()) {
        redirect('/admin');
    }
    view('admin/login', ['title' => 'Entrar', 'error' => null, 'email' => ''], AUTH_LAYOUT);
});

route('POST', '/admin/login', function () {
    verify_csrf();
    $email = input('email');
    $password = (string) ($_POST['password'] ?? '');
    $ip = client_ip();

    $error = null;
    if (login_locked($ip)) {
        $error = 'Muitas tentativas de login. Aguarde ' . LOGIN_LOCK_MINUTES . ' minutos e tente novamente.';
    } else {
        $user = q_one('SELECT * FROM users WHERE email = ?', [$email]);
        if ($user && password_verify($password, $user['password_hash'])) {
            clear_failed_logins($ip);
            if (password_needs_rehash($user['password_hash'], PASSWORD_DEFAULT)) {
                q('UPDATE users SET password_hash = ? WHERE id = ?', [password_hash($password, PASSWORD_DEFAULT), $user['id']]);
            }
            login_user($user);
            redirect('/admin');
        }
        record_failed_login($ip);
        $error = 'E-mail ou senha incorretos.';
    }

    http_response_code(422);
    view('admin/login', ['title' => 'Entrar', 'error' => $error, 'email' => $email], AUTH_LAYOUT);
});

route('POST', '/admin/sair', function () {
    verify_csrf();
    logout_user();
    redirect('/admin/login');
});

/* ======================================================================
 * Painel
 * ==================================================================== */

route('GET', '/admin', function () {
    $user = require_login();
    $mine = can('edit_others_posts') ? '' : ' AND author_id = ' . (int) $user['id'];

    $stats = [
        'published' => (int) q_val("SELECT COUNT(*) FROM posts WHERE status = 'published' AND published_at <= ?{$mine}", [now()]),
        'scheduled' => (int) q_val("SELECT COUNT(*) FROM posts WHERE status = 'published' AND published_at > ?{$mine}", [now()]),
        'pending'   => (int) q_val("SELECT COUNT(*) FROM posts WHERE status = 'pending'{$mine}"),
        'draft'     => (int) q_val("SELECT COUNT(*) FROM posts WHERE status = 'draft'{$mine}"),
        'views'     => (int) q_val("SELECT COALESCE(SUM(views), 0) FROM posts WHERE 1 = 1{$mine}"),
        'messages'  => can('manage_messages') ? unread_messages_count() : null,
    ];

    view('admin/dashboard', [
        'title'   => 'Painel',
        'stats'   => $stats,
        'recent'  => q_all(POST_SELECT . " WHERE 1 = 1{$mine} ORDER BY p.updated_at DESC LIMIT 8"),
        'popular' => q_all(POST_SELECT . " WHERE p.status = 'published'{$mine} ORDER BY p.views DESC LIMIT 5"),
        'pendingList' => can('publish')
            ? q_all(POST_SELECT . " WHERE p.status = 'pending' ORDER BY p.updated_at DESC LIMIT 5")
            : [],
    ], ADMIN_LAYOUT);
});

/* ======================================================================
 * Notícias
 * ==================================================================== */

route('GET', '/admin/noticias', function () {
    $user = require_login();

    $where = ['1 = 1'];
    $params = [];
    if (!can('edit_others_posts')) {
        $where[] = 'p.author_id = ?';
        $params[] = $user['id'];
    }
    $status = input('status');
    if ($status === 'scheduled') {
        $where[] = "p.status = 'published' AND p.published_at > ?";
        $params[] = now();
    } elseif (isset(POST_STATUSES[$status])) {
        $where[] = 'p.status = ?';
        $params[] = $status;
    }
    $categoryId = (int) input('categoria');
    if ($categoryId) {
        $where[] = 'p.category_id = ?';
        $params[] = $categoryId;
    }
    $search = input('q');
    if ($search !== '') {
        $where[] = 'p.title LIKE ?';
        $params[] = '%' . $search . '%';
    }
    $whereSql = implode(' AND ', $where);

    $total = (int) q_val("SELECT COUNT(*) FROM posts p WHERE {$whereSql}", $params);
    $pg = paginate($total, 20, current_page());
    $posts = q_all(POST_SELECT . " WHERE {$whereSql} ORDER BY COALESCE(p.published_at, p.updated_at) DESC, p.id DESC
        LIMIT {$pg['per_page']} OFFSET {$pg['offset']}", $params);

    view('admin/posts', [
        'title'      => 'Notícias',
        'posts'      => $posts,
        'pagination' => $pg,
        'status'     => $status,
        'categoryId' => $categoryId,
        'search'     => $search,
    ], ADMIN_LAYOUT);
});

route('GET', '/admin/noticias/nova', function () {
    require_login();
    view('admin/post_form', [
        'title'  => 'Nova notícia',
        'post'   => ['id' => 0, 'title' => '', 'slug' => '', 'excerpt' => '', 'content' => '', 'featured_image' => null,
            'image_caption' => '', 'category_id' => null, 'status' => 'draft', 'is_featured' => 0, 'published_at' => null],
        'tags'   => '',
        'errors' => [],
    ], ADMIN_LAYOUT);
});

route('GET', '/admin/noticias/{id}/editar', function (string $id) {
    require_login();
    $post = q_one('SELECT * FROM posts WHERE id = ?', [(int) $id]);
    if (!$post) {
        not_found();
    }
    if (!can('edit_post', $post)) {
        forbidden();
    }
    view('admin/post_form', [
        'title'  => 'Editar notícia',
        'post'   => $post,
        'tags'   => implode(', ', array_column(post_tags((int) $post['id']), 'name')),
        'errors' => [],
    ], ADMIN_LAYOUT);
});

route('POST', '/admin/noticias/salvar', function () {
    $user = require_login();
    verify_csrf();

    $id = (int) input('id');
    $existing = null;
    if ($id) {
        $existing = q_one('SELECT * FROM posts WHERE id = ?', [$id]);
        if (!$existing) {
            not_found();
        }
        if (!can('edit_post', $existing)) {
            forbidden();
        }
    }

    $data = [
        'title'         => mb_substr(input('title'), 0, 250),
        'excerpt'       => mb_substr(input('excerpt'), 0, 500),
        'content'       => sanitize_html((string) ($_POST['content'] ?? '')),
        'image_caption' => mb_substr(input('image_caption'), 0, 250),
        'category_id'   => null,
        'status'        => input('status', 'draft'),
    ];

    $categoryId = (int) input('category_id');
    if ($categoryId && q_val('SELECT id FROM categories WHERE id = ?', [$categoryId])) {
        $data['category_id'] = $categoryId;
    }

    $allowedStatuses = can('publish') ? ['draft', 'pending', 'published'] : ['draft', 'pending'];
    if (!in_array($data['status'], $allowedStatuses, true)) {
        $data['status'] = 'draft';
    }

    $data['is_featured'] = can('publish') ? (isset($_POST['is_featured']) ? 1 : 0) : (int) ($existing['is_featured'] ?? 0);

    // Data de publicação: se vazia e publicando agora, usa o momento atual
    $publishedInput = input('published_at');
    $publishedAt = $publishedInput !== '' && strtotime($publishedInput) !== false
        ? date('Y-m-d H:i:s', strtotime($publishedInput))
        : null;
    if ($data['status'] === 'published' && $publishedAt === null) {
        $publishedAt = ($existing && $existing['published_at']) ? $existing['published_at'] : now();
    }
    $data['published_at'] = $publishedAt;

    $slugSource = input('slug') !== '' ? input('slug') : $data['title'];
    $data['slug'] = unique_slug('posts', slugify($slugSource), $id);

    // Imagem destacada
    $data['featured_image'] = $existing['featured_image'] ?? null;
    if (isset($_POST['remove_image'])) {
        $data['featured_image'] = null;
    }
    $errors = [];
    if (($_FILES['featured_image']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
        $upload = handle_image_upload($_FILES['featured_image']);
        if ($upload['ok']) {
            $data['featured_image'] = $upload['path'];
        } else {
            $errors[] = 'Imagem destacada: ' . $upload['error'];
        }
    } elseif (input('featured_media') !== '') {
        // Imagem escolhida da biblioteca de mídia
        $media = q_one('SELECT path FROM media WHERE id = ?', [(int) input('featured_media')]);
        if ($media) {
            $data['featured_image'] = $media['path'];
        }
    }

    if ($data['title'] === '') {
        $errors[] = 'O título é obrigatório.';
    }

    if ($errors) {
        view('admin/post_form', [
            'title'  => $id ? 'Editar notícia' : 'Nova notícia',
            'post'   => array_merge($existing ?? [], $data, ['id' => $id]),
            'tags'   => input('tags'),
            'errors' => $errors,
        ], ADMIN_LAYOUT);
        return;
    }

    $columns = ['title', 'slug', 'excerpt', 'content', 'featured_image', 'image_caption', 'category_id', 'status', 'is_featured', 'published_at'];
    $values = array_map(fn ($c) => $data[$c], $columns);

    if ($existing) {
        $set = implode(', ', array_map(fn ($c) => "{$c} = ?", $columns));
        q("UPDATE posts SET {$set}, updated_at = ? WHERE id = ?", [...$values, now(), $id]);
    } else {
        $cols = implode(', ', $columns);
        $marks = implode(', ', array_fill(0, count($columns), '?'));
        q("INSERT INTO posts ({$cols}, author_id, created_at, updated_at) VALUES ({$marks}, ?, ?, ?)",
            [...$values, $user['id'], now(), now()]);
        $id = last_id();
    }
    sync_tags($id, input('tags'));

    $messages = [
        'draft'     => 'Rascunho salvo.',
        'pending'   => 'Notícia enviada para revisão.',
        'published' => $data['published_at'] > now() ? 'Notícia agendada para ' . short_date($data['published_at']) . '.' : 'Notícia publicada!',
    ];
    flash('success', $messages[$data['status']]);

    redirect('/admin/noticias/' . $id . '/editar');
});

route('POST', '/admin/noticias/{id}/excluir', function (string $id) {
    require_login();
    verify_csrf();
    $post = q_one('SELECT * FROM posts WHERE id = ?', [(int) $id]);
    if (!$post) {
        not_found();
    }
    if (!can('delete_post', $post)) {
        forbidden();
    }
    q('DELETE FROM posts WHERE id = ?', [$post['id']]);
    q('DELETE FROM tags WHERE id NOT IN (SELECT tag_id FROM post_tags)');
    flash('success', 'Notícia “' . $post['title'] . '” excluída.');
    redirect('/admin/noticias');
});

/* ======================================================================
 * Categorias
 * ==================================================================== */

route('GET', '/admin/categorias', function () {
    require_can('manage_categories');
    $editing = null;
    if (input('editar') !== '') {
        $editing = q_one('SELECT * FROM categories WHERE id = ?', [(int) input('editar')]);
    }
    view('admin/categories', [
        'title'      => 'Categorias',
        'categories' => q_all('SELECT c.*, (SELECT COUNT(*) FROM posts p WHERE p.category_id = c.id) AS post_count
                               FROM categories c ORDER BY c.position, c.name'),
        'editing'    => $editing,
    ], ADMIN_LAYOUT);
});

route('POST', '/admin/categorias/salvar', function () {
    require_can('manage_categories');
    verify_csrf();

    $id = (int) input('id');
    $name = mb_substr(input('name'), 0, 100);
    if ($name === '') {
        flash('error', 'O nome da categoria é obrigatório.');
        redirect('/admin/categorias' . ($id ? '?editar=' . $id : ''));
    }
    $slug = unique_slug('categories', slugify(input('slug') !== '' ? input('slug') : $name), $id);
    $description = mb_substr(input('description'), 0, 300);
    $position = (int) input('position', '0');

    if ($id) {
        q('UPDATE categories SET name = ?, slug = ?, description = ?, position = ? WHERE id = ?',
            [$name, $slug, $description, $position, $id]);
        flash('success', 'Categoria atualizada.');
    } else {
        q('INSERT INTO categories (name, slug, description, position, created_at) VALUES (?, ?, ?, ?, ?)',
            [$name, $slug, $description, $position, now()]);
        flash('success', 'Categoria criada.');
    }
    redirect('/admin/categorias');
});

route('POST', '/admin/categorias/{id}/excluir', function (string $id) {
    require_can('manage_categories');
    verify_csrf();
    q('DELETE FROM categories WHERE id = ?', [(int) $id]);
    flash('success', 'Categoria excluída. As notícias dela ficaram sem categoria.');
    redirect('/admin/categorias');
});

/* ======================================================================
 * Mídia
 * ==================================================================== */

route('GET', '/admin/midia', function () {
    $user = require_login();
    $where = can('manage_all_media') ? '1 = 1' : 'uploaded_by = ' . (int) $user['id'];
    $total = (int) q_val("SELECT COUNT(*) FROM media WHERE {$where}");
    $pg = paginate($total, 24, current_page());
    $items = q_all("SELECT m.*, u.name AS uploader FROM media m LEFT JOIN users u ON u.id = m.uploaded_by
        WHERE {$where} ORDER BY m.id DESC LIMIT {$pg['per_page']} OFFSET {$pg['offset']}");

    // Modo "seletor" (usado no formulário de notícia) devolve JSON
    if (input('formato') === 'json') {
        header('Content-Type: application/json; charset=UTF-8');
        echo json_encode(array_map(fn ($m) => [
            'id' => (int) $m['id'], 'url' => upload_url($m['path']), 'thumb' => image_url($m['path'], 400),
            'name' => $m['original_name'],
        ], $items), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        return;
    }

    view('admin/media', ['title' => 'Mídia', 'items' => $items, 'pagination' => $pg], ADMIN_LAYOUT);
});

route('POST', '/admin/midia/enviar', function () {
    require_login();
    verify_csrf();
    $result = handle_image_upload($_FILES['file'] ?? null);

    $wantsJson = str_contains($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json');
    if ($wantsJson) {
        header('Content-Type: application/json; charset=UTF-8');
        if (!$result['ok']) {
            http_response_code(422);
        }
        echo json_encode($result, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        return;
    }

    flash($result['ok'] ? 'success' : 'error', $result['ok'] ? 'Imagem enviada.' : $result['error']);
    redirect('/admin/midia');
});

route('POST', '/admin/midia/miniaturas', function () {
    require_can('manage_all_media');
    verify_csrf();
    $stats = regenerate_all_variants();
    flash('success', $stats['created']
        ? "Miniaturas geradas para {$stats['created']} imagem(ns)."
        : 'Todas as imagens já tinham miniaturas.');
    redirect('/admin/midia');
});

route('POST', '/admin/midia/{id}/excluir', function (string $id) {
    $user = require_login();
    verify_csrf();
    $media = q_one('SELECT * FROM media WHERE id = ?', [(int) $id]);
    if (!$media) {
        not_found();
    }
    if (!can('manage_all_media') && (int) $media['uploaded_by'] !== (int) $user['id']) {
        forbidden();
    }
    delete_media($media);
    flash('success', 'Imagem excluída.');
    redirect('/admin/midia');
});

/* ======================================================================
 * Usuários
 * ==================================================================== */

route('GET', '/admin/usuarios', function () {
    require_can('manage_users');
    view('admin/users', [
        'title' => 'Usuários',
        'users' => q_all('SELECT u.*, (SELECT COUNT(*) FROM posts p WHERE p.author_id = u.id) AS post_count
                          FROM users u ORDER BY u.name'),
    ], ADMIN_LAYOUT);
});

route('GET', '/admin/usuarios/novo', function () {
    require_can('manage_users');
    view('admin/user_form', [
        'title' => 'Novo usuário',
        'user' => ['id' => 0, 'name' => '', 'email' => '', 'role' => 'author', 'bio' => ''],
        'errors' => [],
        'isProfile' => false,
    ], ADMIN_LAYOUT);
});

route('GET', '/admin/usuarios/{id}/editar', function (string $id) {
    require_can('manage_users');
    $user = q_one('SELECT * FROM users WHERE id = ?', [(int) $id]);
    if (!$user) {
        not_found();
    }
    view('admin/user_form', ['title' => 'Editar usuário', 'user' => $user, 'errors' => [], 'isProfile' => false], ADMIN_LAYOUT);
});

route('GET', '/admin/perfil', function () {
    $user = require_login();
    view('admin/user_form', ['title' => 'Meu perfil', 'user' => $user, 'errors' => [], 'isProfile' => true], ADMIN_LAYOUT);
});

route('POST', '/admin/usuarios/salvar', function () {
    $me = require_login();
    verify_csrf();

    $id = (int) input('id');
    $isProfile = $id === (int) $me['id'] && input('perfil') === '1';
    if (!$isProfile && !can('manage_users')) {
        forbidden();
    }

    $existing = $id ? q_one('SELECT * FROM users WHERE id = ?', [$id]) : null;
    if ($id && !$existing) {
        not_found();
    }

    $data = [
        'id'    => $id,
        'name'  => mb_substr(input('name'), 0, 100),
        'email' => mb_substr(input('email'), 0, 150),
        'bio'   => mb_substr(input('bio'), 0, 500),
        'role'  => $isProfile ? $me['role'] : input('role', 'author'),
    ];
    $password = (string) ($_POST['password'] ?? '');

    $errors = [];
    if ($data['name'] === '') {
        $errors[] = 'Informe o nome.';
    }
    if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Informe um e-mail válido.';
    } elseif (q_val('SELECT id FROM users WHERE email = ? AND id != ?', [$data['email'], $id])) {
        $errors[] = 'Já existe um usuário com este e-mail.';
    }
    if (!isset(ROLES[$data['role']])) {
        $errors[] = 'Papel inválido.';
    }
    if (!$existing && mb_strlen($password) < 8) {
        $errors[] = 'A senha deve ter pelo menos 8 caracteres.';
    }
    if ($existing && $password !== '' && mb_strlen($password) < 8) {
        $errors[] = 'A nova senha deve ter pelo menos 8 caracteres.';
    }
    // Não permite deixar o site sem administrador
    if ($existing && $existing['role'] === 'admin' && $data['role'] !== 'admin'
        && (int) q_val("SELECT COUNT(*) FROM users WHERE role = 'admin'") <= 1) {
        $errors[] = 'Este é o único administrador; não é possível alterar o papel dele.';
    }

    if ($errors) {
        view('admin/user_form', [
            'title' => $isProfile ? 'Meu perfil' : ($id ? 'Editar usuário' : 'Novo usuário'),
            'user' => $data, 'errors' => $errors, 'isProfile' => $isProfile,
        ], ADMIN_LAYOUT);
        return;
    }

    if ($existing) {
        q('UPDATE users SET name = ?, email = ?, bio = ?, role = ? WHERE id = ?',
            [$data['name'], $data['email'], $data['bio'], $data['role'], $id]);
        if ($password !== '') {
            q('UPDATE users SET password_hash = ? WHERE id = ?', [password_hash($password, PASSWORD_DEFAULT), $id]);
        }
        flash('success', $isProfile ? 'Perfil atualizado.' : 'Usuário atualizado.');
    } else {
        q('INSERT INTO users (name, email, password_hash, role, bio, created_at) VALUES (?, ?, ?, ?, ?, ?)',
            [$data['name'], $data['email'], password_hash($password, PASSWORD_DEFAULT), $data['role'], $data['bio'], now()]);
        flash('success', 'Usuário criado.');
    }
    redirect($isProfile ? '/admin/perfil' : '/admin/usuarios');
});

route('POST', '/admin/usuarios/{id}/excluir', function (string $id) {
    $me = require_can('manage_users');
    verify_csrf();
    $user = q_one('SELECT * FROM users WHERE id = ?', [(int) $id]);
    if (!$user) {
        not_found();
    }
    if ((int) $user['id'] === (int) $me['id']) {
        flash('error', 'Você não pode excluir a própria conta.');
        redirect('/admin/usuarios');
    }
    db()->beginTransaction();
    // As notícias do usuário excluído passam para quem excluiu
    q('UPDATE posts SET author_id = ? WHERE author_id = ?', [$me['id'], $user['id']]);
    q('DELETE FROM users WHERE id = ?', [$user['id']]);
    db()->commit();
    flash('success', 'Usuário excluído. As notícias dele foram transferidas para você.');
    redirect('/admin/usuarios');
});

/* ======================================================================
 * Configurações
 * ==================================================================== */

route('GET', '/admin/configuracoes', function () {
    require_can('manage_settings');
    view('admin/settings', ['title' => 'Configurações'], ADMIN_LAYOUT);
});

route('POST', '/admin/configuracoes', function () {
    require_can('manage_settings');
    verify_csrf();
    $name = mb_substr(input('site_name'), 0, 100);
    if ($name === '') {
        flash('error', 'O nome do site é obrigatório.');
        redirect('/admin/configuracoes');
    }
    save_setting('site_name', $name);
    save_setting('site_description', mb_substr(input('site_description'), 0, 300));
    save_setting('posts_per_page', (string) max(1, min(50, (int) input('posts_per_page', '10'))));
    save_setting('footer_text', mb_substr(input('footer_text'), 0, 500));
    $contactEmail = input('contact_email');
    save_setting('contact_email', filter_var($contactEmail, FILTER_VALIDATE_EMAIL) ? $contactEmail : '');
    foreach (['social_instagram', 'social_x', 'social_facebook', 'social_youtube'] as $key) {
        $value = input($key);
        save_setting($key, $value !== '' && safe_url($value) !== null && preg_match('#^https?://#i', $value) ? $value : '');
    }
    flash('success', 'Configurações salvas.');
    redirect('/admin/configuracoes');
});

/* ======================================================================
 * Páginas fixas
 * ==================================================================== */

route('GET', '/admin/paginas', function () {
    require_can('manage_pages');
    view('admin/pages', [
        'title' => 'Páginas',
        'pages' => q_all('SELECT p.*, u.name AS author_name FROM pages p
                          LEFT JOIN users u ON u.id = p.author_id ORDER BY p.position, p.title'),
    ], ADMIN_LAYOUT);
});

route('GET', '/admin/paginas/nova', function () {
    require_can('manage_pages');
    view('admin/page_form', [
        'title'  => 'Nova página',
        'page'   => ['id' => 0, 'title' => '', 'slug' => '', 'content' => '', 'template' => 'default',
            'status' => 'draft', 'show_in_menu' => 1, 'position' => 0],
        'errors' => [],
    ], ADMIN_LAYOUT);
});

route('GET', '/admin/paginas/{id}/editar', function (string $id) {
    require_can('manage_pages');
    $page = q_one('SELECT * FROM pages WHERE id = ?', [(int) $id]);
    if (!$page) {
        not_found();
    }
    view('admin/page_form', ['title' => 'Editar página', 'page' => $page, 'errors' => []], ADMIN_LAYOUT);
});

route('POST', '/admin/paginas/salvar', function () {
    $user = require_can('manage_pages');
    verify_csrf();

    $id = (int) input('id');
    $existing = $id ? q_one('SELECT * FROM pages WHERE id = ?', [$id]) : null;
    if ($id && !$existing) {
        not_found();
    }

    $data = [
        'title'        => mb_substr(input('title'), 0, 200),
        'content'      => sanitize_html((string) ($_POST['content'] ?? '')),
        'template'     => isset(PAGE_TEMPLATES[input('template')]) ? input('template') : 'default',
        'status'       => isset(PAGE_STATUSES[input('status')]) ? input('status') : 'draft',
        'show_in_menu' => isset($_POST['show_in_menu']) ? 1 : 0,
        'position'     => (int) input('position', '0'),
    ];

    if ($data['title'] === '') {
        view('admin/page_form', [
            'title'  => $id ? 'Editar página' : 'Nova página',
            'page'   => array_merge($existing ?? [], $data, ['id' => $id, 'slug' => input('slug')]),
            'errors' => ['O título é obrigatório.'],
        ], ADMIN_LAYOUT);
        return;
    }

    $data['slug'] = page_slug(input('slug') !== '' ? input('slug') : $data['title'], $id);
    $columns = array_keys($data);
    $values = array_values($data);

    if ($existing) {
        $set = implode(', ', array_map(fn ($c) => "{$c} = ?", $columns));
        q("UPDATE pages SET {$set}, updated_at = ? WHERE id = ?", [...$values, now(), $id]);
    } else {
        $cols = implode(', ', $columns);
        $marks = implode(', ', array_fill(0, count($columns), '?'));
        q("INSERT INTO pages ({$cols}, author_id, created_at, updated_at) VALUES ({$marks}, ?, ?, ?)",
            [...$values, $user['id'], now(), now()]);
        $id = last_id();
    }

    flash('success', $data['status'] === 'published' ? 'Página publicada!' : 'Rascunho salvo.');
    redirect('/admin/paginas/' . $id . '/editar');
});

route('POST', '/admin/paginas/{id}/excluir', function (string $id) {
    require_can('manage_pages');
    verify_csrf();
    $page = q_one('SELECT * FROM pages WHERE id = ?', [(int) $id]);
    if (!$page) {
        not_found();
    }
    q('DELETE FROM pages WHERE id = ?', [$page['id']]);
    flash('success', 'Página “' . $page['title'] . '” excluída.');
    redirect('/admin/paginas');
});

/* ======================================================================
 * Mensagens do formulário de contato
 * ==================================================================== */

route('GET', '/admin/mensagens', function () {
    require_can('manage_messages');
    $filter = input('filtro');
    $where = $filter === 'nao-lidas' ? 'WHERE m.is_read = 0' : '';
    $total = (int) q_val("SELECT COUNT(*) FROM messages m {$where}");
    $pg = paginate($total, 30, current_page());
    $messages = q_all("SELECT m.*, p.title AS page_title FROM messages m LEFT JOIN pages p ON p.id = m.page_id
        {$where} ORDER BY m.id DESC LIMIT {$pg['per_page']} OFFSET {$pg['offset']}");

    view('admin/messages', [
        'title'      => 'Mensagens',
        'messages'   => $messages,
        'pagination' => $pg,
        'filter'     => $filter,
    ], ADMIN_LAYOUT);
});

route('GET', '/admin/mensagens/{id}', function (string $id) {
    require_can('manage_messages');
    $message = q_one('SELECT m.*, p.title AS page_title FROM messages m LEFT JOIN pages p ON p.id = m.page_id
        WHERE m.id = ?', [(int) $id]);
    if (!$message) {
        not_found();
    }
    if (!$message['is_read']) {
        q('UPDATE messages SET is_read = 1 WHERE id = ?', [$message['id']]);
    }
    view('admin/message', ['title' => 'Mensagem', 'message' => $message], ADMIN_LAYOUT);
});

route('POST', '/admin/mensagens/{id}/nao-lida', function (string $id) {
    require_can('manage_messages');
    verify_csrf();
    q('UPDATE messages SET is_read = 0 WHERE id = ?', [(int) $id]);
    flash('success', 'Mensagem marcada como não lida.');
    redirect('/admin/mensagens');
});

route('POST', '/admin/mensagens/{id}/excluir', function (string $id) {
    require_can('manage_messages');
    verify_csrf();
    q('DELETE FROM messages WHERE id = ?', [(int) $id]);
    flash('success', 'Mensagem excluída.');
    redirect('/admin/mensagens');
});
