<?php
/**
 * Ponto de entrada único do site (front controller).
 * Todas as requisições passam por aqui e são encaminhadas pelo roteador.
 */

// No servidor embutido do PHP, arquivos estáticos (CSS, JS, imagens) são servidos diretamente
if (PHP_SAPI === 'cli-server') {
    $file = __DIR__ . parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    if (is_file($file)) {
        return false;
    }
}

require dirname(__DIR__) . '/src/bootstrap.php';
require ROOT . '/src/routes/site.php';
require ROOT . '/src/routes/admin.php';
require ROOT . '/src/routes/pages.php'; // por último: captura /{slug}

dispatch();
