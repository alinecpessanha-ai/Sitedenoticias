// Botões com confirmação própria (ex.: excluir um item dentro de um formulário maior)
document.addEventListener('click', (event) => {
  const button = event.target.closest('[data-confirm-click]');
  if (button && !window.confirm(button.dataset.confirmClick)) {
    event.preventDefault();
  }
});

// "Selecionar todos" das listas com ações em lote
document.addEventListener('change', (event) => {
  if (!event.target.matches('[data-check-all]')) return;
  const form = event.target.form;
  document.querySelectorAll(`input[name="ids[]"][form="${form.id}"]`).forEach((box) => {
    box.checked = event.target.checked;
  });
});

// Confirmação antes de ações destrutivas (formulários com data-confirm)
document.addEventListener('submit', (event) => {
  const form = event.target;
  if (form.dataset.confirm && !window.confirm(form.dataset.confirm)) {
    event.preventDefault();
    return;
  }
  // Exclusão em lote pede confirmação
  const action = form.querySelector('select[name="action"]');
  if (action && action.value === 'delete' && !event.submitter?.name && !window.confirm('Excluir definitivamente os comentários selecionados?')) {
    event.preventDefault();
  }
});
