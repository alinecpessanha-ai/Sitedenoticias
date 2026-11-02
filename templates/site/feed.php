<?= '<?xml version="1.0" encoding="UTF-8"?>' . "\n" ?>
<rss version="2.0" xmlns:atom="http://www.w3.org/2005/Atom">
<channel>
    <title><?= e(setting('site_name', 'Meu Portal')) ?></title>
    <link><?= e(absolute_url('/')) ?></link>
    <description><?= e(setting('site_description')) ?></description>
    <language>pt-BR</language>
    <atom:link href="<?= e(absolute_url('feed.xml')) ?>" rel="self" type="application/rss+xml"/>
    <?php foreach ($posts as $post): ?>
    <item>
        <title><?= e($post['title']) ?></title>
        <link><?= e(absolute_url('noticia/' . $post['slug'])) ?></link>
        <guid isPermaLink="true"><?= e(absolute_url('noticia/' . $post['slug'])) ?></guid>
        <pubDate><?= date(DATE_RSS, strtotime($post['published_at'])) ?></pubDate>
        <?php if ($post['category_name']): ?><category><?= e($post['category_name']) ?></category><?php endif ?>
        <description><?= e($post['excerpt'] ?: excerpt_from($post['content'], 300)) ?></description>
    </item>
    <?php endforeach ?>
</channel>
</rss>
