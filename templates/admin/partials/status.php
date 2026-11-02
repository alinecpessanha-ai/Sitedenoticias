<?php
if ($post['status'] === 'published' && $post['published_at'] > now()) {
    $key = 'scheduled';
    $label = 'Agendado';
} else {
    $key = $post['status'];
    $label = POST_STATUSES[$key];
}
?>
<span class="status status-<?= e($key) ?>"><?= e($label) ?></span>
