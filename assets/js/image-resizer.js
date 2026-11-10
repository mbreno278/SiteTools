"use strict";

const imageResizer = document.querySelector("#image-resizer");

if (imageResizer) {
    const fileInput = imageResizer.querySelector("#image-file");
    const uploadArea = imageResizer.querySelector("#image-upload-area");
    const dropzone = imageResizer.querySelector("#image-dropzone");
    const loading = imageResizer.querySelector("#image-loading");
    const editor = imageResizer.querySelector("#image-editor");
    const errorMessage = imageResizer.querySelector("#image-error");

    const preview = imageResizer.querySelector("#image-preview");
    const originalInfo = imageResizer.querySelector("#image-original-info");
    const previewInfo = imageResizer.querySelector("#image-preview-info");

    const widthInput = imageResizer.querySelector("#image-width");
    const heightInput = imageResizer.querySelector("#image-height");
    const keepRatio = imageResizer.querySelector("#image-keep-ratio");
    const outputInfo = imageResizer.querySelector("#image-output-info");

    const downloadButton = imageResizer.querySelector("#image-download");
    const removeButton = imageResizer.querySelector("#image-remove");
    const changeButton = imageResizer.querySelector("#image-change");

    const required = [
        fileInput, uploadArea, dropzone, loading, editor, errorMessage,
        preview, originalInfo, previewInfo, widthInput, heightInput,
        keepRatio, outputInfo, downloadButton, removeButton, changeButton
    ];

    if (required.some((el) => !el)) {
        console.warn(
            "[image-resizer] Missing required elements inside #image-resizer. Script aborted safely."
        );
    } else {

    const MAX_FILE_SIZE = 10 * 1024 * 1024;
    const MAX_DIMENSION = 5000;
    const MIN_LOADING_TIME = 450;

    // Qualidade padrão para JPEG / WebP (PNG é lossless)
    const OUTPUT_QUALITY = 0.92;

    let originalImage = null;
    let originalFile = null;
    let originalURL = null;
    let aspectRatio = 1;
    let selectionId = 0;

    function showError(message) {
        errorMessage.textContent = message;
        errorMessage.hidden = false;
    }

    function clearError() {
        errorMessage.textContent = "";
        errorMessage.hidden = true;
    }

    function getDimensions() {
        return {
            width: Number(widthInput.value),
            height: Number(heightInput.value)
        };
    }

    function isValidDimension(value) {
        return Number.isInteger(value) &&
            value >= 1 &&
            value <= MAX_DIMENSION;
    }

    /**
     * Resolve MIME e extensão a partir do arquivo original.
     * Fallback para PNG se o tipo for desconhecido.
     */
    function resolveOutputFormat(file) {
        const type = (file && file.type) || "image/png";

        const map = {
            "image/jpeg": { mime: "image/jpeg", ext: "jpg", quality: OUTPUT_QUALITY },
            "image/jpg":  { mime: "image/jpeg", ext: "jpg", quality: OUTPUT_QUALITY },
            "image/png":  { mime: "image/png",  ext: "png", quality: undefined },
            "image/webp": { mime: "image/webp", ext: "webp", quality: OUTPUT_QUALITY }
        };

        return map[type] || { mime: "image/png", ext: "png", quality: undefined };
    }

    function getBaseName(name) {
        return (name || "image").replace(/\.[^.]+$/, "") || "image";
    }

    function updatePreview() {
        if (!originalImage) return;

        const { width, height } = getDimensions();

        if (!isValidDimension(width) || !isValidDimension(height)) {
            outputInfo.textContent = "Invalid dimensions";
            downloadButton.disabled = true;
            return;
        }

        outputInfo.textContent = `${width} × ${height} px`;
        previewInfo.textContent =
            `Output: ${width} × ${height} px`;

        // Ajusta a visualização sem criar um canvas gigante.
        const container = preview.parentElement;
        const availableWidth = Math.max(100, container.clientWidth - 32);
        const availableHeight = 348;

        const scale = Math.min(
            availableWidth / width,
            availableHeight / height,
            1
        );

        preview.style.width = "auto";
        preview.style.height = "auto";
        preview.style.maxWidth = "100%";
        preview.style.maxHeight = "380px";

        downloadButton.disabled = false;
    }

    function resetEditor() {
        selectionId++;

        originalImage = null;
        originalFile = null;
        aspectRatio = 1;

        if (originalURL) {
            URL.revokeObjectURL(originalURL);
            originalURL = null;
        }

        preview.removeAttribute("src");
        preview.style.width = "";
        preview.style.height = "";

        fileInput.value = "";

        uploadArea.hidden = false;
        loading.hidden = true;
        editor.hidden = true;

        widthInput.value = "";
        heightInput.value = "";

        downloadButton.disabled = false;

        clearError();
    }

    async function loadImage(file) {
        const currentSelection = ++selectionId;

        clearError();

        if (!file) return;

        const allowedTypes = [
            "image/jpeg",
            "image/png",
            "image/webp"
        ];

        if (!allowedTypes.includes(file.type)) {
            showError("Please select a JPG, PNG or WebP image.");
            fileInput.value = "";
            return;
        }

        if (file.size > MAX_FILE_SIZE) {
            showError("The image must be smaller than 10 MB.");
            fileInput.value = "";
            return;
        }

        if (file.size === 0) {
            showError("The selected file is empty.");
            fileInput.value = "";
            return;
        }

        uploadArea.hidden = true;
        editor.hidden = true;
        loading.hidden = false;

        const startedAt = performance.now();
        let temporaryURL = null;

        try {
            // Permite que o spinner seja renderizado antes do processamento.
            await new Promise(resolve => requestAnimationFrame(resolve));

            temporaryURL = URL.createObjectURL(file);

            const image = new Image();

            await new Promise((resolve, reject) => {
                image.onload = resolve;
                image.onerror = () => reject(
                    new Error("The selected file is not a valid image.")
                );
                image.src = temporaryURL;
            });

            if (currentSelection !== selectionId) {
                URL.revokeObjectURL(temporaryURL);
                return;
            }

            const naturalWidth = image.naturalWidth;
            const naturalHeight = image.naturalHeight;

            if (
                !naturalWidth ||
                !naturalHeight ||
                naturalWidth > MAX_DIMENSION ||
                naturalHeight > MAX_DIMENSION
            ) {
                throw new Error(
                    `Image dimensions must be between 1 and ${MAX_DIMENSION} px.`
                );
            }

            const elapsed = performance.now() - startedAt;
            const remaining = MIN_LOADING_TIME - elapsed;

            if (remaining > 0) {
                await new Promise(resolve => setTimeout(resolve, remaining));
            }

            if (currentSelection !== selectionId) {
                URL.revokeObjectURL(temporaryURL);
                return;
            }

            if (originalURL) {
                URL.revokeObjectURL(originalURL);
            }

            originalImage = image;
            originalFile = file;
            originalURL = temporaryURL;
            temporaryURL = null;
            aspectRatio = naturalWidth / naturalHeight;

            preview.src = originalURL;
            originalInfo.textContent =
                `Original: ${naturalWidth} × ${naturalHeight} px`;

            widthInput.value = naturalWidth;
            heightInput.value = naturalHeight;

            loading.hidden = true;
            editor.hidden = false;

            updatePreview();

        } catch (error) {
            if (currentSelection !== selectionId) return;

            loading.hidden = true;
            uploadArea.hidden = false;
            editor.hidden = true;

            showError(error.message || "Unable to load this image.");

            if (temporaryURL) {
                URL.revokeObjectURL(temporaryURL);
            }
        }
    }

    fileInput.addEventListener("change", () => {
        const file = fileInput.files?.[0];
        if (file) loadImage(file);
    });

    widthInput.addEventListener("input", () => {
        if (!originalImage) return;

        const width = Number(widthInput.value);

        if (keepRatio.checked && isValidDimension(width)) {
            const height = Math.round(width / aspectRatio);

            heightInput.value = height <= MAX_DIMENSION
                ? height
                : "";
        }

        updatePreview();
    });

    heightInput.addEventListener("input", () => {
        if (!originalImage) return;

        const height = Number(heightInput.value);

        if (keepRatio.checked && isValidDimension(height)) {
            const width = Math.round(height * aspectRatio);

            widthInput.value = width <= MAX_DIMENSION
                ? width
                : "";
        }

        updatePreview();
    });

    keepRatio.addEventListener("change", () => {
        if (!originalImage || !keepRatio.checked) return;

        const width = Number(widthInput.value);

        if (isValidDimension(width)) {
            const height = Math.round(width / aspectRatio);

            heightInput.value = height <= MAX_DIMENSION
                ? height
                : "";
        }

        updatePreview();
    });

    downloadButton.addEventListener("click", () => {
        if (!originalImage || !originalFile) return;

        const { width, height } = getDimensions();

        if (!isValidDimension(width) || !isValidDimension(height)) {
            showError(
                "Enter valid width and height values between 1 and 5000."
            );
            return;
        }

        clearError();

        const format = resolveOutputFormat(originalFile);

        const canvas = document.createElement("canvas");
        canvas.width = width;
        canvas.height = height;

        const context = canvas.getContext("2d");

        if (!context) {
            showError("Your browser cannot process this image.");
            return;
        }

        // JPEG não tem transparência — fundo branco evita preencher com preto
        if (format.mime === "image/jpeg") {
            context.fillStyle = "#FFFFFF";
            context.fillRect(0, 0, width, height);
        }

        context.imageSmoothingEnabled = true;
        context.imageSmoothingQuality = "high";
        context.drawImage(originalImage, 0, 0, width, height);

        downloadButton.disabled = true;

        const toBlobArgs = format.quality !== undefined
            ? [format.mime, format.quality]
            : [format.mime];

        canvas.toBlob(blob => {
            downloadButton.disabled = false;

            if (!blob) {
                showError("Unable to generate the resized image.");
                canvas.width = 0;
                canvas.height = 0;
                return;
            }

            // Alguns browsers podem cair em PNG se o formato não for suportado
            const effectiveExt = blob.type === format.mime
                ? format.ext
                : (blob.type === "image/jpeg" ? "jpg"
                    : blob.type === "image/webp" ? "webp"
                    : "png");

            const baseName = getBaseName(originalFile.name);
            const downloadURL = URL.createObjectURL(blob);
            const link = document.createElement("a");

            link.href = downloadURL;
            link.download = `${baseName}-resized.${effectiveExt}`;

            document.body.appendChild(link);
            link.click();
            link.remove();

            setTimeout(() => URL.revokeObjectURL(downloadURL), 1000);
            canvas.width = 0;
            canvas.height = 0;

        }, ...toBlobArgs);
    });

    removeButton.addEventListener("click", resetEditor);

    changeButton.addEventListener("click", () => {
        resetEditor();
        fileInput.click();
    });

    // Drag and drop
    ["dragenter", "dragover"].forEach(eventName => {
        dropzone.addEventListener(eventName, event => {
            event.preventDefault();
            dropzone.classList.add("is-dragging");
        });
    });

    ["dragleave", "drop"].forEach(eventName => {
        dropzone.addEventListener(eventName, event => {
            event.preventDefault();
            dropzone.classList.remove("is-dragging");
        });
    });

    dropzone.addEventListener("drop", event => {
        const file = event.dataTransfer?.files?.[0];

        if (!file) return;

        // Passa o arquivo para o input, mantendo o estado consistente.
        try {
            const transfer = new DataTransfer();
            transfer.items.add(file);
            fileInput.files = transfer.files;
        } catch {
            // O arquivo ainda pode ser processado diretamente.
        }

        loadImage(file);
    });

    window.addEventListener("resize", updatePreview);
}
}
