
"use strict";

const compressor = document.querySelector("#image-compressor");

if (compressor) {
    const fileInput = compressor.querySelector("#compress-file");
    const dropzone = compressor.querySelector("#compress-dropzone");
    const loading = compressor.querySelector("#compress-loading");
    const workspace = compressor.querySelector("#compress-workspace");
    const errorBox = compressor.querySelector("#compress-error");

    const preview = compressor.querySelector("#compress-preview");
    const fileName = compressor.querySelector("#compress-file-name");
    const originalSize = compressor.querySelector("#compress-original-size");
    const resultSize = compressor.querySelector("#compress-result-size");
    const dimensions = compressor.querySelector("#compress-dimensions");

    const formatInput = compressor.querySelector("#compress-format");
    const qualityInput = compressor.querySelector("#compress-quality");
    const qualityValue = compressor.querySelector("#compress-quality-value");
    const formatHint = compressor.querySelector("#compress-format-hint");

    const compressButton = compressor.querySelector("#compress-button");
    const downloadButton = compressor.querySelector("#compress-download");
    const removeButton = compressor.querySelector("#compress-remove");
    const changeButton = compressor.querySelector("#compress-change");

    const resultBox = compressor.querySelector("#compress-result");
    const resultTitle = compressor.querySelector("#compress-result-title");
    const savingsText = compressor.querySelector("#compress-savings");

    const MAX_FILE_SIZE = 10 * 1024 * 1024;
    const MAX_PIXELS = 40000000;
    const MAX_SIDE = 30000;

    let sourceImage = null;
    let sourceFile = null;
    let sourceURL = null;
    let compressedBlob = null;
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

    function getExtension(mimeType) {
        const extensions = {
            "image/jpeg": "jpg",
            "image/png": "png",
            "image/webp": "webp"
        };

        return extensions[mimeType] || "png";
    }

    function getBaseName(name) {
        return name.replace(/\.[^.]+$/, "") || "image";
    }

    function resetCompressionResult() {
        compressedBlob = null;
        resultSize.textContent = "Not compressed yet";
        resultBox.hidden = true;
        downloadButton.disabled = true;
    }

    function updateQualityUI() {
        const isPng = formatInput.value === "image/png";

        qualityValue.textContent = `${qualityInput.value}%`;
        qualityInput.disabled = isPng;

        if (isPng) {
            formatHint.textContent =
                "PNG is lossless. The quality slider does not apply to PNG, and the output may not be smaller.";
        } else if (formatInput.value === "image/jpeg") {
            formatHint.textContent =
                "JPEG is suitable for photographs. Lower quality usually produces a smaller file.";
        } else {
            formatHint.textContent =
                "WebP usually offers a good balance between image quality and file size.";
        }
    }

    function resetTool() {
        operationId++;

        sourceImage = null;
        sourceFile = null;
        compressedBlob = null;

        if (sourceURL) {
            URL.revokeObjectURL(sourceURL);
            sourceURL = null;
        }

        preview.removeAttribute("src");
        fileInput.value = "";

        dropzone.hidden = false;
        loading.hidden = true;
        workspace.hidden = true;

        originalSize.textContent = "—";
        resultSize.textContent = "Not compressed yet";
        dimensions.textContent = "—";

        resetCompressionResult();
        clearError();
        compressButton.disabled = false;
        compressButton.innerHTML =
            '<span class="material-symbols-outlined" aria-hidden="true">compress</span> Compress image';
    }

    async function loadImage(file) {
        if (!file) return;

        const allowedTypes = [
            "image/jpeg",
            "image/png",
            "image/webp"
        ];

        clearError();

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

        loading.hidden = false;
        dropzone.hidden = true;
        workspace.hidden = true;

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

            if (sourceURL) {
                URL.revokeObjectURL(sourceURL);
            }

            sourceImage = image;
            sourceFile = file;
            sourceURL = temporaryURL;
            temporaryURL = null;

            preview.src = sourceURL;
            fileName.textContent = file.name;
            originalSize.textContent = formatBytes(file.size);
            dimensions.textContent = `${width} × ${height} px`;

            resetCompressionResult();

            loading.hidden = true;
            workspace.hidden = false;

            updateQualityUI();

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

    async function compressImage() {
        if (!sourceImage || !sourceFile) return;

        clearError();
        resetCompressionResult();

        const currentOperation = operationId;

        compressButton.disabled = true;
        compressButton.textContent = "Compressing...";

        try {
            // Allow the browser to repaint the loading button.
            await new Promise(resolve => requestAnimationFrame(resolve));

            if (currentOperation !== operationId) return;

            const canvas = document.createElement("canvas");
            canvas.width = sourceImage.naturalWidth;
            canvas.height = sourceImage.naturalHeight;

            const context = canvas.getContext("2d");

            if (!context) {
                throw new Error("Your browser cannot process this image.");
            }

            const outputFormat = formatInput.value;
            const quality = Number(qualityInput.value) / 100;

            // JPEG does not support transparency, so use a white background.
            if (outputFormat === "image/jpeg") {
                context.fillStyle = "#FFFFFF";
                context.fillRect(0, 0, canvas.width, canvas.height);
            }

            context.imageSmoothingEnabled = true;
            context.imageSmoothingQuality = "high";
            context.drawImage(sourceImage, 0, 0);

            const blob = await new Promise(resolve => {
                canvas.toBlob(
                    resolve,
                    outputFormat,
                    quality
                );
            });

            canvas.width = 0;
            canvas.height = 0;

            if (currentOperation !== operationId) return;

            if (!blob) {
                throw new Error("Unable to generate the compressed image.");
            }

            // Some browsers may fall back to PNG for unsupported formats.
            if (blob.type !== outputFormat) {
                throw new Error(
                    "This output format is not supported by your browser. Please choose another format."
                );
            }

            compressedBlob = blob;

            const difference = sourceFile.size - blob.size;
            const percentage = (difference / sourceFile.size) * 100;

            resultSize.textContent = formatBytes(blob.size);

            if (difference > 0) {
                resultTitle.textContent = "Compression complete";
                savingsText.textContent =
                    `Saved ${formatBytes(difference)} (${percentage.toFixed(1)}%).`;
            } else if (difference < 0) {
                resultTitle.textContent = "Image converted";
                savingsText.textContent =
                    `The new file is ${formatBytes(Math.abs(difference))} larger. Try WebP or a lower quality setting.`;
            } else {
                resultTitle.textContent = "Image processed";
                savingsText.textContent =
                    "The resulting file has approximately the same size as the original.";
            }

            resultBox.hidden = false;
            downloadButton.disabled = false;

        } catch (error) {
            if (currentOperation === operationId) {
                showError(error.message || "Compression failed.");
            }
        } finally {
            if (currentOperation === operationId) {
                compressButton.disabled = false;
                compressButton.innerHTML =
                    '<span class="material-symbols-outlined" aria-hidden="true">compress</span> Compress image';
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

    qualityInput.addEventListener("input", () => {
        qualityValue.textContent = `${qualityInput.value}%`;
        resetCompressionResult();
    });

    formatInput.addEventListener("change", () => {
        updateQualityUI();
        resetCompressionResult();
    });

    compressButton.addEventListener("click", compressImage);

    downloadButton.addEventListener("click", () => {
        if (!compressedBlob || !sourceFile) return;

        const downloadURL = URL.createObjectURL(compressedBlob);
        const link = document.createElement("a");
        const extension = getExtension(compressedBlob.type);

        link.href = downloadURL;
        link.download = `${getBaseName(sourceFile.name)}-compressed.${extension}`;

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

    updateQualityUI();
}
