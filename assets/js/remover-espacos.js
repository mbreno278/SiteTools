"use strict";

(() => {
    const input = document.getElementById("ts-input");
    const clearBtn = document.getElementById("ts-clear");
    const copyBtn = document.getElementById("ts-copy");
    const feedback = document.getElementById("ts-feedback");
    const buttons = document.querySelectorAll("[data-action]");

    const elChars = document.getElementById("ts-chars");
    const elWords = document.getElementById("ts-words");
    const elLines = document.getElementById("ts-lines");
    const elSpaces = document.getElementById("ts-spaces");

    if (!input) return;

    function showFeedback(msg) {
        feedback.textContent = msg;
        feedback.hidden = false;
        clearTimeout(showFeedback._t);
        showFeedback._t = setTimeout(() => {
            feedback.hidden = true;
        }, 2000);
    }

    function updateStats() {
        const text = input.value;
        const trimmed = text.trim();

        elChars.textContent = String(text.length);
        elWords.textContent = trimmed ? String(trimmed.split(/\s+/).length) : "0";
        elLines.textContent = text === "" ? "0" : String(text.split(/\n/).length);
        elSpaces.textContent = String((text.match(/ /g) || []).length);
    }

    const actions = {
        // Remove espaços no início e fim
        trim: (s) => s.trim(),

        // Vários espaços → um único
        "multi-spaces": (s) => s.replace(/[ \t\u00A0]{2,}/g, " "),

        // Remove todos os espaços (mantém quebras de linha)
        "all-spaces": (s) => s.replace(/[ \t\u00A0]+/g, ""),

        // Tabs → espaço
        tabs: (s) => s.replace(/\t/g, " "),

        // Remove linhas totalmente em branco
        "blank-lines": (s) =>
            s
                .split(/\n/)
                .filter((line) => line.trim() !== "")
                .join("\n"),

        // Trim em cada linha + espaços múltiplos por linha
        "line-spaces": (s) =>
            s
                .split(/\n/)
                .map((line) => line.replace(/[ \t\u00A0]+/g, " ").trim())
                .join("\n"),

        // Limpeza completa recomendada
        "clean-all": (s) => {
            let out = s.replace(/\r\n/g, "\n").replace(/\r/g, "\n");
            out = out.replace(/\t/g, " ");
            out = out.replace(/[ \u00A0]+/g, " ");
            out = out
                .split("\n")
                .map((line) => line.trim())
                .join("\n");
            out = out.replace(/\n{3,}/g, "\n\n");
            return out.trim();
        },

        // Organiza parágrafos: linhas vazias viram separador único
        paragraphs: (s) => {
            let out = s.replace(/\r\n/g, "\n").replace(/\r/g, "\n");
            out = out.replace(/\t/g, " ");
            out = out.replace(/[ \u00A0]+/g, " ");
            // junta linhas quebradas no meio do parágrafo (linha sem vazio seguinte)
            const blocks = out
                .split(/\n\s*\n/)
                .map((block) =>
                    block
                        .split("\n")
                        .map((l) => l.trim())
                        .filter(Boolean)
                        .join(" ")
                )
                .filter(Boolean);
            return blocks.join("\n\n");
        },
    };

    buttons.forEach((btn) => {
        btn.addEventListener("click", () => {
            const type = btn.getAttribute("data-action");
            const fn = actions[type];
            if (!fn) return;

            if (!input.value) {
                showFeedback("Digite ou cole um texto primeiro.");
                input.focus();
                return;
            }

            const before = input.value.length;
            input.value = fn(input.value);
            const after = input.value.length;
            updateStats();
            input.focus();

            const removed = before - after;
            if (removed > 0) {
                showFeedback(`Texto organizado. ${removed} caractere(s) removido(s).`);
            } else {
                showFeedback("Texto organizado.");
            }
        });
    });

    input.addEventListener("input", updateStats);

    clearBtn?.addEventListener("click", () => {
        input.value = "";
        updateStats();
        input.focus();
        feedback.hidden = true;
    });

    copyBtn?.addEventListener("click", async () => {
        if (!input.value) {
            showFeedback("Nada para copiar.");
            return;
        }
        try {
            await navigator.clipboard.writeText(input.value);
            showFeedback("Texto copiado!");
        } catch {
            input.select();
            document.execCommand("copy");
            showFeedback("Texto copiado!");
        }
    });

    updateStats();
})();
