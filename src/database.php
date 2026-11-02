<?php
/**
 * Conexão com o banco e funções utilitárias de consulta.
 */

const SCHEMA_VERSION = 1;

function db(): PDO
{
    static $pdo = null;
    if ($pdo !== null) {
        return $pdo;
    }

    global $config;
    $path = $config['db_path'];
    if (!is_dir(dirname($path))) {
        mkdir(dirname($path), 0775, true);
    }

    $pdo = new PDO('sqlite:' . $path, null, null, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
    $pdo->exec('PRAGMA foreign_keys = ON');
    $pdo->exec('PRAGMA journal_mode = WAL');

    migrate($pdo);

    return $pdo;
}

/** Cria/atualiza as tabelas conforme a versão do esquema. */
function migrate(PDO $pdo): void
{
    $version = (int) $pdo->query('PRAGMA user_version')->fetchColumn();
    if ($version < 1) {
        $pdo->exec(file_get_contents(ROOT . '/src/schema.sql'));
    }
    // Futuras migrações: if ($version < 2) { ... }
    if ($version < SCHEMA_VERSION) {
        $pdo->exec('PRAGMA user_version = ' . SCHEMA_VERSION);
    }
}

function q(string $sql, array $params = []): PDOStatement
{
    $stmt = db()->prepare($sql);
    $stmt->execute($params);
    return $stmt;
}

function q_one(string $sql, array $params = []): ?array
{
    $row = q($sql, $params)->fetch();
    return $row ?: null;
}

function q_all(string $sql, array $params = []): array
{
    return q($sql, $params)->fetchAll();
}

function q_val(string $sql, array $params = []): mixed
{
    $value = q($sql, $params)->fetchColumn();
    return $value === false ? null : $value;
}

function last_id(): int
{
    return (int) db()->lastInsertId();
}

/* ---------- Configurações do site (tabela settings) ---------- */

function setting(string $name, string $default = ''): string
{
    static $cache = null;
    if ($cache === null) {
        $cache = q('SELECT name, value FROM settings')->fetchAll(PDO::FETCH_KEY_PAIR);
    }
    return isset($cache[$name]) && $cache[$name] !== '' ? $cache[$name] : $default;
}

function save_setting(string $name, string $value): void
{
    q('INSERT INTO settings (name, value) VALUES (?, ?)
       ON CONFLICT(name) DO UPDATE SET value = excluded.value', [$name, $value]);
}
