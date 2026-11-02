// "Responder" a um comentário sem recarregar a página.
// Sem JavaScript, o link ?responder=ID continua funcionando.
(function () {
  const form = document.getElementById('comentar');
  if (!form) return;
  const parentInput = form.querySelector('[data-parent-input]');
  const replying = form.querySelector('[data-replying]');
  const replyingName = form.querySelector('[data-replying-name]');
  const textarea = form.querySelector('textarea[name="body"]');

  document.addEventListener('click', (event) => {
    const reply = event.target.closest('[data-reply]');
    if (reply) {
      event.preventDefault();
      parentInput.value = reply.dataset.reply;
      replyingName.textContent = reply.dataset.replyName;
      replying.hidden = false;
      form.scrollIntoView({ behavior: 'smooth', block: 'center' });
      textarea.focus({ preventScroll: true });
      return;
    }
    if (event.target.closest('[data-cancel-reply]')) {
      event.preventDefault();
      parentInput.value = '';
      replying.hidden = true;
    }
  });
})();
