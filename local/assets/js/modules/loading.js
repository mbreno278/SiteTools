/**
 * Estados de carregamento reutilizáveis.
 */
(function (global) {
  "use strict";

  function setButtonLoading(button, loading, loadingText) {
    if (!button) return;
    if (loading) {
      if (!button.dataset.originalHtml) {
        button.dataset.originalHtml = button.innerHTML;
      }
      button.disabled = true;
      button.setAttribute("aria-busy", "true");
      button.classList.add("is-loading");
      if (loadingText) {
        button.innerHTML =
          '<span class="st-spinner" aria-hidden="true"></span> ' + loadingText;
      } else {
        button.innerHTML =
          '<span class="st-spinner" aria-hidden="true"></span> ' +
          (button.dataset.originalHtml || "Aguarde…");
      }
    } else {
      button.disabled = false;
      button.removeAttribute("aria-busy");
      button.classList.remove("is-loading");
      if (button.dataset.originalHtml) {
        button.innerHTML = button.dataset.originalHtml;
      }
    }
  }

  function overlay(show, message) {
    var el = document.getElementById("st-loading-overlay");
    if (show) {
      if (!el) {
        el = document.createElement("div");
        el.id = "st-loading-overlay";
        el.className = "st-loading-overlay";
        el.innerHTML =
          '<div class="st-loading-overlay__box" role="status" aria-live="polite">' +
          '<span class="st-spinner st-spinner--lg" aria-hidden="true"></span>' +
          '<p class="st-loading-overlay__text"></p>' +
          "</div>";
        document.body.appendChild(el);
      }
      el.querySelector(".st-loading-overlay__text").textContent =
        message || "Processando…";
      el.hidden = false;
      document.body.classList.add("st-loading-active");
    } else if (el) {
      el.hidden = true;
      document.body.classList.remove("st-loading-active");
    }
  }

  global.SiteToolsLoading = {
    button: setButtonLoading,
    overlay: overlay,
  };
})(window);
