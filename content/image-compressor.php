<?php
    include_once __DIR__ . '/config/functions-user.php';
    $_COOKIE['user_id'] = 1; // Replace with the actual user ID
    $user_id = $_COOKIE['user_id'];
    $tools_id = e($tool['name']); // Replace with the actual tool ID
    $status =  'processing'; // Replace with the actual status
    $processing_time = 500; // Replace with the actual processing time in milliseconds
    $file_size = 1024; // Replace with the actual file size in bytes
?>

<div class="image-compressor" id="image-compressor">

    <div class="image-compressor__dropzone" id="compress-dropzone">
        <input
            type="file"
            id="compress-file"
            accept="image/jpeg,image/png,image/webp"
            hidden
        >

        <span class="material-symbols-outlined image-compressor__upload-icon"
              aria-hidden="true">cloud_upload</span>

        <strong>Drop your image here</strong>
        <span>or click to browse your files</span>
        <small>JPG, PNG or WebP · Maximum 10 MB</small>
    </div>

    <div class="image-compressor__error"
         id="compress-error"
         role="alert"
         aria-live="polite"
         hidden></div>

    <div class="image-compressor__loading" id="compress-loading" hidden>
        <span class="image-compressor__spinner" aria-hidden="true"></span>
        <strong>Processing your image...</strong>
        <span>Please wait a moment.</span>
    </div>

    <div class="image-compressor__workspace" id="compress-workspace" hidden>

        <div class="image-compressor__preview-panel">
            <div class="image-compressor__panel-heading">
                <div>
                    <h2>Image preview</h2>
                    <p id="compress-file-name">Selected image</p>
                </div>

                <button
                    type="button"
                    class="image-compressor__icon-button"
                    id="compress-remove"
                    aria-label="Remove image"
                    title="Remove image"
                >
                    <span class="material-symbols-outlined"
                          aria-hidden="true">close</span>
                </button>
            </div>

            <div class="image-compressor__preview">
                <img id="compress-preview" alt="Selected image preview">
            </div>

            <div class="image-compressor__stats">
                <div>
                    <span>Original size</span>
                    <strong id="compress-original-size">—</strong>
                </div>

                <div>
                    <span>Compressed size</span>
                    <strong id="compress-result-size">Not compressed yet</strong>
                </div>

                <div>
                    <span>Dimensions</span>
                    <strong id="compress-dimensions">—</strong>
                </div>
            </div>
        </div>

        <div class="image-compressor__settings">
            <h2>Compression settings</h2>
            <p class="image-compressor__description">
                Adjust the output format and quality to reduce your image size.
            </p>

            <div class="image-compressor__field">
                <label for="compress-format">Output format</label>
                <select id="compress-format">
                    <option value="image/webp">WebP — Recommended</option>
                    <option value="image/jpeg">JPEG</option>
                    <option value="image/png">PNG</option>
                </select>
            </div>

            <div class="image-compressor__quality-heading">
                <label for="compress-quality">Image quality</label>
                <strong id="compress-quality-value">75%</strong>
            </div>

            <input
                type="range"
                id="compress-quality"
                min="10"
                max="100"
                value="75"
                step="5"
            >

            <div class="image-compressor__range-labels">
                <span>Smaller file</span>
                <span>Higher quality</span>
            </div>

            <p class="image-compressor__hint" id="compress-format-hint">
                WebP usually offers a good balance between image quality and file size.
            </p>

            <button
                type="button"
                class="button button--primary image-compressor__action"
                id="compress-button"
            >
                <?= user_tool_usage($user_id, $tools_id, $status, $processing_time, $file_size); ?>
                <span class="material-symbols-outlined"
                      aria-hidden="true">compress</span>
                Compress image
            </button>

            <div class="image-compressor__result" id="compress-result" hidden>
                <span class="material-symbols-outlined"
                      aria-hidden="true">check_circle</span>
                <div>
                    <strong id="compress-result-title">Compression complete</strong>
                    <p id="compress-savings">Your image is ready to download.</p>
                </div>
            </div>

            <button
                type="button"
                class="button button--primary image-compressor__action"
                id="compress-download"
                disabled
            >
                <span class="material-symbols-outlined"
                      aria-hidden="true">download</span>
                Download compressed image
            </button>

            <button
                type="button"
                class="button image-compressor__secondary-action"
                id="compress-change"
            >
                Choose another image
            </button>
        </div>

    </div>

    <p class="image-compressor__privacy">
        <span class="material-symbols-outlined"
              aria-hidden="true">lock</span>
        Your images are processed locally in your browser.
    </p>

</div>
