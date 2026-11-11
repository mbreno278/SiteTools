<div class="workspace__header">

    <div>
        <h2>Digite ou cole seu texto</h2>
        <p>
            A contagem é atualizada automaticamente
            no seu navegador.
        </p>
    </div>

    <button
        class="button button--secondary button--compact"
        type="button"
        data-clear-text
    >
        <span
            class="material-symbols-outlined"
            aria-hidden="true"
        >delete</span>
        Limpar
    </button>

</div>

<label
    class="sr-only"
    for="word-counter-input"
>
    Texto para contar
</label>

<textarea
    id="word-counter-input"
    class="text-workspace"
    placeholder="Comece a digitar ou cole seu texto aqui..."
    spellcheck="true"
></textarea>

<div class="stats-grid" aria-live="polite">

    <div class="stat-card">
        <span>Palavras</span>
        <strong id="stat-words">0</strong>
    </div>

    <div class="stat-card">
        <span>Caracteres</span>
        <strong id="stat-chars">0</strong>
    </div>

    <div class="stat-card">
        <span>Sem espaços</span>
        <strong id="stat-chars-no-spaces">0</strong>
    </div>

    <div class="stat-card">
        <span>Linhas</span>
        <strong id="stat-lines">0</strong>
    </div>

</div>

<div class="workspace__footer">

    <span class="privacy-note">
        <span
            class="material-symbols-outlined"
            aria-hidden="true"
        >lock</span>
        Seu texto é processado localmente no navegador.
    </span>

    <button
        class="button button--primary"
        type="button"
        data-copy-text
    >
        <span
            class="material-symbols-outlined"
            aria-hidden="true"
        >content_copy</span>
        Copiar texto
    </button>

</div>