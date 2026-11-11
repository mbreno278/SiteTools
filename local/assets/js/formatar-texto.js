"use strict";

(() => {
    const input = document.getElementById("tf-input");
    const clearBtn = document.getElementById("tf-clear");
    const copyBtn = document.getElementById("tf-copy");
    const feedback = document.getElementById("tf-feedback");
    const buttons = document.querySelectorAll("[data-format]");

    if (!input) return;

    function showFeedback(msg) {
        feedback.textContent = msg;
        feedback.hidden = false;
        clearTimeout(showFeedback._t);
        showFeedback._t = setTimeout(() => {
            feedback.hidden = true;
        }, 2000);
    }

    function removeAccents(str) {
        return str.normalize("NFD").replace(/[\u0300-\u036f]/g, "");
    }

    function toTitleCase(str) {
        return str.toLocaleLowerCase("pt-BR").replace(/(^|\s|[-_/])(\S)/g, (_, sep, ch) => {
            return sep + ch.toLocaleUpperCase("pt-BR");
        });
    }

    function toSentenceCase(str) {
        const lower = str.toLocaleLowerCase("pt-BR");
        return lower.replace(/(^\s*\S|[.!?]\s+\S)/g, (m) => m.toLocaleUpperCase("pt-BR"));
    }

    function toToggleCase(str) {
        let out = "";
        for (const ch of str) {
            const upper = ch.toLocaleUpperCase("pt-BR");
            const lower = ch.toLocaleLowerCase("pt-BR");
            if (ch === upper && ch !== lower) out += lower;
            else if (ch === lower && ch !== upper) out += upper;
            else out += ch;
        }
        return out;
    }

    function toAlternating(str) {
        let out = "";
        let upper = true;
        for (const ch of str) {
            if (/\s/.test(ch)) {
                out += ch;
                continue;
            }
            out += upper ? ch.toLocaleUpperCase("pt-BR") : ch.toLocaleLowerCase("pt-BR");
            upper = !upper;
        }
        return out;
    }

    function toCamelCase(str) {
        const cleaned = removeAccents(str)
            .replace(/[^a-zA-Z0-9]+/g, " ")
            .trim()
            .split(/\s+/);

        if (cleaned.length === 0 || cleaned[0] === "") return "";

        return cleaned
            .map((word, i) => {
                const w = word.toLowerCase();
                if (i === 0) return w;
                return w.charAt(0).toUpperCase() + w.slice(1);
            })
            .join("");
    }

    function toSnakeCase(str) {
        return removeAccents(str)
            .trim()
            .replace(/[^a-zA-Z0-9]+/g, "_")
            .replace(/_+/g, "_")
            .replace(/^_|_$/g, "")
            .toLowerCase();
    }

    function toKebabCase(str) {
        return removeAccents(str)
            .trim()
            .replace(/[^a-zA-Z0-9]+/g, "-")
            .replace(/-+/g, "-")
            .replace(/^-|-$/g, "")
            .toLowerCase();
    }

    function toSlug(str) {
        return toKebabCase(str);
    }

    const formatters = {
        upper: (s) => s.toLocaleUpperCase("pt-BR"),
        lower: (s) => s.toLocaleLowerCase("pt-BR"),
        title: toTitleCase,
        sentence: toSentenceCase,
        toggle: toToggleCase,
        alternating: toAlternating,
        camel: toCamelCase,
        snake: toSnakeCase,
        kebab: toKebabCase,
        slug: toSlug,
        "remove-accents": removeAccents,
        reverse: (s) => Array.from(s).reverse().join(""),
    };

    buttons.forEach((btn) => {
        btn.addEventListener("click", () => {
            const type = btn.getAttribute("data-format");
            const fn = formatters[type];
            if (!fn) return;

            const value = input.value;
            if (!value) {
                showFeedback("Digite ou cole um texto primeiro.");
                input.focus();
                return;
            }

            input.value = fn(value);
            input.focus();
            showFeedback("Texto formatado.");
        });
    });

    clearBtn?.addEventListener("click", () => {
        input.value = "";
        input.focus();
        feedback.hidden = true;
    });

    copyBtn?.addEventListener("click", async () => {
        const value = input.value;
        if (!value) {
            showFeedback("Nada para copiar.");
            return;
        }

        try {
            await navigator.clipboard.writeText(value);
            showFeedback("Texto copiado!");
        } catch {
            input.select();
            document.execCommand("copy");
            showFeedback("Texto copiado!");
        }
    });
})();
