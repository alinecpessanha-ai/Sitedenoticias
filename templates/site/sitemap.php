<?= '<?xml version="1.0" encoding="UTF-8"?>' . "\n" ?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
    <url><loc><?= e(absolute_url('/')) ?></loc><changefreq>hourly</changefreq></url>
    <?php foreach ($categories as $cat): ?>
    <url><loc><?= e(absolute_url('categoria/' . $cat['slug'])) ?></loc><changefreq>hourly</changefreq></url>
    <?php endforeach ?>
    <?php foreach ($posts as $post): ?>
    <url><loc><?= e(absolute_url('noticia/' . $post['slug'])) ?></loc><lastmod><?= date('c', strtotime($post['updated_at'])) ?></lastmod></url>
    <?php endforeach ?>
</urlset>
