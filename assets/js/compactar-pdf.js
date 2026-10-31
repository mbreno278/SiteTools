"use strict";

(() => {
    const dropzone = document.getElementById("pc-dropzone");
    const fileInput = document.getElementById("pc-file");
    const workspace = document.getElementById("pc-workspace");
    const loading = document.getElementById("pc-loading");
    const errorEl = document.getElementById("pc-error");
    const filename = document.getElementById("pc-filename");
    const filesize = document.getElementById("pc-filesize");
    const removeBtn = document.getElementById("pc-remove");
    const submitBtn = document.getElementById("pc-submit");
    const quality = document.getElementById("pc-quality");

    let file = null;
    const MAX = 30 * 1024 * 1024;

    function showError(msg) {
        errorEl.textContent = msg;
        errorEl.hidden = false;
    }
    function clearError() {
        errorEl.textContent = "";
        errorEl.hidden = true;
    }
    function fmtSize(n) {
        if (n >= 1048576) return (n / 1048576).toFixed(2) + " MB";
        return (n / 1024).toFixed(0) + " KB";
    }

    function setFile(f) {
        clearError();
        if (!f || f.type !== "application/pdf" && !f.name.toLowerCase().endsWith(".pdf")) {
            showError("Selecione um arquivo PDF válido.");
            return;
        }
        if (f.size > MAX) {
            showError("O PDF deve ter no máximo 30 MB.");
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
    dropzone.addEventListener("dragover", (e) => { e.preventDefault(); dropzone.classList.add("is-dragging"); });
    dropzone.addEventListener("dragleave", () => dropzone.classList.remove("is-dragging"));
    dropzone.addEventListener("drop", (e) => {
        e.preventDefault();
        dropzone.classList.remove("is-dragging");
        if (e.dataTransfer.files[0]) setFile(e.dataTransfer.files[0]);
    });
    fileInput.addEventListener("change", () => { if (fileInput.files[0]) setFile(fileInput.files[0]); });

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
        fd.append("quality", quality.value);

        try {
            const res = await fetch("tools/compactar-pdf.php", { method: "POST", body: fd, credentials: "same-origin" });
            if (!res.ok) {
                const err = await res.json().catch(() => ({}));
                throw new Error(err.error || "Falha ao compactar.");
            }
            const blob = await res.blob();
            const a = document.createElement("a");
            a.href = URL.createObjectURL(blob);
            a.download = file.name.replace(/\.pdf$/i, "") + "-compactado.pdf";
            a.click();
            URL.revokeObjectURL(a.href);
            workspace.hidden = false;
            loading.hidden = true;
        } catch (e) {
            loading.hidden = true;
            workspace.hidden = false;
            showError(e.message);
        }
    });
})();
