"use strict";

(() => {
    const typeEl = document.getElementById("lg-type");
    const amountEl = document.getElementById("lg-amount");
    const styleEl = document.getElementById("lg-style");
    const startEl = document.getElementById("lg-start");
    const generateBtn = document.getElementById("lg-generate");
    const output = document.getElementById("lg-output");
    const copyBtn = document.getElementById("lg-copy");
    const clearBtn = document.getElementById("lg-clear");
    const feedback = document.getElementById("lg-feedback");
    const elChars = document.getElementById("lg-chars");
    const elWords = document.getElementById("lg-words");
    const elParas = document.getElementById("lg-paras");

    if (!generateBtn || !output) return;

    const LATIN = [
        "lorem", "ipsum", "dolor", "sit", "amet", "consectetur", "adipiscing", "elit",
        "sed", "do", "eiusmod", "tempor", "incididunt", "ut", "labore", "et", "dolore",
        "magna", "aliqua", "enim", "ad", "minim", "veniam", "quis", "nostrud",
        "exercitation", "ullamco", "laboris", "nisi", "aliquip", "ex", "ea", "commodo",
        "consequat", "duis", "aute", "irure", "in", "reprehenderit", "voluptate",
        "velit", "esse", "cillum", "fugiat", "nulla", "pariatur", "excepteur", "sint",
        "occaecat", "cupidatat", "non", "proident", "sunt", "culpa", "qui", "officia",
        "deserunt", "mollit", "anim", "id", "est", "laborum", "curabitur", "pretium",
        "tincidunt", "lacus", "suspendisse", "potenti", "mauris", "varius", "odio",
        "pharetra", "placerat", "vestibulum", "porta", "felis", "euismod", "semper",
        "viverra", "nam", "libero", "tempus", "faucibus", "habitasse", "platea",
    ];

    const PT = [
        "texto", "exemplo", "conteudo", "pagina", "projeto", "sistema", "usuario",
        "interface", "design", "layout", "ferramenta", "online", "rapido", "simples",
        "pratico", "moderno", "digital", "arquivo", "documento", "resultado",
        "processo", "opcao", "formato", "qualidade", "servico", "plataforma",
        "navegador", "aplicacao", "recurso", "funcao", "modelo", "estrutura",
        "organizacao", "detalhe", "informacao", "mensagem", "paragrafo", "frase",
        "palavra", "espaco", "linha", "bloco", "secao", "titulo", "descricao",
        "exemplo", "demonstracao", "preenchimento", "visual", "teste", "amostra",
        "completo", "basico", "avancado", "automatico", "manual", "personalizado",
        "eficiente", "seguro", "gratuito", "disponivel", "atualizado", "confiavel",
    ];

    const CLASSIC_START =
        "Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua.";

    const PT_START =
        "Este e um texto de exemplo usado para preencher o layout e visualizar como o conteudo fica na pagina.";

    function rand(min, max) {
        return Math.floor(Math.random() * (max - min + 1)) + min;
    }

    function pick(arr) {
        return arr[rand(0, arr.length - 1)];
    }

    function capitalize(word) {
        return word.charAt(0).toUpperCase() + word.slice(1);
    }

    function makeSentence(words, dictionary) {
        const count = words || rand(6, 14);
        const parts = [];
        for (let i = 0; i < count; i++) {
            parts.push(pick(dictionary));
        }
        parts[0] = capitalize(parts[0]);
        return parts.join(" ") + ".";
    }

    function makeParagraph(dictionary, sentences) {
        const count = sentences || rand(3, 6);
        const list = [];
        for (let i = 0; i < count; i++) {
            list.push(makeSentence(null, dictionary));
        }
        return list.join(" ");
    }

    function generate() {
        let amount = parseInt(amountEl.value, 10);
        if (isNaN(amount) || amount < 1) amount = 1;
        if (amount > 50) amount = 50;
        amountEl.value = String(amount);

        const type = typeEl.value;
        const style = styleEl.value;
        const dictionary = style === "pt" ? PT : LATIN;
        const startClassic = startEl.checked;

        const parts = [];

        if (type === "words") {
            const words = [];
            for (let i = 0; i < amount; i++) {
                words.push(pick(dictionary));
            }
            if (startClassic && style === "classic") {
                words[0] = "Lorem";
                if (words.length > 1) words[1] = "ipsum";
            }
            parts.push(words.join(" "));
        } else if (type === "sentences") {
            for (let i = 0; i < amount; i++) {
                if (i === 0 && startClassic) {
                    parts.push(style === "pt" ? PT_START : CLASSIC_START);
                } else {
                    parts.push(makeSentence(null, dictionary));
                }
            }
        } else {
            // paragraphs
            for (let i = 0; i < amount; i++) {
                if (i === 0 && startClassic) {
                    const rest = makeParagraph(dictionary, rand(2, 4));
                    parts.push((style === "pt" ? PT_START : CLASSIC_START) + " " + rest);
                } else {
                    parts.push(makeParagraph(dictionary));
                }
            }
        }

        const text = type === "paragraphs" ? parts.join("\n\n") : parts.join(" ");
        output.value = text;
        updateStats(text);
        showFeedback("Texto gerado!");
    }

    function updateStats(text) {
        const t = text || "";
        elChars.textContent = String(t.length);
        const trimmed = t.trim();
        elWords.textContent = trimmed ? String(trimmed.split(/\s+/).length) : "0";
        elParas.textContent = trimmed ? String(t.split(/\n\s*\n/).filter(Boolean).length) : "0";
    }

    function showFeedback(msg) {
        feedback.textContent = msg;
        feedback.hidden = false;
        clearTimeout(showFeedback._t);
        showFeedback._t = setTimeout(() => {
            feedback.hidden = true;
        }, 2000);
    }

    generateBtn.addEventListener("click", generate);

    copyBtn?.addEventListener("click", async () => {
        if (!output.value) {
            showFeedback("Gere um texto primeiro.");
            return;
        }
        try {
            await navigator.clipboard.writeText(output.value);
            showFeedback("Texto copiado!");
        } catch {
            output.select();
            document.execCommand("copy");
            showFeedback("Texto copiado!");
        }
    });

    clearBtn?.addEventListener("click", () => {
        output.value = "";
        updateStats("");
        feedback.hidden = true;
    });

    // Gera um exemplo ao carregar
    generate();
})();
