<div class="tt-downloader" id="tt-downloader">

    <!-- Estado inicial -->
    <div class="tt-downloader__form-panel" id="tt-form-panel">

        <div class="tt-downloader__input-group">
            <label class="tt-downloader__label" for="tt-url">
                Cole o link do TikTok
            </label>

            <div class="tt-downloader__input-wrap">
                <span class="material-symbols-outlined tt-downloader__input-icon" aria-hidden="true">link</span>
                <input
                    type="url"
                    id="tt-url"
                    class="tt-downloader__input"
                    placeholder="https://www.tiktok.com/@usuario/video/..."
                    autocomplete="off"
                    spellcheck="false"
                >
                <button
                    type="button"
                    class="tt-downloader__paste-btn"
                    id="tt-paste"
                    title="Colar da área de transferência"
                    aria-label="Colar link"
                >
                    <span class="material-symbols-outlined" aria-hidden="true">content_paste</span>
                </button>
            </div>

            <p class="tt-downloader__hint">
                Suporta links de vídeo do TikTok (tiktok.com, vm.tiktok.com e vt.tiktok.com).
            </p>
        </div>

        <button
            type="button"
            class="button button--primary tt-downloader__submit"
            id="tt-submit"
        >
            <span class="material-symbols-outlined" aria-hidden="true">search</span>
            Analisar vídeo
        </button>

        <div
            class="tt-downloader__error"
            id="tt-error"
            role="alert"
            aria-live="polite"
            hidden
        ></div>
    </div>

    <!-- Loading -->
    <div class="tt-downloader__loading" id="tt-loading" hidden>
        <span class="tt-downloader__spinner" aria-hidden="true"></span>
        <strong>Buscando informações do vídeo...</strong>
        <span>Isso pode levar alguns segundos.</span>
    </div>

    <!-- Resultado -->
    <div class="tt-downloader__result" id="tt-result" hidden>

        <div class="tt-downloader__video-card">
            <div class="tt-downloader__thumbnail-wrap">
                <img
                    id="tt-thumbnail"
                    class="tt-downloader__thumbnail"
                    src=""
                    alt="Preview do vídeo"
                >
                <span class="tt-downloader__duration" id="tt-duration"></span>
            </div>

            <div class="tt-downloader__video-info">
                <h2 class="tt-downloader__title" id="tt-title">—</h2>
                <p class="tt-downloader__channel" id="tt-channel">—</p>

                <div class="tt-downloader__meta">
                    <span class="tt-downloader__meta-item" id="tt-likes-wrap" hidden>
                        <span class="material-symbols-outlined" aria-hidden="true">favorite</span>
                        <span id="tt-likes">—</span>
                    </span>
                    <span class="tt-downloader__meta-item" id="tt-views-wrap" hidden>
                        <span class="material-symbols-outlined" aria-hidden="true">visibility</span>
                        <span id="tt-views">—</span>
                    </span>
                    <span class="tt-downloader__meta-item" id="tt-comments-wrap" hidden>
                        <span class="material-symbols-outlined" aria-hidden="true">chat_bubble</span>
                        <span id="tt-comments">—</span>
                    </span>
                </div>

                <button
                    type="button"
                    class="button tt-downloader__change-btn"
                    id="tt-change"
                >
                    <span class="material-symbols-outlined" aria-hidden="true">link</span>
                    Usar outro link
                </button>
            </div>
        </div>

        <div class="tt-downloader__formats">
            <h3 class="tt-downloader__formats-title">
                <span class="material-symbols-outlined" aria-hidden="true">download</span>
                Escolha a qualidade
            </h3>

            <div class="tt-downloader__format-list" id="tt-format-list"></div>

            <p class="tt-downloader__privacy">
                <span class="material-symbols-outlined" aria-hidden="true">info</span>
                Use apenas conteúdo que você tem autorização para baixar. Respeite os direitos autorais e os termos do TikTok.
            </p>
        </div>
    </div>

    <!-- Download em andamento -->
    <div class="tt-downloader__downloading" id="tt-downloading" hidden>
        <span class="tt-downloader__spinner" aria-hidden="true"></span>
        <strong id="tt-download-status">Preparando download...</strong>
        <span>Não feche esta página.</span>
        <div class="tt-downloader__progress-bar">
            <div class="tt-downloader__progress-fill" id="tt-progress"></div>
        </div>
    </div>

</div>
