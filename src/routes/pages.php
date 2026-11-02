<?php
/**
 * Páginas fixas na raiz do site (ex.: /quem-somos, /contato).
 *
 * IMPORTANTE: este arquivo deve ser carregado por último, porque /{slug}
 * captura qualquer endereço de um nível só e as rotas são testadas em ordem.
 */

const CONTACT_MAX_PER_WINDOW = 3;   // mensagens por IP...
const CONTACT_WINDOW_MINUTES = 10;  // ...dentro deste intervalo

/** Busca a página; rascunhos só aparecem para quem pode editá-los. */
function find_visible_page(string $slug): array
{
    $page = q_one('SELECT * FROM pages WHERE slug = ?', [$slug]);
    if (!$page || ($page['status'] !== 'published' && !can('manage_pages'))) {
        not_found();
    }
    return $page;
}

function render_page(array $page, array $errors = [], array $old = []): void
{
    view('site/page', [
        'title'       => $page['title'],
        'description' => excerpt_from($page['content'], 160),
        'page'        => $page,
        'preview'     => $page['status'] !== 'published',
        'errors'      => $errors,
        'old'         => $old,
    ], SITE_LAYOUT);
}

route('GET', '/{slug}', function (string $slug) {
    render_page(find_visible_page($slug));
});

/* ---------- Envio do formulário de contato ---------- */
route('POST', '/{slug}', function (string $slug) {
    $page = find_visible_page($slug);
    if ($page['template'] !== 'contact') {
        not_found();
    }
    verify_csrf();

    $old = [
        'name'    => mb_substr(input('name'), 0, 100),
        'email'   => mb_substr(input('email'), 0, 150),
        'subject' => mb_substr(input('subject'), 0, 150),
        'body'    => mb_substr(input('body'), 0, 5000),
    ];

    // Armadilha para robôs: campo invisível que pessoas não preenchem
    if (input('website') !== '') {
        flash('success', 'Mensagem enviada! Obrigado pelo contato.');
        redirect($page['slug'] . '#contato');
    }

    $errors = [];
    if ($old['name'] === '') {
        $errors[] = 'Informe o seu nome.';
    }
    if (!filter_var($old['email'], FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Informe um e-mail válido.';
    }
    if (mb_strlen($old['body']) < 10) {
        $errors[] = 'Escreva uma mensagem com pelo menos 10 caracteres.';
    }

    $ip = client_ip();
    $since = date('Y-m-d H:i:s', time() - CONTACT_WINDOW_MINUTES * 60);
    if ((int) q_val('SELECT COUNT(*) FROM messages WHERE ip = ? AND created_at > ?', [$ip, $since]) >= CONTACT_MAX_PER_WINDOW) {
        $errors[] = 'Você enviou muitas mensagens em pouco tempo. Aguarde alguns minutos e tente novamente.';
    }

    if ($errors) {
        http_response_code(422);
        render_page($page, $errors, $old);
        return;
    }

    q('INSERT INTO messages (name, email, subject, body, ip, page_id, created_at) VALUES (?, ?, ?, ?, ?, ?, ?)',
        [$old['name'], $old['email'], $old['subject'], $old['body'], $ip, $page['id'], now()]);

    notify_contact_email($old);

    flash('success', 'Mensagem enviada! Obrigado pelo contato.');
    redirect($page['slug'] . '#contato');
});

/**
 * Avisa por e-mail sobre a nova mensagem, se um e-mail estiver configurado.
 * Usa a função mail() do PHP, que depende do servidor (nem toda hospedagem envia).
 * A mensagem fica salva no painel de qualquer forma.
 */
function notify_contact_email(array $msg): void
{
    $to = setting('contact_email');
    if ($to === '' || !filter_var($to, FILTER_VALIDATE_EMAIL) || !function_exists('mail')) {
        return;
    }
    // Remove quebras de linha para impedir injeção de cabeçalhos
    $clean = fn (string $s) => trim((string) preg_replace('/[\r\n]+/', ' ', $s));
    $site = $clean(setting('site_name', 'Site'));
    $subject = '=?UTF-8?B?' . base64_encode('[' . $site . '] ' . ($clean($msg['subject']) ?: 'Nova mensagem de contato')) . '?=';
    $body = "Nome: {$clean($msg['name'])}\nE-mail: {$msg['email']}\n\n{$msg['body']}\n\n"
        . 'Veja todas as mensagens em ' . absolute_url('admin/mensagens');
    $headers = [
        'Content-Type: text/plain; charset=UTF-8',
        'Reply-To: ' . $clean($msg['email']),
    ];
    @mail($to, $subject, $body, implode("\r\n", $headers));
}
