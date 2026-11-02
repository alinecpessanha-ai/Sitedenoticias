<?php
/**
 * Funções auxiliares usadas em todo o sistema.
 */

/** Escapa texto para exibição segura no HTML. */
function e(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function base_path(): string
{
    global $config;
    return rtrim($config['base_url'] ?? '', '/');
}

function url(string $path = '/'): string
{
    return base_path() . '/' . ltrim($path, '/');
}

function absolute_url(string $path = '/'): string
{
    global $config;
    if (!empty($config['site_url'])) {
        return rtrim($config['site_url'], '/') . '/' . ltrim($path, '/');
    }
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    return $scheme . '://' . $host . url($path);
}

function asset(string $path): string
{
    $file = ROOT . '/public/assets/' . ltrim($path, '/');
    $version = is_file($file) ? '?v=' . filemtime($file) : '';
    return url('assets/' . ltrim($path, '/')) . $version;
}

function upload_url(?string $path): string
{
    return $path ? url('uploads/' . $path) : '';
}

function redirect(string $path): never
{
    header('Location: ' . url($path));
    exit;
}

/* ---------- Templates ---------- */

function render(string $template, array $data = []): string
{
    extract($data, EXTR_SKIP);
    ob_start();
    require ROOT . '/templates/' . $template . '.php';
    return (string) ob_get_clean();
}

function view(string $template, array $data = [], ?string $layout = null): void
{
    $content = render($template, $data);
    echo $layout ? render($layout, $data + ['content' => $content]) : $content;
}

/* ---------- Mensagens flash ---------- */

function flash(string $type, string $message): void
{
    $_SESSION['flash'][] = [$type, $message];
}

function get_flashes(): array
{
    $messages = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);
    return $messages;
}

/* ---------- Proteção CSRF ---------- */

function csrf_token(): string
{
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="_csrf" value="' . e(csrf_token()) . '">';
}

function verify_csrf(): void
{
    $token = $_POST['_csrf'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    if (!is_string($token) || !hash_equals(csrf_token(), $token)) {
        http_response_code(419);
        exit('Sessão expirada ou token inválido. Volte, recarregue a página e tente novamente.');
    }
}

/* ---------- Entrada ---------- */

function input(string $key, string $default = ''): string
{
    $value = $_POST[$key] ?? $_GET[$key] ?? $default;
    return is_string($value) ? trim($value) : $default;
}

function client_ip(): string
{
    return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
}

/* ---------- Texto ---------- */

function slugify(string $text): string
{
    $text = mb_strtolower($text);
    $text = strtr($text, [
        'á' => 'a', 'à' => 'a', 'â' => 'a', 'ã' => 'a', 'ä' => 'a',
        'é' => 'e', 'è' => 'e', 'ê' => 'e', 'ë' => 'e',
        'í' => 'i', 'ì' => 'i', 'î' => 'i', 'ï' => 'i',
        'ó' => 'o', 'ò' => 'o', 'ô' => 'o', 'õ' => 'o', 'ö' => 'o',
        'ú' => 'u', 'ù' => 'u', 'û' => 'u', 'ü' => 'u',
        'ç' => 'c', 'ñ' => 'n',
    ]);
    $text = preg_replace('/[^a-z0-9]+/', '-', $text);
    $text = trim((string) $text, '-');
    return $text !== '' ? mb_substr($text, 0, 120) : 'item';
}

/** Garante que o slug seja único na tabela, acrescentando -2, -3... se preciso. */
function unique_slug(string $table, string $slug, int $ignoreId = 0): string
{
    $base = $slug;
    $i = 2;
    while (q_val("SELECT id FROM {$table} WHERE slug = ? AND id != ?", [$slug, $ignoreId])) {
        $slug = $base . '-' . $i++;
    }
    return $slug;
}

function plain_text(string $html): string
{
    $text = html_entity_decode(strip_tags(str_replace('<', ' <', $html)), ENT_QUOTES | ENT_HTML5, 'UTF-8');
    return trim((string) preg_replace('/\s+/u', ' ', $text));
}

function excerpt_from(string $html, int $length = 180): string
{
    $text = plain_text($html);
    return mb_strlen($text) > $length ? rtrim(mb_substr($text, 0, $length)) . '…' : $text;
}

function reading_time(string $html): int
{
    $words = count(preg_split('/\s+/u', plain_text($html), -1, PREG_SPLIT_NO_EMPTY));
    return max(1, (int) ceil($words / 200));
}

/* ---------- Datas ---------- */

function now(): string
{
    return date('Y-m-d H:i:s');
}

function format_date(?string $datetime, bool $withTime = false): string
{
    if (!$datetime) {
        return '';
    }
    $months = ['janeiro', 'fevereiro', 'março', 'abril', 'maio', 'junho', 'julho',
        'agosto', 'setembro', 'outubro', 'novembro', 'dezembro'];
    $ts = strtotime($datetime);
    $text = date('j', $ts) . ' de ' . $months[(int) date('n', $ts) - 1] . ' de ' . date('Y', $ts);
    return $withTime ? $text . ', às ' . date('H\hi', $ts) : $text;
}

function weekday(?string $datetime = null): string
{
    $days = ['domingo', 'segunda-feira', 'terça-feira', 'quarta-feira', 'quinta-feira', 'sexta-feira', 'sábado'];
    return $days[(int) date('w', $datetime ? strtotime($datetime) : time())];
}

/** Tempo relativo para notícias recentes: "há 5 min", "há 3 h", "ontem"; depois disso, a data. */
function time_ago(?string $datetime): string
{
    if (!$datetime) {
        return '';
    }
    $diff = time() - strtotime($datetime);
    if ($diff < 60) {
        return 'agora';
    }
    if ($diff < 3600) {
        return 'há ' . floor($diff / 60) . ' min';
    }
    if ($diff < 86400) {
        return 'há ' . floor($diff / 3600) . ' h';
    }
    if ($diff < 172800) {
        return 'ontem';
    }
    return date('d/m', strtotime($datetime));
}

function short_date(?string $datetime): string
{
    return $datetime ? date('d/m/Y H:i', strtotime($datetime)) : '—';
}

/* ---------- Paginação ---------- */

function paginate(int $total, int $perPage, int $page): array
{
    $pages = max(1, (int) ceil($total / $perPage));
    $page = min(max(1, $page), $pages);
    return [
        'page'     => $page,
        'pages'    => $pages,
        'per_page' => $perPage,
        'offset'   => ($page - 1) * $perPage,
        'total'    => $total,
    ];
}

function current_page(): int
{
    return max(1, (int) ($_GET['pagina'] ?? 1));
}

function page_url(int $page): string
{
    $query = $_GET;
    $query['pagina'] = $page;
    return url(request_path()) . '?' . http_build_query($query);
}

function format_bytes(int $bytes): string
{
    if ($bytes >= 1048576) {
        return number_format($bytes / 1048576, 1, ',', '.') . ' MB';
    }
    return number_format($bytes / 1024, 0, ',', '.') . ' KB';
}
