# Como publicar o site (HostGator e outras hospedagens com cPanel)

Tempo estimado: 10 minutos. Você vai precisar do arquivo **`sitedenoticias-….zip`**
(gerado com `php bin/empacotar.php`, ou o que você recebeu pronto).

> Os nomes dos menus podem variar um pouco conforme o plano e a versão do cPanel.
> Se não encontrar alguma opção, o suporte da HostGator resolve rapidamente pelo chat.

## 1. Ajuste a versão do PHP

1. Entre no **Portal do Cliente** da HostGator e abra o **cPanel** do seu domínio.
2. Procure **"Selecionar versão do PHP"** (ou **"MultiPHP Manager"**).
3. Escolha **PHP 8.3** (qualquer versão a partir da 8.1 funciona) para o domínio e salve.

Normalmente as extensões necessárias (`pdo_sqlite`, `gd`, `mbstring`, `dom`, `fileinfo`) já vêm ativadas.
Se faltar alguma, o próprio site vai avisar no passo 4, dizendo exatamente qual ativar.

## 2. Envie os arquivos

1. No cPanel, abra o **Gerenciador de Arquivos** e entre na pasta **`public_html`**.
2. **Apague** os arquivos de exemplo que vierem lá (como `index.html` ou `default.html`).
   Se o domínio já tiver outro site, faça um backup antes!
3. Clique em **Carregar** (Upload) e envie o `.zip`.
4. Volte para `public_html`, clique com o botão direito no `.zip` e escolha **Extrair**.
5. Depois de extrair, apague o `.zip`.

A pasta `public_html` deve ficar assim:

```
public_html/
├── .htaccess      ← arquivo oculto: ative "Mostrar arquivos ocultos" nas Configurações para vê-lo
├── bin/
├── config.php
├── public/
├── src/
├── storage/
└── templates/
```

Não precisa mudar nada no `config.php`: o site detecta o endereço sozinho.
As pastas internas (`src`, `storage`, `templates`...) ficam protegidas: o navegador não consegue abri-las.

## 3. Ative o HTTPS (cadeado)

1. No cPanel, abra **"SSL/TLS Status"** e rode o **AutoSSL** para o seu domínio (é gratuito).
   Em contas novas ele costuma já estar ativo; o certificado pode levar alguns minutos para sair.
2. Com o cadeado funcionando, abra o arquivo **`.htaccess`** da `public_html` (botão **Editar**)
   e tire o `#` do começo destas duas linhas, para sempre usar HTTPS:

   ```
   # RewriteCond %{HTTPS} off
   # RewriteRule ^ https://%{HTTP_HOST}%{REQUEST_URI} [R=301,L]
   ```

## 4. Instale

1. Acesse **`https://seudominio.com.br/admin`**.
2. Preencha o nome do site e crie a sua conta de administrador.
3. Pronto! Comece criando as editorias em **Categorias** e publique a primeira notícia.

Se aparecer a página **"Quase lá! O servidor precisa de um ajuste"**, siga as instruções dela
(geralmente é ativar uma extensão do PHP no passo 1 ou dar permissão 755 a uma pasta) e recarregue.

## 5. Configure o e-mail de avisos (opcional)

Em **Configurações**, informe um e-mail para ser avisado de novas mensagens do formulário de contato.
Use de preferência um e-mail do próprio domínio (crie um em **cPanel → Contas de e-mail**),
pois as hospedagens costumam bloquear envios em nome de outros provedores.

---

## Como atualizar o site depois

Quando houver uma versão nova do sistema, gere/baixe o `.zip` novo e repita o **passo 2** —
extraindo **por cima** dos arquivos atuais (confirme a substituição).

O pacote **não contém** o banco de dados nem as imagens enviadas, então **notícias, usuários,
comentários e fotos são preservados**. Mudanças no banco são aplicadas sozinhas no primeiro acesso.

## Como fazer backup

Baixe periodicamente pelo Gerenciador de Arquivos (botão **Compactar** e depois **Baixar**):

- `storage/database.sqlite` — todo o conteúdo do site (notícias, usuários, comentários...)
- `public/uploads/` — as imagens

O cPanel também tem a opção **Backup** para baixar a conta inteira.

## Problemas comuns

| Sintoma | O que fazer |
|---|---|
| Página "Quase lá! O servidor precisa de um ajuste" | Siga a lista mostrada na página. |
| Aparece a página padrão da HostGator | Apague o `index.html`/`default.html` que sobrou na `public_html`. |
| "Erro 500" sem explicação | Veja o arquivo `storage/erros.log` pelo Gerenciador de Arquivos e mande o conteúdo para o suporte. |
| A home abre, mas as notícias dão "Página não encontrada" | Confira se o `.htaccess` foi extraído (ele é oculto: ative "Mostrar arquivos ocultos"). |
| Imagens não sobem | Dê permissão **755** à pasta `public/uploads` e verifique o limite de upload do PHP (5 MB ou mais). |
| Site em subpasta (ex.: `seudominio.com.br/portal`) | Crie `config.local.php` com `<?php return ['base_url' => '/portal'];` |

## Configurações só do servidor

Para mudar alguma configuração sem mexer no `config.php` (que é substituído nas atualizações),
crie um arquivo **`config.local.php`** ao lado dele. Exemplo:

```php
<?php
return [
    'site_url'      => 'https://meuportal.com.br', // endereço fixo para RSS e sitemap
    'upload_max_mb' => 10,                          // imagens de até 10 MB
];
```
