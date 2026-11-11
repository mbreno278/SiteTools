<div class="yt-downloader" id="yt-downloader">

    <!-- Estado inicial: formulário de URL -->
    <div class="yt-downloader__form-panel" id="yt-form-panel">

        <div class="yt-downloader__input-group">
            <label class="yt-downloader__label" for="yt-url">
                Cole o link do vídeo do YouTube
            </label>

            <div class="yt-downloader__input-wrap">
                <span class="material-symbols-outlined yt-downloader__input-icon" aria-hidden="true">link</span>
                <input
                    type="url"
                    id="yt-url"
                    class="yt-downloader__input"
                    placeholder="https://www.youtube.com/watch?v=..."
                    autocomplete="off"
                    spellcheck="false"
                >
                <button
                    type="button"
                    class="yt-downloader__paste-btn"
                    id="yt-paste"
                    title="Colar da área de transferência"
                    aria-label="Colar link"
                >
                    <span class="material-symbols-outlined" aria-hidden="true">content_paste</span>
                </button>
            </div>

            <p class="yt-downloader__hint">
                Suporta links de vídeo, shorts e playlists (apenas o primeiro vídeo).
            </p>
        </div>

        <button
            type="button"
            class="button button--primary yt-downloader__submit"
            id="yt-submit"
        >
            <span class="material-symbols-outlined" aria-hidden="true">search</span>
            Analisar vídeo
        </button>

        <div
            class="yt-downloader__error"
            id="yt-error"
            role="alert"
            aria-live="polite"
            hidden
        ></div>
    </div>

    <!-- Estado de carregamento -->
    <div class="yt-downloader__loading" id="yt-loading" hidden>
        <span class="yt-downloader__spinner" aria-hidden="true"></span>
        <strong>Buscando informações do vídeo...</strong>
        <span>Isso pode levar alguns segundos.</span>
    </div>

    <!-- Resultado: preview + opções de download -->
    <div class="yt-downloader__result" id="yt-result" hidden>

        <div class="yt-downloader__video-card">
            <div class="yt-downloader__thumbnail-wrap">
                <img
                    id="yt-thumbnail"
                    class="yt-downloader__thumbnail"
                    src=""
                    alt="Thumbnail do vídeo"
                >
                <span class="yt-downloader__duration" id="yt-duration"></span>
            </div>

            <div class="yt-downloader__video-info">
                <h2 class="yt-downloader__title" id="yt-title">—</h2>
                <p class="yt-downloader__channel" id="yt-channel">—</p>

                <div class="yt-downloader__meta">
                    <span class="yt-downloader__meta-item">
                        <span class="material-symbols-outlined" aria-hidden="true">visibility</span>
                        <span id="yt-views">—</span>
                    </span>
                    <span class="yt-downloader__meta-item">
                        <span class="material-symbols-outlined" aria-hidden="true">schedule</span>
                        <span id="yt-upload">—</span>
                    </span>
                </div>

                <button
                    type="button"
                    class="button yt-downloader__change-btn"
                    id="yt-change"
                >
                    <span class="material-symbols-outlined" aria-hidden="true">link</span>
                    Usar outro link
                </button>
            </div>
        </div>

        <div class="yt-downloader__formats">
            <h3 class="yt-downloader__formats-title">
                <span class="material-symbols-outlined" aria-hidden="true">download</span>
                Escolha a qualidade
            </h3>

            <div class="yt-downloader__format-list" id="yt-format-list">
                <!-- Preenchido via JS -->
            </div>

            <p class="yt-downloader__privacy">
                <span class="material-symbols-outlined" aria-hidden="true">info</span>
                O download é processado no servidor. Use apenas conteúdo que você tem autorização para baixar.
            </p>
        </div>
    </div>

    <!-- Estado de download em andamento -->
    <div class="yt-downloader__downloading" id="yt-downloading" hidden>
        <span class="yt-downloader__spinner" aria-hidden="true"></span>
        <strong id="yt-download-status">Preparando download...</strong>
        <span>Não feche esta página.</span>
        <div class="yt-downloader__progress-bar">
            <div class="yt-downloader__progress-fill" id="yt-progress"></div>
        </div>
    </div>

</div>
