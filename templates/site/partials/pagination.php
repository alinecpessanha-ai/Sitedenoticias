<?php if (!empty($pagination) && $pagination['pages'] > 1): $p = $pagination['page']; ?>
    <nav class="pagination" aria-label="Paginação">
        <?php if ($p > 1): ?><a href="<?= e(page_url($p - 1)) ?>">← Anteriores</a><?php endif ?>
        <span>Página <?= $p ?> de <?= $pagination['pages'] ?></span>
        <?php if ($p < $pagination['pages']): ?><a href="<?= e(page_url($p + 1)) ?>">Próximas →</a><?php endif ?>
    </nav>
<?php endif ?>
