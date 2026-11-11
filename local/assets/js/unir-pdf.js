"use strict";

(() => {
    const dropzone = document.getElementById("pm-dropzone");
    const fileInput = document.getElementById("pm-files");
    const workspace = document.getElementById("pm-workspace");
    const loading = document.getElementById("pm-loading");
    const errorEl = document.getElementById("pm-error");
    const listEl = document.getElementById("pm-file-list");
    const submitBtn = document.getElementById("pm-submit");
    const addMore = document.getElementById("pm-add-more");

    let files = [];
    const MAX = 30 * 1024 * 1024;

    function showError(msg) { errorEl.textContent = msg; errorEl.hidden = false; }
    function clearError() { errorEl.textContent = ""; errorEl.hidden = true; }

    function renderList() {
        listEl.replaceChildren();
        files.forEach((f, i) => {
            const li = document.createElement("li");
            li.draggable = true;
            li.dataset.index = String(i);
            const dragIcon = document.createElement("span");
            dragIcon.className = "material-symbols-outlined drag";
            dragIcon.setAttribute("aria-hidden", "true");
            dragIcon.textContent = "drag_indicator";

            const name = document.createElement("strong");
            name.textContent = f.name;

            const removeButton = document.createElement("button");
            removeButton.type = "button";
            removeButton.setAttribute("aria-label", "Remover");
            removeButton.dataset.i = String(i);

            const removeIcon = document.createElement("span");
            removeIcon.className = "material-symbols-outlined";
            removeIcon.setAttribute("aria-hidden", "true");
            removeIcon.textContent = "close";
            removeButton.appendChild(removeIcon);

            li.append(dragIcon, name, removeButton);
            li.querySelector("button").addEventListener("click", () => {
                files.splice(i, 1);
                if (files.length < 2) {
                    workspace.hidden = true;
                    dropzone.hidden = false;
                }
                renderList();
            });
            li.addEventListener("dragstart", () => li.classList.add("dragging"));
            li.addEventListener("dragend", () => {
                li.classList.remove("dragging");
                const order = [...listEl.querySelectorAll("li")].map((el) => files[+el.dataset.index]);
                // rebuild from DOM order
            });
            listEl.appendChild(li);
        });

        // simple reorder on drop
        listEl.querySelectorAll("li").forEach((li) => {
            li.addEventListener("dragover", (e) => e.preventDefault());
            li.addEventListener("drop", (e) => {
                e.preventDefault();
                const from = +document.querySelector("li.dragging")?.dataset.index;
                const to = +li.dataset.index;
                if (Number.isInteger(from) && Number.isInteger(to) && from !== to) {
                    const item = files.splice(from, 1)[0];
                    files.splice(to, 0, item);
                    renderList();
                }
            });
        });
    }

    function addFiles(list) {
        clearError();
        for (const f of list) {
            if (!f.name.toLowerCase().endsWith(".pdf") && f.type !== "application/pdf") continue;
            if (f.size > MAX) { showError(`${f.name} excede 30 MB.`); continue; }
            files.push(f);
        }
        if (files.length >= 2) {
            dropzone.hidden = true;
            workspace.hidden = false;
            renderList();
        } else if (files.length === 1) {
            showError("Adicione pelo menos 2 arquivos PDF.");
        }
    }

    dropzone.addEventListener("click", () => fileInput.click());
    addMore.addEventListener("click", () => fileInput.click());
    dropzone.addEventListener("dragover", (e) => { e.preventDefault(); dropzone.classList.add("is-dragging"); });
    dropzone.addEventListener("dragleave", () => dropzone.classList.remove("is-dragging"));
    dropzone.addEventListener("drop", (e) => {
        e.preventDefault();
        dropzone.classList.remove("is-dragging");
        addFiles(e.dataTransfer.files);
    });
    fileInput.addEventListener("change", () => {
        if (fileInput.files?.length) addFiles(fileInput.files);
        fileInput.value = "";
    });

    submitBtn.addEventListener("click", async () => {
        if (files.length < 2) { showError("Selecione pelo menos 2 PDFs."); return; }
        clearError();
        workspace.hidden = true;
        loading.hidden = false;

        const fd = new FormData();
        files.forEach((f) => fd.append("pdfs[]", f));

        try {
            const res = await fetch("tools/unir-pdf.php", { method: "POST", body: fd, credentials: "same-origin" });
            if (!res.ok) {
                const err = await res.json().catch(() => ({}));
                throw new Error(err.error || "Falha ao unir PDFs.");
            }
            const blob = await res.blob();
            const a = document.createElement("a");
            a.href = URL.createObjectURL(blob);
            a.download = "pdfs-unidos.pdf";
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
