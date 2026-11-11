<div class="text-format" id="text-format">

    <div class="text-format__header">
        <div>
            <h2>Digite ou cole seu texto</h2>
            <p>Escolha um formato abaixo. Tudo é processado no seu navegador.</p>
        </div>
        <button type="button" class="button button--secondary button--compact" id="tf-clear">
            <span class="material-symbols-outlined" aria-hidden="true">delete</span>
            Limpar
        </button>
    </div>

    <label class="sr-only" for="tf-input">Texto para formatar</label>
    <textarea
        id="tf-input"
        class="text-format__textarea"
        placeholder="Comece a digitar ou cole seu texto aqui..."
        spellcheck="true"
    ></textarea>

    <div class="text-format__actions" role="group" aria-label="Formatos de texto">
        <button type="button" class="button text-format__btn" data-format="upper">
            MAIÚSCULAS
        </button>
        <button type="button" class="button text-format__btn" data-format="lower">
            minúsculas
        </button>
        <button type="button" class="button text-format__btn" data-format="title">
            Título De Cada Palavra
        </button>
        <button type="button" class="button text-format__btn" data-format="sentence">
            Frase normal
        </button>
        <button type="button" class="button text-format__btn" data-format="toggle">
            iNVERTER cAPITALIZAÇÃO
        </button>
        <button type="button" class="button text-format__btn" data-format="alternating">
            AlTeRnAdO
        </button>
        <button type="button" class="button text-format__btn" data-format="camel">
            camelCase
        </button>
        <button type="button" class="button text-format__btn" data-format="snake">
            snake_case
        </button>
        <button type="button" class="button text-format__btn" data-format="kebab">
            kebab-case
        </button>
        <button type="button" class="button text-format__btn" data-format="slug">
            slug-url
        </button>
        <button type="button" class="button text-format__btn" data-format="remove-accents">
            Remover acentos
        </button>
        <button type="button" class="button text-format__btn" data-format="reverse">
            Inverter texto
        </button>
    </div>

    <div class="text-format__footer">
        <span class="text-format__privacy">
            <span class="material-symbols-outlined" aria-hidden="true">lock</span>
            Seu texto é processado localmente no navegador.
        </span>

        <div class="text-format__footer-actions">
            <button type="button" class="button button--secondary" id="tf-copy">
                <span class="material-symbols-outlined" aria-hidden="true">content_copy</span>
                Copiar
            </button>
        </div>
    </div>

    <p class="text-format__feedback" id="tf-feedback" role="status" aria-live="polite" hidden></p>

</div>
