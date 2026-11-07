/**
 * SiteTools — configuração central de URLs e endpoints.
 * Carregar ANTES dos demais scripts da aplicação.
 */
(function (global) {
  "use strict";

  function detectBase() {
    // data-base no <html> ou <body> tem prioridade
    var el = document.documentElement;
    var attr = el.getAttribute("data-base") || (document.body && document.body.getAttribute("data-base"));
    if (attr) {
      return attr.replace(/\/?$/, "/");
    }

    // Inferir a partir do path atual
    var path = window.location.pathname || "/";
    // Remove arquivo final
    if (/\.php$/i.test(path)) {
      path = path.replace(/\/[^/]+\.php$/i, "/");
    }
    // Se estiver em /account/ ou /admin/, sobe um nível
    path = path.replace(/\/(account|admin|tools|content)\/?$/i, "/");
    if (!path.endsWith("/")) path += "/";
    return path;
  }

  var base = detectBase();

  var config = {
    baseUrl: base,
    url: function (path) {
      path = String(path || "").replace(/^\//, "");
      return base + path;
    },
    endpoints: {
      login: "tools/login.php",
      logout: "tools/logout.php",
      register: "tools/cadastro.php",
      recover: "tools/recuperar-senha.php",
      reset: "tools/redefinir-senha.php",
      favorite: "account/api/favorite.php",
      imageApi: "tools/api-image.php",
      wordToPdf: "tools/word-to-pdf.php",
      pdfToWord: "tools/pdf-to-word.php",
    },
    csrfMeta: 'meta[name="csrf-token"]',
    getCsrfToken: function () {
      var meta = document.querySelector(this.csrfMeta);
      return meta ? meta.getAttribute("content") || "" : "";
    },
  };

  // Resolve endpoints absolutos
  Object.keys(config.endpoints).forEach(function (key) {
    var rel = config.endpoints[key];
    config.endpoints[key] = config.url(rel);
  });

  global.SiteToolsConfig = config;
})(window);
