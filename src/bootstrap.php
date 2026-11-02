<?php
declare(strict_types=1);

define('ROOT', dirname(__DIR__));

$config = require ROOT . '/config.php';

date_default_timezone_set($config['timezone'] ?? 'UTC');
mb_internal_encoding('UTF-8');

if (!empty($config['debug'])) {
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
} else {
    ini_set('display_errors', '0');
}

session_name('cms_session');
session_set_cookie_params([
    'httponly' => true,
    'samesite' => 'Lax',
    'secure'   => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
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
require ROOT . '/src/router.php';
