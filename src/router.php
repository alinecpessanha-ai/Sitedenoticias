<?php
/**
 * Roteador simples: associa método + caminho (ex.: /noticia/{slug}) a uma função.
 */

$GLOBALS['routes'] = [];

function route(string $method, string $pattern, callable $handler): void
{
    $GLOBALS['routes'][] = [$method, $pattern, $handler];
}

function request_path(): string
{
    static $path = null;
    if ($path === null) {
        $path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
        $base = base_path();
        if ($base !== '' && str_starts_with($path, $base)) {
            $path = substr($path, strlen($base));
        }
        $path = '/' . trim(rawurldecode($path), '/');
    }
    return $path;
}

function dispatch(): void
{
    $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
    $path = request_path();

    foreach ($GLOBALS['routes'] as [$routeMethod, $pattern, $handler]) {
        if ($routeMethod !== $method && !($routeMethod === 'GET' && $method === 'HEAD')) {
            continue;
        }
        $regex = '#^' . preg_replace('#\{(\w+)\}#', '(?P<$1>[^/]+)', $pattern) . '$#u';
        if (preg_match($regex, $path, $matches)) {
            $params = array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY);
            $handler(...$params);
            return;
        }
    }

    not_found();
}

function not_found(): never
{
    http_response_code(404);
    view('site/404', ['title' => 'Página não encontrada'], 'site/layout');
    exit;
}
