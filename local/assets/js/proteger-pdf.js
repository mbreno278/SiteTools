"use strict";

(() => {
    const dropzone = document.getElementById("pp-dropzone");
    const fileInput = document.getElementById("pp-file");
    const workspace = document.getElementById("pp-workspace");
    const loading = document.getElementById("pp-loading");
    const errorEl = document.getElementById("pp-error");
    const filename = document.getElementById("pp-filename");
    const filesize = document.getElementById("pp-filesize");
    const removeBtn = document.getElementById("pp-remove");
    const submitBtn = document.getElementById("pp-submit");
    const pass1 = document.getElementById("pp-password");
    const pass2 = document.getElementById("pp-password2");

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
        pass1.value = ""; pass2.value = "";
    });

    submitBtn.addEventListener("click", async () => {
        if (!file) return;
        clearError();
        if (pass1.value.length < 3) { showError("A senha deve ter pelo menos 3 caracteres."); return; }
        if (pass1.value !== pass2.value) { showError("As senhas não coincidem."); return; }

        workspace.hidden = true;
        loading.hidden = false;

        const fd = new FormData();
        fd.append("pdf", file);
        fd.append("password", pass1.value);

        try {
            const res = await fetch("tools/proteger-pdf.php", { method: "POST", body: fd, credentials: "same-origin" });
            if (!res.ok) {
                const err = await res.json().catch(() => ({}));
                throw new Error(err.error || "Falha ao proteger.");
            }
            const blob = await res.blob();
            const a = document.createElement("a");
            a.href = URL.createObjectURL(blob);
            a.download = file.name.replace(/\.pdf$/i, "") + "-protegido.pdf";
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
