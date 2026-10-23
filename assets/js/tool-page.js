(() => {
    "use strict";

    /* ========================================
       UTILITÁRIOS
    ======================================== */

    const $ = (selector, root = document) => root.querySelector(selector);

    const setStatus = (element, message = "", state = "") => {
        if (!element) return;

        element.textContent = message;
        element.dataset.state = state;
    };

    const setIcon = (button, name) => {
        const icon = $("span", button);

        if (icon) {
            icon.textContent = name;
        }
    };

    const formatNumber = (value) => value.toLocaleString("pt-BR");

    /* ========================================
       MENU LATERAL
    ======================================== */

    const menuToggle = $("#menu-toggle");
    const sidebar = $("#site-sidebar");

    if (menuToggle && sidebar) {
        const setMenuState = (isOpen) => {
            sidebar.classList.toggle("is-open", isOpen);
            menuToggle.setAttribute("aria-expanded", String(isOpen));
            setIcon(menuToggle, isOpen ? "close" : "menu");
        };

        menuToggle.addEventListener("click", () => {
            const isOpen = !sidebar.classList.contains("is-open");

            setMenuState(isOpen);
        });

        document.addEventListener("keydown", (event) => {
            if (event.key === "Escape" && sidebar.classList.contains("is-open")) {
                setMenuState(false);
                menuToggle.focus();
            }
        });
    }

    /* ========================================
       CONTADOR DE PALAVRAS
    ======================================== */

    const textInput = $("#word-counter-input");

    if (textInput) {
        const wordCount = $("#stat-words");
        const charCount = $("#stat-chars");
        const charNoSpacesCount = $("#stat-chars-no-spaces");
        const lineCount = $("#stat-lines");

        const clearButton = $("[data-clear-text]");
        const copyButton = $("[data-copy-text]");
        const privacyNote = $(".privacy-note");

        let copyFeedbackTimeout;

        const updateStats = () => {
            const text = textInput.value;
            const trimmedText = text.trim();

            const words = trimmedText
                ? trimmedText.split(/\s+/u).length
                : 0;

            const characters = text.length;
            const charactersWithoutSpaces = text.replace(/\s/gu, "").length;

            const lines = text.length
                ? text.split(/\r\n|\r|\n/u).length
                : 0;

            if (wordCount) {
                wordCount.textContent = formatNumber(words);
            }

            if (charCount) {
                charCount.textContent = formatNumber(characters);
            }

            if (charNoSpacesCount) {
                charNoSpacesCount.textContent = formatNumber(charactersWithoutSpaces);
            }

            if (lineCount) {
                lineCount.textContent = formatNumber(lines);
            }
        };

        textInput.addEventListener("input", updateStats);

        /* Limpar texto */

        if (clearButton) {
            clearButton.addEventListener("click", () => {
                textInput.value = "";

                updateStats();
                textInput.focus();
            });
        }

        /* Copiar texto */

        if (copyButton) {
            const defaultCopyContent = copyButton.innerHTML;

            copyButton.addEventListener("click", async () => {
                try {
                    await navigator.clipboard.writeText(textInput.value);

                    copyButton.innerHTML =
                        '<span class="material-symbols-outlined" aria-hidden="true">check</span> Copiado';

                    window.clearTimeout(copyFeedbackTimeout);

                    copyFeedbackTimeout = window.setTimeout(() => {
                        copyButton.innerHTML = defaultCopyContent;
                    }, 1600);

                    setStatus(privacyNote, "");
                } catch {
                    textInput.focus();
                    textInput.select();

                    setStatus(
                        privacyNote,
                        "Não foi possível copiar automaticamente. Selecione e copie o texto manualmente.",
                        "error"
                    );
                }
            });
        }

        updateStats();
    }

    /* ========================================
       REDIMENSIONADOR DE IMAGENS
    ======================================== */

    const imageInput = $("#image-file");

    if (imageInput) {
        const preview = $("#image-preview");
        const previewImage = $("#image-preview-img");
        const fileName = $("#image-file-name");
        const imageStatus = $("#image-status");
        const imageForm = $("#image-resizer-form");

        const MAX_FILE_SIZE = 10 * 1024 * 1024;
        const MAX_DIMENSION = 5000;

        let previewUrl = null;

        /* Liberar URL temporária da pré-visualização */

        const releasePreviewUrl = () => {
            if (previewUrl) {
                URL.revokeObjectURL(previewUrl);
                previewUrl = null;
            }
        };

        /* Pré-visualização */

        imageInput.addEventListener("change", () => {
            releasePreviewUrl();

            if (previewImage) {
                previewImage.removeAttribute("src");
            }

            if (preview) {
                preview.hidden = true;
            }

            const file = imageInput.files?.[0];

            if (!file) {
                setStatus(imageStatus);
                return;
            }

            if (!file.type.startsWith("image/")) {
                imageInput.value = "";

                setStatus(
                    imageStatus,
                    "Selecione um arquivo de imagem válido.",
                    "error"
                );

                return;
            }

            if (file.size > MAX_FILE_SIZE) {
                imageInput.value = "";

                setStatus(
                    imageStatus,
                    "O arquivo excede o limite de 10 MB.",
                    "error"
                );

                return;
            }

            if (!preview || !previewImage || !fileName) {
                setStatus(
                    imageStatus,
                    "Não foi possível exibir a pré-visualização da imagem.",
                    "error"
                );

                return;
            }

            previewUrl = URL.createObjectURL(file);
            previewImage.src = previewUrl;

            fileName.textContent =
                `${file.name} (${formatNumber(Math.ceil(file.size / 1024))} KB)`;

            preview.hidden = false;

            setStatus(imageStatus);
        });

        /* Processamento da imagem */

        if (imageForm) {
            imageForm.addEventListener("submit", async (event) => {
                event.preventDefault();

                const file = imageInput.files?.[0];
                const width = Number($("#image-width")?.value);
                const height = Number($("#image-height")?.value);

                const submitButton = $('button[type="submit"]', imageForm);

                if (!file) {
                    setStatus(
                        imageStatus,
                        "Selecione uma imagem primeiro.",
                        "error"
                    );

                    return;
                }

                if (!file.type.startsWith("image/")) {
                    setStatus(
                        imageStatus,
                        "Selecione um arquivo de imagem válido.",
                        "error"
                    );

                    return;
                }

                if (file.size > MAX_FILE_SIZE) {
                    setStatus(
                        imageStatus,
                        "O arquivo excede o limite de 10 MB.",
                        "error"
                    );

                    return;
                }

                const validDimensions =
                    Number.isInteger(width) &&
                    Number.isInteger(height) &&
                    width >= 1 &&
                    height >= 1 &&
                    width <= MAX_DIMENSION &&
                    height <= MAX_DIMENSION;

                if (!validDimensions) {
                    setStatus(
                        imageStatus,
                        "Informe dimensões entre 1 e 5000 pixels.",
                        "error"
                    );

                    return;
                }

                const formData = new FormData();

                formData.append("file", file);
                formData.append("width", String(width));
                formData.append("height", String(height));

                if (submitButton) {
                    submitButton.disabled = true;
                }

                setStatus(imageStatus, "Processando imagem...");

                try {
                    const response = await fetch("/api/images/resize", {
                        method: "POST",
                        body: formData
                    });

                    if (!response.ok) {
                        let message = "Não foi possível redimensionar a imagem.";

                        try {
                            const body = await response.json();

                            if (typeof body.detail === "string" && body.detail) {
                                message = body.detail;
                            }
                        } catch {
                            // A resposta pode não conter JSON válido.
                        }

                        throw new Error(message);
                    }

                    const blob = await response.blob();

                    if (!blob.size) {
                        throw new Error("A API retornou um arquivo vazio.");
                    }

                    const downloadUrl = URL.createObjectURL(blob);
                    const link = document.createElement("a");

                    link.href = downloadUrl;
                    link.download = "imagem-redimensionada.jpg";

                    document.body.appendChild(link);
                    link.click();
                    link.remove();

                    // Dá tempo ao navegador para iniciar o download.
                    window.setTimeout(() => {
                        URL.revokeObjectURL(downloadUrl);
                    }, 1000);

                    setStatus(
                        imageStatus,
                        "Imagem processada. O download deve começar automaticamente.",
                        "success"
                    );
                } catch (error) {
                    const message = error instanceof TypeError
                        ? "Não foi possível conectar à API de imagens. Verifique a conexão e o endereço da API."
                        : error.message || "Ocorreu um erro ao processar a imagem.";

                    setStatus(imageStatus, message, "error");
                } finally {
                    if (submitButton) {
                        submitButton.disabled = false;
                    }
                }
            });
        }

        /* Limpeza ao sair da página */

        window.addEventListener("pagehide", releasePreviewUrl);
    }

    /* ========================================
       UNIÃO DE ARQUIVOS PDF
    ======================================== */

    const pdfInput = $("#pdf-files");

    if (pdfInput) {
        const fileList = $("#pdf-file-list");
        const pdfStatus = $("#pdf-status");
        const pdfForm = $("#pdf-merge-form");

        /* Listar arquivos selecionados */

        pdfInput.addEventListener("change", () => {
            if (fileList) {
                fileList.replaceChildren();

                const files = Array.from(pdfInput.files || []);
                const fragment = document.createDocumentFragment();

                files.forEach((file) => {
                    const item = document.createElement("li");

                    item.textContent =
                        `${file.name} (${formatNumber(Math.ceil(file.size / 1024))} KB)`;

                    fragment.appendChild(item);
                });

                fileList.appendChild(fragment);
            }

            const files = Array.from(pdfInput.files || []);

            if (files.length === 1) {
                setStatus(
                    pdfStatus,
                    "Selecione pelo menos dois arquivos PDF.",
                    "error"
                );

                return;
            }

            setStatus(pdfStatus);
        });

        /* Preparar união dos PDFs */

        if (pdfForm) {
            pdfForm.addEventListener("submit", (event) => {
                event.preventDefault();

                const files = Array.from(pdfInput.files || []);

                if (files.length < 2) {
                    setStatus(
                        pdfStatus,
                        "Selecione pelo menos dois arquivos PDF.",
                        "error"
                    );

                    return;
                }

                const invalidFile = files.some((file) => {
                    return file.type !== "application/pdf" &&
                        !file.name.toLowerCase().endsWith(".pdf");
                });

                if (invalidFile) {
                    setStatus(
                        pdfStatus,
                        "Verifique os arquivos selecionados. Todos devem ser PDFs.",
                        "error"
                    );

                    return;
                }

                setStatus(
                    pdfStatus,
                    "A interface está pronta, mas a união de PDFs ainda depende da implementação e configuração do endpoint na API.",
                    "error"
                );
            });
        }
    }
})();