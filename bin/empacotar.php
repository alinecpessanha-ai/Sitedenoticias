<?php
/**
 * Gera um .zip pronto para enviar à hospedagem (ex.: HostGator, Hostinger, Locaweb).
 *
 * Uso:
 *   php bin/empacotar.php
 *
 * O pacote NÃO inclui o banco de dados (storage/database.sqlite), as imagens enviadas
 * (public/uploads) nem o config.local.php. Por isso, extrair uma versão nova por cima
 * de um site já instalado atualiza o sistema sem apagar notícias, usuários ou imagens.
 */
if (PHP_SAPI !== 'cli') {
    exit("Este script deve ser executado no terminal.\n");
}
if (!class_exists('ZipArchive')) {
    exit("A extensão zip do PHP não está instalada. Instale-a ou compacte a pasta manualmente.\n");
}

$root = dirname(__DIR__);

/** Arquivos e pastas da raiz que entram no pacote. */
$include = ['.htaccess', 'config.php', 'README.md', 'INSTALACAO.md', 'bin', 'public', 'src', 'templates', 'storage'];

/** Nunca entram (caminhos relativos à raiz; * vale para qualquer trecho). */
$exclude = [
    'storage/*',             // banco, logs e sessões ficam no servidor
    'public/uploads/*',      // imagens enviadas ficam no servidor
    'config.local.php',
];
/** Exceções às exclusões acima (arquivos de proteção das pastas). */
$keep = ['storage/.htaccess', 'storage/.gitkeep', 'public/uploads/.htaccess'];

function excluded(string $path, array $exclude, array $keep): bool
{
    if (in_array($path, $keep, true)) {
        return false;
    }
    foreach ($exclude as $pattern) {
        if (fnmatch($pattern, $path)) {
            return true;
        }
    }
    // Lixo de sistema operacional/editores
    return (bool) preg_match('#(^|/)(\.DS_Store|Thumbs\.db|\.idea|\.vscode)(/|$)#', $path);
}

if (!is_dir($root . '/dist')) {
    mkdir($root . '/dist', 0775, true);
}
$target = $root . '/dist/sitedenoticias-' . date('Y-m-d-His') . '.zip';

$zip = new ZipArchive();
if ($zip->open($target, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
    exit("Não foi possível criar {$target}\n");
}

$count = 0;
foreach ($include as $item) {
    $full = $root . '/' . $item;
    if (is_file($full)) {
        $zip->addFile($full, $item);
        $count++;
        continue;
    }
    if (!is_dir($full)) {
        continue;
    }
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($full, FilesystemIterator::SKIP_DOTS),
        RecursiveIteratorIterator::SELF_FIRST
    );
    foreach ($iterator as $file) {
        $relative = str_replace('\\', '/', substr($file->getPathname(), strlen($root) + 1));
        if (excluded($relative, $exclude, $keep)) {
            continue;
        }
        if ($file->isDir()) {
            $zip->addEmptyDir($relative);
        } else {
            $zip->addFile($file->getPathname(), $relative);
            $count++;
        }
    }
}
// Pastas que precisam existir no servidor, mesmo vazias
$zip->addEmptyDir('storage');
$zip->addEmptyDir('public/uploads');
$zip->close();

printf("Pacote criado: %s\n%d arquivos, %s KB.\n", substr($target, strlen($root) + 1), $count, number_format(filesize($target) / 1024, 0, ',', '.'));
echo "Envie este .zip para a hospedagem e siga o INSTALACAO.md.\n";
