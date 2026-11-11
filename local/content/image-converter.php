
<div class="image-converter" id="image-converter">

    <div class="image-converter__dropzone" id="converter-dropzone">
        <input
            type="file"
            id="converter-file"
            accept="image/jpeg,image/png,image/webp"
            hidden
        >

        <span class="material-symbols-outlined image-converter__upload-icon"
              aria-hidden="true">imagesmode</span>

        <strong>Drop your image here</strong>
        <span>or click to browse your files</span>
        <small>JPG, PNG or WebP · Maximum 10 MB</small>
    </div>

    <div
        class="image-converter__error"
        id="converter-error"
        role="alert"
        aria-live="polite"
        hidden
    ></div>

    <div class="image-converter__loading" id="converter-loading" hidden>
        <span class="image-converter__spinner" aria-hidden="true"></span>
        <strong>Preparing your image...</strong>
        <span>Please wait a moment.</span>
    </div>

    <div class="image-converter__workspace" id="converter-workspace" hidden>

        <section class="image-converter__preview-panel">
            <div class="image-converter__panel-heading">
                <div>
                    <h2>Image preview</h2>
                    <p id="converter-file-name">Selected image</p>
                </div>

                <button
                    type="button"
                    class="image-converter__icon-button"
                    id="converter-remove"
                    aria-label="Remove image"
                    title="Remove image"
                >
                    <span class="material-symbols-outlined"
                          aria-hidden="true">close</span>
                </button>
            </div>

            <div class="image-converter__preview">
                <img id="converter-preview" alt="Selected image preview">
            </div>

            <div class="image-converter__stats">
                <div>
                    <span>Original format</span>
                    <strong id="converter-original-format">—</strong>
                </div>

                <div>
                    <span>Original size</span>
                    <strong id="converter-original-size">—</strong>
                </div>

                <div>
                    <span>Dimensions</span>
                    <strong id="converter-dimensions">—</strong>
                </div>
            </div>
        </section>

        <section class="image-converter__settings">
            <h2>Conversion settings</h2>

            <p class="image-converter__description">
                Choose the output format for your image.
            </p>

            <div class="image-converter__formats">
                <label class="image-converter__format-option">
                    <input
                        type="radio"
                        name="converter-format"
                        value="image/webp"
                        checked
                    >
                    <span class="image-converter__format-card">
                        <strong>WebP</strong>
                        <small>Web optimized</small>
                    </span>
                </label>

                <label class="image-converter__format-option">
                    <input
                        type="radio"
                        name="converter-format"
                        value="image/jpeg"
                    >
                    <span class="image-converter__format-card">
                        <strong>JPG</strong>
                        <small>Photographs</small>
                    </span>
                </label>

                <label class="image-converter__format-option">
                    <input
                        type="radio"
                        name="converter-format"
                        value="image/png"
                    >
                    <span class="image-converter__format-card">
                        <strong>PNG</strong>
                        <small>Transparency</small>
                    </span>
                </label>
            </div>

            <div class="image-converter__quality" id="converter-quality-section">
                <div class="image-converter__quality-heading">
                    <label for="converter-quality">Output quality</label>
                    <strong id="converter-quality-value">85%</strong>
                </div>

                <input
                    type="range"
                    id="converter-quality"
                    min="10"
                    max="100"
                    value="85"
                    step="5"
                >

                <div class="image-converter__range-labels">
                    <span>Smaller file</span>
                    <span>Higher quality</span>
                </div>
            </div>

            <p class="image-converter__hint" id="converter-format-hint">
                WebP is a modern format that supports transparency.
            </p>

            <button
                type="button"
                class="button button--primary image-converter__action"
                id="converter-button"
            >
                <span class="material-symbols-outlined"
                      aria-hidden="true">sync_alt</span>
                Convert image
            </button>

            <div class="image-converter__result" id="converter-result" hidden>
                <span class="material-symbols-outlined"
                      aria-hidden="true">check_circle</span>

                <div>
                    <strong id="converter-result-title">Conversion complete</strong>
                    <p id="converter-result-details"></p>
                </div>
            </div>

            <button
                type="button"
                class="button button--primary image-converter__action"
                id="converter-download"
                disabled
            >
                <span class="material-symbols-outlined"
                      aria-hidden="true">download</span>
                Download converted image
            </button>

            <button
                type="button"
                class="button image-converter__secondary-action"
                id="converter-change"
            >
                Choose another image
            </button>
        </section>
    </div>

    <p class="image-converter__privacy">
        <span class="material-symbols-outlined"
              aria-hidden="true">lock</span>
        Your images are processed locally in your browser.
    </p>

</div>
