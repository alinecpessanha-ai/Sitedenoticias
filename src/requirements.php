<?php
/**
 * Verifica se o servidor tem o que o CMS precisa, ANTES de carregar o resto do sistema.
 * Se faltar algo, mostra uma página explicando como resolver (em vez de um erro em branco).
 *
 * Atenção: este arquivo usa só sintaxe antiga de propósito, para funcionar até em
 * versões velhas do PHP e conseguir avisar que é preciso atualizar.
 */

function cms_requirement_problems($root)
{
    $problems = array();

    if (version_compare(PHP_VERSION, '8.1.0', '<')) {
        $problems[] = array(
            'A versão do PHP é ' . PHP_VERSION . ', mas o CMS precisa da 8.1 ou mais nova.',
            'No painel da hospedagem, procure "Versão do PHP" (ou "Select PHP Version") e escolha 8.3.'
        );
    }

    $extensions = array(
        'pdo_sqlite' => 'banco de dados',
        'dom'        => 'limpeza do texto das notícias',
        'mbstring'   => 'textos com acentos',
        'gd'         => 'miniaturas das imagens',
        'fileinfo'   => 'verificação dos uploads',
    );
    foreach ($extensions as $ext => $purpose) {
        if (!extension_loaded($ext)) {
            $problems[] = array(
                'Falta a extensão "' . $ext . '" do PHP (usada para ' . $purpose . ').',
                'No painel da hospedagem, em "Extensões do PHP" (ou "PHP Extensions"), ative "' . $ext . '".'
            );
        }
    }

    foreach (array('storage', 'public/uploads') as $dir) {
        $path = $root . '/' . $dir;
        if (!is_dir($path)) {
            @mkdir($path, 0775, true);
        }
        if (!is_dir($path) || !is_writable($path)) {
            $problems[] = array(
                'A pasta "' . $dir . '" não tem permissão de escrita.',
                'No gerenciador de arquivos da hospedagem, mude a permissão da pasta "' . $dir . '" para 755 (ou 775).'
            );
        }
    }

    return $problems;
}

function cms_check_requirements($root)
{
    $problems = cms_requirement_problems($root);
    if (!$problems) {
        return;
    }

    if (!headers_sent()) {
        http_response_code(500);
        header('Content-Type: text/html; charset=UTF-8');
    }
    echo '<!doctype html><html lang="pt-BR"><head><meta charset="utf-8">'
        . '<meta name="viewport" content="width=device-width, initial-scale=1"><title>Configuração necessária</title>'
        . '<style>body{font-family:system-ui,sans-serif;max-width:680px;margin:40px auto;padding:0 16px;line-height:1.5;color:#1d2327}'
        . 'li{margin-bottom:14px}small{display:block;color:#555}code{background:#f0f0f0;padding:1px 5px;border-radius:3px}</style></head><body>'
        . '<h1>Quase lá! O servidor precisa de um ajuste</h1>'
        . '<p>O site ainda não pode funcionar nesta hospedagem pelos motivos abaixo:</p><ul>';
    foreach ($problems as $problem) {
        echo '<li><strong>' . htmlspecialchars($problem[0], ENT_QUOTES, 'UTF-8') . '</strong>'
            . '<small>Como resolver: ' . htmlspecialchars($problem[1], ENT_QUOTES, 'UTF-8') . '</small></li>';
    }
    echo '</ul><p>Depois de ajustar, recarregue esta página. Se não encontrar as opções, peça ao suporte da hospedagem '
        . '— basta copiar a lista acima.</p></body></html>';
    exit;
}
