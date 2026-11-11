
(() => {
    "use strict";

    const root = document.documentElement;
    const storageKey = "sitetools-theme";
    const themeToggle = document.querySelector("#theme-toggle");

    function getSavedTheme() {
        try {
            return localStorage.getItem(storageKey);
        } catch {
            return null;
        }
    }

    function applyTheme(theme) {
        const isDark = theme === "dark";

        root.dataset.theme = isDark ? "dark" : "light";

        if (!themeToggle) return;

        const icon = themeToggle.querySelector(
            ".material-symbols-outlined"
        );

        if (icon) {
            icon.textContent = isDark ? "light_mode" : "dark_mode";
        }

        themeToggle.setAttribute(
            "aria-label",
            isDark ? "Ativar tema claro" : "Ativar tema escuro"
        );

        themeToggle.setAttribute("aria-pressed", String(isDark));

        themeToggle.title = isDark
            ? "Ativar tema claro"
            : "Ativar tema escuro";
    }

    function saveTheme(theme) {
        try {
            localStorage.setItem(storageKey, theme);
        } catch {
            // A alternância continua funcionando sem armazenamento.
        }
    }

    applyTheme(getSavedTheme() === "dark" ? "dark" : "light");

    themeToggle?.addEventListener("click", () => {
        const currentTheme = root.dataset.theme;
        const nextTheme = currentTheme === "dark" ? "light" : "dark";

        applyTheme(nextTheme);
        saveTheme(nextTheme);
    });
})();
