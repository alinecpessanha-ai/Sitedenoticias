<?php /** Editor visual. Variáveis: $name (campo), $value (HTML), $label. */ ?>
<div class="editor" data-editor data-upload-url="<?= url('admin/midia/enviar') ?>">
    <div class="editor-toolbar" role="toolbar" aria-label="Formatação">
        <button type="button" data-cmd="bold" title="Negrito"><b>N</b></button>
        <button type="button" data-cmd="italic" title="Itálico"><i>I</i></button>
        <button type="button" data-cmd="underline" title="Sublinhado"><u>S</u></button>
        <span class="sep"></span>
        <button type="button" data-cmd="formatBlock" data-value="p" title="Parágrafo">¶</button>
        <button type="button" data-cmd="formatBlock" data-value="h2" title="Subtítulo">H2</button>
        <button type="button" data-cmd="formatBlock" data-value="h3" title="Intertítulo">H3</button>
        <button type="button" data-cmd="formatBlock" data-value="blockquote" title="Citação">“ ”</button>
        <span class="sep"></span>
        <button type="button" data-cmd="insertUnorderedList" title="Lista">• Lista</button>
        <button type="button" data-cmd="insertOrderedList" title="Lista numerada">1. Lista</button>
        <span class="sep"></span>
        <button type="button" data-cmd="link" title="Inserir link">Link</button>
        <button type="button" data-cmd="unlink" title="Remover link">Sem link</button>
        <button type="button" data-cmd="image" title="Inserir imagem">Imagem</button>
        <button type="button" data-cmd="video" title="Inserir vídeo do YouTube ou Vimeo">Vídeo</button>
        <span class="sep"></span>
        <button type="button" data-cmd="removeFormat" title="Limpar formatação">Limpar</button>
        <button type="button" data-cmd="source" title="Editar HTML">&lt;/&gt;</button>
    </div>
    <div class="editor-area post-content" contenteditable="true" aria-label="<?= e($label ?? 'Conteúdo') ?>"></div>
    <textarea name="<?= e($name ?? 'content') ?>" class="editor-source" hidden><?= e($value ?? '') ?></textarea>
    <input type="file" accept="image/jpeg,image/png,image/gif,image/webp" class="editor-file" hidden>
</div>
