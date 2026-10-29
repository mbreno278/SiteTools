
<section class="pdf-word-tool" aria-labelledby="pdf-word-title">
    <div class="pdf-word-tool__header">
        <span class="pdf-word-tool__icon" aria-hidden="true">📄</span>
        <h2 id="pdf-word-title">PDF para Word</h2>
        <p>Converta seus arquivos PDF em documentos Word editáveis.</p>
    </div>

    <form id="pdf-word-form">
        <label class="pdf-word-tool__dropzone" for="pdf-word-file">
            <span aria-hidden="true">⬆</span>
            <strong>Selecione um arquivo PDF</strong>
            <span>ou clique aqui para procurar no computador</span>
            <small>PDF · Máximo de 20 MB</small>
        </label>

        <input
            type="file"
            id="pdf-word-file"
            name="pdf"
            accept=".pdf,application/pdf"
            required
        >

        <p id="pdf-word-filename" aria-live="polite">
            Nenhum arquivo selecionado.
        </p>

        <button type="submit" id="pdf-word-submit">
            Converter para Word
        </button>

        <p id="pdf-word-status" role="status" aria-live="polite"></p>
    </form>
</section>
