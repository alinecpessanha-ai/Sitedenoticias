# CMS de Notícias

Um sistema de gerenciamento de conteúdo (CMS) próprio para sites de notícias, inspirado no WordPress.
Feito em **PHP 8 puro + SQLite**, sem frameworks nem dependências: roda em praticamente qualquer hospedagem.

## Funcionalidades

**Site público**
- Páginas fixas na raiz do site (`/quem-somos`, `/contato`...) com links no rodapé
- Formulário de contato com proteção anti-spam (campo armadilha + limite de envios por IP)
- Página inicial com manchete, destaques, últimas notícias e blocos por editoria
- Página da notícia com linha fina, autor, tempo de leitura, imagem com legenda, tags, compartilhamento e "Leia também"
- Páginas de categoria, tag e autor, busca, paginação e "Mais lidas"
- RSS (`/feed.xml`), sitemap (`/sitemap.xml`), `robots.txt` e meta tags Open Graph
- Layout responsivo com modo escuro automático
- Imagens responsivas: cada tela recebe a versão mais leve que mantém a nitidez (`srcset`)

**Painel administrativo (`/admin`)**
- Instalação guiada no primeiro acesso
- Editor visual (negrito, intertítulos, citações, listas, links, imagens e vídeos do YouTube/Vimeo, modo HTML)
- Rascunho → revisão → publicação, com **agendamento** por data futura e marcação de destaque
- Categorias (com ordem no menu), tags, biblioteca de mídia
- **Miniaturas automáticas** (400, 800 e 1600 px) ao enviar imagens, com correção da rotação de fotos de celular
- Páginas fixas com modelo "Padrão" ou "Contato" (com formulário)
- Caixa de **Mensagens** do formulário de contato, com contador de não lidas e aviso opcional por e-mail
- Usuários com papéis: **Administrador**, **Editor** e **Autor**
- Configurações do site (nome, slogan, rodapé, redes sociais, itens por página)
- Painel com estatísticas, notícias para revisar e mais lidas

**Segurança**
- Senhas com `password_hash`, sessão regenerada no login, limite de tentativas de login
- Proteção CSRF em todos os formulários
- HTML das notícias sanitizado no servidor (bloqueia `<script>`, `javascript:`, eventos `on*` e iframes de origens não permitidas)
- Upload só de imagens reais (JPG, PNG, GIF, WEBP), com nome aleatório e execução de scripts bloqueada na pasta

## Como rodar

Requisitos: PHP 8.1+ com as extensões `pdo_sqlite`, `dom`, `mbstring`, `fileinfo` e `gd` (vêm na maioria das instalações).
A extensão `exif` é opcional: com ela, fotos de celular ficam na orientação correta nas miniaturas.

```bash
php -S localhost:8000 -t public public/index.php
```

Abra http://localhost:8000/admin e siga a instalação. O banco é criado sozinho em `storage/database.sqlite`.

### Em produção (Apache/hospedagem compartilhada)

1. Envie os arquivos e aponte a raiz do domínio (document root) para a pasta `public/`.
2. O `public/.htaccess` já redireciona as URLs para o `index.php` (precisa do `mod_rewrite`).
3. Garanta permissão de escrita nas pastas `storage/` e `public/uploads/`.
4. Em `config.php`, mude `debug` para `false` e preencha `site_url` (ex.: `https://meuportal.com.br`).

No Nginx, use `try_files $uri /index.php?$query_string;`.

## Estrutura

```
config.php              Configurações
bin/miniaturas.php      Gera miniaturas de imagens antigas (terminal)
public/                 Raiz pública (index.php, CSS, JS, uploads)
src/
  bootstrap.php         Inicialização
  database.php          Conexão SQLite, migrações e configurações do site
  migrations/           Alterações do banco, numeradas (001_..., 002_...)
  auth.php              Login, papéis e permissões
  sanitizer.php         Limpeza do HTML das notícias
  uploads.php           Upload de imagens
  images.php            Miniaturas e imagens responsivas
  content.php           Consultas de notícias, categorias e tags
  router.php            Roteador
  routes/site.php       Rotas do site público
  routes/admin.php      Rotas do painel
  routes/pages.php      Páginas fixas e formulário de contato (carregado por último)
templates/
  site/                 Tema do site
  admin/                Telas do painel
```

### Miniaturas

Ao enviar uma imagem, o CMS cria versões de 400, 800 e 1600 px ao lado do original (`foto-800w.jpg` etc.).
O site usa `srcset`, então o navegador baixa só o tamanho necessário — uma foto de celular de 1 MB
chega ao leitor com 20–80 KB. GIFs não são redimensionados, para não perder a animação.

Para imagens enviadas antes desse recurso, use o botão **Gerar miniaturas que faltam** em *Mídia*, ou:

```bash
php bin/miniaturas.php            # gera as que faltam
php bin/miniaturas.php --refazer  # recria todas (ex.: depois de mudar os tamanhos em src/images.php)
```

### Banco de dados e atualizações

As mudanças no banco ficam em `src/migrations/`, em arquivos numerados. Ao atualizar o código de um site
já instalado, as migrações novas são aplicadas sozinhas no próximo acesso — não é preciso reinstalar.
Para alterar o banco, crie um novo arquivo com o próximo número (ex.: `003_minha_mudanca.sql`).

Para mudar o visual do site, edite `templates/site/` e `public/assets/css/site.css`.

## Próximos passos sugeridos

- Revisões/histórico de alterações das notícias
- Comentários moderados e newsletter
- Cache de páginas para alto tráfego
- Suporte a MySQL/PostgreSQL
