document.querySelectorAll('.js-confirm-delete').forEach((form) => {
  form.addEventListener('submit', (event) => {
    const ok = window.confirm('Supprimer définitivement ce message ?');
    if (!ok) {
      event.preventDefault();
    }
  });
});

document.addEventListener('DOMContentLoaded', () => {
  const flashMessages = document.querySelectorAll('[data-flash-message="1"]');

  flashMessages.forEach((flash) => {
    const autoHide = flash.dataset.autohide === 'true';
    const delay = parseInt(flash.dataset.delay || '3000', 10);

    if (!autoHide || Number.isNaN(delay) || delay < 0) {
      return;
    }

    window.setTimeout(() => {
      flash.classList.add('is-hiding');

      window.setTimeout(() => {
        flash.remove();
      }, 300);
    }, delay);
  });
});