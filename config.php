<?php
/**
 * Configurações do CMS.
 *
 * Em geral não é preciso mudar nada: o site detecta o endereço sozinho e só mostra
 * erros detalhados quando roda no seu computador (php -S).
 *
 * Para mudar algo SÓ no servidor, crie um arquivo config.local.php ao lado deste, por exemplo:
 *     <?php return ['site_url' => 'https://meuportal.com.br'];
 * Ele não vem no pacote de instalação, então nunca é sobrescrito em atualizações.
 */
$config = [
    // Caminho do banco SQLite (fica fora da pasta pública por segurança)
    'db_path' => __DIR__ . '/storage/database.sqlite',

    // Se o site rodar em uma subpasta (ex.: https://exemplo.com/portal), use '/portal'
    'base_url' => '',

    // URL completa do site, usada no RSS e no sitemap. Deixe vazio para detectar automaticamente.
    'site_url' => '',

    'timezone' => 'America/Sao_Paulo',

    // Tamanho máximo de imagens enviadas (em MB)
    'upload_max_mb' => 5,

    // Erros detalhados na tela: só no seu computador (php -S) e nos scripts de terminal.
    // Em produção os erros vão para storage/erros.log.
    'debug' => in_array(PHP_SAPI, ['cli-server', 'cli'], true),
];

if (is_file(__DIR__ . '/config.local.php')) {
    $config = array_replace($config, require __DIR__ . '/config.local.php');
}

return $config;
