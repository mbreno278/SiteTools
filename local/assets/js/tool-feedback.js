/**
 * Helpers para páginas de ferramentas: loading + toast + erros de API.
 * Uso típico nos scripts por ferramenta:
 *
 *   const btn = form.querySelector('[type=submit]');
 *   SiteToolsLoading.button(btn, true, 'Processando…');
 *   SiteToolsLoading.overlay(true, 'Convertendo arquivo…');
 *   try { ... } catch(e) { SiteToolsToast.error(e.message); }
 *   finally {
 *     SiteToolsLoading.button(btn, false);
 *     SiteToolsLoading.overlay(false);
 *   }
 */
(function (global) {
  "use strict";

  function withToolRequest(button, overlayMessage, fn) {
    return async function () {
      if (global.SiteToolsLoading) {
        global.SiteToolsLoading.button(button, true, "Processando…");
        if (overlayMessage) global.SiteToolsLoading.overlay(true, overlayMessage);
      }
      try {
        return await fn();
      } catch (err) {
        var msg = (err && err.message) || "Falha ao processar.";
        if (global.SiteToolsToast) global.SiteToolsToast.error(msg);
        else alert(msg);
        throw err;
      } finally {
        if (global.SiteToolsLoading) {
          global.SiteToolsLoading.button(button, false);
          global.SiteToolsLoading.overlay(false);
        }
      }
    };
  }

  global.SiteToolsToolFeedback = {
    withToolRequest: withToolRequest,
  };
})(window);
