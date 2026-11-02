<?php
declare(strict_types=1);

define('ROOT', dirname(__DIR__));

$config = require ROOT . '/config.php';

date_default_timezone_set($config['timezone'] ?? 'UTC');
mb_internal_encoding('UTF-8');

error_reporting(E_ALL);
if (!empty($config['debug'])) {
    ini_set('display_errors', '1');
} else {
    // Produção: o visitante não vê detalhes técnicos; eles ficam registrados no log
    ini_set('display_errors', '0');
    ini_set('log_errors', '1');
    ini_set('error_log', ROOT . '/storage/erros.log');
}

/** O acesso é por HTTPS? (considera também proxies/CDN, comuns em hospedagens) */
function is_https(): bool
{
    return (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || ($_SERVER['SERVER_PORT'] ?? '') === '443'
        || strtolower($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https';
}

session_name('cms_session');
session_set_cookie_params([
    'httponly' => true,
    'samesite' => 'Lax',
    'secure'   => is_https(),
    'path'     => '/',
]);
if (PHP_SAPI !== 'cli') { // scripts de terminal (bin/) não usam sessão
    session_start();
}

require ROOT . '/src/helpers.php';
require ROOT . '/src/database.php';
require ROOT . '/src/auth.php';
require ROOT . '/src/sanitizer.php';
require ROOT . '/src/images.php';
require ROOT . '/src/uploads.php';
require ROOT . '/src/content.php';
require ROOT . '/src/revisions.php';
require ROOT . '/src/comments.php';
require ROOT . '/src/router.php';
