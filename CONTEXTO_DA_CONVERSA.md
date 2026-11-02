# Contexto do projeto: CMS de notícias próprio

> **Para o Claude que receber este arquivo:** é o resumo de uma conversa anterior em que construímos este
> projeto juntos. Leia tudo antes de agir. Continue respondendo **em português do Brasil**, explicando as
> coisas de forma simples (a dona do projeto não é programadora). O código está no GitHub; peça acesso
> ao repositório se ainda não tiver. A próxima tarefa pendente está na seção **"O que falta fazer"**.

---

## 1. O que é o projeto

Um **site de notícias com um CMS próprio**, parecido com o WordPress, feito do zero.

- **Repositório:** `alinecpessanha-ai/Sitedenoticias` (GitHub)
- **Branch com todo o trabalho:** `claude/cms-news-site-65449c`
  (o repositório não tinha nenhum branch antes; não existe `main`. Esse branch é o único.)
- **Tecnologia:** PHP 8.1+ puro + SQLite, **sem frameworks e sem dependências** (nada de Composer/npm).
  Escolhido para rodar em qualquer hospedagem compartilhada barata, como o WordPress.
- **Visual:** estilo jornal (títulos com serifa, vermelho como cor de destaque), responsivo, modo escuro automático.
- O nome "Diário da Cidade" usado nas capturas era só de teste; o nome real é definido na instalação.

## 2. O que já está pronto (e testado)

Tudo abaixo foi implementado, testado (incluindo em navegador real com Playwright e num Apache com
mod_php igual ao de hospedagem compartilhada) e enviado ao GitHub, em 7 commits:

| Commit | Funcionalidade |
|---|---|
| `9ff31bc` | Base do CMS: site público, painel `/admin`, instalação guiada, notícias, categorias, tags, mídia, usuários, configurações, RSS, sitemap, segurança |
| `e9699b5` | Páginas fixas (Quem somos, Contato) na raiz do site + formulário de contato + caixa de Mensagens |
| `8702eb1` | Miniaturas automáticas (400/800/1600 px), `srcset`, correção de rotação EXIF de fotos de celular |
| `3ee1e31` | Histórico de alterações das notícias: versões, comparação palavra por palavra, restaurar |
| `9215145` | Comentários com moderação: respostas, anti-spam, fila no painel, ações em lote, resposta da redação |
| `67b0eba` | Front-end completo: menu lateral no celular, faixa "Últimas", progresso de leitura, copiar link, voltar ao topo, conteúdo de demonstração |
| `68bf37c` | Preparação para hospedagem PHP: `.htaccess` na raiz, verificação de requisitos, empacotador `.zip`, guia `INSTALACAO.md` |

### Resumo das funcionalidades

**Site público:** home (faixa "Últimas", manchete + destaques, últimas notícias, blocos por editoria, "Mais lidas"),
página da notícia (linha fina, autor, tempo de leitura, imagem com legenda, tags, compartilhar, "Leia também",
comentários), páginas de categoria/tag/autor/busca com paginação, páginas fixas (`/quem-somos`, `/contato`),
RSS (`/feed.xml`), sitemap, robots.txt, Open Graph.

**Painel `/admin`:** editor visual próprio (sem bibliotecas), rascunho → revisão → publicação, agendamento
por data futura, destaque na home, histórico de versões, categorias, tags, biblioteca de mídia, páginas,
mensagens de contato, moderação de comentários, usuários com 3 papéis e configurações do site.

**Papéis:** Administrador (tudo) · Editor (publica/edita tudo, categorias, mídia, páginas, mensagens, comentários)
· Autor (só as próprias notícias, envia para revisão; não publica).

**Segurança:** `password_hash`, sessão regenerada no login, limite de tentativas de login, CSRF em todos os
formulários, HTML das notícias sanitizado no servidor (`src/sanitizer.php`), uploads validados (só imagens reais,
nome aleatório, PHP bloqueado na pasta), pastas internas bloqueadas por `.htaccess`.

## 3. Como o código está organizado

```
.htaccess               Encaminha tudo para public/ e bloqueia pastas internas (quando o site fica inteiro na public_html)
config.php              Configurações (debug liga sozinho só em php -S e no terminal); config.local.php sobrescreve no servidor
INSTALACAO.md           Passo a passo de publicação no cPanel (HostGator etc.)
bin/demo.php            Cria/remove conteúdo de demonstração (24 notícias com imagens geradas)
bin/empacotar.php       Gera dist/sitedenoticias-*.zip para enviar à hospedagem (sem banco nem uploads)
bin/miniaturas.php      Gera miniaturas de imagens antigas
public/index.php        Front controller: checa requisitos → bootstrap → rotas → dispatch()
public/assets/          css/site.css, css/admin.css, js/site.js, js/editor.js, js/admin.js, js/comments.js
src/requirements.php    Verificação de PHP/extensões/permissões (sintaxe antiga de propósito)
src/bootstrap.php       Inicialização, sessão, is_https(), log de erros em storage/erros.log
src/database.php        PDO SQLite, migrações automáticas, setting()/save_setting()
src/migrations/         001_inicial, 002_paginas_e_mensagens, 003_historico_de_noticias, 004_comentarios
src/helpers.php         e(), url(), view()/render(), CSRF, slugify(), datas em pt-BR, time_ago(), paginação
src/auth.php            Login, papéis e can($habilidade)
src/sanitizer.php       Lista de tags/atributos permitidos; iframes só YouTube/Vimeo
src/images.php          Miniaturas, image_attrs() (srcset), responsive_content()
src/uploads.php         Upload e exclusão de imagens
src/content.php         find_posts(), páginas fixas, menu do rodapé
src/revisions.php       Versões e diff (LCS) por parágrafo e por palavra
src/comments.php        Regras, anti-spam e moderação de comentários
src/router.php          Roteador simples (route('GET', '/noticia/{slug}', fn...))
src/routes/site.php     Rotas públicas
src/routes/admin.php    Rotas do painel
src/routes/pages.php    Páginas fixas em /{slug} — PRECISA ser carregado por último
templates/site/, templates/admin/   Templates PHP
```

### Detalhes importantes (para não quebrar nada)

- **Banco:** SQLite em `storage/database.sqlite` (fora da pasta pública). A versão fica em `PRAGMA user_version`.
  Para mudar o banco, crie `src/migrations/005_...sql`; ela roda sozinha no próximo acesso.
- **Ordem das rotas:** `src/routes/pages.php` captura qualquer `/{slug}`, então é incluído por último.
  Slugs de páginas que colidem com rotas do sistema ganham `-pagina` (veja `RESERVED_SLUGS` em `src/content.php`).
- **Nomes de funções:** `page_url(int)` é da paginação; a URL de página fixa é `static_page_url(array)`.
- **HTML das notícias** é sanitizado **ao salvar**; os templates o imprimem sem escapar.
  Todo o resto é escapado com `e()`.
- **Status de notícia:** `draft`, `pending`, `published`. "Agendada" = `published` com `published_at` no futuro.
- **Comentários:** `pending`, `approved`, `spam`; um nível de resposta (`parent_id`).
- **Restaurar versão** traz o conteúdo, mas não muda status nem data de publicação (decisão consciente).
- O pacote `.zip` **não inclui** banco nem uploads: extrair uma versão nova por cima atualiza sem perder dados.

## 4. Como rodar no computador

```bash
git clone https://github.com/alinecpessanha-ai/Sitedenoticias.git
cd Sitedenoticias
git checkout claude/cms-news-site-65449c
php -S localhost:8000 -t public public/index.php
# abra http://localhost:8000/admin e faça a instalação
php bin/demo.php            # (opcional) preenche com conteúdo de demonstração
php bin/demo.php --remover  # remove a demonstração
```

## 5. Onde paramos — publicação

- A dona quer o site no ar. **Vercel foi descartada**: ela apaga os arquivos (banco SQLite e imagens se perderiam).
  A escolha foi **hospedagem PHP**, e ela tem conta na **HostGator** (cPanel).
- Já está pronto: `.htaccess` da raiz (funciona com o site inteiro dentro da `public_html`), página de
  requisitos, `bin/empacotar.php` e o guia **`INSTALACAO.md`**. Tudo testado num Apache com mod_php:
  instalação, bloqueio das pastas internas (403), upload de imagens, atualização por cima sem perder dados.
- **Ainda NÃO foi publicado.** Ela pediu que o Claude usasse a aba do Chrome já logada na HostGator, mas a
  sessão anterior rodava na nuvem, sem acesso ao navegador dela.

## 6. O que falta fazer

### Próxima tarefa: publicar na HostGator

Se você (Claude) tiver acesso ao Chrome dela (extensão **Claude in Chrome**, com a aba da HostGator logada):

1. Gere o pacote: `php bin/empacotar.php` (ou use o `.zip` que ela já baixou: `sitedenoticias-2026-11-02-011048.zip`).
2. No cPanel: **Selecionar versão do PHP** → 8.3 (mínimo 8.1).
3. **Gerenciador de Arquivos** → `public_html` → apague `index.html`/`default.html` de exemplo
   (se houver outro site lá, **pergunte antes** e faça backup) → **Carregar** o `.zip` → **Extrair** → apague o `.zip`.
   Confira se o `.htaccess` (arquivo oculto) foi extraído.
4. **SSL/TLS Status** → AutoSSL. Depois, se quiser forçar HTTPS, tire o `#` das 2 linhas indicadas no `.htaccess`.
5. Abra `https://dominio/admin`. **Quem cria a senha do administrador é ela**: não digite senhas por ela.
6. Se aparecer "Quase lá! O servidor precisa de um ajuste", siga a lista mostrada.
7. Confira: home, uma notícia, `/contato`, `/feed.xml` e que `/config.php` dá 403.

Sem acesso ao navegador, oriente-a passo a passo pelo `INSTALACAO.md` e peça prints.

### Ideias sugeridas para depois (nenhuma começada)

- Newsletter
- Cache de páginas para picos de acesso
- Suporte a MySQL/PostgreSQL
- Visual personalizado (cores/fontes/logo) — ela pode mandar um site de referência

## 7. Preferências e avisos

- Conversar em **português**, com explicações simples e objetivas; mostrar capturas de tela quando possível.
- Commits vão para o branch `claude/cms-news-site-65449c`. Não criar pull request sem ela pedir.
- Os dados de teste das sessões anteriores (usuário `admin@example.com`, a página "Admin", as notícias de teste
  com imagem azul) existiam **só no banco local da sessão antiga** — não estão no GitHub nem no pacote.
- O conteúdo de demonstração (`bin/demo.php`) recebe a tag "demonstração"; lembrar de removê-lo antes de usar
  o site de verdade (ele só é criado se alguém rodar o script).
