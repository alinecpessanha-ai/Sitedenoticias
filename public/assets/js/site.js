/**
 * Interações do site público. Tudo é opcional: sem JavaScript, o site continua funcionando.
 */
(function () {
  /* ---------- Menu lateral (celular) ---------- */
  const menu = document.querySelector('[data-menu]');
  const openBtn = document.querySelector('[data-menu-open]');
  if (menu && openBtn) {
    const open = () => {
      menu.hidden = false;
      openBtn.setAttribute('aria-expanded', 'true');
      document.body.classList.add('no-scroll');
      requestAnimationFrame(() => menu.classList.add('open'));
      menu.querySelector('a, input, button')?.focus();
    };
    const close = () => {
      menu.classList.remove('open');
      openBtn.setAttribute('aria-expanded', 'false');
      document.body.classList.remove('no-scroll');
      setTimeout(() => { menu.hidden = true; }, 200);
      openBtn.focus();
    };
    openBtn.addEventListener('click', open);
    menu.querySelectorAll('[data-menu-close]').forEach((el) => el.addEventListener('click', close));
    document.addEventListener('keydown', (e) => {
      if (e.key === 'Escape' && !menu.hidden) close();
    });
  }

  /* ---------- Voltar ao topo ---------- */
  const toTop = document.querySelector('[data-to-top]');
  /* ---------- Barra de progresso de leitura (página da notícia) ---------- */
  const progress = document.querySelector('[data-read-progress]');
  const article = document.querySelector('.single');

  let ticking = false;
  const onScroll = () => {
    if (ticking) return;
    ticking = true;
    requestAnimationFrame(() => {
      if (toTop) toTop.hidden = window.scrollY < 800;
      if (progress && article) {
        const rect = article.getBoundingClientRect();
        const total = rect.height - window.innerHeight;
        const done = total > 0 ? Math.min(1, Math.max(0, -rect.top / total)) : 1;
        progress.style.transform = `scaleX(${done})`;
      }
      ticking = false;
    });
  };
  window.addEventListener('scroll', onScroll, { passive: true });
  onScroll();
  toTop?.addEventListener('click', () => window.scrollTo({ top: 0, behavior: 'smooth' }));

  /* ---------- Compartilhar ---------- */
  document.querySelectorAll('[data-copy-link]').forEach((btn) => {
    btn.addEventListener('click', async () => {
      const original = btn.textContent;
      try {
        await navigator.clipboard.writeText(btn.dataset.copyLink);
        btn.textContent = 'Link copiado!';
      } catch (e) {
        window.prompt('Copie o link:', btn.dataset.copyLink);
      }
      setTimeout(() => { btn.textContent = original; }, 2000);
    });
  });

  // Compartilhamento nativo (celulares): abre a lista de apps do aparelho
  document.querySelectorAll('[data-native-share]').forEach((btn) => {
    if (!navigator.share) return;
    btn.hidden = false;
    btn.addEventListener('click', () => {
      navigator.share({ title: btn.dataset.title, url: btn.dataset.url }).catch(() => {});
    });
  });
})();
