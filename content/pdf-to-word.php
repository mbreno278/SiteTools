<div class="pdf-word" id="pdf-word">

    <div class="pdf-word__dropzone" id="pw-dropzone">
        <input type="file" id="pw-file" accept="application/pdf,.pdf" hidden>
        <span class="material-symbols-outlined pdf-word__icon" aria-hidden="true">picture_as_pdf</span>
        <strong>Solte o PDF aqui</strong>
        <span>ou clique para selecionar</span>
        <small>PDF · Máximo 20 MB · Texto selecionável funciona melhor</small>
    </div>

    <div class="pdf-word__error" id="pw-error" role="alert" hidden></div>

    <div class="pdf-word__workspace" id="pw-workspace" hidden>
        <div class="pdf-word__file-card">
            <span class="material-symbols-outlined" aria-hidden="true">description</span>
            <div>
                <strong id="pw-filename">—</strong>
                <span id="pw-filesize">—</span>
            </div>
            <button type="button" class="pdf-word__icon-btn" id="pw-remove" aria-label="Remover">
                <span class="material-symbols-outlined" aria-hidden="true">close</span>
            </button>
        </div>

        <button type="button" class="button button--primary pdf-word__action" id="pw-submit">
            <span class="material-symbols-outlined" aria-hidden="true">description</span>
            Converter para Word
        </button>
    </div>

    <div class="pdf-word__loading" id="pw-loading" hidden>
        <span class="pdf-word__spinner"></span>
        <strong>Convertendo PDF para Word...</strong>
        <span>PDFs grandes podem levar até 1–2 minutos. Não feche a página.</span>
    </div>

    <p class="pdf-word__hint">
        <span class="material-symbols-outlined" aria-hidden="true">info</span>
        Funciona melhor com PDFs de texto. PDFs só de imagem (escaneados) podem gerar documento quase vazio.
        Requer LibreOffice instalado no servidor.
    </p>

</div>
