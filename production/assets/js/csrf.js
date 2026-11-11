"use strict";

/**
 * SiteTools — anexa automaticamente o token CSRF em requisições POST/PUT/PATCH/DELETE.
 * Depende de: <meta name="csrf-token" content="...">
 */
(() => {
    const meta = document.querySelector('meta[name="csrf-token"]');
    const token = meta && meta.getAttribute("content");

    if (!token || typeof window.fetch !== "function") {
        return;
    }

    const originalFetch = window.fetch.bind(window);

    window.fetch = function (input, init) {
        init = init ? { ...init } : {};

        const method = String(init.method || "GET").toUpperCase();
        const needsCsrf = !["GET", "HEAD", "OPTIONS"].includes(method);

        if (needsCsrf) {
            const headers = new Headers(init.headers || {});

            if (!headers.has("X-CSRF-Token")) {
                headers.set("X-CSRF-Token", token);
            }

            init.headers = headers;

            if (init.body instanceof FormData && !init.body.has("_csrf")) {
                init.body.append("_csrf", token);
            }
        }

        return originalFetch(input, init);
    };

    // Expõe para uso manual se necessário
    window.SiteToolsCSRF = {
        token: token,
        headerName: "X-CSRF-Token",
    };
})();
