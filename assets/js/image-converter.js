
"use strict";

const converter = document.querySelector("#image-converter");

if (converter) {
    const fileInput = converter.querySelector("#converter-file");
    const dropzone = converter.querySelector("#converter-dropzone");
    const loading = converter.querySelector("#converter-loading");
    const workspace = converter.querySelector("#converter-workspace");
    const errorBox = converter.querySelector("#converter-error");

    const preview = converter.querySelector("#converter-preview");
    const fileName = converter.querySelector("#converter-file-name");
    const originalFormat = converter.querySelector("#converter-original-format");
    const originalSize = converter.querySelector("#converter-original-size");
    const dimensions = converter.querySelector("#converter-dimensions");

    const formatInputs = converter.querySelectorAll(
        'input[name="converter-format"]'
    );
    const qualitySection = converter.querySelector(
        "#converter-quality-section"
    );
    const qualityInput = converter.querySelector("#converter-quality");
    const qualityValue = converter.querySelector("#converter-quality-value");
    const formatHint = converter.querySelector("#converter-format-hint");

    const convertButton = converter.querySelector("#converter-button");
    const downloadButton = converter.querySelector("#converter-download");
    const removeButton = converter.querySelector("#converter-remove");
    const changeButton = converter.querySelector("#converter-change");

    const resultBox = converter.querySelector("#converter-result");
    const resultTitle = converter.querySelector("#converter-result-title");
    const resultDetails = converter.querySelector("#converter-result-details");

    const MAX_FILE_SIZE = 10 * 1024 * 1024;
    const MAX_PIXELS = 40000000;
    const MAX_SIDE = 30000;

    let sourceImage = null;
    let sourceFile = null;
    let sourceURL = null;
    let convertedBlob = null;
    let operationId = 0;

    function showError(message) {
        errorBox.textContent = message;
        errorBox.hidden = false;
    }

    function clearError() {
        errorBox.textContent = "";
        errorBox.hidden = true;
    }

    function formatBytes(bytes) {
        if (bytes < 1024) {
            return `${bytes} B`;
        }

        if (bytes < 1024 * 1024) {
            return `${(bytes / 1024).toFixed(1)} KB`;
        }

        return `${(bytes / (1024 * 1024)).toFixed(2)} MB`;
    }

    function getSelectedFormat() {
        return converter.querySelector(
            'input[name="converter-format"]:checked'
        ).value;
    }

    function getExtension(mimeType) {
        const extensions = {
            "image/jpeg": "jpg",
            "image/png": "png",
            "image/webp": "webp"
        };

        return extensions[mimeType] || "png";
    }

    function getFormatName(mimeType) {
        const formats = {
            "image/jpeg": "JPG",
            "image/png": "PNG",
            "image/webp": "WebP"
        };

        return formats[mimeType] || "Unknown";
    }

    function getBaseName(name) {
        return name.replace(/\.[^.]+$/, "") || "image";
    }

    function resetResult() {
        convertedBlob = null;
        resultBox.hidden = true;
        resultDetails.textContent = "";
        downloadButton.disabled = true;
    }

    function updateFormatUI() {
        const format = getSelectedFormat();
        const isPng = format === "image/png";

        qualitySection.hidden = isPng;
        qualityInput.disabled = isPng;
        qualityValue.textContent = `${qualityInput.value}%`;

        if (format === "image/webp") {
            formatHint.textContent =
                "WebP supports transparency and usually provides a good balance between image quality and file size.";
        } else if (format === "image/jpeg") {
            formatHint.textContent =
                "JPG is widely supported and suitable for photographs. Transparent areas will become white.";
        } else {
            formatHint.textContent =
                "PNG supports transparency and lossless output. The converted file may be larger than the original.";
        }

        resetResult();
    }

    function resetTool() {
        operationId++;

        sourceImage = null;
        sourceFile = null;
        convertedBlob = null;

        if (sourceURL) {
            URL.revokeObjectURL(sourceURL);
            sourceURL = null;
        }

        preview.removeAttribute("src");
        fileInput.value = "";

        dropzone.hidden = false;
        loading.hidden = true;
        workspace.hidden = true;

        originalFormat.textContent = "—";
        originalSize.textContent = "—";
        dimensions.textContent = "—";

        resetResult();
        clearError();

        convertButton.disabled = false;
        convertButton.innerHTML =
            '<span class="material-symbols-outlined" aria-hidden="true">sync_alt</span> Convert image';
    }

    async function loadImage(file) {
        if (!file) return;

        clearError();

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

        if (file.size === 0) {
            showError("The selected file is empty.");
            fileInput.value = "";
            return;
        }

        if (file.size > MAX_FILE_SIZE) {
            showError("The image must be smaller than 10 MB.");
            fileInput.value = "";
            return;
        }

        const currentOperation = ++operationId;

        // Invalidate any previous image while loading a new one.
        sourceImage = null;
        sourceFile = null;
        convertedBlob = null;

        if (sourceURL) {
            URL.revokeObjectURL(sourceURL);
            sourceURL = null;
        }

        resetResult();

        dropzone.hidden = true;
        workspace.hidden = true;
        loading.hidden = false;

        let temporaryURL = null;

        try {
            temporaryURL = URL.createObjectURL(file);

            const image = new Image();

            await new Promise((resolve, reject) => {
                image.onload = resolve;
                image.onerror = () => reject(
                    new Error("The selected file is not a valid image.")
                );

                image.src = temporaryURL;
            });

            if (currentOperation !== operationId) return;

            const width = image.naturalWidth;
            const height = image.naturalHeight;

            if (
                !width ||
                !height ||
                width > MAX_SIDE ||
                height > MAX_SIDE ||
                width * height > MAX_PIXELS
            ) {
                throw new Error(
                    "This image is too large to process safely."
                );
            }

            sourceImage = image;
            sourceFile = file;
            sourceURL = temporaryURL;
            temporaryURL = null;

            preview.src = sourceURL;
            fileName.textContent = file.name;
            originalFormat.textContent = getFormatName(file.type);
            originalSize.textContent = formatBytes(file.size);
            dimensions.textContent = `${width} × ${height} px`;

            loading.hidden = true;
            workspace.hidden = false;

            updateFormatUI();

        } catch (error) {
            if (currentOperation !== operationId) return;

            loading.hidden = true;
            dropzone.hidden = false;
            workspace.hidden = true;

            showError(error.message || "Unable to load this image.");

        } finally {
            if (temporaryURL) {
                URL.revokeObjectURL(temporaryURL);
            }
        }
    }

    async function convertImage() {
        if (!sourceImage || !sourceFile) return;

        clearError();
        resetResult();

        const currentOperation = operationId;

        convertButton.disabled = true;
        convertButton.textContent = "Converting...";

        try {
            await new Promise(resolve => requestAnimationFrame(resolve));

            if (currentOperation !== operationId) return;

            const format = getSelectedFormat();
            const quality = Number(qualityInput.value) / 100;

            const canvas = document.createElement("canvas");
            canvas.width = sourceImage.naturalWidth;
            canvas.height = sourceImage.naturalHeight;

            const context = canvas.getContext("2d");

            if (!context) {
                throw new Error(
                    "Your browser cannot process this image."
                );
            }

            // JPEG cannot contain transparency.
            if (format === "image/jpeg") {
                context.fillStyle = "#FFFFFF";
                context.fillRect(0, 0, canvas.width, canvas.height);
            }

            context.imageSmoothingEnabled = true;
            context.imageSmoothingQuality = "high";
            context.drawImage(sourceImage, 0, 0);

            const blob = await new Promise(resolve => {
                // PNG ignores the quality argument.
                canvas.toBlob(
                    resolve,
                    format,
                    format === "image/png" ? undefined : quality
                );
            });

            canvas.width = 0;
            canvas.height = 0;

            if (currentOperation !== operationId) return;

            if (!blob) {
                throw new Error(
                    "Unable to convert the image. Please try another format."
                );
            }

            // Avoid downloading PNG under a JPG or WebP extension
            // if the browser does not support the requested format.
            if (blob.type !== format) {
                throw new Error(
                    "Your browser does not support this output format. Please choose another format."
                );
            }

            convertedBlob = blob;

            const difference = sourceFile.size - blob.size;
            const percentage = (difference / sourceFile.size) * 100;

            resultTitle.textContent =
                `Conversion complete — ${getFormatName(blob.type)}`;

            if (difference > 0) {
                resultDetails.textContent =
                    `Original: ${formatBytes(sourceFile.size)}. Converted: ${formatBytes(blob.size)}. File size reduced by ${percentage.toFixed(1)}%.`;
            } else if (difference < 0) {
                resultDetails.textContent =
                    `Original: ${formatBytes(sourceFile.size)}. Converted: ${formatBytes(blob.size)}. The converted file is ${formatBytes(Math.abs(difference))} larger.`;
            } else {
                resultDetails.textContent =
                    `Original and converted files are both ${formatBytes(blob.size)}.`;
            }

            resultBox.hidden = false;
            downloadButton.disabled = false;

        } catch (error) {
            if (currentOperation === operationId) {
                showError(error.message || "Image conversion failed.");
            }
        } finally {
            if (currentOperation === operationId) {
                convertButton.disabled = false;
                convertButton.innerHTML =
                    '<span class="material-symbols-outlined" aria-hidden="true">sync_alt</span> Convert image';
            }
        }
    }

    fileInput.addEventListener("change", () => {
        const file = fileInput.files?.[0];

        if (file) {
            loadImage(file);
        }
    });

    dropzone.addEventListener("click", () => {
        fileInput.click();
    });

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

        if (file) {
            loadImage(file);
        }
    });

    formatInputs.forEach(input => {
        input.addEventListener("change", updateFormatUI);
    });

    qualityInput.addEventListener("input", () => {
        qualityValue.textContent = `${qualityInput.value}%`;
        resetResult();
    });

    convertButton.addEventListener("click", convertImage);

    downloadButton.addEventListener("click", () => {
        if (!convertedBlob || !sourceFile) return;

        const downloadURL = URL.createObjectURL(convertedBlob);
        const link = document.createElement("a");
        const extension = getExtension(convertedBlob.type);

        link.href = downloadURL;
        link.download =
            `${getBaseName(sourceFile.name)}-converted.${extension}`;

        document.body.appendChild(link);
        link.click();
        link.remove();

        setTimeout(() => URL.revokeObjectURL(downloadURL), 1000);
    });

    removeButton.addEventListener("click", resetTool);

    changeButton.addEventListener("click", () => {
        resetTool();
        fileInput.click();
    });

    updateFormatUI();
}
