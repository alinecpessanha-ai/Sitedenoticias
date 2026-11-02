-- Páginas fixas (Quem somos, Contato, Expediente...) e mensagens do formulário de contato

CREATE TABLE IF NOT EXISTS pages (
    id           INTEGER PRIMARY KEY AUTOINCREMENT,
    title        TEXT NOT NULL,
    slug         TEXT NOT NULL UNIQUE,
    content      TEXT NOT NULL DEFAULT '',
    template     TEXT NOT NULL DEFAULT 'default' CHECK (template IN ('default', 'contact')),
    status       TEXT NOT NULL DEFAULT 'draft' CHECK (status IN ('draft', 'published')),
    show_in_menu INTEGER NOT NULL DEFAULT 1,
    position     INTEGER NOT NULL DEFAULT 0,
    author_id    INTEGER REFERENCES users(id) ON DELETE SET NULL,
    created_at   TEXT NOT NULL,
    updated_at   TEXT NOT NULL
);

CREATE TABLE IF NOT EXISTS messages (
    id         INTEGER PRIMARY KEY AUTOINCREMENT,
    name       TEXT NOT NULL,
    email      TEXT NOT NULL,
    subject    TEXT NOT NULL DEFAULT '',
    body       TEXT NOT NULL,
    ip         TEXT NOT NULL DEFAULT '',
    page_id    INTEGER REFERENCES pages(id) ON DELETE SET NULL,
    is_read    INTEGER NOT NULL DEFAULT 0,
    created_at TEXT NOT NULL
);

CREATE INDEX IF NOT EXISTS idx_messages_ip ON messages(ip, created_at);

-- Páginas iniciais, já publicadas, para você editar pelo painel
INSERT OR IGNORE INTO pages (title, slug, content, template, status, position, created_at, updated_at) VALUES
(
    'Quem somos',
    'quem-somos',
    '<p>Conte aqui a história do seu portal: quando surgiu, qual é a missão e o que os leitores podem esperar da cobertura.</p><h2>Nossa equipe</h2><p>Apresente as pessoas da redação.</p><h2>Princípios editoriais</h2><p>Explique como o portal apura, corrige e separa notícia de opinião.</p>',
    'default', 'published', 1, datetime('now', 'localtime'), datetime('now', 'localtime')
),
(
    'Contato',
    'contato',
    '<p>Tem uma sugestão de pauta, uma correção ou quer falar com a redação? Preencha o formulário abaixo e responderemos o mais rápido possível.</p>',
    'contact', 'published', 2, datetime('now', 'localtime'), datetime('now', 'localtime')
);
