"use strict";

/**
 * Contador de palavras — processado localmente no navegador.
 */
(() => {
  const $ = (selector, root = document) => root.querySelector(selector);
  const formatNumber = (value) => value.toLocaleString("pt-BR");

  const textInput = $("#word-counter-input");
  if (!textInput) return;

  const wordCount = $("#stat-words");
  const charCount = $("#stat-chars");
  const charNoSpacesCount = $("#stat-chars-no-spaces");
  const lineCount = $("#stat-lines");
  const clearButton = $("[data-clear-text]");
  const copyButton = $("[data-copy-text]");
  const privacyNote = $(".privacy-note");

  let copyFeedbackTimeout;

  const setStatus = (element, message = "", state = "") => {
    if (!element) return;
    element.textContent = message;
    if (state) {
      element.dataset.state = state;
    } else {
      delete element.dataset.state;
    }
  };

  const updateStats = () => {
    const text = textInput.value;
    const trimmedText = text.trim();
    const words = trimmedText ? trimmedText.split(/\s+/u).length : 0;
    const characters = text.length;
    const charactersWithoutSpaces = text.replace(/\s/gu, "").length;
    const lines = text.length ? text.split(/\r\n|\r|\n/u).length : 0;

    if (wordCount) wordCount.textContent = formatNumber(words);
    if (charCount) charCount.textContent = formatNumber(characters);
    if (charNoSpacesCount) {
      charNoSpacesCount.textContent = formatNumber(charactersWithoutSpaces);
    }
    if (lineCount) lineCount.textContent = formatNumber(lines);
  };

  textInput.addEventListener("input", updateStats);

  if (clearButton) {
    clearButton.addEventListener("click", () => {
      textInput.value = "";
      updateStats();
      textInput.focus();
    });
  }

  if (copyButton) {
    const defaultCopyContent = copyButton.innerHTML;

    copyButton.addEventListener("click", async () => {
      try {
        await navigator.clipboard.writeText(textInput.value);
        copyButton.innerHTML =
          '<span class="material-symbols-outlined" aria-hidden="true">check</span> Copiado';
        window.clearTimeout(copyFeedbackTimeout);
        copyFeedbackTimeout = window.setTimeout(() => {
          copyButton.innerHTML = defaultCopyContent;
        }, 1600);
        setStatus(privacyNote, "");
      } catch {
        textInput.focus();
        textInput.select();
        setStatus(
          privacyNote,
          "Não foi possível copiar automaticamente. Selecione e copie o texto manualmente.",
          "error"
        );
      }
    });
  }

  updateStats();
})();
