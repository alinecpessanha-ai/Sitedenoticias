<?php
/**
 * Conteúdo de demonstração: preenche o site com editorias, notícias fictícias,
 * imagens geradas e comentários, para ver como a home fica com bastante conteúdo.
 *
 * Uso:
 *   php bin/demo.php            cria o conteúdo de demonstração
 *   php bin/demo.php --remover  apaga tudo o que foi criado por este script
 *
 * Tudo o que é criado fica registrado, então a remoção não toca no seu conteúdo real.
 * As notícias também recebem a tag "demonstração".
 */
if (PHP_SAPI !== 'cli') {
    exit("Este script deve ser executado no terminal.\n");
}

require dirname(__DIR__) . '/src/bootstrap.php';

if (!has_users()) {
    exit("Faça a instalação primeiro (acesse /admin no navegador) e depois rode este script.\n");
}

$registry = json_decode(setting('demo_content', '{}'), true) ?: [];

/* ---------------------------------------------------------------------- */
if (in_array('--remover', $argv, true)) {
    if (!$registry) {
        exit("Não há conteúdo de demonstração para remover.\n");
    }
    $marks = fn (array $ids) => implode(',', array_fill(0, max(1, count($ids)), '?'));
    $posts = $registry['posts'] ?? [];
    if ($posts) {
        q('DELETE FROM posts WHERE id IN (' . $marks($posts) . ')', $posts);
    }
    foreach (q_all('SELECT * FROM media WHERE id IN (' . $marks($registry['media'] ?? []) . ')', $registry['media'] ?? []) as $media) {
        delete_media($media);
    }
    // Só apaga editorias criadas pela demonstração que ficaram vazias
    $removedCats = 0;
    foreach ($registry['categories'] ?? [] as $catId) {
        if (!q_val('SELECT 1 FROM posts WHERE category_id = ?', [$catId])) {
            q('DELETE FROM categories WHERE id = ?', [$catId]);
            $removedCats++;
        }
    }
    q('DELETE FROM tags WHERE id NOT IN (SELECT tag_id FROM post_tags)');
    save_setting('demo_content', '');
    printf("Removidos: %d notícias, %d imagens e %d editorias de demonstração.\n",
        count($posts), count($registry['media'] ?? []), $removedCats);
    exit;
}

if ($registry) {
    exit("O conteúdo de demonstração já existe. Para recriar, rode antes: php bin/demo.php --remover\n");
}

/* ---------------------------------------------------------------------- */

$authorId = (int) q_val("SELECT id FROM users WHERE role = 'admin' ORDER BY id LIMIT 1");
$registry = ['posts' => [], 'media' => [], 'categories' => []];

/** Cria uma imagem 1600x900 com degradê e formas, salva na biblioteca e gera as miniaturas. */
function demo_image(array $rgb, int $seed, int $authorId): array
{
    mt_srand($seed);
    $w = 1600;
    $h = 900;
    $img = imagecreatetruecolor($w, $h);
    [$r, $g, $b] = $rgb;
    for ($y = 0; $y < $h; $y++) {
        $t = $y / $h;
        $color = imagecolorallocate($img, (int) ($r * (1 - $t * .55)), (int) ($g * (1 - $t * .55)), (int) ($b * (1 - $t * .35) + 40 * $t));
        imageline($img, 0, $y, $w, $y, $color);
    }
    imagealphablending($img, true);
    for ($i = 0; $i < 7; $i++) {
        $shade = imagecolorallocatealpha($img, 255, 255, 255, mt_rand(95, 118));
        $size = mt_rand(200, 700);
        imagefilledellipse($img, mt_rand(0, $w), mt_rand(0, $h), $size, $size, $shade);
    }
    for ($i = 0; $i < 5; $i++) {
        $dark = imagecolorallocatealpha($img, 0, 0, 0, mt_rand(100, 120));
        $x = mt_rand(0, $w);
        imagefilledrectangle($img, $x, mt_rand(300, 700), $x + mt_rand(60, 220), $h, $dark);
    }

    $subdir = date('Y/m');
    $dir = ROOT . '/public/uploads/' . $subdir;
    if (!is_dir($dir)) {
        mkdir($dir, 0775, true);
    }
    $path = $subdir . '/demo-' . bin2hex(random_bytes(6)) . '.jpg';
    imagejpeg($img, upload_path($path), 85);
    imagedestroy($img);

    q('INSERT INTO media (path, original_name, mime, size, width, height, uploaded_by, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?)',
        [$path, 'demonstracao.jpg', 'image/jpeg', filesize(upload_path($path)), $w, $h, $authorId, now()]);
    $id = last_id();
    generate_image_variants($path);
    return [$id, $path];
}

$catalog = [
    'Política' => [[180, 40, 50], [
        ['Câmara aprova plano de mobilidade urbana para os próximos dez anos', 'Projeto prevê novas ciclovias, corredores de ônibus e integração tarifária', ['mobilidade', 'câmara']],
        ['Prefeitura anuncia reforma administrativa e corte de secretarias', 'Mudança reduz de 18 para 12 o número de pastas a partir de janeiro', ['prefeitura']],
        ['Audiência pública discute revisão do plano diretor', 'Moradores de bairros da zona norte pedem mais áreas verdes', ['plano diretor', 'participação']],
        ['Orçamento participativo abre votação on-line nesta semana', 'Cada bairro pode escolher até três obras prioritárias', ['orçamento', 'participação']],
    ]],
    'Economia' => [[30, 110, 80], [
        ['Feira de empreendedorismo reúne 200 expositores no centro', 'Evento oferece oficinas gratuitas de gestão e crédito para pequenos negócios', ['empreendedorismo']],
        ['Preço da cesta básica recua pelo terceiro mês seguido', 'Queda foi puxada por hortaliças e feijão, segundo levantamento', ['custo de vida']],
        ['Novo polo tecnológico deve gerar mil empregos até 2028', 'Primeiras empresas começam a operar no segundo semestre', ['emprego', 'tecnologia']],
        ['Comércio projeta alta de 6% nas vendas de fim de ano', 'Lojistas apostam em promoções antecipadas para atrair consumidores', ['comércio']],
    ]],
    'Cidades' => [[40, 90, 170], [
        ['Nova linha de ônibus liga zona sul ao hospital regional', 'Itinerário reduz em até 40 minutos o tempo de viagem', ['transporte', 'saúde']],
        ['Parque da orla ganha iluminação de LED e academia ao ar livre', 'Obra foi concluída dois meses antes do previsto', ['parques', 'obras']],
        ['Campanha de vacinação vai até as escolas a partir de segunda', 'Equipes vão atualizar a caderneta de crianças e adolescentes', ['saúde', 'educação']],
        ['Chuva forte provoca alagamentos e interdita três avenidas', 'Defesa Civil orienta moradores de áreas de risco', ['clima', 'defesa civil']],
    ]],
    'Esportes' => [[230, 120, 20], [
        ['Time da casa vence clássico e assume a liderança do estadual', 'Gol da vitória saiu nos acréscimos, de cabeça', ['futebol']],
        ['Corrida de rua reúne 5 mil atletas na manhã de domingo', 'Prova teve percursos de 5 e 10 quilômetros pela orla', ['corrida']],
        ['Escola municipal leva ouro nos jogos estudantis', 'Equipe de vôlei feminino venceu as cinco partidas do torneio', ['vôlei', 'educação']],
        ['Ginásio poliesportivo reabre após dois anos de reforma', 'Espaço recebe aulas gratuitas de basquete, judô e ginástica', ['obras']],
    ]],
    'Cultura' => [[120, 60, 160], [
        ['Festival de cinema exibe 40 filmes gratuitos em praças', 'Programação inclui sessões ao ar livre e debates com diretores', ['cinema', 'festival']],
        ['Biblioteca pública amplia horário e passa a abrir aos sábados', 'Acervo ganhou 3 mil novos títulos neste ano', ['leitura']],
        ['Museu da cidade inaugura exposição sobre a história do porto', 'Mostra reúne fotografias inéditas do início do século passado', ['museu', 'história']],
        ['Escola de samba apresenta enredo sobre os 200 anos do bairro', 'Ensaios técnicos começam no próximo mês', ['carnaval']],
    ]],
    'Tecnologia' => [[20, 140, 150], [
        ['Wi-Fi gratuito chega a 50 praças e terminais de ônibus', 'Conexão tem limite de uma hora por acesso', ['internet']],
        ['Estudantes criam aplicativo para mapear buracos nas ruas', 'Projeto venceu feira de ciências e será testado pela prefeitura', ['inovação', 'educação']],
        ['Curso gratuito de programação abre 500 vagas para jovens', 'Inscrições vão até o fim do mês pela internet', ['educação', 'emprego']],
        ['Hospital passa a usar prontuário eletrônico em todas as alas', 'Sistema deve reduzir filas e erros de medicação', ['saúde']],
    ]],
];

$paragraphs = [
    'A medida foi anunciada nesta manhã e, segundo os responsáveis, deve beneficiar milhares de moradores nos próximos meses. O cronograma completo será divulgado no site oficial.',
    'De acordo com dados preliminares, a expectativa é que os primeiros resultados apareçam já no início do próximo ano. Especialistas ouvidos pela reportagem consideram a iniciativa positiva, mas cobram transparência na execução.',
    'Moradores da região acompanharam o anúncio com expectativa. “Esperamos que dessa vez saia do papel”, disse uma comerciante que vive no bairro há mais de vinte anos.',
    'O investimento total previsto é de R$ 12 milhões, com recursos próprios e de convênios. Parte do valor ainda depende de aprovação em outras instâncias.',
    'A reportagem procurou os órgãos envolvidos, que informaram que novas informações serão divulgadas ao longo da semana.',
];

$categoryIds = [];
foreach ($catalog as $name => [$rgb]) {
    $slug = slugify($name);
    $id = q_val('SELECT id FROM categories WHERE slug = ?', [$slug]);
    if (!$id) {
        q('INSERT INTO categories (name, slug, description, position, created_at) VALUES (?, ?, ?, ?, ?)',
            [$name, $slug, 'Notícias de ' . mb_strtolower($name), count($categoryIds) + 1, now()]);
        $id = last_id();
        $registry['categories'][] = $id;
    }
    $categoryIds[$name] = (int) $id;
}

$readers = ['Mariana Costa', 'José Ribeiro', 'Patrícia Lima', 'Carlos Eduardo', 'Fernanda Alves', 'Rafael Nunes'];
$remarks = [
    'Finalmente uma boa notícia para o bairro!',
    'Vamos ver se cumprem o prazo dessa vez.',
    'Matéria muito bem explicada, parabéns à equipe.',
    'Alguém sabe se vale também para a zona oeste?',
    'Importante acompanhar de perto como o dinheiro vai ser usado.',
];

$minutesAgo = 12;
$n = 0;
foreach ($catalog as $category => [$rgb, $items]) {
    foreach ($items as [$title, $excerpt, $tags]) {
        $n++;
        [$mediaId, $image] = demo_image($rgb, $n * 7919, $authorId);
        $registry['media'][] = $mediaId;

        shuffle($paragraphs);
        $content = '<p>' . e($excerpt) . '. ' . e($paragraphs[0]) . '</p><p>' . e($paragraphs[1]) . '</p>'
            . '<h2>O que muda</h2><p>' . e($paragraphs[2]) . '</p><blockquote><p>' . e($paragraphs[3]) . '</p></blockquote>'
            . '<p>' . e($paragraphs[4]) . '</p>';

        $publishedAt = date('Y-m-d H:i:s', time() - $minutesAgo * 60);
        $minutesAgo += mt_rand(35, 190);

        q('INSERT INTO posts (title, slug, excerpt, content, featured_image, image_caption, category_id, author_id, status,
                              is_featured, views, published_at, created_at, updated_at)
           VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)', [
            $title, unique_slug('posts', slugify($title)), $excerpt, $content, $image, 'Imagem ilustrativa (demonstração)',
            $categoryIds[$category], $authorId, 'published', $n % 6 === 1 ? 1 : 0, mt_rand(40, 4000),
            $publishedAt, $publishedAt, $publishedAt,
        ]);
        $postId = last_id();
        $registry['posts'][] = $postId;
        sync_tags($postId, implode(', ', [...$tags, 'demonstração']));
        save_revision($postId, $authorId, 'Criada pelo conteúdo de demonstração');

        // Alguns comentários aprovados
        for ($c = 0, $count = mt_rand(0, 3); $c < $count; $c++) {
            $reader = $readers[array_rand($readers)];
            $when = date('Y-m-d H:i:s', strtotime($publishedAt) + mt_rand(5, 120) * 60);
            q("INSERT INTO comments (post_id, author_name, author_email, body, status, ip, created_at, approved_at)
               VALUES (?, ?, ?, ?, 'approved', '127.0.0.1', ?, ?)",
                [$postId, $reader, slugify($reader) . '@exemplo.com', $remarks[array_rand($remarks)], $when, $when]);
        }
    }
}

save_setting('demo_content', json_encode($registry));
printf("Pronto! Criadas %d notícias com imagens em %d editorias.\n", count($registry['posts']), count($categoryIds));
echo "Abra o site para ver. Para apagar tudo depois: php bin/demo.php --remover\n";
