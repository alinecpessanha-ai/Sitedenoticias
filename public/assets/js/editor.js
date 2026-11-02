/**
 * Editor visual simples (sem dependências) para o conteúdo das notícias.
 * O HTML gerado é enviado em um <textarea> e limpo no servidor (src/sanitizer.php).
 */
(function () {
  const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';

  document.querySelectorAll('[data-editor]').forEach(initEditor);
  initFeaturedImage();

  function initEditor(wrapper) {
    const toolbar = wrapper.querySelector('.editor-toolbar');
    const area = wrapper.querySelector('.editor-area');
    const source = wrapper.querySelector('.editor-source');
    const fileInput = wrapper.querySelector('.editor-file');
    const form = wrapper.closest('form');
    let sourceMode = false;
    let savedRange = null;

    area.innerHTML = source.value.trim() || '<p><br></p>';
    document.execCommand('defaultParagraphSeparator', false, 'p');

    // Guarda a posição do cursor para inserir imagens/links no lugar certo
    const saveSelection = () => {
      const sel = window.getSelection();
      if (sel.rangeCount && area.contains(sel.getRangeAt(0).commonAncestorContainer)) {
        savedRange = sel.getRangeAt(0).cloneRange();
      }
    };
    const restoreSelection = () => {
      area.focus();
      if (savedRange) {
        const sel = window.getSelection();
        sel.removeAllRanges();
        sel.addRange(savedRange);
      }
    };
    area.addEventListener('keyup', saveSelection);
    area.addEventListener('mouseup', saveSelection);
    area.addEventListener('blur', saveSelection);

    const sync = () => {
      if (!sourceMode) source.value = area.innerHTML;
    };
    area.addEventListener('input', sync);
    form.addEventListener('submit', sync);

    // Evita perder a seleção ao clicar na barra de ferramentas
    toolbar.addEventListener('mousedown', (e) => {
      if (e.target.closest('button')) e.preventDefault();
    });

    toolbar.addEventListener('click', (e) => {
      const btn = e.target.closest('button');
      if (!btn) return;
      const cmd = btn.dataset.cmd;

      if (cmd === 'source') {
        toggleSource(btn);
        return;
      }
      if (sourceMode) return;

      switch (cmd) {
        case 'formatBlock':
          document.execCommand('formatBlock', false, '<' + btn.dataset.value + '>');
          break;
        case 'link': {
          saveSelection();
          const href = window.prompt('Endereço do link (https://...)', 'https://');
          if (href && href !== 'https://') {
            restoreSelection();
            document.execCommand('createLink', false, href);
          }
          break;
        }
        case 'image':
          saveSelection();
          fileInput.click();
          break;
        case 'video': {
          saveSelection();
          const link = window.prompt('Cole o link do vídeo do YouTube ou Vimeo');
          const embed = link && videoEmbedUrl(link.trim());
          if (link && !embed) {
            window.alert('Link de vídeo não reconhecido.');
          } else if (embed) {
            restoreSelection();
            document.execCommand('insertHTML', false,
              '<iframe src="' + embed + '" title="Vídeo" allowfullscreen frameborder="0"></iframe><p><br></p>');
          }
          break;
        }
        default:
          document.execCommand(cmd, false, null);
      }
      sync();
    });

    fileInput.addEventListener('change', async () => {
      const file = fileInput.files[0];
      fileInput.value = '';
      if (!file) return;
      try {
        const data = await uploadImage(wrapper.dataset.uploadUrl, file);
        const alt = window.prompt('Descrição da imagem (texto alternativo, para acessibilidade):', '') || '';
        restoreSelection();
        document.execCommand('insertHTML', false,
          '<figure><img src="' + escapeAttr(data.url) + '" alt="' + escapeAttr(alt) + '"></figure><p><br></p>');
        sync();
      } catch (err) {
        window.alert(err.message);
      }
    });

    function toggleSource(btn) {
      sourceMode = !sourceMode;
      if (sourceMode) {
        source.value = area.innerHTML;
        source.hidden = false;
        area.hidden = true;
      } else {
        area.innerHTML = source.value;
        source.hidden = true;
        area.hidden = false;
      }
      btn.classList.toggle('active', sourceMode);
      toolbar.classList.toggle('source-mode', sourceMode);
    }
  }

  async function uploadImage(url, file) {
    const body = new FormData();
    body.append('file', file);
    body.append('_csrf', csrf);
    const res = await fetch(url, { method: 'POST', body, headers: { Accept: 'application/json' } });
    let data;
    try {
      data = await res.json();
    } catch (e) {
      throw new Error('Falha no envio da imagem.');
    }
    if (!data.ok) throw new Error(data.error || 'Falha no envio da imagem.');
    return data;
  }

  function videoEmbedUrl(link) {
    let m = link.match(/(?:youtube\.com\/(?:watch\?(?:.*&)?v=|embed\/|shorts\/|live\/)|youtu\.be\/)([\w-]{11})/);
    if (m) return 'https://www.youtube-nocookie.com/embed/' + m[1];
    m = link.match(/vimeo\.com\/(?:video\/)?(\d+)/);
    if (m) return 'https://player.vimeo.com/video/' + m[1];
    return null;
  }

  function escapeAttr(value) {
    return String(value).replace(/&/g, '&amp;').replace(/"/g, '&quot;').replace(/</g, '&lt;');
  }

  // Imagem destacada: pré-visualização do arquivo e seletor da biblioteca
  function initFeaturedImage() {
    const preview = document.querySelector('[data-featured-preview]');
    const fileInput = document.querySelector('[data-featured-file]');
    const mediaInput = document.querySelector('[data-featured-media]');
    const pickerBtn = document.querySelector('[data-media-picker]');
    const dialog = document.querySelector('[data-media-dialog]');
    if (!preview) return;

    const showPreview = (src) => {
      preview.innerHTML = '';
      const img = document.createElement('img');
      img.src = src;
      img.alt = '';
      preview.appendChild(img);
    };

    fileInput?.addEventListener('change', () => {
      if (fileInput.files[0]) {
        mediaInput.value = '';
        showPreview(URL.createObjectURL(fileInput.files[0]));
      }
    });

    pickerBtn?.addEventListener('click', async () => {
      const grid = dialog.querySelector('[data-media-grid]');
      grid.textContent = 'Carregando…';
      dialog.showModal();
      try {
        const res = await fetch(pickerBtn.dataset.source, { headers: { Accept: 'application/json' } });
        const items = await res.json();
        grid.innerHTML = '';
        if (!items.length) grid.textContent = 'Nenhuma imagem na biblioteca ainda.';
        items.forEach((item) => {
          const btn = document.createElement('button');
          btn.type = 'button';
          btn.className = 'media-pick';
          btn.title = item.name;
          const img = document.createElement('img');
          img.src = item.thumb || item.url;
          img.alt = item.name;
          img.loading = 'lazy';
          btn.appendChild(img);
          btn.addEventListener('click', () => {
            mediaInput.value = item.id;
            if (fileInput) fileInput.value = '';
            showPreview(item.thumb || item.url);
            dialog.close();
          });
          grid.appendChild(btn);
        });
      } catch (e) {
        grid.textContent = 'Não foi possível carregar a biblioteca.';
      }
    });

    dialog?.querySelector('[data-close]')?.addEventListener('click', () => dialog.close());
  }
})();
