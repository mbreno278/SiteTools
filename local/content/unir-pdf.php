<div class="pdf-tool" id="pdf-merge">

    <div class="pdf-tool__dropzone" id="pm-dropzone">
        <input type="file" id="pm-files" accept="application/pdf,.pdf" multiple hidden>
        <span class="material-symbols-outlined pdf-tool__icon" aria-hidden="true">picture_as_pdf</span>
        <strong>Solte os PDFs aqui</strong>
        <span>ou clique para selecionar (mínimo 2 arquivos)</span>
        <small>PDF · Máximo 30 MB por arquivo</small>
    </div>

    <div class="pdf-tool__error" id="pm-error" role="alert" hidden></div>

    <div class="pdf-tool__workspace" id="pm-workspace" hidden>
        <p class="pdf-tool__hint-order">A ordem abaixo será a ordem no PDF final. Arraste para reordenar.</p>
        <ul class="pdf-tool__file-list" id="pm-file-list"></ul>

        <div class="pdf-tool__actions-row">
            <button type="button" class="button pdf-tool__secondary" id="pm-add-more">
                <span class="material-symbols-outlined" aria-hidden="true">add</span>
                Adicionar mais
            </button>
            <button type="button" class="button button--primary pdf-tool__action" id="pm-submit">
                <span class="material-symbols-outlined" aria-hidden="true">merge</span>
                Unir PDFs
            </button>
        </div>
    </div>

    <div class="pdf-tool__loading" id="pm-loading" hidden>
        <span class="pdf-tool__spinner"></span>
        <strong>Unindo PDFs...</strong>
        <span>Aguarde alguns segundos.</span>
    </div>

    <p class="pdf-tool__privacy">
        <span class="material-symbols-outlined" aria-hidden="true">lock</span>
        Os arquivos são processados no servidor e removidos após o download.
    </p>
</div>
