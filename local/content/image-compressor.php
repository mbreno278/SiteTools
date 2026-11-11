<?php
/**
 * Image Compressor tool UI
 * Included from page.php when $handler === 'image-compressor'
 *
 * $tool is available from page.php scope.
 */

// Caminho correto: content/ → sobe um nível → config/
$functionsUser = __DIR__ . '/../config/functions-user.php';
if (is_file($functionsUser)) {
    include_once $functionsUser;
}

// Não force cookie em produção — use só se realmente precisar de fallback de dev
// $_COOKIE['user_id'] = 1;

$user_id = isset($_COOKIE['user_id']) ? (int) $_COOKIE['user_id'] : null;
$tools_id = isset($tool['name']) ? (string) $tool['name'] : 'image-compressor';
$status = 'processing';
$processing_time = 500;
$file_size = 1024;

// Tracking opcional (não deve quebrar o HTML do botão)
$usageHtml = '';
if ($user_id && function_exists('user_tool_usage')) {
    // Se a função só registra no banco e não imprime HTML, chame aqui e deixe $usageHtml vazio.
    // Se ela retorna/imprime markup, capture com output buffering:
    ob_start();
    try {
        user_tool_usage($user_id, $tools_id, $status, $processing_time, $file_size);
    } catch (Throwable $e) {
        error_log('[image-compressor] user_tool_usage: ' . $e->getMessage());
    }
    $usageHtml = trim(ob_get_clean());
}
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

            <?php if ($usageHtml !== ''): ?>
                <div class="image-compressor__usage" hidden aria-hidden="true">
                    <?= $usageHtml /* só se for markup seguro e intencional */ ?>
                </div>
            <?php endif; ?>

            <button
                type="button"
                class="button button--primary image-compressor__action"
                id="compress-button"
            >
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
