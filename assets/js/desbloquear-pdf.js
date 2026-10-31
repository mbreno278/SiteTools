"use strict";

(() => {
    const dropzone = document.getElementById("pu-dropzone");
    const fileInput = document.getElementById("pu-file");
    const workspace = document.getElementById("pu-workspace");
    const loading = document.getElementById("pu-loading");
    const errorEl = document.getElementById("pu-error");
    const filename = document.getElementById("pu-filename");
    const filesize = document.getElementById("pu-filesize");
    const removeBtn = document.getElementById("pu-remove");
    const submitBtn = document.getElementById("pu-submit");
    const passEl = document.getElementById("pu-password");

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
        passEl.value = "";
    });

    submitBtn.addEventListener("click", async () => {
        if (!file) return;
        clearError();
        if (!passEl.value) { showError("Informe a senha do PDF."); return; }

        workspace.hidden = true;
        loading.hidden = false;

        const fd = new FormData();
        fd.append("pdf", file);
        fd.append("password", passEl.value);

        try {
            const res = await fetch("tools/desbloquear-pdf.php", { method: "POST", body: fd, credentials: "same-origin" });
            if (!res.ok) {
                const err = await res.json().catch(() => ({}));
                throw new Error(err.error || "Falha ao desbloquear.");
            }
            const blob = await res.blob();
            const a = document.createElement("a");
            a.href = URL.createObjectURL(blob);
            a.download = file.name.replace(/\.pdf$/i, "") + "-desbloqueado.pdf";
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
