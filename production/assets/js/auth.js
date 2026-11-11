"use strict";

(() => {
    const modals = {
        login: document.getElementById("auth-modal-login"),
        register: document.getElementById("auth-modal-register"),
        forgot: document.getElementById("auth-modal-forgot"),
    };

    /** URL absoluta para tools/ a partir da pasta do site */
    function toolsUrl(file) {
        const path = window.location.pathname;
        // .../page.php ou .../index.php ou .../algo.php → pasta base
        let base = path.replace(/\/[^/]*\.php.*$/i, "/");
        if (!base.endsWith("/")) base += "/";
        return base + "tools/" + file;
    }

    function csrfToken() {
        return document.querySelector('meta[name="csrf-token"]')?.getAttribute("content") || "";
    }

    function openModal(which) {
        closeAll();
        const modal = modals[which] || modals.login;
        if (!modal) return;
        modal.hidden = false;
        modal.setAttribute("aria-hidden", "false");
        document.body.classList.add("auth-modal-open");
        const first = modal.querySelector("input:not([type=hidden])");
        if (first) setTimeout(() => first.focus(), 50);
    }

    function closeAll() {
        Object.values(modals).forEach((m) => {
            if (!m) return;
            m.hidden = true;
            m.setAttribute("aria-hidden", "true");
        });
        document.body.classList.remove("auth-modal-open");
        ["login-error", "register-error", "forgot-error", "forgot-success"].forEach((id) => {
            const el = document.getElementById(id);
            if (el) {
                el.hidden = true;
                el.textContent = "";
            }
        });
    }

    document.querySelectorAll("[data-auth-open]").forEach((btn) => {
        btn.addEventListener("click", () => openModal(btn.getAttribute("data-auth-open") || "login"));
    });

    document.querySelectorAll("[data-auth-close]").forEach((el) => {
        el.addEventListener("click", closeAll);
    });

    document.addEventListener("keydown", (e) => {
        if (e.key === "Escape") closeAll();
    });

    async function postForm(form, errorEl, successEl, submitBtn) {
        if (errorEl) {
            errorEl.hidden = true;
            errorEl.textContent = "";
        }
        if (successEl) {
            successEl.hidden = true;
            successEl.textContent = "";
        }
        if (submitBtn) submitBtn.disabled = true;

        const fd = new FormData(form);
        const action = form.getAttribute("action") || form.action;

        try {
            const res = await fetch(action, {
                method: "POST",
                body: fd,
                credentials: "same-origin",
            });
            const data = await res.json().catch(() => ({}));

            if (!res.ok || data.error) {
                throw new Error(data.error || "Não foi possível concluir a operação.");
            }

            if (successEl && data.message) {
                successEl.textContent = data.message;
                successEl.hidden = false;
                form.querySelectorAll("input:not([type=hidden])").forEach((i) => (i.value = ""));
                if (submitBtn) submitBtn.disabled = false;
                return;
            }

            window.location.reload();
        } catch (err) {
            if (errorEl) {
                errorEl.textContent = err.message || "Erro inesperado.";
                errorEl.hidden = false;
            }
            if (submitBtn) submitBtn.disabled = false;
        }
    }

    document.getElementById("auth-login-form")?.addEventListener("submit", (e) => {
        e.preventDefault();
        postForm(e.target, document.getElementById("login-error"), null, document.getElementById("login-submit"));
    });

    document.getElementById("auth-register-form")?.addEventListener("submit", (e) => {
        e.preventDefault();
        const p1 = document.getElementById("register-password")?.value || "";
        const p2 = document.getElementById("register-password-confirm")?.value || "";
        const err = document.getElementById("register-error");
        if (p1 !== p2) {
            err.textContent = "As senhas não coincidem.";
            err.hidden = false;
            return;
        }
        postForm(e.target, err, null, document.getElementById("register-submit"));
    });

    document.getElementById("auth-forgot-form")?.addEventListener("submit", (e) => {
        e.preventDefault();
        postForm(
            e.target,
            document.getElementById("forgot-error"),
            document.getElementById("forgot-success"),
            document.getElementById("forgot-submit")
        );
    });

    // Logout: intercepta o form se existir; senão usa botão
    const logoutForm = document.getElementById("auth-logout-form");
    if (logoutForm) {
        logoutForm.addEventListener("submit", async (e) => {
            e.preventDefault();
            const token = csrfToken();
            const fd = new FormData(logoutForm);
            fd.set("_ajax", "1");
            if (token && !fd.has("_csrf")) fd.set("_csrf", token);

            try {
                await fetch(toolsUrl("logout.php"), {
                    method: "POST",
                    body: fd,
                    credentials: "same-origin",
                    headers: token ? { "X-CSRF-Token": token, Accept: "application/json" } : { Accept: "application/json" },
                });
            } catch (_) {
                /* mesmo com erro de rede, tenta limpar a UI */
            }
            // Força saída da área logada
            window.location.href = toolsUrl("logout.php").replace(/tools\/logout\.php$/, "index.php");
            window.location.reload();
        });
    } else {
        document.getElementById("auth-logout-btn")?.addEventListener("click", async () => {
            const token = csrfToken();
            const fd = new FormData();
            fd.append("_ajax", "1");
            if (token) fd.append("_csrf", token);

            try {
                await fetch(toolsUrl("logout.php"), {
                    method: "POST",
                    body: fd,
                    credentials: "same-origin",
                    headers: token ? { "X-CSRF-Token": token, Accept: "application/json" } : { Accept: "application/json" },
                });
            } catch (_) {}
            window.location.reload();
        });
    }
})();
