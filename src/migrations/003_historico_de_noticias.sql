-- Histórico de alterações das notícias: uma "foto" da notícia a cada vez que ela é salva

CREATE TABLE IF NOT EXISTS post_revisions (
    id             INTEGER PRIMARY KEY AUTOINCREMENT,
    post_id        INTEGER NOT NULL REFERENCES posts(id) ON DELETE CASCADE,
    user_id        INTEGER REFERENCES users(id) ON DELETE SET NULL,
    title          TEXT NOT NULL,
    excerpt        TEXT NOT NULL DEFAULT '',
    content        TEXT NOT NULL DEFAULT '',
    featured_image TEXT,
    image_caption  TEXT NOT NULL DEFAULT '',
    category_id    INTEGER,          -- sem chave estrangeira: a categoria pode ser excluída depois
    tags           TEXT NOT NULL DEFAULT '',
    status         TEXT NOT NULL,
    published_at   TEXT,
    note           TEXT NOT NULL DEFAULT '',
    created_at     TEXT NOT NULL
);

CREATE INDEX IF NOT EXISTS idx_post_revisions_post ON post_revisions(post_id, id);

-- Ponto de partida: a versão atual de cada notícia que já existe
INSERT INTO post_revisions (post_id, user_id, title, excerpt, content, featured_image, image_caption,
                            category_id, tags, status, published_at, note, created_at)
SELECT p.id, p.author_id, p.title, p.excerpt, p.content, p.featured_image, p.image_caption, p.category_id,
       COALESCE((SELECT group_concat(name, ', ') FROM (
           SELECT t.name FROM tags t JOIN post_tags pt ON pt.tag_id = t.id WHERE pt.post_id = p.id ORDER BY t.name
       )), ''),
       p.status, p.published_at, 'Versão existente quando o histórico foi ativado', p.updated_at
FROM posts p;
