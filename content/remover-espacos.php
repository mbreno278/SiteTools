<div class="text-spaces" id="text-spaces">

    <div class="text-spaces__header">
        <div>
            <h2>Digite ou cole seu texto</h2>
            <p>Remova espaços extras e organize o texto. Tudo é processado no navegador.</p>
        </div>
        <button type="button" class="button button--secondary button--compact" id="ts-clear">
            <span class="material-symbols-outlined" aria-hidden="true">delete</span>
            Limpar
        </button>
    </div>

    <label class="sr-only" for="ts-input">Texto para limpar</label>
    <textarea
        id="ts-input"
        class="text-spaces__textarea"
        placeholder="Cole aqui o texto com espaços extras, linhas em branco ou formatação bagunçada..."
        spellcheck="true"
    ></textarea>

    <div class="text-spaces__actions" role="group" aria-label="Opções de limpeza">
        <button type="button" class="button text-spaces__btn" data-action="trim">
            <span class="material-symbols-outlined" aria-hidden="true">content_cut</span>
            Remover espaços nas pontas
        </button>
        <button type="button" class="button text-spaces__btn" data-action="multi-spaces">
            <span class="material-symbols-outlined" aria-hidden="true">space_bar</span>
            Espaços duplos → um
        </button>
        <button type="button" class="button text-spaces__btn" data-action="all-spaces">
            <span class="material-symbols-outlined" aria-hidden="true">block</span>
            Remover todos os espaços
        </button>
        <button type="button" class="button text-spaces__btn" data-action="tabs">
            <span class="material-symbols-outlined" aria-hidden="true">keyboard_tab</span>
            Tabs → espaço
        </button>
        <button type="button" class="button text-spaces__btn" data-action="blank-lines">
            <span class="material-symbols-outlined" aria-hidden="true">density_small</span>
            Remover linhas em branco
        </button>
        <button type="button" class="button text-spaces__btn" data-action="line-spaces">
            <span class="material-symbols-outlined" aria-hidden="true">format_align_left</span>
            Limpar espaços por linha
        </button>
        <button type="button" class="button text-spaces__btn text-spaces__btn--primary" data-action="clean-all">
            <span class="material-symbols-outlined" aria-hidden="true">auto_fix_high</span>
            Limpar tudo (recomendado)
        </button>
        <button type="button" class="button text-spaces__btn" data-action="paragraphs">
            <span class="material-symbols-outlined" aria-hidden="true">notes</span>
            Organizar parágrafos
        </button>
    </div>

    <div class="text-spaces__stats" aria-live="polite">
        <div class="text-spaces__stat">
            <span>Caracteres</span>
            <strong id="ts-chars">0</strong>
        </div>
        <div class="text-spaces__stat">
            <span>Palavras</span>
            <strong id="ts-words">0</strong>
        </div>
        <div class="text-spaces__stat">
            <span>Linhas</span>
            <strong id="ts-lines">0</strong>
        </div>
        <div class="text-spaces__stat">
            <span>Espaços</span>
            <strong id="ts-spaces">0</strong>
        </div>
    </div>

    <div class="text-spaces__footer">
        <span class="text-spaces__privacy">
            <span class="material-symbols-outlined" aria-hidden="true">lock</span>
            Seu texto é processado localmente no navegador.
        </span>
        <button type="button" class="button button--primary" id="ts-copy">
            <span class="material-symbols-outlined" aria-hidden="true">content_copy</span>
            Copiar texto
        </button>
    </div>

    <p class="text-spaces__feedback" id="ts-feedback" role="status" aria-live="polite" hidden></p>

</div>
