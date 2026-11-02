<?php
/**
 * Upload de imagens para a biblioteca de mídia.
 */

const IMAGE_TYPES = [
    'image/jpeg' => 'jpg',
    'image/png'  => 'png',
    'image/gif'  => 'gif',
    'image/webp' => 'webp',
];

/**
 * Valida e salva uma imagem enviada.
 * Retorna ['ok' => true, 'id' => ..., 'path' => ...] ou ['ok' => false, 'error' => '...'].
 */
function handle_image_upload(mixed $file): array
{
    global $config;

    if (!is_array($file) || !isset($file['error']) || is_array($file['error'])) {
        return ['ok' => false, 'error' => 'Envio inválido.'];
    }

    switch ($file['error']) {
        case UPLOAD_ERR_OK:
            break;
        case UPLOAD_ERR_NO_FILE:
            return ['ok' => false, 'error' => 'Nenhum arquivo selecionado.'];
        case UPLOAD_ERR_INI_SIZE:
        case UPLOAD_ERR_FORM_SIZE:
            return ['ok' => false, 'error' => 'O arquivo é maior do que o permitido pelo servidor.'];
        default:
            return ['ok' => false, 'error' => 'Falha no envio do arquivo (código ' . (int) $file['error'] . ').'];
    }

    $maxBytes = (int) ($config['upload_max_mb'] ?? 5) * 1024 * 1024;
    if ($file['size'] > $maxBytes) {
        return ['ok' => false, 'error' => 'A imagem deve ter no máximo ' . (int) $config['upload_max_mb'] . ' MB.'];
    }

    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);
    if (!isset(IMAGE_TYPES[$mime])) {
        return ['ok' => false, 'error' => 'Formato não suportado. Use JPG, PNG, GIF ou WEBP.'];
    }

    $info = @getimagesize($file['tmp_name']);
    if ($info === false) {
        return ['ok' => false, 'error' => 'O arquivo não parece ser uma imagem válida.'];
    }

    $subdir = date('Y/m');
    $dir = ROOT . '/public/uploads/' . $subdir;
    if (!is_dir($dir) && !mkdir($dir, 0775, true)) {
        return ['ok' => false, 'error' => 'Não foi possível criar a pasta de uploads.'];
    }

    $filename = bin2hex(random_bytes(10)) . '.' . IMAGE_TYPES[$mime];
    $target = $dir . '/' . $filename;
    $moved = is_uploaded_file($file['tmp_name'])
        ? move_uploaded_file($file['tmp_name'], $target)
        : false;
    if (!$moved) {
        return ['ok' => false, 'error' => 'Não foi possível salvar o arquivo.'];
    }

    $path = $subdir . '/' . $filename;
    $originalName = mb_substr(basename((string) ($file['name'] ?? 'imagem')), 0, 200);
    $user = current_user();

    q('INSERT INTO media (path, original_name, mime, size, width, height, uploaded_by, created_at)
       VALUES (?, ?, ?, ?, ?, ?, ?, ?)', [
        $path, $originalName, $mime, (int) $file['size'], $info[0], $info[1], $user['id'] ?? null, now(),
    ]);

    return ['ok' => true, 'id' => last_id(), 'path' => $path, 'url' => upload_url($path)];
}

function delete_media(array $media): void
{
    $file = ROOT . '/public/uploads/' . $media['path'];
    $real = realpath($file);
    $uploads = realpath(ROOT . '/public/uploads');
    if ($real && $uploads && str_starts_with($real, $uploads . DIRECTORY_SEPARATOR)) {
        @unlink($real);
    }
    q('DELETE FROM media WHERE id = ?', [$media['id']]);
}
