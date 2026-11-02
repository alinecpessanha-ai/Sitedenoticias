<?php
/**
 * Autenticação, papéis e permissões.
 *
 * Papéis:
 *  - admin:  tudo (usuários, configurações, conteúdo)
 *  - editor: publica e edita qualquer notícia, gerencia categorias, mídia, páginas, mensagens e comentários
 *  - author: escreve as próprias notícias e as envia para revisão
 */

const ROLES = [
    'admin'  => 'Administrador',
    'editor' => 'Editor',
    'author' => 'Autor',
];

const LOGIN_MAX_ATTEMPTS = 5;
const LOGIN_LOCK_MINUTES = 15;

function has_users(): bool
{
    return (bool) q_val('SELECT 1 FROM users LIMIT 1');
}

function current_user(): ?array
{
    static $user = false;
    if ($user === false) {
        $user = isset($_SESSION['user_id'])
            ? q_one('SELECT * FROM users WHERE id = ?', [$_SESSION['user_id']])
            : null;
    }
    return $user;
}

function login_user(array $user): void
{
    session_regenerate_id(true);
    $_SESSION['user_id'] = (int) $user['id'];
}

function logout_user(): void
{
    $_SESSION = [];
    session_regenerate_id(true);
}

function require_login(): array
{
    if (!has_users()) {
        redirect('/admin/instalar');
    }
    $user = current_user();
    if (!$user) {
        redirect('/admin/login');
    }
    return $user;
}

/** Verifica se o usuário logado pode executar uma ação. */
function can(string $ability, ?array $post = null): bool
{
    $user = current_user();
    if (!$user) {
        return false;
    }
    $role = $user['role'];
    $isEditor = in_array($role, ['admin', 'editor'], true);

    return match ($ability) {
        'manage_users', 'manage_settings' => $role === 'admin',
        'manage_categories', 'publish', 'edit_others_posts', 'manage_all_media',
        'manage_pages', 'manage_messages', 'manage_comments' => $isEditor,
        // Autores só mexem nas próprias notícias, e apenas enquanto não forem publicadas
        'edit_post', 'delete_post' => $isEditor || ($post !== null
            && (int) $post['author_id'] === (int) $user['id']
            && $post['status'] !== 'published'),
        default => false,
    };
}

function forbidden(): never
{
    http_response_code(403);
    view('admin/forbidden', ['title' => 'Acesso negado'], 'admin/layout');
    exit;
}

function require_can(string $ability, ?array $post = null): array
{
    $user = require_login();
    if (!can($ability, $post)) {
        forbidden();
    }
    return $user;
}

/* ---------- Limite de tentativas de login ---------- */

function login_locked(string $ip): bool
{
    $since = date('Y-m-d H:i:s', time() - LOGIN_LOCK_MINUTES * 60);
    $count = (int) q_val('SELECT COUNT(*) FROM login_attempts WHERE ip = ? AND attempted_at > ?', [$ip, $since]);
    return $count >= LOGIN_MAX_ATTEMPTS;
}

function record_failed_login(string $ip): void
{
    q('INSERT INTO login_attempts (ip, attempted_at) VALUES (?, ?)', [$ip, now()]);
    q('DELETE FROM login_attempts WHERE attempted_at < ?', [date('Y-m-d H:i:s', time() - 86400)]);
}

function clear_failed_logins(string $ip): void
{
    q('DELETE FROM login_attempts WHERE ip = ?', [$ip]);
}
