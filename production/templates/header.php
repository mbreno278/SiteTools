<header class="header">

    <div class="header__column">
        <a class="header__logo" href="index.php" aria-label="SiteTools — Página inicial">

            <img
                class="page__logo"
                src="./assets/img/logo.png"
                alt=""
                width="48"
                height="48"
            >

            <div class="header__title">
                <span class="page__title">SiteTools</span>
                <span class="page__subtitle">
                    Ferramentas online para o seu dia a dia
                </span>
            </div>

        </a>
    </div>

    <div class="header__column">

        <form
            class="form__search"
            action="tools.php"
            method="get"
            role="search"
        >

            <label for="search">Buscar ferramentas</label>

            <input
                type="search"
                id="search"
                name="search"
                placeholder="Buscar ferramentas..."
                autocomplete="off"
                required
            >

            <button type="submit" aria-label="Pesquisar">
                <span
                    class="material-symbols-outlined"
                    aria-hidden="true"
                >search</span>
            </button>

        </form>

    </div>

    <div class="header__column header__column--auth">

        <button
            class="button button-mode-light"
            id="theme-toggle"
            type="button"
            aria-label="Ativar tema escuro"
            aria-pressed="false"
            title="Ativar tema escuro">
            <span
                class="material-symbols-outlined"
                aria-hidden="true">dark_mode</span>
        </button>
    </div>

</header>
