/**
 * Acessibilidade: foco visível, atalhos básicos, skip link.
 */
(function (global) {
  "use strict";

  function enhanceFocus() {
    // Detecta navegação por teclado para reforçar outline
    function add() {
      document.documentElement.classList.add("st-using-keyboard");
    }
    function remove() {
      document.documentElement.classList.remove("st-using-keyboard");
    }
    window.addEventListener("keydown", function (e) {
      if (e.key === "Tab") add();
    });
    window.addEventListener("mousedown", remove);
  }

  function ensureSkipLink() {
    if (document.querySelector(".st-skip-link")) return;
    var main =
      document.getElementById("main-content") ||
      document.querySelector("main") ||
      document.querySelector(".workspace") ||
      document.querySelector(".ua-main");
    if (!main) return;
    if (!main.id) main.id = "main-content";

    var a = document.createElement("a");
    a.className = "st-skip-link";
    a.href = "#" + main.id;
    a.textContent = "Ir para o conteúdo";
    document.body.insertBefore(a, document.body.firstChild);
  }

  function trapNotes() {
    // Marca elementos interativos sem foco natural (raro, mas útil)
    document.querySelectorAll("[onclick][tabindex='-1']").forEach(function (el) {
      if (!el.hasAttribute("tabindex")) el.setAttribute("tabindex", "0");
    });
  }

  function init() {
    enhanceFocus();
    ensureSkipLink();
    trapNotes();
  }

  if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", init);
  } else {
    init();
  }

  global.SiteToolsA11y = { init: init };
})(window);
