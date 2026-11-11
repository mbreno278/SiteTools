<div class="pdf-tool" id="pdf-split">

    <div class="pdf-tool__dropzone" id="ps-dropzone">
        <input type="file" id="ps-file" accept="application/pdf,.pdf" hidden>
        <span class="material-symbols-outlined pdf-tool__icon" aria-hidden="true">picture_as_pdf</span>
        <strong>Solte o PDF aqui</strong>
        <span>ou clique para selecionar</span>
        <small>PDF · Máximo 30 MB</small>
    </div>

    <div class="pdf-tool__error" id="ps-error" role="alert" hidden></div>

    <div class="pdf-tool__workspace" id="ps-workspace" hidden>
        <div class="pdf-tool__file-card">
            <span class="material-symbols-outlined" aria-hidden="true">description</span>
            <div>
                <strong id="ps-filename">—</strong>
                <span id="ps-filesize">—</span>
            </div>
            <button type="button" class="pdf-tool__icon-btn" id="ps-remove" aria-label="Remover">
                <span class="material-symbols-outlined" aria-hidden="true">close</span>
            </button>
        </div>

        <div class="pdf-tool__field">
            <label for="ps-mode">Modo de separação</label>
            <select id="ps-mode" class="pdf-tool__select">
                <option value="each">Uma página por arquivo</option>
                <option value="range">Intervalo de páginas</option>
            </select>
        </div>

        <div class="pdf-tool__field" id="ps-range-wrap" hidden>
            <label for="ps-range">Páginas (ex: 1-3 ou 1,3,5)</label>
            <input type="text" id="ps-range" class="pdf-tool__input" placeholder="1-3">
        </div>

        <button type="button" class="button button--primary pdf-tool__action" id="ps-submit">
            <span class="material-symbols-outlined" aria-hidden="true">call_split</span>
            Separar PDF
        </button>
    </div>

    <div class="pdf-tool__loading" id="ps-loading" hidden>
        <span class="pdf-tool__spinner"></span>
        <strong>Separando PDF...</strong>
        <span>Aguarde alguns segundos.</span>
    </div>

    <p class="pdf-tool__privacy">
        <span class="material-symbols-outlined" aria-hidden="true">lock</span>
        O arquivo é processado no servidor e removido após o download.
    </p>
</div>
