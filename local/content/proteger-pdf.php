<div class="pdf-tool" id="pdf-protect">

    <div class="pdf-tool__dropzone" id="pp-dropzone">
        <input type="file" id="pp-file" accept="application/pdf,.pdf" hidden>
        <span class="material-symbols-outlined pdf-tool__icon" aria-hidden="true">picture_as_pdf</span>
        <strong>Solte o PDF aqui</strong>
        <span>ou clique para selecionar</span>
        <small>PDF · Máximo 30 MB</small>
    </div>

    <div class="pdf-tool__error" id="pp-error" role="alert" hidden></div>

    <div class="pdf-tool__workspace" id="pp-workspace" hidden>
        <div class="pdf-tool__file-card">
            <span class="material-symbols-outlined" aria-hidden="true">description</span>
            <div>
                <strong id="pp-filename">—</strong>
                <span id="pp-filesize">—</span>
            </div>
            <button type="button" class="pdf-tool__icon-btn" id="pp-remove" aria-label="Remover">
                <span class="material-symbols-outlined" aria-hidden="true">close</span>
            </button>
        </div>

        <div class="pdf-tool__field">
            <label for="pp-password">Senha de proteção</label>
            <input type="password" id="pp-password" class="pdf-tool__input" placeholder="Digite a senha" autocomplete="new-password">
        </div>

        <div class="pdf-tool__field">
            <label for="pp-password2">Confirmar senha</label>
            <input type="password" id="pp-password2" class="pdf-tool__input" placeholder="Repita a senha" autocomplete="new-password">
        </div>

        <button type="button" class="button button--primary pdf-tool__action" id="pp-submit">
            <span class="material-symbols-outlined" aria-hidden="true">lock</span>
            Proteger PDF
        </button>
    </div>

    <div class="pdf-tool__loading" id="pp-loading" hidden>
        <span class="pdf-tool__spinner"></span>
        <strong>Protegendo PDF...</strong>
        <span>Aguarde alguns segundos.</span>
    </div>

    <p class="pdf-tool__privacy">
        <span class="material-symbols-outlined" aria-hidden="true">lock</span>
        A senha não é armazenada. O arquivo é removido após o download.
    </p>
</div>
