<?php
/**
 * Gera as miniaturas das imagens já enviadas.
 *
 * Uso:
 *   php bin/miniaturas.php            gera só as que faltam
 *   php bin/miniaturas.php --refazer  recria todas (ex.: depois de mudar IMAGE_WIDTHS)
 */
if (PHP_SAPI !== 'cli') {
    exit("Este script deve ser executado no terminal.\n");
}

require dirname(__DIR__) . '/src/bootstrap.php';

$force = in_array('--refazer', $argv, true);
echo $force ? "Recriando todas as miniaturas...\n" : "Gerando miniaturas que faltam...\n";

$stats = regenerate_all_variants($force);
printf("Pronto: %d imagem(ns) analisada(s), %d com miniaturas novas.\n", $stats['images'], $stats['created']);
