(() => {
  const toastEl = document.getElementById('ua-toast');

  function showToast(message, type = 'info') {
    if (!toastEl) {
      alert(message);
      return;
    }
    toastEl.textContent = message;
    toastEl.className = 'ua-toast ua-toast--' + type;
    toastEl.hidden = false;
    clearTimeout(toastEl._timer);
    toastEl._timer = setTimeout(() => {
      toastEl.hidden = true;
    }, 3200);
  }

  document.addEventListener('click', async (event) => {
    const button = event.target.closest('[data-favorite-toggle]');
    if (!button) return;

    event.preventDefault();
    button.disabled = true;

    const toolId = Number(button.dataset.favoriteToggle);
    const wasActive = button.dataset.active === '1';

    try {
      const response = await fetch('api/favorite.php', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-CSRF-Token': document.querySelector('meta[name="csrf-token"]')?.content || ''
        },
        body: JSON.stringify({ tool_id: toolId })
      });

      const data = await response.json();
      if (!response.ok || !data.success) {
        throw new Error(data.error || 'Não foi possível atualizar.');
      }

      const isActive = !!data.active;
      button.dataset.active = isActive ? '1' : '0';

      // Atualiza ícone / texto
      const icon = button.querySelector('.material-symbols-outlined');
      if (icon) {
        icon.textContent = isActive ? 'favorite' : 'favorite_border';
        button.classList.toggle('is-active', isActive);
        button.title = isActive ? 'Remover dos favoritos' : 'Adicionar aos favoritos';
      } else {
        button.textContent = isActive ? 'Remover' : 'Adicionar aos favoritos';
      }

      // Se estiver na página de favoritos e removeu, some a linha
      if (!isActive && button.dataset.removeRow === '1') {
        const row = button.closest('.ua-list-row');
        if (row) {
          row.style.transition = 'opacity .2s, transform .2s';
          row.style.opacity = '0';
          row.style.transform = 'translateX(12px)';
          setTimeout(() => row.remove(), 220);
        }
      }

      showToast(isActive ? 'Adicionado aos favoritos' : 'Removido dos favoritos', 'success');
    } catch (error) {
      showToast(error.message || 'Erro ao atualizar favorito', 'error');
    } finally {
      button.disabled = false;
    }
  });
})();
