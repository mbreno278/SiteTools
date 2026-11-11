(() => {
    const root = document.documentElement;
    const toggle = document.getElementById('themeToggle');
    const sidebar = document.getElementById('sidebar');
    const backdrop = document.getElementById('sidebarBackdrop');
    const menuButton = document.getElementById('sidebarToggle');

    const savedTheme = localStorage.getItem('sitetools-admin-theme');
    if (savedTheme) root.dataset.theme = savedTheme;

    toggle?.addEventListener('click', () => {
        const next = root.dataset.theme === 'dark' ? 'light' : 'dark';
        root.dataset.theme = next;
        localStorage.setItem('sitetools-admin-theme', next);
        const icon = toggle.querySelector('.material-symbols-rounded');
        if (icon) icon.textContent = next === 'dark' ? 'light_mode' : 'dark_mode';
    });

    const closeSidebar = () => {
        sidebar?.classList.remove('is-open');
        backdrop?.classList.remove('is-visible');
    };
    menuButton?.addEventListener('click', () => {
        sidebar?.classList.toggle('is-open');
        backdrop?.classList.toggle('is-visible');
    });
    backdrop?.addEventListener('click', closeSidebar);

    document.querySelectorAll('[data-table-search]').forEach(input => {
        input.addEventListener('input', () => {
            const table = document.getElementById(input.dataset.tableSearch);
            if (!table) return;
            const query = input.value.toLocaleLowerCase('pt-BR');
            table.querySelectorAll('tbody tr').forEach(row => {
                row.hidden = !row.textContent.toLocaleLowerCase('pt-BR').includes(query);
            });
        });
    });
})();
