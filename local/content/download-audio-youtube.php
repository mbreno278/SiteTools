<div class="yt-audio" id="yt-audio">

    <!-- Estado inicial: formulário de URL -->
    <div class="yt-audio__form-panel" id="yta-form-panel">

        <div class="yt-audio__input-group">
            <label class="yt-audio__label" for="yta-url">
                Cole o link do vídeo do YouTube
            </label>

            <div class="yt-audio__input-wrap">
                <span class="material-symbols-outlined yt-audio__input-icon" aria-hidden="true">link</span>
                <input
                    type="url"
                    id="yta-url"
                    class="yt-audio__input"
                    placeholder="https://www.youtube.com/watch?v=..."
                    autocomplete="off"
                    spellcheck="false"
                >
                <button
                    type="button"
                    class="yt-audio__paste-btn"
                    id="yta-paste"
                    title="Colar da área de transferência"
                    aria-label="Colar link"
                >
                    <span class="material-symbols-outlined" aria-hidden="true">content_paste</span>
                </button>
            </div>

            <p class="yt-audio__hint">
                Extrai apenas o áudio do vídeo. Suporta links de vídeo, shorts e music.youtube.com.
            </p>
        </div>

        <button
            type="button"
            class="button button--primary yt-audio__submit"
            id="yta-submit"
        >
            <span class="material-symbols-outlined" aria-hidden="true">search</span>
            Analisar áudio
        </button>

        <div
            class="yt-audio__error"
            id="yta-error"
            role="alert"
            aria-live="polite"
            hidden
        ></div>
    </div>

    <!-- Estado de carregamento -->
    <div class="yt-audio__loading" id="yta-loading" hidden>
        <span class="yt-audio__spinner" aria-hidden="true"></span>
        <strong>Buscando informações do áudio...</strong>
        <span>Isso pode levar alguns segundos.</span>
    </div>

    <!-- Resultado: preview + opções de download -->
    <div class="yt-audio__result" id="yta-result" hidden>

        <div class="yt-audio__video-card">
            <div class="yt-audio__thumbnail-wrap">
                <img
                    id="yta-thumbnail"
                    class="yt-audio__thumbnail"
                    src=""
                    alt="Capa do áudio"
                >
                <span class="yt-audio__duration" id="yta-duration"></span>
                <span class="yt-audio__play-icon material-symbols-outlined" aria-hidden="true">music_note</span>
            </div>

            <div class="yt-audio__video-info">
                <h2 class="yt-audio__title" id="yta-title">—</h2>
                <p class="yt-audio__channel" id="yta-channel">—</p>

                <div class="yt-audio__meta">
                    <span class="yt-audio__meta-item">
                        <span class="material-symbols-outlined" aria-hidden="true">visibility</span>
                        <span id="yta-views">—</span>
                    </span>
                    <span class="yt-audio__meta-item">
                        <span class="material-symbols-outlined" aria-hidden="true">schedule</span>
                        <span id="yta-upload">—</span>
                    </span>
                </div>

                <button
                    type="button"
                    class="button yt-audio__change-btn"
                    id="yta-change"
                >
                    <span class="material-symbols-outlined" aria-hidden="true">link</span>
                    Usar outro link
                </button>
            </div>
        </div>

        <div class="yt-audio__formats">
            <h3 class="yt-audio__formats-title">
                <span class="material-symbols-outlined" aria-hidden="true">headphones</span>
                Escolha a qualidade do áudio
            </h3>

            <div class="yt-audio__format-list" id="yta-format-list">
                <!-- Preenchido via JS -->
            </div>

            <p class="yt-audio__privacy">
                <span class="material-symbols-outlined" aria-hidden="true">info</span>
                O áudio é extraído e convertido no servidor. Use apenas conteúdo que você tem autorização para baixar.
            </p>
        </div>
    </div>

    <!-- Estado de download em andamento -->
    <div class="yt-audio__downloading" id="yta-downloading" hidden>
        <span class="yt-audio__spinner" aria-hidden="true"></span>
        <strong id="yta-download-status">Preparando áudio...</strong>
        <span>Não feche esta página. A conversão pode levar alguns segundos.</span>
        <div class="yt-audio__progress-bar">
            <div class="yt-audio__progress-fill" id="yta-progress"></div>
        </div>
    </div>

</div>
