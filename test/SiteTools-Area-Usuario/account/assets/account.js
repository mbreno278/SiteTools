document.addEventListener('click', async (event) => {
  const button = event.target.closest('[data-favorite-toggle]');
  if (!button) return;
  button.disabled = true;
  try {
    const response = await fetch('api/favorite.php', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-Token': document.querySelector('meta[name="csrf-token"]')?.content || ''
      },
      body: JSON.stringify({ tool_id: Number(button.dataset.favoriteToggle) })
    });
    const data = await response.json();
    if (!response.ok || !data.success) throw new Error(data.error || 'Não foi possível atualizar.');
    button.dataset.active = data.active ? '1' : '0';
    button.textContent = data.active ? 'Remover dos favoritos' : 'Adicionar aos favoritos';
  } catch (error) {
    alert(error.message);
  } finally {
    button.disabled = false;
  }
});
