"use strict";

function uaToast(message, isError) {
  const el = document.getElementById("ua-toast");
  if (!el) {
    alert(message);
    return;
  }
  el.hidden = false;
  el.textContent = message;
  el.classList.toggle("is-error", !!isError);
  clearTimeout(el._timer);
  el._timer = setTimeout(() => {
    el.hidden = true;
  }, 3200);
}

document.addEventListener("click", async (event) => {
  const button = event.target.closest("[data-favorite-toggle]");
  if (!button) return;

  event.preventDefault();
  button.disabled = true;

  try {
    const response = await fetch("api/favorite.php", {
      method: "POST",
      headers: {
        "Content-Type": "application/json",
        "X-CSRF-Token":
          document.querySelector('meta[name="csrf-token"]')?.content || "",
      },
      credentials: "same-origin",
      body: JSON.stringify({
        tool_id: Number(button.dataset.favoriteToggle),
      }),
    });

    const data = await response.json().catch(() => ({}));
    if (!response.ok || !data.success) {
      throw new Error(data.message || data.error || "Não foi possível atualizar.");
    }

    button.dataset.active = data.active ? "1" : "0";
    if (button.classList.contains("ua-button--danger") && !data.active) {
      const row = button.closest(".ua-list-row");
      if (row) row.remove();
    } else {
      button.textContent = data.active ? "★ Favorito" : "☆ Favoritar";
    }
    uaToast(data.message || (data.active ? "Favoritado." : "Removido."));
  } catch (error) {
    uaToast(error.message || "Erro", true);
  } finally {
    button.disabled = false;
  }
});
