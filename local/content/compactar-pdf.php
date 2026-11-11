<div class="pdf-tool" id="pdf-compress">

    <div class="pdf-tool__dropzone" id="pc-dropzone">
        <input type="file" id="pc-file" accept="application/pdf,.pdf" hidden>
        <span class="material-symbols-outlined pdf-tool__icon" aria-hidden="true">picture_as_pdf</span>
        <strong>Solte o PDF aqui</strong>
        <span>ou clique para selecionar</span>
        <small>PDF · Máximo 30 MB</small>
    </div>

    <div class="pdf-tool__error" id="pc-error" role="alert" hidden></div>

    <div class="pdf-tool__workspace" id="pc-workspace" hidden>
        <div class="pdf-tool__file-card">
            <span class="material-symbols-outlined" aria-hidden="true">description</span>
            <div>
                <strong id="pc-filename">—</strong>
                <span id="pc-filesize">—</span>
            </div>
            <button type="button" class="pdf-tool__icon-btn" id="pc-remove" aria-label="Remover">
                <span class="material-symbols-outlined" aria-hidden="true">close</span>
            </button>
        </div>

        <div class="pdf-tool__field">
            <label for="pc-quality">Nível de compressão</label>
            <select id="pc-quality" class="pdf-tool__select">
                <option value="screen">Alta compressão (menor qualidade)</option>
                <option value="ebook" selected>Média (recomendado)</option>
                <option value="printer">Baixa compressão (melhor qualidade)</option>
                <option value="prepress">Mínima (quase original)</option>
            </select>
        </div>

        <button type="button" class="button button--primary pdf-tool__action" id="pc-submit">
            <span class="material-symbols-outlined" aria-hidden="true">compress</span>
            Compactar PDF
        </button>
    </div>

    <div class="pdf-tool__loading" id="pc-loading" hidden>
        <span class="pdf-tool__spinner"></span>
        <strong>Compactando PDF...</strong>
        <span>Aguarde alguns segundos.</span>
    </div>

    <p class="pdf-tool__privacy">
        <span class="material-symbols-outlined" aria-hidden="true">lock</span>
        O arquivo é processado no servidor e removido após o download.
    </p>
</div>
