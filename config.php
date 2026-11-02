<?php
/**
 * Configurações do CMS.
 * Ajuste estes valores conforme o seu ambiente (desenvolvimento ou produção).
 */
return [
    // Caminho do banco SQLite (fica fora da pasta pública por segurança)
    'db_path' => __DIR__ . '/storage/database.sqlite',

    // Se o site rodar em uma subpasta (ex.: https://exemplo.com/portal), use '/portal'
    'base_url' => '',

    // URL completa do site, usada no RSS e no sitemap. Deixe vazio para detectar automaticamente.
    'site_url' => '',

    'timezone' => 'America/Sao_Paulo',

    // Tamanho máximo de imagens enviadas (em MB)
    'upload_max_mb' => 5,

    // Mostra erros detalhados na tela. Use false em produção!
    'debug' => true,
];
