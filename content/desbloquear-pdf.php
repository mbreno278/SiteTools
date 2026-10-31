<div class="pdf-tool" id="pdf-unlock">

    <div class="pdf-tool__dropzone" id="pu-dropzone">
        <input type="file" id="pu-file" accept="application/pdf,.pdf" hidden>
        <span class="material-symbols-outlined pdf-tool__icon" aria-hidden="true">picture_as_pdf</span>
        <strong>Solte o PDF protegido aqui</strong>
        <span>ou clique para selecionar</span>
        <small>PDF · Máximo 30 MB</small>
    </div>

    <div class="pdf-tool__error" id="pu-error" role="alert" hidden></div>

    <div class="pdf-tool__workspace" id="pu-workspace" hidden>
        <div class="pdf-tool__file-card">
            <span class="material-symbols-outlined" aria-hidden="true">description</span>
            <div>
                <strong id="pu-filename">—</strong>
                <span id="pu-filesize">—</span>
            </div>
            <button type="button" class="pdf-tool__icon-btn" id="pu-remove" aria-label="Remover">
                <span class="material-symbols-outlined" aria-hidden="true">close</span>
            </button>
        </div>

        <div class="pdf-tool__field">
            <label for="pu-password">Senha do PDF</label>
            <input type="password" id="pu-password" class="pdf-tool__input" placeholder="Digite a senha atual" autocomplete="current-password">
        </div>

        <button type="button" class="button button--primary pdf-tool__action" id="pu-submit">
            <span class="material-symbols-outlined" aria-hidden="true">lock_open</span>
            Desbloquear PDF
        </button>
    </div>

    <div class="pdf-tool__loading" id="pu-loading" hidden>
        <span class="pdf-tool__spinner"></span>
        <strong>Desbloqueando PDF...</strong>
        <span>Aguarde alguns segundos.</span>
    </div>

    <p class="pdf-tool__privacy">
        <span class="material-symbols-outlined" aria-hidden="true">lock</span>
        É necessário conhecer a senha. A senha não é armazenada.
    </p>
</div>
