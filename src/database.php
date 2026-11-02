<?php
/**
 * Conexão com o banco e funções utilitárias de consulta.
 */

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

/**
 * Aplica as migrações pendentes de src/migrations (001_..., 002_..., em ordem).
 * A versão atual do banco fica guardada em PRAGMA user_version.
 * Para mudar o banco no futuro, crie um novo arquivo com o próximo número.
 */
function migrate(PDO $pdo): void
{
    $version = (int) $pdo->query('PRAGMA user_version')->fetchColumn();
    $files = glob(ROOT . '/src/migrations/*.sql');
    sort($files);

    foreach ($files as $file) {
        $number = (int) basename($file);
        if ($number <= $version) {
            continue;
        }
        $pdo->beginTransaction();
        try {
            $pdo->exec(file_get_contents($file));
            $pdo->exec('PRAGMA user_version = ' . $number);
            $pdo->commit();
        } catch (Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
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
