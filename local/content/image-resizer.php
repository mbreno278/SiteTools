<div class="workspace__header">
    <div>
        <h2>Resize Your Image</h2>
        <p>
            Upload an image, customize its dimensions
            and download the resized result.
        </p>
    </div>
</div>

<div class="image-resizer" id="image-resizer">

    <!-- Upload -->
    <div class="image-resizer__upload" id="image-upload-area">

        <label
            class="image-resizer__dropzone"
            id="image-dropzone"
            for="image-file"
        >
            <span
                class="material-symbols-outlined image-resizer__upload-icon"
                aria-hidden="true"
            >cloud_upload</span>

            <strong>Choose an image to upload</strong>

            <span>Click to browse or drag and drop</span>

            <small>PNG, JPG or WebP · Maximum 10 MB</small>

            <input
                type="file"
                id="image-file"
                name="file"
                accept="image/png,image/jpeg,image/webp"
            >
        </label>

    </div>

    <!-- Loading -->
    <div
        class="image-resizer__loading"
        id="image-loading"
        role="status"
        aria-live="polite"
        hidden
    >
        <span
            class="image-resizer__spinner"
            aria-hidden="true"
        ></span>

        <strong>Uploading image...</strong>

        <span>Please wait while we prepare your image.</span>
    </div>

    <!-- Error -->
    <p
        class="image-resizer__error"
        id="image-error"
        role="alert"
        hidden
    ></p>

    <!-- Editor: hidden until the image loads -->
    <div
        class="image-resizer__editor"
        id="image-editor"
        hidden
    >

        <div class="image-resizer__preview-panel">

            <div class="image-resizer__preview-heading">
                <div>
                    <h3>Image preview</h3>
                    <p id="image-original-info">
                        Original image
                    </p>
                </div>

                <button
                    type="button"
                    class="image-resizer__icon-button"
                    id="image-remove"
                    aria-label="Remove image"
                    title="Remove image"
                >
                    <span
                        class="material-symbols-outlined"
                        aria-hidden="true"
                    >close</span>
                </button>
            </div>

            <div class="image-resizer__preview">
                <img
                    id="image-preview"
                    alt="Image preview"
                >
            </div>

            <p
                class="image-resizer__dimensions"
                id="image-preview-info"
                aria-live="polite"
            ></p>

        </div>

        <div class="image-resizer__settings">

            <h3>Resize settings</h3>

            <p class="image-resizer__description">
                Enter your desired image dimensions in pixels.
            </p>

            <div class="image-resizer__fields">

                <div class="image-resizer__field">
                    <label for="image-width">Width (px)</label>

                    <input
                        type="number"
                        id="image-width"
                        min="1"
                        max="5000"
                        step="1"
                        required
                    >
                </div>

                <div class="image-resizer__field">
                    <label for="image-height">Height (px)</label>

                    <input
                        type="number"
                        id="image-height"
                        min="1"
                        max="5000"
                        step="1"
                        required
                    >
                </div>

            </div>

            <label class="image-resizer__ratio">
                <input
                    type="checkbox"
                    id="image-keep-ratio"
                    checked
                >

                <span
                    class="material-symbols-outlined"
                    aria-hidden="true"
                >lock</span>

                <span>
                    <strong>Maintain aspect ratio</strong>
                    <small>Keep the original image proportions.</small>
                </span>
            </label>

            <div class="image-resizer__output-info">
                <span>Output dimensions</span>
                <strong id="image-output-info">—</strong>
            </div>

            <button
                type="button"
                class="button button--primary image-resizer__download"
                id="image-download"
            >
                <span
                    class="material-symbols-outlined"
                    aria-hidden="true"
                >download</span>

                Download resized image
            </button>

            <button
                type="button"
                class="button button--secondary image-resizer__change"
                id="image-change"
            >
                Choose another image
            </button>

            <p class="image-resizer__privacy">
                <span
                    class="material-symbols-outlined"
                    aria-hidden="true"
                >lock</span>

                Your image stays in your browser.
            </p>

        </div>

    </div>

</div>