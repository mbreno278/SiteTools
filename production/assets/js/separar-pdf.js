"use strict";

(() => {
    const dropzone = document.getElementById("ps-dropzone");
    const fileInput = document.getElementById("ps-file");
    const workspace = document.getElementById("ps-workspace");
    const loading = document.getElementById("ps-loading");
    const errorEl = document.getElementById("ps-error");
    const filename = document.getElementById("ps-filename");
    const filesize = document.getElementById("ps-filesize");
    const removeBtn = document.getElementById("ps-remove");
    const submitBtn = document.getElementById("ps-submit");
    const modeEl = document.getElementById("ps-mode");
    const rangeWrap = document.getElementById("ps-range-wrap");
    const rangeEl = document.getElementById("ps-range");

    let file = null;
    const MAX = 30 * 1024 * 1024;

    function showError(msg) { errorEl.textContent = msg; errorEl.hidden = false; }
    function clearError() { errorEl.textContent = ""; errorEl.hidden = true; }
    function fmtSize(n) {
        return n >= 1048576 ? (n / 1048576).toFixed(2) + " MB" : (n / 1024).toFixed(0) + " KB";
    }

    function setFile(f) {
        clearError();
        if (!f || (!f.name.toLowerCase().endsWith(".pdf") && f.type !== "application/pdf")) {
            showError("Selecione um PDF válido.");
            return;
        }
        if (f.size > MAX) { showError("Máximo 30 MB."); return; }
        file = f;
        filename.textContent = f.name;
        filesize.textContent = fmtSize(f.size);
        dropzone.hidden = true;
        workspace.hidden = false;
        loading.hidden = true;
    }

    modeEl.addEventListener("change", () => {
        rangeWrap.hidden = modeEl.value !== "range";
    });

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
        file = null; fileInput.value = "";
        workspace.hidden = true; dropzone.hidden = false; clearError();
    });

    submitBtn.addEventListener("click", async () => {
        if (!file) return;
        clearError();
        if (modeEl.value === "range" && !rangeEl.value.trim()) {
            showError("Informe o intervalo de páginas (ex: 1-3).");
            return;
        }
        workspace.hidden = true;
        loading.hidden = false;

        const fd = new FormData();
        fd.append("pdf", file);
        fd.append("mode", modeEl.value);
        fd.append("range", rangeEl.value.trim());

        try {
            const res = await fetch("tools/separar-pdf.php", { method: "POST", body: fd, credentials: "same-origin" });
            if (!res.ok) {
                const err = await res.json().catch(() => ({}));
                throw new Error(err.error || "Falha ao separar.");
            }
            const blob = await res.blob();
            const ctype = res.headers.get("Content-Type") || "";
            const a = document.createElement("a");
            a.href = URL.createObjectURL(blob);
            a.download = ctype.includes("zip") ? "pdf-separado.zip" : "pagina.pdf";
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
