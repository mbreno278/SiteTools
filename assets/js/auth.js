"use strict";

(() => {
    const loginModal = document.getElementById("auth-modal-login");
    const registerModal = document.getElementById("auth-modal-register");
    const loginForm = document.getElementById("auth-login-form");
    const registerForm = document.getElementById("auth-register-form");
    const loginError = document.getElementById("login-error");
    const registerError = document.getElementById("register-error");
    const logoutBtn = document.getElementById("auth-logout-btn");

    function openModal(which) {
        closeAll();
        const modal = which === "register" ? registerModal : loginModal;
        if (!modal) return;
        modal.hidden = false;
        modal.setAttribute("aria-hidden", "false");
        document.body.classList.add("auth-modal-open");
        const first = modal.querySelector("input");
        if (first) setTimeout(() => first.focus(), 50);
    }

    function closeAll() {
        [loginModal, registerModal].forEach((m) => {
            if (!m) return;
            m.hidden = true;
            m.setAttribute("aria-hidden", "true");
        });
        document.body.classList.remove("auth-modal-open");
        if (loginError) { loginError.hidden = true; loginError.textContent = ""; }
        if (registerError) { registerError.hidden = true; registerError.textContent = ""; }
    }

    document.querySelectorAll("[data-auth-open]").forEach((btn) => {
        btn.addEventListener("click", () => {
            openModal(btn.getAttribute("data-auth-open") || "login");
        });
    });

    document.querySelectorAll("[data-auth-close]").forEach((el) => {
        el.addEventListener("click", closeAll);
    });

    document.addEventListener("keydown", (e) => {
        if (e.key === "Escape") closeAll();
    });

    async function submitForm(form, errorEl, submitBtn) {
        if (!form) return;
        errorEl.hidden = true;
        errorEl.textContent = "";
        submitBtn.disabled = true;

        const fd = new FormData(form);

        try {
            const res = await fetch(form.action, {
                method: "POST",
                body: fd,
                credentials: "same-origin",
            });

            const data = await res.json().catch(() => ({}));

            if (!res.ok || data.error) {
                throw new Error(data.error || "Não foi possível concluir a operação.");
            }

            // Sucesso: recarrega para atualizar o header
            window.location.reload();
        } catch (err) {
            errorEl.textContent = err.message || "Erro inesperado.";
            errorEl.hidden = false;
            submitBtn.disabled = false;
        }
    }

    loginForm?.addEventListener("submit", (e) => {
        e.preventDefault();
        submitForm(loginForm, loginError, document.getElementById("login-submit"));
    });

    registerForm?.addEventListener("submit", (e) => {
        e.preventDefault();
        const p1 = document.getElementById("register-password")?.value || "";
        const p2 = document.getElementById("register-password-confirm")?.value || "";
        if (p1 !== p2) {
            registerError.textContent = "As senhas não coincidem.";
            registerError.hidden = false;
            return;
        }
        submitForm(registerForm, registerError, document.getElementById("register-submit"));
    });

    logoutBtn?.addEventListener("click", async () => {
        const token = document.querySelector('meta[name="csrf-token"]')?.content || "";
        const fd = new FormData();
        if (token) fd.append("_csrf", token);

        try {
            await fetch("tools/logout.php", {
                method: "POST",
                body: fd,
                credentials: "same-origin",
                headers: token ? { "X-CSRF-Token": token } : {},
            });
        } catch (_) {
            /* ignore */
        }
        window.location.reload();
    });
})();
