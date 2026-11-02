<?php
/**
 * Histórico de alterações das notícias (revisões) e comparação entre versões.
 *
 * A cada vez que uma notícia é salva, guardamos uma cópia do estado dela.
 * Comparar uma versão com a anterior mostra exatamente o que aquele salvamento mudou.
 */

/** Quantas versões guardar por notícia (as mais antigas são apagadas). */
const REVISIONS_KEEP = 50;

/** Campos guardados em cada versão, com o nome exibido no painel. */
const REVISION_FIELDS = [
    'title'          => 'Título',
    'excerpt'        => 'Linha fina',
    'content'        => 'Texto',
    'featured_image' => 'Imagem destacada',
    'image_caption'  => 'Legenda da imagem',
    'category_id'    => 'Categoria',
    'tags'           => 'Tags',
    'status'         => 'Status',
    'published_at'   => 'Data de publicação',
];

/** Estado atual de uma notícia no formato de uma versão. */
function post_snapshot(int $postId): ?array
{
    $post = q_one('SELECT * FROM posts WHERE id = ?', [$postId]);
    if (!$post) {
        return null;
    }
    $snapshot = array_intersect_key($post, REVISION_FIELDS);
    $snapshot['tags'] = implode(', ', array_column(post_tags($postId), 'name'));
    return $snapshot;
}

/**
 * Guarda a versão atual da notícia no histórico.
 * Não cria versão se nada mudou desde a última (ex.: clicou em Salvar sem alterar).
 */
function save_revision(int $postId, ?int $userId, string $note = ''): bool
{
    $snapshot = post_snapshot($postId);
    if (!$snapshot) {
        return false;
    }
    $last = q_one('SELECT * FROM post_revisions WHERE post_id = ? ORDER BY id DESC LIMIT 1', [$postId]);
    if ($last && !revision_changes($last, $snapshot)) {
        return false;
    }

    $columns = array_keys(REVISION_FIELDS);
    $cols = implode(', ', $columns);
    $marks = implode(', ', array_fill(0, count($columns), '?'));
    q("INSERT INTO post_revisions (post_id, user_id, {$cols}, note, created_at) VALUES (?, ?, {$marks}, ?, ?)", [
        $postId, $userId, ...array_map(fn ($c) => $snapshot[$c], $columns), $note, now(),
    ]);

    // Mantém só as versões mais recentes
    q('DELETE FROM post_revisions WHERE post_id = ? AND id NOT IN (
           SELECT id FROM post_revisions WHERE post_id = ? ORDER BY id DESC LIMIT ' . REVISIONS_KEEP . '
       )', [$postId, $postId]);

    return true;
}

/** Lista as versões de uma notícia, da mais nova para a mais antiga, com o nome de quem salvou. */
function post_revisions(int $postId, ?int $limit = null): array
{
    $sql = 'SELECT r.*, u.name AS user_name FROM post_revisions r LEFT JOIN users u ON u.id = r.user_id
            WHERE r.post_id = ? ORDER BY r.id DESC';
    return q_all($limit ? "{$sql} LIMIT " . (int) $limit : $sql, [$postId]);
}

/** Versão imediatamente anterior a esta (ou null, se for a primeira guardada). */
function previous_revision(array $revision): ?array
{
    return q_one('SELECT r.*, u.name AS user_name FROM post_revisions r LEFT JOIN users u ON u.id = r.user_id
                  WHERE r.post_id = ? AND r.id < ? ORDER BY r.id DESC LIMIT 1', [$revision['post_id'], $revision['id']]);
}

/** Campos que diferem entre duas versões: lista de chaves de REVISION_FIELDS. */
function revision_changes(array $old, array $new): array
{
    $changed = [];
    foreach (array_keys(REVISION_FIELDS) as $field) {
        if ((string) ($old[$field] ?? '') !== (string) ($new[$field] ?? '')) {
            $changed[] = $field;
        }
    }
    return $changed;
}

/** Valor de um campo formatado para leitura no painel. */
function revision_display_value(string $field, mixed $value): string
{
    if ($value === null || $value === '') {
        return '—';
    }
    return match ($field) {
        'category_id'  => (string) (q_val('SELECT name FROM categories WHERE id = ?', [(int) $value]) ?? '(categoria excluída)'),
        'status'       => POST_STATUSES[$value] ?? (string) $value,
        'published_at' => short_date((string) $value),
        default        => (string) $value,
    };
}

/* ======================================================================
 * Comparação de textos (diff)
 * ==================================================================== */

/**
 * Compara duas listas e devolve as operações para transformar $a em $b:
 * [['=', item], ['-', item], ['+', item], ...]. Usa o algoritmo LCS
 * (maior subsequência comum), depois de descartar início e fim iguais.
 */
function diff_sequences(array $a, array $b): array
{
    $start = 0;
    while ($start < count($a) && $start < count($b) && $a[$start] === $b[$start]) {
        $start++;
    }
    $endA = count($a);
    $endB = count($b);
    while ($endA > $start && $endB > $start && $a[$endA - 1] === $b[$endB - 1]) {
        $endA--;
        $endB--;
    }

    $midA = array_slice($a, $start, $endA - $start);
    $midB = array_slice($b, $start, $endB - $start);
    $n = count($midA);
    $m = count($midB);

    // Proteção: textos gigantes totalmente diferentes viram "removeu tudo / adicionou tudo"
    if ($n * $m > 4_000_000) {
        $middle = array_merge(array_map(fn ($x) => ['-', $x], $midA), array_map(fn ($x) => ['+', $x], $midB));
    } else {
        // Tabela LCS (de trás para frente)
        $lcs = array_fill(0, $n + 1, array_fill(0, $m + 1, 0));
        for ($i = $n - 1; $i >= 0; $i--) {
            for ($j = $m - 1; $j >= 0; $j--) {
                $lcs[$i][$j] = $midA[$i] === $midB[$j]
                    ? $lcs[$i + 1][$j + 1] + 1
                    : max($lcs[$i + 1][$j], $lcs[$i][$j + 1]);
            }
        }
        $middle = [];
        $i = $j = 0;
        while ($i < $n && $j < $m) {
            if ($midA[$i] === $midB[$j]) {
                $middle[] = ['=', $midA[$i]];
                $i++;
                $j++;
            } elseif ($lcs[$i + 1][$j] >= $lcs[$i][$j + 1]) {
                $middle[] = ['-', $midA[$i++]];
            } else {
                $middle[] = ['+', $midB[$j++]];
            }
        }
        while ($i < $n) {
            $middle[] = ['-', $midA[$i++]];
        }
        while ($j < $m) {
            $middle[] = ['+', $midB[$j++]];
        }
    }

    return array_merge(
        array_map(fn ($x) => ['=', $x], array_slice($a, 0, $start)),
        $middle,
        array_map(fn ($x) => ['=', $x], array_slice($a, $endA))
    );
}

/** Diferença palavra por palavra, em HTML seguro (<del> removido, <ins> adicionado). */
function diff_words_html(string $old, string $new): string
{
    $split = fn (string $s) => preg_split('/(\s+)/u', $s, -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY) ?: [];
    $html = '';
    $buffer = ['=' => '', '-' => '', '+' => ''];
    $flush = function () use (&$html, &$buffer) {
        if ($buffer['-'] !== '') {
            $html .= '<del>' . e($buffer['-']) . '</del>';
        }
        if ($buffer['+'] !== '') {
            $html .= '<ins>' . e($buffer['+']) . '</ins>';
        }
        $buffer['-'] = $buffer['+'] = '';
    };
    foreach (diff_sequences($split($old), $split($new)) as [$op, $word]) {
        if ($op === '=') {
            $flush();
            $html .= e($word);
        } else {
            $buffer[$op] .= $word;
        }
    }
    $flush();
    return $html;
}

/** Proporção de palavras em comum entre dois trechos (0 = nada em comum, 1 = iguais). */
function text_similarity(string $a, string $b): float
{
    $wordsA = preg_split('/\s+/u', $a, -1, PREG_SPLIT_NO_EMPTY) ?: [];
    $wordsB = preg_split('/\s+/u', $b, -1, PREG_SPLIT_NO_EMPTY) ?: [];
    $longest = max(count($wordsA), count($wordsB));
    if ($longest === 0) {
        return 1.0;
    }
    $common = count(array_filter(diff_sequences($wordsA, $wordsB), fn ($op) => $op[0] === '='));
    return $common / $longest;
}

/**
 * Converte o HTML do texto em linhas legíveis (um parágrafo por linha),
 * marcando intertítulos, itens de lista, imagens e vídeos.
 */
function content_lines(string $html): array
{
    $html = preg_replace_callback('/<img\b[^>]*>/i', function ($m) {
        preg_match('/\salt="([^"]*)"/i', $m[0], $alt);
        return "\n[Imagem" . (!empty($alt[1]) ? ': ' . $alt[1] : '') . "]\n";
    }, $html);
    $html = preg_replace('/<iframe\b[^>]*>.*?<\/iframe>/is', "\n[Vídeo]\n", $html);
    $html = preg_replace('/<h([2-4])\b[^>]*>/i', "\n## ", $html);
    $html = preg_replace('/<li\b[^>]*>/i', "\n• ", $html);
    $html = preg_replace('/<blockquote\b[^>]*>/i', "\n“", $html);
    $html = preg_replace('/<br\s*\/?>|<\/(p|h[2-4]|li|blockquote|figure|figcaption|tr|div)>/i', "\n", $html);
    $text = html_entity_decode(strip_tags($html), ENT_QUOTES | ENT_HTML5, 'UTF-8');

    $lines = [];
    foreach (explode("\n", $text) as $line) {
        $line = trim((string) preg_replace('/[ \t\x{00A0}]+/u', ' ', $line));
        if ($line !== '') {
            $lines[] = $line;
        }
    }
    return $lines;
}

/**
 * Comparação do texto parágrafo por parágrafo, pronta para exibir.
 * Retorna blocos: ['type' => 'same'|'changed'|'removed'|'added'|'skip', 'html' => ..., 'count' => ...].
 * Parágrafos iguais longe das mudanças são resumidos ("12 parágrafos sem alteração").
 */
function diff_content_blocks(string $oldHtml, string $newHtml, int $context = 1): array
{
    $ops = diff_sequences(content_lines($oldHtml), content_lines($newHtml));
    $blocks = [];

    // Agrupa remoções + adições seguidas: pares viram "parágrafo alterado" com diff de palavras
    for ($i = 0, $total = count($ops); $i < $total;) {
        if ($ops[$i][0] === '=') {
            $blocks[] = ['type' => 'same', 'html' => e($ops[$i][1])];
            $i++;
            continue;
        }
        $removed = $added = [];
        while ($i < $total && $ops[$i][0] !== '=') {
            if ($ops[$i][0] === '-') {
                $removed[] = $ops[$i][1];
            } else {
                $added[] = $ops[$i][1];
            }
            $i++;
        }
        // Pares parecidos viram "alterado" (diff de palavras); os demais, removido/adicionado
        $pairs = min(count($removed), count($added));
        $leftRemoved = $leftAdded = [];
        for ($k = 0; $k < $pairs; $k++) {
            if (text_similarity($removed[$k], $added[$k]) >= 0.4) {
                $blocks[] = ['type' => 'changed', 'html' => diff_words_html($removed[$k], $added[$k])];
            } else {
                $leftRemoved[] = $removed[$k];
                $leftAdded[] = $added[$k];
            }
        }
        foreach ([...$leftRemoved, ...array_slice($removed, $pairs)] as $line) {
            $blocks[] = ['type' => 'removed', 'html' => e($line)];
        }
        foreach ([...$leftAdded, ...array_slice($added, $pairs)] as $line) {
            $blocks[] = ['type' => 'added', 'html' => e($line)];
        }
    }

    // Mantém só o contexto próximo das mudanças
    $keep = [];
    foreach ($blocks as $index => $block) {
        if ($block['type'] !== 'same') {
            for ($k = max(0, $index - $context); $k <= min(count($blocks) - 1, $index + $context); $k++) {
                $keep[$k] = true;
            }
        }
    }
    $result = [];
    $skipped = 0;
    foreach ($blocks as $index => $block) {
        if (isset($keep[$index])) {
            if ($skipped) {
                $result[] = ['type' => 'skip', 'count' => $skipped];
                $skipped = 0;
            }
            $result[] = $block;
        } else {
            $skipped++;
        }
    }
    if ($skipped) {
        $result[] = ['type' => 'skip', 'count' => $skipped];
    }
    return $result;
}
