<div class="ig-downloader" id="ig-downloader">

    <!-- Estado inicial: formulário de URL -->
    <div class="ig-downloader__form-panel" id="ig-form-panel">

        <div class="ig-downloader__input-group">
            <label class="ig-downloader__label" for="ig-url">
                Cole o link do Instagram
            </label>

            <div class="ig-downloader__input-wrap">
                <span class="material-symbols-outlined ig-downloader__input-icon" aria-hidden="true">link</span>
                <input
                    type="url"
                    id="ig-url"
                    class="ig-downloader__input"
                    placeholder="https://www.instagram.com/reel/..."
                    autocomplete="off"
                    spellcheck="false"
                >
                <button
                    type="button"
                    class="ig-downloader__paste-btn"
                    id="ig-paste"
                    title="Colar da área de transferência"
                    aria-label="Colar link"
                >
                    <span class="material-symbols-outlined" aria-hidden="true">content_paste</span>
                </button>
            </div>

            <p class="ig-downloader__hint">
                Suporta Reels, posts com vídeo e IGTV. Posts só com imagem não são suportados. Contas privadas e Stories podem não funcionar.
            </p>
        </div>

        <button
            type="button"
            class="button button--primary ig-downloader__submit"
            id="ig-submit"
        >
            <span class="material-symbols-outlined" aria-hidden="true">search</span>
            Analisar vídeo
        </button>

        <div
            class="ig-downloader__error"
            id="ig-error"
            role="alert"
            aria-live="polite"
            hidden
        ></div>
    </div>

    <!-- Estado de carregamento -->
    <div class="ig-downloader__loading" id="ig-loading" hidden>
        <span class="ig-downloader__spinner" aria-hidden="true"></span>
        <strong>Buscando informações do vídeo...</strong>
        <span>Isso pode levar alguns segundos.</span>
    </div>

    <!-- Resultado -->
    <div class="ig-downloader__result" id="ig-result" hidden>

        <div class="ig-downloader__video-card">
            <div class="ig-downloader__thumbnail-wrap">
                <img
                    id="ig-thumbnail"
                    class="ig-downloader__thumbnail"
                    src=""
                    alt="Preview do vídeo"
                >
                <span class="ig-downloader__duration" id="ig-duration"></span>
                <span class="ig-downloader__badge" id="ig-type-badge">Reel</span>
            </div>

            <div class="ig-downloader__video-info">
                <h2 class="ig-downloader__title" id="ig-title">—</h2>
                <p class="ig-downloader__channel" id="ig-channel">—</p>

                <div class="ig-downloader__meta">
                    <span class="ig-downloader__meta-item" id="ig-likes-wrap" hidden>
                        <span class="material-symbols-outlined" aria-hidden="true">favorite</span>
                        <span id="ig-likes">—</span>
                    </span>
                    <span class="ig-downloader__meta-item" id="ig-views-wrap" hidden>
                        <span class="material-symbols-outlined" aria-hidden="true">visibility</span>
                        <span id="ig-views">—</span>
                    </span>
                </div>

                <button
                    type="button"
                    class="button ig-downloader__change-btn"
                    id="ig-change"
                >
                    <span class="material-symbols-outlined" aria-hidden="true">link</span>
                    Usar outro link
                </button>
            </div>
        </div>

        <div class="ig-downloader__formats">
            <h3 class="ig-downloader__formats-title">
                <span class="material-symbols-outlined" aria-hidden="true">download</span>
                Escolha a qualidade
            </h3>

            <div class="ig-downloader__format-list" id="ig-format-list">
                <!-- Preenchido via JS -->
            </div>

            <p class="ig-downloader__privacy">
                <span class="material-symbols-outlined" aria-hidden="true">info</span>
                Use apenas conteúdo que você tem autorização para baixar. Respeite os direitos autorais e os termos do Instagram.
            </p>
        </div>
    </div>

    <!-- Estado de download -->
    <div class="ig-downloader__downloading" id="ig-downloading" hidden>
        <span class="ig-downloader__spinner" aria-hidden="true"></span>
        <strong id="ig-download-status">Preparando download...</strong>
        <span>Não feche esta página.</span>
        <div class="ig-downloader__progress-bar">
            <div class="ig-downloader__progress-fill" id="ig-progress"></div>
        </div>
    </div>

</div>
