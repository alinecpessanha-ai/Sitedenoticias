-- Comentários dos leitores, com moderação

CREATE TABLE IF NOT EXISTS comments (
    id           INTEGER PRIMARY KEY AUTOINCREMENT,
    post_id      INTEGER NOT NULL REFERENCES posts(id) ON DELETE CASCADE,
    parent_id    INTEGER REFERENCES comments(id) ON DELETE CASCADE,  -- resposta a outro comentário
    user_id      INTEGER REFERENCES users(id) ON DELETE SET NULL,    -- preenchido quando a redação responde
    author_name  TEXT NOT NULL,
    author_email TEXT NOT NULL,
    body         TEXT NOT NULL,
    status       TEXT NOT NULL DEFAULT 'pending' CHECK (status IN ('pending', 'approved', 'spam')),
    ip           TEXT NOT NULL DEFAULT '',
    created_at   TEXT NOT NULL,
    approved_at  TEXT
);

CREATE INDEX IF NOT EXISTS idx_comments_post   ON comments(post_id, status, id);
CREATE INDEX IF NOT EXISTS idx_comments_status ON comments(status, id);
CREATE INDEX IF NOT EXISTS idx_comments_ip     ON comments(ip, created_at);
CREATE INDEX IF NOT EXISTS idx_comments_email  ON comments(author_email, status);

-- Permite fechar os comentários de uma notícia específica
ALTER TABLE posts ADD COLUMN comments_enabled INTEGER NOT NULL DEFAULT 1;

-- Configurações padrão
INSERT OR IGNORE INTO settings (name, value) VALUES
    ('comments_enabled', '1'),
    ('comments_moderation', 'all'),
    ('comments_close_days', '0'),
    ('comments_blocklist', '');
