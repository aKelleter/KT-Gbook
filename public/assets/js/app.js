document.querySelectorAll('.js-confirm-delete').forEach((form) => {
  form.addEventListener('submit', (event) => {
    const ok = window.confirm('Supprimer définitivement ce message ?');
    if (!ok) {
      event.preventDefault();
    }
  });
});
