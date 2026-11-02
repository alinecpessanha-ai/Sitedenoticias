<?php
/**
 * Rotas do site público.
 */

const SITE_LAYOUT = 'site/layout';

function posts_per_page(): int
{
    return max(1, min(50, (int) setting('posts_per_page', '10')));
}

/* ---------- Página inicial ---------- */
route('GET', '/', function () {
    // Destaques: notícias marcadas como destaque; se não houver, as mais recentes
    [$hero] = find_posts(['featured' => true, 'limit' => 3]);
    if (!$hero) {
        [$hero] = find_posts(['limit' => 3]);
    }
    $heroIds = array_column($hero, 'id');

    [$latest] = find_posts(['limit' => 8, 'exclude' => $heroIds]);

    $sections = [];
    foreach (all_categories() as $category) {
        [$posts] = find_posts(['category_id' => $category['id'], 'limit' => 4, 'exclude' => $heroIds]);
        if ($posts) {
            $sections[] = ['category' => $category, 'posts' => $posts];
        }
        if (count($sections) >= 6) {
            break;
        }
    }

    view('site/home', [
        'hero'     => $hero,
        'latest'   => $latest,
        'sections' => $sections,
    ], SITE_LAYOUT);
});

/* ---------- Todas as notícias ---------- */
route('GET', '/noticias', function () {
    [, $total] = find_posts(['limit' => 1]);
    $pg = paginate($total, posts_per_page(), current_page());
    [$posts] = find_posts(['limit' => $pg['per_page'], 'offset' => $pg['offset']]);

    view('site/list', [
        'title'      => 'Todas as notícias',
        'heading'    => 'Todas as notícias',
        'posts'      => $posts,
        'pagination' => $pg,
    ], SITE_LAYOUT);
});

/* ---------- Notícia ---------- */
route('GET', '/noticia/{slug}', function (string $slug) {
    $post = find_post_by_slug($slug);
    if (!$post) {
        not_found();
    }

    $preview = false;
    if (!is_public($post)) {
        // Rascunhos e agendados só podem ser vistos por quem pode editá-los
        if (!can('edit_post', $post)) {
            not_found();
        }
        $preview = true;
    } else {
        q('UPDATE posts SET views = views + 1 WHERE id = ?', [$post['id']]);
    }

    $related = [];
    if ($post['category_id']) {
        [$related] = find_posts(['category_id' => $post['category_id'], 'exclude' => [$post['id']], 'limit' => 3]);
    }

    view('site/post', [
        'title'       => $post['title'],
        'description' => $post['excerpt'] ?: excerpt_from($post['content'], 160),
        'og_image'    => $post['featured_image'] ? absolute_url('uploads/' . $post['featured_image']) : null,
        'og_type'     => 'article',
        'post'        => $post,
        'tags'        => post_tags((int) $post['id']),
        'related'     => $related,
        'preview'     => $preview,
        'canEdit'     => can('edit_post', $post),
    ], SITE_LAYOUT);
});

/* ---------- Categoria ---------- */
route('GET', '/categoria/{slug}', function (string $slug) {
    $category = q_one('SELECT * FROM categories WHERE slug = ?', [$slug]);
    if (!$category) {
        not_found();
    }
    [, $total] = find_posts(['category_id' => $category['id'], 'limit' => 1]);
    $pg = paginate($total, posts_per_page(), current_page());
    [$posts] = find_posts(['category_id' => $category['id'], 'limit' => $pg['per_page'], 'offset' => $pg['offset']]);

    view('site/list', [
        'title'          => $category['name'],
        'description'    => $category['description'],
        'heading'        => $category['name'],
        'subheading'     => $category['description'],
        'posts'          => $posts,
        'pagination'     => $pg,
        'activeCategory' => $category['id'],
    ], SITE_LAYOUT);
});

/* ---------- Tag ---------- */
route('GET', '/tag/{slug}', function (string $slug) {
    $tag = q_one('SELECT * FROM tags WHERE slug = ?', [$slug]);
    if (!$tag) {
        not_found();
    }
    [, $total] = find_posts(['tag_id' => $tag['id'], 'limit' => 1]);
    $pg = paginate($total, posts_per_page(), current_page());
    [$posts] = find_posts(['tag_id' => $tag['id'], 'limit' => $pg['per_page'], 'offset' => $pg['offset']]);

    view('site/list', [
        'title'      => 'Tag: ' . $tag['name'],
        'heading'    => '#' . $tag['name'],
        'subheading' => 'Notícias com a tag “' . $tag['name'] . '”',
        'posts'      => $posts,
        'pagination' => $pg,
    ], SITE_LAYOUT);
});

/* ---------- Autor ---------- */
route('GET', '/autor/{id}', function (string $id) {
    $author = q_one('SELECT id, name, bio FROM users WHERE id = ?', [(int) $id]);
    if (!$author) {
        not_found();
    }
    [, $total] = find_posts(['author_id' => $author['id'], 'limit' => 1]);
    $pg = paginate($total, posts_per_page(), current_page());
    [$posts] = find_posts(['author_id' => $author['id'], 'limit' => $pg['per_page'], 'offset' => $pg['offset']]);

    view('site/list', [
        'title'      => $author['name'],
        'heading'    => $author['name'],
        'subheading' => $author['bio'] ?: 'Notícias escritas por ' . $author['name'],
        'posts'      => $posts,
        'pagination' => $pg,
    ], SITE_LAYOUT);
});

/* ---------- Busca ---------- */
route('GET', '/busca', function () {
    $term = mb_substr(input('q'), 0, 100);
    $posts = [];
    $pg = null;
    if ($term !== '') {
        [, $total] = find_posts(['search' => $term, 'limit' => 1]);
        $pg = paginate($total, posts_per_page(), current_page());
        [$posts] = find_posts(['search' => $term, 'limit' => $pg['per_page'], 'offset' => $pg['offset']]);
    }

    view('site/list', [
        'title'      => $term !== '' ? 'Busca: ' . $term : 'Busca',
        'heading'    => 'Resultados da busca',
        'subheading' => $term !== ''
            ? ($pg['total'] . ' resultado(s) para “' . $term . '”')
            : 'Digite um termo para buscar.',
        'posts'      => $posts,
        'pagination' => $pg,
        'searchTerm' => $term,
        'robots'     => 'noindex',
    ], SITE_LAYOUT);
});

/* ---------- RSS ---------- */
route('GET', '/feed.xml', function () {
    [$posts] = find_posts(['limit' => 20]);
    header('Content-Type: application/rss+xml; charset=UTF-8');
    echo render('site/feed', ['posts' => $posts]);
});

/* ---------- Sitemap ---------- */
route('GET', '/sitemap.xml', function () {
    [$posts] = find_posts(['limit' => 5000]);
    header('Content-Type: application/xml; charset=UTF-8');
    echo render('site/sitemap', ['posts' => $posts, 'categories' => all_categories()]);
});

route('GET', '/robots.txt', function () {
    header('Content-Type: text/plain; charset=UTF-8');
    echo "User-agent: *\nDisallow: /admin\nSitemap: " . absolute_url('sitemap.xml') . "\n";
});
