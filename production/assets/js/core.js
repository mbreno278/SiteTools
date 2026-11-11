/**
 * SiteTools — bootstrap do front-end (módulos base).
 * Ordem sugerida no HTML:
 *   config.js → csrf.js → modules/* → core.js → scripts de página
 */
(function (global) {
  "use strict";

  function ready(fn) {
    if (document.readyState === "loading") {
      document.addEventListener("DOMContentLoaded", fn);
    } else {
      fn();
    }
  }

  ready(function () {
    // Evita submit duplo em forms com data-st-once
    document.querySelectorAll("form[data-st-once]").forEach(function (form) {
      form.addEventListener("submit", function () {
        var btn = form.querySelector('[type="submit"]');
        if (btn && global.SiteToolsLoading) {
          global.SiteToolsLoading.button(btn, true, "Enviando…");
        }
      });
    });

    // Links externos: segurança básica
    document.querySelectorAll('a[target="_blank"]').forEach(function (a) {
      if (!a.getAttribute("rel")) {
        a.setAttribute("rel", "noopener noreferrer");
      }
    });
  });

  global.SiteTools = global.SiteTools || {};
  global.SiteTools.ready = ready;
  global.SiteTools.config = global.SiteToolsConfig || null;
  global.SiteTools.toast = global.SiteToolsToast || null;
  global.SiteTools.loading = global.SiteToolsLoading || null;
  global.SiteTools.http = global.SiteToolsHttp || null;
})(window);
