"use strict";

(() => {
    const dropzone = document.getElementById("wp-dropzone");
    const fileInput = document.getElementById("wp-file");
    const workspace = document.getElementById("wp-workspace");
    const loading = document.getElementById("wp-loading");
    const errorEl = document.getElementById("wp-error");
    const filename = document.getElementById("wp-filename");
    const filesize = document.getElementById("wp-filesize");
    const removeBtn = document.getElementById("wp-remove");
    const submitBtn = document.getElementById("wp-submit");

    let file = null;
    const MAX = 20 * 1024 * 1024;

    function showError(msg) {
        errorEl.textContent = msg;
        errorEl.hidden = false;
    }
    function clearError() {
        errorEl.textContent = "";
        errorEl.hidden = true;
    }
    function fmtSize(n) {
        return n >= 1048576 ? (n / 1048576).toFixed(2) + " MB" : (n / 1024).toFixed(0) + " KB";
    }
    function isWord(f) {
        const n = f.name.toLowerCase();
        return n.endsWith(".docx") || n.endsWith(".doc")
            || f.type === "application/msword"
            || f.type === "application/vnd.openxmlformats-officedocument.wordprocessingml.document";
    }

    function setFile(f) {
        clearError();
        if (!f || !isWord(f)) {
            showError("Selecione um arquivo Word (.doc ou .docx).");
            return;
        }
        if (f.size > MAX) {
            showError("O arquivo deve ter no máximo 20 MB.");
            return;
        }
        if (f.size === 0) {
            showError("O arquivo está vazio.");
            return;
        }
        file = f;
        filename.textContent = f.name;
        filesize.textContent = fmtSize(f.size);
        dropzone.hidden = true;
        workspace.hidden = false;
        loading.hidden = true;
    }

    dropzone.addEventListener("click", () => fileInput.click());
    dropzone.addEventListener("dragover", (e) => {
        e.preventDefault();
        dropzone.classList.add("is-dragging");
    });
    dropzone.addEventListener("dragleave", () => dropzone.classList.remove("is-dragging"));
    dropzone.addEventListener("drop", (e) => {
        e.preventDefault();
        dropzone.classList.remove("is-dragging");
        if (e.dataTransfer.files[0]) setFile(e.dataTransfer.files[0]);
    });
    fileInput.addEventListener("change", () => {
        if (fileInput.files[0]) setFile(fileInput.files[0]);
    });

    removeBtn.addEventListener("click", () => {
        file = null;
        fileInput.value = "";
        workspace.hidden = true;
        dropzone.hidden = false;
        clearError();
    });

    submitBtn.addEventListener("click", async () => {
        if (!file) return;
        clearError();
        workspace.hidden = true;
        loading.hidden = false;

        const fd = new FormData();
        fd.append("word", file);

        try {
            const res = await fetch("tools/word-to-pdf.php", {
                method: "POST",
                body: fd,
                credentials: "same-origin"
            });

            if (!res.ok) {
                const err = await res.json().catch(() => ({}));
                throw new Error(err.error || "Não foi possível converter o arquivo.");
            }

            const blob = await res.blob();
            if (!blob || blob.size < 100) {
                throw new Error("O conversor retornou um arquivo vazio.");
            }

            const ctype = res.headers.get("Content-Type") || "";
            if (ctype.includes("json")) {
                const text = await blob.text();
                const data = JSON.parse(text);
                throw new Error(data.error || "Falha na conversão.");
            }

            const a = document.createElement("a");
            a.href = URL.createObjectURL(blob);
            a.download = file.name.replace(/\.(docx?|DOCX?)$/i, "") + ".pdf";
            document.body.appendChild(a);
            a.click();
            a.remove();
            setTimeout(() => URL.revokeObjectURL(a.href), 2000);

            workspace.hidden = false;
            loading.hidden = true;
        } catch (e) {
            loading.hidden = true;
            workspace.hidden = false;
            showError(e.message || "Erro na conversão.");
        }
    });
})();
