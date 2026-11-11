(() => {
    "use strict";

    /* ========================================
       UTILITÁRIOS GLOBAIS DA PÁGINA DE FERRAMENTA
       (lógica específica de cada tool fica no seu JS)
    ======================================== */

    const $ = (selector, root = document) => root.querySelector(selector);

    const setIcon = (button, name) => {
        const icon = $("span", button);
        if (icon) {
            icon.textContent = name;
        }
    };

    /* ========================================
       MENU LATERAL
    ======================================== */

    const menuToggle = $("#menu-toggle");
    const sidebar = $("#site-sidebar");

    if (menuToggle && sidebar) {
        const setMenuState = (isOpen) => {
            sidebar.classList.toggle("is-open", isOpen);
            menuToggle.setAttribute("aria-expanded", String(isOpen));
            setIcon(menuToggle, isOpen ? "close" : "menu");
        };

        menuToggle.addEventListener("click", () => {
            const isOpen = !sidebar.classList.contains("is-open");
            setMenuState(isOpen);
        });

        document.addEventListener("keydown", (event) => {
            if (event.key === "Escape" && sidebar.classList.contains("is-open")) {
                setMenuState(false);
                menuToggle.focus();
            }
        });
    }
})();
