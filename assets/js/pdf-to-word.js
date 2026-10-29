
"use strict";

const form = document.querySelector("#pdf-word-form");
const fileInput = document.querySelector("#pdf-word-file");
const fileName = document.querySelector("#pdf-word-filename");
const submitButton = document.querySelector("#pdf-word-submit");
const status = document.querySelector("#pdf-word-status");

const MAX_SIZE = 20 * 1024 * 1024;

fileInput.addEventListener("change", () => {
    const file = fileInput.files[0];

    fileName.textContent = file
        ? `${file.name} (${(file.size / 1024 / 1024).toFixed(2)} MB)`
        : "Nenhum arquivo selecionado.";
});

form.addEventListener("submit", async (event) => {
    event.preventDefault();

    const file = fileInput.files[0];

    if (!file) {
        status.textContent = "Selecione um arquivo PDF.";
        return;
    }

    if (
        !file.name.toLowerCase().endsWith(".pdf") ||
        file.size > MAX_SIZE ||
        file.size === 0
    ) {
        status.textContent =
            "Selecione um PDF válido de até 20 MB.";
        return;
    }

    const formData = new FormData();
    formData.append("pdf", file);

    submitButton.disabled = true;
    status.textContent = "Convertendo PDF para Word...";

    try {
        const response = await fetch("tools/pdf-to-word.php", {
            method: "POST",
            body: formData,
            credentials: "same-origin"
        });

        if (!response.ok) {
            const error = await response.json().catch(() => ({}));
            throw new Error(
                error.error || "Não foi possível converter o arquivo."
            );
        }

        const blob = await response.blob();

        if (blob.size === 0) {
            throw new Error("O conversor retornou um arquivo vazio.");
        }

        const url = URL.createObjectURL(blob);
        const link = document.createElement("a");

        const originalName = file.name.replace(/\.pdf$/i, "");
        const safeName = originalName.replace(/[^a-zA-Z0-9_-]+/g, "_");

        link.href = url;
        link.download = `${safeName || "documento"}.docx`;

        document.body.appendChild(link);
        link.click();
        link.remove();

        setTimeout(() => URL.revokeObjectURL(url), 1000);

        status.textContent = "Conversão concluída. Download iniciado.";
    } catch (error) {
        status.textContent = error.message;
    } finally {
        submitButton.disabled = false;
    }
});
