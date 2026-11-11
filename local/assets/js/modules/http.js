/**
 * Fetch helper com CSRF e parsing JSON seguro.
 * Depende de: config.js (opcional), csrf.js (opcional — header já injetado).
 */
(function (global) {
  "use strict";

  function csrfToken() {
    if (global.SiteToolsConfig && global.SiteToolsConfig.getCsrfToken) {
      return global.SiteToolsConfig.getCsrfToken();
    }
    var meta = document.querySelector('meta[name="csrf-token"]');
    return meta ? meta.getAttribute("content") || "" : "";
  }

  /**
   * @param {string} url
   * @param {RequestInit & {json?: any, formData?: FormData}} options
   */
  async function request(url, options) {
    options = options || {};
    var headers = new Headers(options.headers || {});
    var method = String(options.method || "GET").toUpperCase();

    if (!["GET", "HEAD", "OPTIONS"].includes(method) && !headers.has("X-CSRF-Token")) {
      headers.set("X-CSRF-Token", csrfToken());
    }

    var body = options.body;
    if (options.json !== undefined) {
      headers.set("Content-Type", "application/json");
      body = JSON.stringify(options.json);
    } else if (options.formData instanceof FormData) {
      body = options.formData;
      if (!body.has("_csrf")) {
        body.append("_csrf", csrfToken());
      }
    }

    var res = await fetch(url, {
      method: method,
      headers: headers,
      body: body,
      credentials: "same-origin",
      signal: options.signal,
    });

    var data = null;
    var text = await res.text();
    if (text) {
      try {
        data = JSON.parse(text);
      } catch (_) {
        data = { raw: text };
      }
    }

    return { ok: res.ok, status: res.status, data: data, response: res };
  }

  /**
   * Mensagem amigável a partir de resposta da API.
   */
  function errorMessage(result, fallback) {
    if (!result) return fallback || "Falha na requisição.";
    var d = result.data || {};
    return (
      d.message ||
      d.error ||
      fallback ||
      "Não foi possível concluir a operação."
    );
  }

  global.SiteToolsHttp = {
    request: request,
    errorMessage: errorMessage,
    csrfToken: csrfToken,
  };
})(window);
