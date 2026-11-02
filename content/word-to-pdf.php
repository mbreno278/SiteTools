<div class="word-pdf" id="word-pdf">

    <div class="word-pdf__dropzone" id="wp-dropzone">
        <input type="file" id="wp-file" accept=".doc,.docx,application/msword,application/vnd.openxmlformats-officedocument.wordprocessingml.document" hidden>
        <span class="material-symbols-outlined word-pdf__icon" aria-hidden="true">description</span>
        <strong>Solte o Word aqui</strong>
        <span>ou clique para selecionar</span>
        <small>DOC / DOCX · Máximo 20 MB</small>
    </div>

    <div class="word-pdf__error" id="wp-error" role="alert" hidden></div>

    <div class="word-pdf__workspace" id="wp-workspace" hidden>
        <div class="word-pdf__file-card">
            <span class="material-symbols-outlined" aria-hidden="true">article</span>
            <div>
                <strong id="wp-filename">—</strong>
                <span id="wp-filesize">—</span>
            </div>
            <button type="button" class="word-pdf__icon-btn" id="wp-remove" aria-label="Remover">
                <span class="material-symbols-outlined" aria-hidden="true">close</span>
            </button>
        </div>

        <button type="button" class="button button--primary word-pdf__action" id="wp-submit">
            <span class="material-symbols-outlined" aria-hidden="true">picture_as_pdf</span>
            Converter para PDF
        </button>
    </div>

    <div class="word-pdf__loading" id="wp-loading" hidden>
        <span class="word-pdf__spinner"></span>
        <strong>Convertendo Word para PDF...</strong>
        <span>Isso pode levar alguns segundos. Não feche a página.</span>
    </div>

    <p class="word-pdf__hint">
        <span class="material-symbols-outlined" aria-hidden="true">info</span>
        Requer LibreOffice instalado no servidor. Formatação, imagens e tabelas costumam ser preservadas.
    </p>

</div>
