
<div class="image-cropper" id="image-cropper">

    <div class="image-cropper__dropzone" id="crop-dropzone">
        <input
            type="file"
            id="crop-file"
            accept="image/jpeg,image/png,image/webp"
            hidden
        >

        <span class="material-symbols-outlined image-cropper__upload-icon"
              aria-hidden="true">crop</span>

        <strong>Drop your image here</strong>
        <span>or click to browse your files</span>
        <small>JPG, PNG or WebP · Maximum 10 MB</small>
    </div>

    <div class="image-cropper__error"
         id="crop-error"
         role="alert"
         aria-live="polite"
         hidden></div>

    <div class="image-cropper__loading" id="crop-loading" hidden>
        <span class="image-cropper__spinner" aria-hidden="true"></span>
        <strong>Loading your image...</strong>
    </div>

    <div class="image-cropper__workspace" id="crop-workspace" hidden>

        <section class="image-cropper__editor">
            <div class="image-cropper__section-heading">
                <div>
                    <h2>Crop your image</h2>
                    <p>Drag the selection to choose the area you want to keep.</p>
                </div>

                <button
                    type="button"
                    class="image-cropper__icon-button"
                    id="crop-remove"
                    aria-label="Remove image"
                    title="Remove image"
                >
                    <span class="material-symbols-outlined"
                          aria-hidden="true">close</span>
                </button>
            </div>

            <div class="image-cropper__stage-wrapper">
                <div class="image-cropper__stage" id="crop-stage">
                    <img id="crop-preview" alt="Image to crop" draggable="false">

                    <div
                        class="image-cropper__selection"
                        id="crop-selection"
                        role="group"
                        aria-label="Crop selection"
                    >
                        <span class="image-cropper__grid image-cropper__grid--vertical-one"></span>
                        <span class="image-cropper__grid image-cropper__grid--vertical-two"></span>
                        <span class="image-cropper__grid image-cropper__grid--horizontal-one"></span>
                        <span class="image-cropper__grid image-cropper__grid--horizontal-two"></span>

                        <span class="image-cropper__handle image-cropper__handle--nw"
                              data-handle="nw"></span>
                        <span class="image-cropper__handle image-cropper__handle--ne"
                              data-handle="ne"></span>
                        <span class="image-cropper__handle image-cropper__handle--sw"
                              data-handle="sw"></span>
                        <span class="image-cropper__handle image-cropper__handle--se"
                              data-handle="se"></span>
                    </div>
                </div>
            </div>

            <div class="image-cropper__dimensions">
                <span>Crop dimensions</span>
                <strong id="crop-dimensions">—</strong>
            </div>

            <div class="image-cropper__actions">
                <button
                    type="button"
                    class="button image-cropper__secondary-action"
                    id="crop-reset"
                >
                    <span class="material-symbols-outlined"
                          aria-hidden="true">restart_alt</span>
                    Reset crop
                </button>
            </div>
        </section>

        <aside class="image-cropper__settings">
            <h2>Crop settings</h2>
            <p class="image-cropper__description">
                Choose a crop ratio or adjust the selection manually.
            </p>

            <div class="image-cropper__field">
                <label for="crop-ratio">Aspect ratio</label>
                <select id="crop-ratio">
                    <option value="free">Free</option>
                    <option value="1:1">Square (1:1)</option>
                    <option value="4:3">Landscape (4:3)</option>
                    <option value="3:2">Photo (3:2)</option>
                    <option value="16:9">Widescreen (16:9)</option>
                </select>
            </div>

            <div class="image-cropper__field">
                <label for="crop-format">Output format</label>
                <select id="crop-format">
                    <option value="image/png">PNG</option>
                    <option value="image/jpeg">JPG</option>
                    <option value="image/webp">WebP</option>
                </select>
            </div>

            <div class="image-cropper__quality" id="crop-quality-section" hidden>
                <div class="image-cropper__quality-heading">
                    <label for="crop-quality">Image quality</label>
                    <strong id="crop-quality-value">90%</strong>
                </div>

                <input
                    type="range"
                    id="crop-quality"
                    min="10"
                    max="100"
                    value="90"
                    step="5"
                >
            </div>

            <div class="image-cropper__info">
                <span class="material-symbols-outlined"
                      aria-hidden="true">info</span>
                <p>
                    Drag inside the selection to move it. Drag a corner
                    to resize it. The darkened area will be removed.
                </p>
            </div>

            <button
                type="button"
                class="button button--primary image-cropper__action"
                id="crop-download"
            >
                <span class="material-symbols-outlined"
                      aria-hidden="true">download</span>
                Crop and download
            </button>

            <button
                type="button"
                class="button image-cropper__secondary-action"
                id="crop-change"
            >
                Choose another image
            </button>

            <p class="image-cropper__file-info" id="crop-file-info"></p>
        </aside>
    </div>

    <p class="image-cropper__privacy">
        <span class="material-symbols-outlined"
              aria-hidden="true">lock</span>
        Your image is processed locally in your browser.
    </p>
</div>
