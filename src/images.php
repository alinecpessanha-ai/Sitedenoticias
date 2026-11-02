<?php
/**
 * Miniaturas automáticas e imagens responsivas.
 *
 * Para cada imagem enviada são criadas versões menores ao lado do original:
 *   2026/11/abc123.jpg  →  abc123-400w.jpg, abc123-800w.jpg, abc123-1600w.jpg
 * O site usa essas versões com srcset, e o navegador escolhe a mais adequada
 * ao tamanho da tela (celular baixa a pequena, monitor grande baixa a maior).
 */

/** Larguras geradas (em pixels). Só são criadas versões menores que o original. */
const IMAGE_WIDTHS = [400, 800, 1600];

/** Qualidade de compressão (0–100) para JPEG e WebP. */
const IMAGE_QUALITY = 82;

/** Imagens acima disso (em megapixels) não são processadas, para não esgotar a memória. */
const IMAGE_MAX_MEGAPIXELS = 50;

/* Larguras usadas pelo navegador para escolher a versão (atributo sizes) */
const SIZES_FULL = '(max-width: 960px) 100vw, 820px';
const SIZES_HERO = '(max-width: 960px) 100vw, 760px';
const SIZES_CARD = '(max-width: 600px) 100vw, (max-width: 960px) 50vw, 280px';
const SIZES_LIST = '(max-width: 600px) 110px, 240px';

function upload_path(string $path): string
{
    return ROOT . '/public/uploads/' . $path;
}

/** Caminho (relativo a uploads/) da versão com a largura indicada. */
function variant_path(string $path, int $width): string
{
    $info = pathinfo($path);
    $dir = $info['dirname'] !== '.' ? $info['dirname'] . '/' : '';
    return $dir . $info['filename'] . '-' . $width . 'w.' . ($info['extension'] ?? 'jpg');
}

/**
 * Gera as versões menores de uma imagem.
 * Retorna as larguras criadas. GIFs são ignorados (redimensionar perderia a animação).
 */
function generate_image_variants(string $path, bool $overwrite = false): array
{
    $file = upload_path($path);
    $info = @getimagesize($file);
    if ($info === false || !in_array($info[2], [IMAGETYPE_JPEG, IMAGETYPE_PNG, IMAGETYPE_WEBP], true)) {
        return [];
    }
    if ($info[0] * $info[1] > IMAGE_MAX_MEGAPIXELS * 1_000_000) {
        return [];
    }

    $source = match ($info[2]) {
        IMAGETYPE_JPEG => @imagecreatefromjpeg($file),
        IMAGETYPE_PNG  => @imagecreatefrompng($file),
        IMAGETYPE_WEBP => @imagecreatefromwebp($file),
    };
    if (!$source) {
        return [];
    }
    if ($info[2] === IMAGETYPE_JPEG) {
        $source = apply_exif_orientation($source, $file);
    }

    $srcW = imagesx($source);
    $srcH = imagesy($source);
    $created = [];

    foreach (IMAGE_WIDTHS as $width) {
        if ($width >= $srcW) {
            continue; // nunca aumenta a imagem
        }
        $target = upload_path(variant_path($path, $width));
        if (!$overwrite && is_file($target)) {
            $created[] = $width;
            continue;
        }
        $height = max(1, (int) round($srcH * $width / $srcW));
        $resized = imagecreatetruecolor($width, $height);
        if ($info[2] !== IMAGETYPE_JPEG) {
            // Mantém a transparência de PNG e WebP
            imagealphablending($resized, false);
            imagesavealpha($resized, true);
            imagefill($resized, 0, 0, imagecolorallocatealpha($resized, 0, 0, 0, 127));
        }
        imagecopyresampled($resized, $source, 0, 0, 0, 0, $width, $height, $srcW, $srcH);

        if ($info[2] === IMAGETYPE_JPEG) {
            imageinterlace($resized, true); // JPEG progressivo: aparece aos poucos em conexões lentas
        }
        $saved = match ($info[2]) {
            IMAGETYPE_JPEG => imagejpeg($resized, $target, IMAGE_QUALITY),
            IMAGETYPE_PNG  => imagepng($resized, $target, 6),
            IMAGETYPE_WEBP => imagewebp($resized, $target, IMAGE_QUALITY),
        };
        imagedestroy($resized);
        clearstatcache(true, $target);
        if ($saved && filesize($target) >= filesize($file)) {
            // Em imagens simples (ex.: PNG de poucas cores) a "miniatura" pode sair maior: aí o original é melhor
            @unlink($target);
        } elseif ($saved) {
            $created[] = $width;
        }
    }
    imagedestroy($source);

    return $created;
}

/**
 * Fotos de celular costumam vir "deitadas" com uma marcação EXIF dizendo como girar.
 * As miniaturas não guardam o EXIF, então a rotação é aplicada nos pixels.
 */
function apply_exif_orientation(GdImage $image, string $file): GdImage
{
    if (!function_exists('exif_read_data')) {
        return $image;
    }
    $exif = @exif_read_data($file);
    $orientation = (int) ($exif['Orientation'] ?? 1);

    $rotated = match ($orientation) {
        3, 4 => imagerotate($image, 180, 0),
        5, 6 => imagerotate($image, -90, 0),
        7, 8 => imagerotate($image, 90, 0),
        default => $image,
    };
    if (in_array($orientation, [2, 4, 5, 7], true)) {
        imageflip($rotated, IMG_FLIP_HORIZONTAL);
    }
    return $rotated ?: $image;
}

/**
 * Largura e altura reais da imagem, já considerando a rotação EXIF de fotos de celular.
 * Retorna [largura, altura] ou null.
 */
function image_dimensions(string $file): ?array
{
    $info = @getimagesize($file);
    if ($info === false) {
        return null;
    }
    [$width, $height] = $info;
    if ($info[2] === IMAGETYPE_JPEG && function_exists('exif_read_data')) {
        $orientation = (int) (@exif_read_data($file)['Orientation'] ?? 1);
        if ($orientation >= 5 && $orientation <= 8) {
            [$width, $height] = [$height, $width];
        }
    }
    return [$width, $height];
}

/** Versões existentes de uma imagem: [largura => caminho relativo]. */
function image_variants(string $path): array
{
    static $cache = [];
    if (!isset($cache[$path])) {
        $cache[$path] = [];
        foreach (IMAGE_WIDTHS as $width) {
            $variant = variant_path($path, $width);
            if (is_file(upload_path($variant))) {
                $cache[$path][$width] = $variant;
            }
        }
    }
    return $cache[$path];
}

function delete_image_variants(string $path): void
{
    foreach (IMAGE_WIDTHS as $width) {
        $file = upload_path(variant_path($path, $width));
        if (is_file($file)) {
            @unlink($file);
        }
    }
}

/** URL da menor versão com pelo menos $minWidth px (ou o original). Útil para miniaturas do painel. */
function image_url(string $path, int $minWidth = 0): string
{
    foreach (image_variants($path) as $width => $variant) {
        if ($width >= $minWidth) {
            return upload_url($variant);
        }
    }
    return upload_url($path);
}

/**
 * Atributos src/srcset/sizes para uma imagem enviada pelo painel.
 * Exemplo: <img <?= image_attrs($post['featured_image'], SIZES_CARD) ?> alt="">
 */
function image_attrs(string $path, string $sizes): string
{
    $variants = image_variants($path);
    if (!$variants) {
        return 'src="' . e(upload_url($path)) . '"';
    }

    $candidates = [];
    foreach ($variants as $width => $variant) {
        $candidates[] = upload_url($variant) . ' ' . $width . 'w';
    }
    // O original só entra quando não há versão grande (fotos enormes não precisam ir para o leitor)
    if (!isset($variants[max(IMAGE_WIDTHS)])) {
        $original = image_dimensions(upload_path($path));
        if ($original && $original[0] > max(array_keys($variants))) {
            $candidates[] = upload_url($path) . ' ' . $original[0] . 'w';
        }
    }

    // src padrão: a versão média (para navegadores que ignoram srcset)
    $fallback = $variants[800] ?? end($variants);

    return 'src="' . e(upload_url($fallback)) . '" srcset="' . e(implode(', ', $candidates)) . '" sizes="' . e($sizes) . '"';
}

/**
 * Deixa responsivas as imagens dentro do texto de notícias e páginas:
 * acrescenta srcset/sizes e carregamento preguiçoso (loading="lazy").
 */
function responsive_content(string $html): string
{
    $prefix = url('uploads/');
    return (string) preg_replace_callback('/<img\b([^>]*)>/i', function ($m) use ($prefix) {
        $attrs = $m[1];
        if (!preg_match('/\ssrc="([^"]+)"/i', $attrs, $src) || !str_starts_with($src[1], $prefix)) {
            return $m[0];
        }
        $path = html_entity_decode(substr($src[1], strlen($prefix)), ENT_QUOTES, 'UTF-8');
        if (str_contains($path, '..') || preg_match('/\ssrcset=/i', $attrs)) {
            return $m[0];
        }
        $attrs = preg_replace('/\ssrc="[^"]*"/i', '', $attrs);
        $lazy = preg_match('/\sloading=/i', $attrs) ? '' : ' loading="lazy"';
        return '<img ' . image_attrs($path, SIZES_FULL) . $lazy . $attrs . '>';
    }, $html);
}

/**
 * Gera as miniaturas de todas as imagens da biblioteca (útil para imagens antigas).
 * Com $force = true, recria inclusive as que já existem (ex.: depois de mudar IMAGE_WIDTHS).
 * Retorna ['images' => total analisado, 'created' => imagens que ganharam miniaturas].
 */
function regenerate_all_variants(bool $force = false): array
{
    @set_time_limit(0);
    $stats = ['images' => 0, 'created' => 0];
    foreach (q_all('SELECT path FROM media ORDER BY id') as $media) {
        $stats['images']++;
        $before = count(image_variants($media['path']));
        $after = count(generate_image_variants($media['path'], $force));
        if ($force ? $after > 0 : $after > $before) {
            $stats['created']++;
        }
    }
    return $stats;
}
