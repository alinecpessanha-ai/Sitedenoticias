<?php
/**
 * Comentários dos leitores com moderação.
 *
 * Fluxo: o leitor envia → o comentário fica "pendente" (ou vai para "spam" se for suspeito)
 * → um editor aprova no painel → aparece na notícia.
 * Opcionalmente, quem já teve um comentário aprovado é aprovado automaticamente.
 */

const COMMENT_STATUSES = [
    'pending'  => 'Pendente',
    'approved' => 'Aprovado',
    'spam'     => 'Spam',
];

const COMMENT_MODERATION = [
    'all'   => 'Aprovar manualmente todos os comentários',
    'known' => 'Aprovar automaticamente quem já teve um comentário aprovado',
];

const COMMENT_MIN_LENGTH = 3;
const COMMENT_MAX_LENGTH = 3000;
const COMMENT_MAX_LINKS = 2;          // mais links que isso → spam
const COMMENT_MAX_PER_WINDOW = 5;     // comentários por IP...
const COMMENT_WINDOW_MINUTES = 10;    // ...neste intervalo

/** Os comentários estão abertos nesta notícia? (Retorna o motivo se estiverem fechados.) */
function comments_closed_reason(array $post): ?string
{
    if (setting('comments_enabled', '1') !== '1') {
        return 'disabled';
    }
    if (empty($post['comments_enabled'])) {
        return 'Os comentários estão fechados nesta notícia.';
    }
    if (!is_public($post)) {
        return 'Os comentários abrem quando a notícia for publicada.';
    }
    $days = (int) setting('comments_close_days', '0');
    if ($days > 0 && strtotime($post['published_at']) < time() - $days * 86400) {
        return "Os comentários são fechados {$days} dias após a publicação.";
    }
    return null;
}

/** Comentários aprovados, organizados em conversa: [comentário + 'replies' => [...]]. */
function approved_comments(int $postId): array
{
    $rows = q_all("SELECT c.*, u.role AS user_role FROM comments c LEFT JOIN users u ON u.id = c.user_id
                   WHERE c.post_id = ? AND c.status = 'approved' ORDER BY c.id", [$postId]);
    $threads = [];
    foreach ($rows as $row) {
        if (!$row['parent_id']) {
            $threads[$row['id']] = $row + ['replies' => []];
        }
    }
    foreach ($rows as $row) {
        if ($row['parent_id'] && isset($threads[$row['parent_id']])) {
            $threads[$row['parent_id']]['replies'][] = $row;
        }
    }
    return array_values($threads);
}

function approved_comment_count(int $postId): int
{
    return (int) q_val("SELECT COUNT(*) FROM comments WHERE post_id = ? AND status = 'approved'", [$postId]);
}

function pending_comments_count(): int
{
    return (int) q_val("SELECT COUNT(*) FROM comments WHERE status = 'pending'");
}

/** Motivo para considerar o comentário spam, ou null se parecer legítimo. */
function comment_spam_reason(string $name, string $email, string $body): ?string
{
    $links = preg_match_all('#https?://|www\.#i', $body);
    if ($links > COMMENT_MAX_LINKS) {
        return 'muitos links';
    }
    $haystack = mb_strtolower("{$name}\n{$email}\n{$body}");
    foreach (preg_split('/\R/', setting('comments_blocklist')) as $word) {
        $word = mb_strtolower(trim($word));
        if ($word !== '' && str_contains($haystack, $word)) {
            return 'palavra bloqueada';
        }
    }
    return null;
}

/**
 * Valida e grava um comentário de leitor.
 * Retorna ['ok' => true, 'id' => ..., 'status' => ...] ou ['ok' => false, 'errors' => [...]].
 */
function submit_comment(array $post, array $input, string $ip): array
{
    $name = mb_substr(trim($input['name'] ?? ''), 0, 80);
    $email = mb_substr(trim($input['email'] ?? ''), 0, 150);
    $body = trim(str_replace("\r\n", "\n", $input['body'] ?? ''));
    $parentId = (int) ($input['parent_id'] ?? 0) ?: null;

    $errors = [];
    if ($name === '') {
        $errors[] = 'Informe o seu nome.';
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Informe um e-mail válido (ele não será publicado).';
    }
    if (mb_strlen($body) < COMMENT_MIN_LENGTH) {
        $errors[] = 'Escreva o seu comentário.';
    } elseif (mb_strlen($body) > COMMENT_MAX_LENGTH) {
        $errors[] = 'O comentário pode ter no máximo ' . COMMENT_MAX_LENGTH . ' caracteres.';
    }
    if ($parentId) {
        // Só dá para responder a um comentário aprovado, desta notícia, que não seja ele próprio uma resposta
        $parent = q_one('SELECT id FROM comments WHERE id = ? AND post_id = ? AND status = ? AND parent_id IS NULL',
            [$parentId, $post['id'], 'approved']);
        if (!$parent) {
            $parentId = null;
        }
    }
    $since = date('Y-m-d H:i:s', time() - COMMENT_WINDOW_MINUTES * 60);
    if ((int) q_val('SELECT COUNT(*) FROM comments WHERE ip = ? AND created_at > ?', [$ip, $since]) >= COMMENT_MAX_PER_WINDOW) {
        $errors[] = 'Você enviou muitos comentários em pouco tempo. Aguarde alguns minutos.';
    }
    if ($errors) {
        return ['ok' => false, 'errors' => $errors];
    }

    $status = 'pending';
    if (comment_spam_reason($name, $email, $body) !== null) {
        $status = 'spam';
    } elseif (setting('comments_moderation', 'all') === 'known'
        && q_val("SELECT 1 FROM comments WHERE author_email = ? AND status = 'approved' LIMIT 1", [$email])) {
        $status = 'approved';
    }

    q('INSERT INTO comments (post_id, parent_id, author_name, author_email, body, status, ip, created_at, approved_at)
       VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)', [
        $post['id'], $parentId, $name, $email, $body, $status, $ip, now(), $status === 'approved' ? now() : null,
    ]);

    return ['ok' => true, 'id' => last_id(), 'status' => $status];
}

/** Iniciais para o "avatar" (sem serviços externos, para não expor o leitor). */
function initials(string $name): string
{
    $parts = preg_split('/\s+/u', trim($name), -1, PREG_SPLIT_NO_EMPTY) ?: ['?'];
    $first = mb_substr($parts[0], 0, 1);
    $last = count($parts) > 1 ? mb_substr(end($parts), 0, 1) : '';
    return mb_strtoupper($first . $last);
}

/** Cor estável por nome, para diferenciar os avatares. */
function avatar_hue(string $name): int
{
    return crc32(mb_strtolower($name)) % 360;
}
