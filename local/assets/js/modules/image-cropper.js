"use strict";

/**
 * Image Cropper — defensive version
 * Aborts safely if required DOM nodes are missing (won't break theme/login).
 */
(function () {
  const cropper = document.querySelector("#image-cropper");
  if (!cropper) return;

  const fileInput = cropper.querySelector("#crop-file");
  const dropzone = cropper.querySelector("#crop-dropzone");
  const loading = cropper.querySelector("#crop-loading");
  const workspace = cropper.querySelector("#crop-workspace");
  const errorBox = cropper.querySelector("#crop-error");

  const stage = cropper.querySelector("#crop-stage");
  const preview = cropper.querySelector("#crop-preview");
  const selection = cropper.querySelector("#crop-selection");

  const ratioInput = cropper.querySelector("#crop-ratio");
  const formatInput = cropper.querySelector("#crop-format");
  const qualitySection = cropper.querySelector("#crop-quality-section");
  const qualityInput = cropper.querySelector("#crop-quality");
  const qualityValue = cropper.querySelector("#crop-quality-value");

  const dimensionsLabel = cropper.querySelector("#crop-dimensions");
  const fileInfo = cropper.querySelector("#crop-file-info");

  const downloadButton = cropper.querySelector("#crop-download");
  const resetButton = cropper.querySelector("#crop-reset");
  const removeButton = cropper.querySelector("#crop-remove");
  const changeButton = cropper.querySelector("#crop-change");

  const required = [
    fileInput, dropzone, loading, workspace, errorBox, stage, preview,
    selection, ratioInput, formatInput, qualitySection, qualityInput,
    qualityValue, dimensionsLabel, fileInfo, downloadButton, resetButton,
    removeButton, changeButton
  ];

  if (required.some((el) => !el)) {
    console.warn(
      "[image-cropper] Missing required elements inside #image-cropper. Script aborted safely."
    );
    return;
  }

  const toolId = Number(cropper.dataset.toolId);
  const historyURL = cropper.dataset.historyUrl;

  const MAX_FILE_SIZE = 10 * 1024 * 1024;
  const MAX_PIXELS = 40000000;
  const MAX_SIDE = 30000;
  const MIN_SELECTION = 24;

  const ratios = {
    free: null,
    "1:1": 1,
    "4:3": 4 / 3,
    "3:2": 3 / 2,
    "16:9": 16 / 9
  };

  let sourceImage = null;
  let sourceFile = null;
  let sourceURL = null;
  let operationId = 0;

  let crop = { x: 0, y: 0, w: 0, h: 0 };
  let interaction = null;
  let stageWidth = 0;
  let stageHeight = 0;

  async function saveToolUsage({ status, processingTime, fileSize }) {
    if (!historyURL || !Number.isInteger(toolId) || toolId <= 0) {
      return false;
    }

    try {
      const response = await fetch(historyURL, {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({
          tool_id: toolId,
          status: status,
          processing_time_ms: processingTime,
          file_size_bytes: fileSize
        })
      });

      const result = await response.json().catch(() => ({}));

      if (!response.ok || !result.success) {
        throw new Error(result.message || "Unable to save usage.");
      }

      return true;
    } catch (error) {
      console.error("Tool usage history error:", error);
      return false;
    }
  }

  function showError(message) {
    errorBox.textContent = message;
    errorBox.hidden = false;
  }

  function clearError() {
    errorBox.textContent = "";
    errorBox.hidden = true;
  }

  function formatBytes(bytes) {
    if (bytes < 1024) return `${bytes} B`;
    if (bytes < 1024 * 1024) return `${(bytes / 1024).toFixed(1)} KB`;
    return `${(bytes / (1024 * 1024)).toFixed(2)} MB`;
  }

  function getSelectedRatio() {
    return ratios[ratioInput.value] ?? null;
  }

  function getSelectedFormat() {
    return formatInput.value || "image/png";
  }

  function getExtension(type) {
    return (
      {
        "image/jpeg": "jpg",
        "image/png": "png",
        "image/webp": "webp"
      }[type] || "png"
    );
  }

  function getBaseName(name) {
    return (name || "image").replace(/\.[^.]+$/, "") || "image";
  }

  function clamp(value, min, max) {
    return Math.min(Math.max(value, min), max);
  }

  function getStagePoint(event) {
    const rect = stage.getBoundingClientRect();
    return {
      x: clamp(event.clientX - rect.left, 0, stageWidth),
      y: clamp(event.clientY - rect.top, 0, stageHeight)
    };
  }

  function getNaturalCrop() {
    const scaleX = sourceImage.naturalWidth / stageWidth;
    const scaleY = sourceImage.naturalHeight / stageHeight;

    const x = clamp(
      Math.round(crop.x * scaleX),
      0,
      sourceImage.naturalWidth - 1
    );

    const y = clamp(
      Math.round(crop.y * scaleY),
      0,
      sourceImage.naturalHeight - 1
    );

    const right = clamp(
      Math.round((crop.x + crop.w) * scaleX),
      x + 1,
      sourceImage.naturalWidth
    );

    const bottom = clamp(
      Math.round((crop.y + crop.h) * scaleY),
      y + 1,
      sourceImage.naturalHeight
    );

    return {
      x,
      y,
      width: right - x,
      height: bottom - y
    };
  }

  function renderCrop() {
    selection.style.left = `${crop.x}px`;
    selection.style.top = `${crop.y}px`;
    selection.style.width = `${crop.w}px`;
    selection.style.height = `${crop.h}px`;

    if (sourceImage) {
      const natural = getNaturalCrop();
      dimensionsLabel.textContent = `${natural.width} × ${natural.height} px`;
    }
  }

  function fitStage() {
    if (!sourceImage) return;

    const wrapper = cropper.querySelector(".image-cropper__stage-wrapper");
    if (!wrapper) return;

    const availableWidth = Math.max(100, wrapper.clientWidth - 24);
    const naturalWidth = sourceImage.naturalWidth;
    const naturalHeight = sourceImage.naturalHeight;

    const scale = Math.min(
      availableWidth / naturalWidth,
      560 / naturalHeight,
      1
    );

    stageWidth = Math.max(1, Math.round(naturalWidth * scale));
    stageHeight = Math.max(1, Math.round(naturalHeight * scale));

    stage.style.width = `${stageWidth}px`;
    stage.style.height = `${stageHeight}px`;

    preview.style.width = "100%";
    preview.style.height = "100%";

    setInitialCrop();
  }

  function setInitialCrop() {
    if (!sourceImage) return;

    const ratio = getSelectedRatio();

    let width = stageWidth * 0.8;
    let height = stageHeight * 0.8;

    if (ratio) {
      if (width / height > ratio) {
        width = height * ratio;
      } else {
        height = width / ratio;
      }
    }

    crop = {
      x: (stageWidth - width) / 2,
      y: (stageHeight - height) / 2,
      w: width,
      h: height
    };

    renderCrop();
  }

  function resetTool() {
    operationId++;

    sourceImage = null;
    sourceFile = null;
    interaction = null;

    if (sourceURL) {
      URL.revokeObjectURL(sourceURL);
      sourceURL = null;
    }

    preview.removeAttribute("src");
    fileInput.value = "";

    stage.style.width = "";
    stage.style.height = "";

    dropzone.hidden = false;
    loading.hidden = true;
    workspace.hidden = true;

    dimensionsLabel.textContent = "—";
    fileInfo.textContent = "";

    downloadButton.disabled = false;
    clearError();
  }

  async function loadImage(file) {
    if (!file) return;

    clearError();

    const allowedTypes = ["image/jpeg", "image/png", "image/webp"];

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

    sourceImage = null;
    sourceFile = null;

    if (sourceURL) {
      URL.revokeObjectURL(sourceURL);
      sourceURL = null;
    }

    dropzone.hidden = true;
    workspace.hidden = true;
    loading.hidden = false;

    let temporaryURL = null;

    try {
      temporaryURL = URL.createObjectURL(file);

      const image = new Image();

      await new Promise((resolve, reject) => {
        image.onload = resolve;
        image.onerror = () =>
          reject(new Error("Unable to read the selected image."));
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
        throw new Error("This image is too large to process safely.");
      }

      sourceImage = image;
      sourceFile = file;
      sourceURL = temporaryURL;
      temporaryURL = null;

      preview.src = sourceURL;

      fileInfo.textContent = `${file.name} · ${width} × ${height} px · ${formatBytes(file.size)}`;

      loading.hidden = true;
      workspace.hidden = false;

      fitStage();
      updateFormatUI();
    } catch (error) {
      if (currentOperation !== operationId) return;

      loading.hidden = true;
      dropzone.hidden = false;
      workspace.hidden = true;

      showError(error.message || "Unable to load the image.");
    } finally {
      if (temporaryURL) {
        URL.revokeObjectURL(temporaryURL);
      }
    }
  }

  function updateFormatUI() {
    const isPng = getSelectedFormat() === "image/png";
    qualitySection.hidden = isPng;
    qualityInput.disabled = isPng;
    qualityValue.textContent = `${qualityInput.value}%`;
  }

  function getCropBoundsForRatio(anchorX, anchorY, pointX, pointY, handle) {
    const ratio = getSelectedRatio();

    let left = Math.min(anchorX, pointX);
    let right = Math.max(anchorX, pointX);
    let top = Math.min(anchorY, pointY);
    let bottom = Math.max(anchorY, pointY);

    if (!ratio) {
      return {
        x: left,
        y: top,
        w: right - left,
        h: bottom - top
      };
    }

    const anchors = {
      nw: { x: anchorX, y: anchorY, sx: -1, sy: -1 },
      ne: { x: anchorX, y: anchorY, sx: 1, sy: -1 },
      sw: { x: anchorX, y: anchorY, sx: -1, sy: 1 },
      se: { x: anchorX, y: anchorY, sx: 1, sy: 1 }
    };

    const anchor = anchors[handle];

    if (!anchor) {
      return {
        x: left,
        y: top,
        w: right - left,
        h: bottom - top
      };
    }

    const sx = anchor.sx;
    const sy = anchor.sy;

    let width = Math.abs(pointX - anchor.x);
    let height = Math.abs(pointY - anchor.y);

    if (width / Math.max(height, 0.001) > ratio) {
      width = height * ratio;
    } else {
      height = width / ratio;
    }

    width = Math.min(width, sx > 0 ? stageWidth - anchor.x : anchor.x);
    height = Math.min(height, sy > 0 ? stageHeight - anchor.y : anchor.y);

    if (width / Math.max(height, 0.001) > ratio) {
      width = height * ratio;
    } else {
      height = width / ratio;
    }

    return {
      x: sx > 0 ? anchor.x : anchor.x - width,
      y: sy > 0 ? anchor.y : anchor.y - height,
      w: width,
      h: height
    };
  }

  function startNewSelection(point) {
    interaction = {
      type: "new",
      startX: point.x,
      startY: point.y,
      original: { ...crop }
    };

    crop = {
      x: point.x,
      y: point.y,
      w: 0,
      h: 0
    };

    renderCrop();
  }

  function onPointerDown(event) {
    if (!sourceImage || event.button !== 0) return;

    const point = getStagePoint(event);
    const handleElement = event.target.closest("[data-handle]");
    const handle = handleElement?.dataset.handle;

    const isInside =
      point.x >= crop.x &&
      point.x <= crop.x + crop.w &&
      point.y >= crop.y &&
      point.y <= crop.y + crop.h;

    if (handle) {
      const anchors = {
        nw: { x: crop.x + crop.w, y: crop.y + crop.h },
        ne: { x: crop.x, y: crop.y + crop.h },
        sw: { x: crop.x + crop.w, y: crop.y },
        se: { x: crop.x, y: crop.y }
      };

      interaction = {
        type: "resize",
        handle,
        anchor: anchors[handle],
        original: { ...crop }
      };
    } else if (
      event.target === selection ||
      event.target.closest("#crop-selection")
    ) {
      interaction = {
        type: "move",
        startX: point.x,
        startY: point.y,
        original: { ...crop }
      };
    } else if (isInside) {
      interaction = {
        type: "move",
        startX: point.x,
        startY: point.y,
        original: { ...crop }
      };
    } else {
      startNewSelection(point);
    }

    event.preventDefault();

    try {
      stage.setPointerCapture(event.pointerId);
    } catch {
      // Pointer capture may be unavailable.
    }
  }

  function onPointerMove(event) {
    if (!interaction || !sourceImage) return;

    const point = getStagePoint(event);
    const state = interaction;

    if (state.type === "move") {
      const dx = point.x - state.startX;
      const dy = point.y - state.startY;

      crop = {
        ...state.original,
        x: clamp(state.original.x + dx, 0, stageWidth - state.original.w),
        y: clamp(state.original.y + dy, 0, stageHeight - state.original.h)
      };
    } else if (state.type === "new") {
      const ratio = getSelectedRatio();

      let width = Math.abs(point.x - state.startX);
      let height = Math.abs(point.y - state.startY);

      if (ratio) {
        if (width / Math.max(height, 0.001) > ratio) {
          width = height * ratio;
        } else {
          height = width / ratio;
        }
      }

      const directionX = point.x >= state.startX ? 1 : -1;
      const directionY = point.y >= state.startY ? 1 : -1;

      width = Math.min(
        width,
        directionX > 0 ? stageWidth - state.startX : state.startX
      );

      height = Math.min(
        height,
        directionY > 0 ? stageHeight - state.startY : state.startY
      );

      if (ratio) {
        if (width / Math.max(height, 0.001) > ratio) {
          width = height * ratio;
        } else {
          height = width / ratio;
        }
      }

      crop = {
        x: directionX > 0 ? state.startX : state.startX - width,
        y: directionY > 0 ? state.startY : state.startY - height,
        w: width,
        h: height
      };
    } else if (state.type === "resize") {
      crop = getCropBoundsForRatio(
        state.anchor.x,
        state.anchor.y,
        point.x,
        point.y,
        state.handle
      );

      crop.x = clamp(crop.x, 0, stageWidth);
      crop.y = clamp(crop.y, 0, stageHeight);
      crop.w = Math.min(crop.w, stageWidth - crop.x);
      crop.h = Math.min(crop.h, stageHeight - crop.y);
    }

    renderCrop();
  }

  function onPointerUp(event) {
    if (!interaction) return;

    interaction = null;

    try {
      if (stage.hasPointerCapture(event.pointerId)) {
        stage.releasePointerCapture(event.pointerId);
      }
    } catch {
      // Nothing to release.
    }
  }

  function resetCrop() {
    if (!sourceImage) return;
    clearError();
    setInitialCrop();
  }

  async function exportCrop() {
    if (!sourceImage || !sourceFile) return;

    clearError();

    if (crop.w < 1 || crop.h < 1) {
      showError("Select a valid area of the image before cropping.");
      return;
    }

    const currentOperation = operationId;
    const startedAt = performance.now();
    const originalFileSize = sourceFile.size;

    downloadButton.disabled = true;
    downloadButton.textContent = "Processing...";

    try {
      await new Promise((resolve) => requestAnimationFrame(resolve));

      if (currentOperation !== operationId) return;

      const bounds = getNaturalCrop();
      const format = getSelectedFormat();
      const quality = Number(qualityInput.value) / 100;

      const canvas = document.createElement("canvas");
      canvas.width = bounds.width;
      canvas.height = bounds.height;

      const context = canvas.getContext("2d");

      if (!context) {
        throw new Error("Your browser cannot process this image.");
      }

      if (format === "image/jpeg") {
        context.fillStyle = "#FFFFFF";
        context.fillRect(0, 0, canvas.width, canvas.height);
      }

      context.drawImage(
        sourceImage,
        bounds.x,
        bounds.y,
        bounds.width,
        bounds.height,
        0,
        0,
        bounds.width,
        bounds.height
      );

      const blob = await new Promise((resolve) => {
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
        throw new Error("Unable to export the cropped image.");
      }

      if (blob.type !== format) {
        throw new Error(
          "Your browser does not support this output format. Choose another format."
        );
      }

      const downloadURL = URL.createObjectURL(blob);
      const link = document.createElement("a");

      link.href = downloadURL;
      link.download = `${getBaseName(sourceFile.name)}-cropped.${getExtension(blob.type)}`;

      document.body.appendChild(link);
      link.click();
      link.remove();

      setTimeout(() => URL.revokeObjectURL(downloadURL), 1000);

      fileInfo.textContent = `Cropped: ${bounds.width} × ${bounds.height} px · ${formatBytes(blob.size)}`;

      await saveToolUsage({
        status: "completed",
        processingTime: Math.round(performance.now() - startedAt),
        fileSize: originalFileSize
      });
    } catch (error) {
      if (currentOperation === operationId) {
        showError(error.message || "Unable to crop this image.");

        await saveToolUsage({
          status: "failed",
          processingTime: Math.round(performance.now() - startedAt),
          fileSize: originalFileSize
        });
      }
    } finally {
      if (currentOperation === operationId) {
        downloadButton.disabled = false;
        downloadButton.innerHTML =
          '<span class="material-symbols-outlined" aria-hidden="true">download</span> Crop and download';
      }
    }
  }

  // Event listeners only after required elements verified
  fileInput.addEventListener("change", () => {
    const file = fileInput.files?.[0];
    if (file) loadImage(file);
  });

  dropzone.addEventListener("click", () => fileInput.click());

  ["dragenter", "dragover"].forEach((eventName) => {
    dropzone.addEventListener(eventName, (event) => {
      event.preventDefault();
      dropzone.classList.add("is-dragging");
    });
  });

  ["dragleave", "drop"].forEach((eventName) => {
    dropzone.addEventListener(eventName, (event) => {
      event.preventDefault();
      dropzone.classList.remove("is-dragging");
    });
  });

  dropzone.addEventListener("drop", (event) => {
    const file = event.dataTransfer?.files?.[0];
    if (file) loadImage(file);
  });

  stage.addEventListener("pointerdown", onPointerDown);
  stage.addEventListener("pointermove", onPointerMove);
  stage.addEventListener("pointerup", onPointerUp);
  stage.addEventListener("pointercancel", onPointerUp);

  ratioInput.addEventListener("change", resetCrop);
  formatInput.addEventListener("change", updateFormatUI);

  qualityInput.addEventListener("input", () => {
    qualityValue.textContent = `${qualityInput.value}%`;
  });

  resetButton.addEventListener("click", resetCrop);
  downloadButton.addEventListener("click", exportCrop);
  removeButton.addEventListener("click", resetTool);

  changeButton.addEventListener("click", () => {
    resetTool();
    fileInput.click();
  });

  window.addEventListener("resize", () => {
    if (!sourceImage) return;
    fitStage();
  });

  updateFormatUI();
})();
