<div class="lorem-gen" id="lorem-gen">

    <div class="lorem-gen__controls">

        <div class="lorem-gen__field">
            <label for="lg-type">Tipo</label>
            <select id="lg-type" class="lorem-gen__select">
                <option value="paragraphs">Parágrafos</option>
                <option value="sentences">Frases</option>
                <option value="words">Palavras</option>
            </select>
        </div>

        <div class="lorem-gen__field">
            <label for="lg-amount">Quantidade</label>
            <input
                type="number"
                id="lg-amount"
                class="lorem-gen__input"
                min="1"
                max="50"
                value="3"
            >
        </div>

        <div class="lorem-gen__field">
            <label for="lg-style">Estilo</label>
            <select id="lg-style" class="lorem-gen__select">
                <option value="classic">Lorem Ipsum clássico</option>
                <option value="pt">Texto em português</option>
            </select>
        </div>

        <div class="lorem-gen__field lorem-gen__field--check">
            <label class="lorem-gen__check">
                <input type="checkbox" id="lg-start" checked>
                Começar com “Lorem ipsum…”
            </label>
        </div>

        <button type="button" class="button button--primary lorem-gen__submit" id="lg-generate">
            <span class="material-symbols-outlined" aria-hidden="true">auto_awesome</span>
            Gerar texto
        </button>
    </div>

    <div class="lorem-gen__result-wrap">
        <div class="lorem-gen__result-header">
            <strong>Resultado</strong>
            <div class="lorem-gen__result-actions">
                <button type="button" class="button button--secondary button--compact" id="lg-copy">
                    <span class="material-symbols-outlined" aria-hidden="true">content_copy</span>
                    Copiar
                </button>
                <button type="button" class="button button--secondary button--compact" id="lg-clear">
                    <span class="material-symbols-outlined" aria-hidden="true">delete</span>
                    Limpar
                </button>
            </div>
        </div>

        <textarea
            id="lg-output"
            class="lorem-gen__output"
            readonly
            placeholder="O texto gerado aparecerá aqui..."
        ></textarea>

        <div class="lorem-gen__stats" aria-live="polite">
            <span><strong id="lg-chars">0</strong> caracteres</span>
            <span><strong id="lg-words">0</strong> palavras</span>
            <span><strong id="lg-paras">0</strong> parágrafos</span>
        </div>
    </div>

    <p class="lorem-gen__privacy">
        <span class="material-symbols-outlined" aria-hidden="true">lock</span>
        Geração local no navegador — nenhum texto é enviado ao servidor.
    </p>

    <p class="lorem-gen__feedback" id="lg-feedback" role="status" aria-live="polite" hidden></p>

</div>
