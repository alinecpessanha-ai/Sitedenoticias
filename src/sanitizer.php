<?php
/**
 * Limpa o HTML vindo do editor, mantendo só tags e atributos seguros.
 * Isso impede que alguém injete scripts (XSS) no conteúdo das notícias.
 */

const HTML_ALLOWED = [
    'p' => [], 'br' => [], 'hr' => [],
    'strong' => [], 'b' => [], 'em' => [], 'i' => [], 'u' => [], 's' => [],
    'sub' => [], 'sup' => [], 'code' => [], 'pre' => [],
    'h2' => [], 'h3' => [], 'h4' => [],
    'blockquote' => ['cite'],
    'ul' => [], 'ol' => [], 'li' => [],
    'a' => ['href', 'title', 'target', 'rel'],
    'img' => ['src', 'alt', 'title', 'width', 'height'],
    'figure' => [], 'figcaption' => [],
    'table' => [], 'thead' => [], 'tbody' => [], 'tr' => [], 'th' => ['colspan', 'rowspan'], 'td' => ['colspan', 'rowspan'],
    'iframe' => ['src', 'width', 'height', 'title', 'allow', 'allowfullscreen', 'frameborder'],
];

/** Tags removidas por completo, junto com o conteúdo. */
const HTML_DROP = ['script', 'style', 'object', 'embed', 'form', 'input', 'button', 'select', 'textarea', 'svg', 'math', 'noscript', 'template', 'head', 'meta', 'link'];

/** Únicas origens aceitas para vídeos incorporados. */
const IFRAME_ALLOWED_PREFIXES = [
    'https://www.youtube.com/embed/',
    'https://www.youtube-nocookie.com/embed/',
    'https://player.vimeo.com/video/',
];

const HTML_BLOCK_TAGS = ['p', 'div', 'h2', 'h3', 'h4', 'ul', 'ol', 'blockquote', 'figure', 'table', 'pre', 'hr'];

function sanitize_html(string $html): string
{
    if (trim($html) === '') {
        return '';
    }

    $doc = new DOMDocument('1.0', 'UTF-8');
    $previous = libxml_use_internal_errors(true);
    $doc->loadHTML(
        '<?xml encoding="UTF-8"><div id="__cms_root">' . $html . '</div>',
        LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD | LIBXML_NONET
    );
    libxml_clear_errors();
    libxml_use_internal_errors($previous);

    $root = (new DOMXPath($doc))->query('//div[@id="__cms_root"]')->item(0);
    if (!$root) {
        return '';
    }

    sanitize_children($root);
    wrap_loose_inline($root);

    $output = '';
    foreach ($root->childNodes as $child) {
        $output .= $doc->saveHTML($child);
    }
    return trim($output);
}

function sanitize_children(DOMNode $parent): void
{
    foreach (iterator_to_array($parent->childNodes) as $node) {
        if ($node instanceof DOMText) {
            continue;
        }
        if (!$node instanceof DOMElement) {
            // Comentários, instruções de processamento etc.
            $parent->removeChild($node);
            continue;
        }

        $tag = strtolower($node->tagName);

        if (in_array($tag, HTML_DROP, true)) {
            $parent->removeChild($node);
            continue;
        }

        // <div> simples (gerado por alguns navegadores) vira parágrafo
        if ($tag === 'div' && !has_block_child($node)) {
            $node = rename_element($node, 'p');
            $tag = 'p';
        }

        if (!isset(HTML_ALLOWED[$tag])) {
            // Tag não permitida: mantém o conteúdo, remove a tag
            sanitize_children($node);
            while ($node->firstChild) {
                $parent->insertBefore($node->firstChild, $node);
            }
            $parent->removeChild($node);
            continue;
        }

        sanitize_attributes($node, HTML_ALLOWED[$tag]);

        if ($tag === 'iframe' && !is_allowed_iframe($node->getAttribute('src'))) {
            $parent->removeChild($node);
            continue;
        }
        if ($tag === 'img' && $node->getAttribute('src') === '') {
            $parent->removeChild($node);
            continue;
        }
        if ($tag === 'a' && $node->getAttribute('target') === '_blank') {
            $node->setAttribute('rel', 'noopener noreferrer');
        }

        sanitize_children($node);
    }
}

function sanitize_attributes(DOMElement $node, array $allowed): void
{
    foreach (iterator_to_array($node->attributes) as $attr) {
        $name = strtolower($attr->name);
        if (!in_array($name, $allowed, true)) {
            $node->removeAttribute($attr->name);
            continue;
        }
        if (in_array($name, ['href', 'src', 'cite'], true)) {
            $safe = safe_url($attr->value);
            if ($safe === null) {
                $node->removeAttribute($attr->name);
            } else {
                $node->setAttribute($attr->name, $safe);
            }
        }
        if ($name === 'target' && $attr->value !== '_blank') {
            $node->removeAttribute($attr->name);
        }
    }
}

/** Aceita apenas http(s), mailto, âncoras e caminhos relativos. Bloqueia javascript:, data: etc. */
function safe_url(string $url): ?string
{
    $url = trim((string) preg_replace('/[\x00-\x20]+/', '', $url));
    if ($url === '') {
        return null;
    }
    if (preg_match('#^(https?:|mailto:)#i', $url) || $url[0] === '/' || $url[0] === '#') {
        return $url;
    }
    if (!preg_match('#^[a-z][a-z0-9+.-]*:#i', $url)) {
        return $url; // relativo, ex.: "pagina.html"
    }
    return null;
}

function is_allowed_iframe(string $src): bool
{
    foreach (IFRAME_ALLOWED_PREFIXES as $prefix) {
        if (str_starts_with($src, $prefix)) {
            return true;
        }
    }
    return false;
}

/** Envolve textos e elementos inline soltos no nível principal em <p>. */
function wrap_loose_inline(DOMElement $root): void
{
    $doc = $root->ownerDocument;
    $paragraph = null;
    foreach (iterator_to_array($root->childNodes) as $node) {
        $isBlock = $node instanceof DOMElement && in_array(strtolower($node->tagName), [...HTML_BLOCK_TAGS, 'iframe'], true);
        if ($isBlock) {
            $paragraph = null;
            continue;
        }
        if ($paragraph === null) {
            if ($node instanceof DOMText && trim($node->textContent) === '') {
                continue;
            }
            $paragraph = $doc->createElement('p');
            $root->insertBefore($paragraph, $node);
        }
        $paragraph->appendChild($node);
    }
}

function has_block_child(DOMElement $node): bool
{
    foreach ($node->childNodes as $child) {
        if ($child instanceof DOMElement && in_array(strtolower($child->tagName), HTML_BLOCK_TAGS, true)) {
            return true;
        }
    }
    return false;
}

function rename_element(DOMElement $node, string $newTag): DOMElement
{
    $new = $node->ownerDocument->createElement($newTag);
    while ($node->firstChild) {
        $new->appendChild($node->firstChild);
    }
    $node->parentNode->replaceChild($new, $node);
    return $new;
}
