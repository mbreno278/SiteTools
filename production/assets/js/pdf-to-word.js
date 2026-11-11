"use strict";

(() => {
    const dropzone = document.getElementById("pw-dropzone");
    const fileInput = document.getElementById("pw-file");
    const workspace = document.getElementById("pw-workspace");
    const loading = document.getElementById("pw-loading");
    const errorEl = document.getElementById("pw-error");
    const filename = document.getElementById("pw-filename");
    const filesize = document.getElementById("pw-filesize");
    const removeBtn = document.getElementById("pw-remove");
    const submitBtn = document.getElementById("pw-submit");

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

    function setFile(f) {
        clearError();
        if (!f || (!f.name.toLowerCase().endsWith(".pdf") && f.type !== "application/pdf")) {
            showError("Selecione um arquivo PDF válido.");
            return;
        }
        if (f.size > MAX) {
            showError("O PDF deve ter no máximo 20 MB.");
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
        fd.append("pdf", file);

        try {
            const res = await fetch("tools/pdf-to-word.php", {
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
            a.download = file.name.replace(/\.pdf$/i, "") + ".docx";
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
