<?php
/**
 * Consultas de conteúdo (notícias, categorias, tags) usadas pelo site e pelo painel.
 */

const POST_SELECT = 'SELECT p.*, c.name AS category_name, c.slug AS category_slug, u.name AS author_name
    FROM posts p
    LEFT JOIN categories c ON c.id = p.category_id
    LEFT JOIN users u ON u.id = p.author_id';

const POST_STATUSES = [
    'draft'     => 'Rascunho',
    'pending'   => 'Aguardando revisão',
    'published' => 'Publicado',
];

/**
 * Busca notícias publicadas (data de publicação já passou).
 * Opções: category_id, tag_id, author_id, search, featured, exclude (array de ids),
 *         order ('recent' | 'views'), limit, offset.
 * Retorna [lista, total].
 */
function find_posts(array $opts = []): array
{
    $where = ["p.status = 'published'", 'p.published_at <= :now'];
    $params = [':now' => now()];

    if (!empty($opts['category_id'])) {
        $where[] = 'p.category_id = :cat';
        $params[':cat'] = (int) $opts['category_id'];
    }
    if (!empty($opts['tag_id'])) {
        $where[] = 'p.id IN (SELECT post_id FROM post_tags WHERE tag_id = :tag)';
        $params[':tag'] = (int) $opts['tag_id'];
    }
    if (!empty($opts['author_id'])) {
        $where[] = 'p.author_id = :author';
        $params[':author'] = (int) $opts['author_id'];
    }
    if (!empty($opts['featured'])) {
        $where[] = 'p.is_featured = 1';
    }
    if (isset($opts['search']) && $opts['search'] !== '') {
        $term = '%' . addcslashes($opts['search'], '%_\\') . '%';
        $where[] = "(p.title LIKE :s1 ESCAPE '\\' OR p.excerpt LIKE :s2 ESCAPE '\\' OR p.content LIKE :s3 ESCAPE '\\')";
        $params[':s1'] = $params[':s2'] = $params[':s3'] = $term;
    }
    foreach (array_values($opts['exclude'] ?? []) as $i => $id) {
        $where[] = "p.id != :ex{$i}";
        $params[":ex{$i}"] = (int) $id;
    }

    $order = ($opts['order'] ?? 'recent') === 'views' ? 'p.views DESC, p.published_at DESC' : 'p.published_at DESC, p.id DESC';
    $limit = max(1, (int) ($opts['limit'] ?? 10));
    $offset = max(0, (int) ($opts['offset'] ?? 0));
    $whereSql = implode(' AND ', $where);

    $total = (int) q_val("SELECT COUNT(*) FROM posts p WHERE {$whereSql}", $params);
    $posts = q_all(POST_SELECT . " WHERE {$whereSql} ORDER BY {$order} LIMIT {$limit} OFFSET {$offset}", $params);

    return [$posts, $total];
}

function find_post_by_slug(string $slug): ?array
{
    return q_one(POST_SELECT . ' WHERE p.slug = ?', [$slug]);
}

function is_public(array $post): bool
{
    return $post['status'] === 'published' && $post['published_at'] !== null && $post['published_at'] <= now();
}

function post_url(array $post): string
{
    return url('noticia/' . $post['slug']);
}

function post_tags(int $postId): array
{
    return q_all('SELECT t.* FROM tags t JOIN post_tags pt ON pt.tag_id = t.id WHERE pt.post_id = ? ORDER BY t.name', [$postId]);
}

/** Salva as tags informadas como texto separado por vírgulas. */
function sync_tags(int $postId, string $input): void
{
    q('DELETE FROM post_tags WHERE post_id = ?', [$postId]);
    $names = array_unique(array_filter(array_map(
        fn ($t) => mb_substr(trim($t), 0, 50),
        explode(',', $input)
    )));
    foreach ($names as $name) {
        $slug = slugify($name);
        $tagId = q_val('SELECT id FROM tags WHERE slug = ?', [$slug]);
        if (!$tagId) {
            q('INSERT INTO tags (name, slug) VALUES (?, ?)', [$name, $slug]);
            $tagId = last_id();
        }
        q('INSERT OR IGNORE INTO post_tags (post_id, tag_id) VALUES (?, ?)', [$postId, $tagId]);
    }
    // Remove tags que não estão mais em uso
    q('DELETE FROM tags WHERE id NOT IN (SELECT tag_id FROM post_tags)');
}

function all_categories(): array
{
    static $cache = null;
    return $cache ??= q_all('SELECT * FROM categories ORDER BY position, name');
}

function most_read(int $limit = 5): array
{
    return find_posts(['order' => 'views', 'limit' => $limit])[0];
}

/* ======================================================================
 * Páginas fixas e mensagens de contato
 * ==================================================================== */

const PAGE_TEMPLATES = [
    'default' => 'Padrão',
    'contact' => 'Contato (com formulário)',
];

const PAGE_STATUSES = [
    'draft'     => 'Rascunho',
    'published' => 'Publicada',
];

/**
 * Endereços que já são usados pelo sistema e não podem virar página
 * (as páginas ficam na raiz do site, ex.: /quem-somos).
 */
const RESERVED_SLUGS = [
    'admin', 'noticia', 'noticias', 'categoria', 'tag', 'autor', 'busca',
    'feed.xml', 'sitemap.xml', 'robots.txt', 'uploads', 'assets', 'index.php',
];

/** Gera um slug de página válido: único e fora da lista de reservados. */
function page_slug(string $source, int $ignoreId = 0): string
{
    $slug = slugify($source);
    if (in_array($slug, RESERVED_SLUGS, true)) {
        $slug .= '-pagina';
    }
    return unique_slug('pages', $slug, $ignoreId);
}

function static_page_url(array $page): string
{
    return url($page['slug']);
}

/** Páginas publicadas marcadas para aparecer no menu do rodapé. */
function menu_pages(): array
{
    static $cache = null;
    return $cache ??= q_all("SELECT id, title, slug FROM pages
        WHERE status = 'published' AND show_in_menu = 1 ORDER BY position, title");
}

function unread_messages_count(): int
{
    return (int) q_val('SELECT COUNT(*) FROM messages WHERE is_read = 0');
}
