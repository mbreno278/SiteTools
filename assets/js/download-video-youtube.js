"use strict";

(() => {
    const formPanel   = document.getElementById("yt-form-panel");
    const loadingEl   = document.getElementById("yt-loading");
    const resultEl    = document.getElementById("yt-result");
    const downloading = document.getElementById("yt-downloading");

    const urlInput    = document.getElementById("yt-url");
    const pasteBtn    = document.getElementById("yt-paste");
    const submitBtn   = document.getElementById("yt-submit");
    const errorEl     = document.getElementById("yt-error");
    const changeBtn   = document.getElementById("yt-change");

    const thumbnail   = document.getElementById("yt-thumbnail");
    const titleEl     = document.getElementById("yt-title");
    const channelEl   = document.getElementById("yt-channel");
    const durationEl  = document.getElementById("yt-duration");
    const viewsEl     = document.getElementById("yt-views");
    const uploadEl    = document.getElementById("yt-upload");
    const formatList  = document.getElementById("yt-format-list");
    const progressEl  = document.getElementById("yt-progress");
    const statusEl    = document.getElementById("yt-download-status");

    let currentVideoId = null;
    let currentUrl     = null;

    /* ========== Helpers ========== */

    function show(el) {
        if (el) el.hidden = false;
    }

    function hide(el) {
        if (el) el.hidden = true;
    }

    function showError(msg) {
        errorEl.textContent = msg;
        show(errorEl);
    }

    function clearError() {
        errorEl.textContent = "";
        hide(errorEl);
    }

    function setState(state) {
        hide(formPanel);
        hide(loadingEl);
        hide(resultEl);
        hide(downloading);

        if (state === "form")        show(formPanel);
        else if (state === "loading") show(loadingEl);
        else if (state === "result")  show(resultEl);
        else if (state === "downloading") show(downloading);
    }

    function isValidYoutubeUrl(url) {
        try {
            const u = new URL(url);
            const host = u.hostname.replace(/^www\./, "");
            if (host === "youtu.be") return u.pathname.length > 1;
            if (host === "youtube.com" || host === "m.youtube.com" || host === "music.youtube.com") {
                return u.searchParams.has("v") ||
                       u.pathname.startsWith("/shorts/") ||
                       u.pathname.startsWith("/embed/") ||
                       u.pathname.startsWith("/live/");
            }
            return false;
        } catch {
            return false;
        }
    }

    function formatDuration(seconds) {
        if (!seconds && seconds !== 0) return "";
        const h = Math.floor(seconds / 3600);
        const m = Math.floor((seconds % 3600) / 60);
        const s = Math.floor(seconds % 60);
        if (h > 0) {
            return `${h}:${String(m).padStart(2, "0")}:${String(s).padStart(2, "0")}`;
        }
        return `${m}:${String(s).padStart(2, "0")}`;
    }

    function formatViews(n) {
        if (n == null) return "—";
        if (n >= 1_000_000) return (n / 1_000_000).toFixed(1).replace(/\.0$/, "") + " mi";
        if (n >= 1_000) return (n / 1_000).toFixed(1).replace(/\.0$/, "") + " mil";
        return String(n);
    }

    function formatFilesize(bytes) {
        if (!bytes) return "";
        if (bytes >= 1_073_741_824) return (bytes / 1_073_741_824).toFixed(1) + " GB";
        if (bytes >= 1_048_576) return (bytes / 1_048_576).toFixed(1) + " MB";
        return (bytes / 1024).toFixed(0) + " KB";
    }

    /* ========== Events ========== */

    pasteBtn?.addEventListener("click", async () => {
        try {
            const text = await navigator.clipboard.readText();
            if (text) {
                urlInput.value = text.trim();
                urlInput.focus();
            }
        } catch {
            // Clipboard API may be blocked
        }
    });

    urlInput?.addEventListener("keydown", (e) => {
        if (e.key === "Enter") {
            e.preventDefault();
            submitBtn.click();
        }
    });

    submitBtn?.addEventListener("click", async () => {
        clearError();
        const url = (urlInput.value || "").trim();

        if (!url) {
            showError("Cole um link do YouTube para continuar.");
            urlInput.focus();
            return;
        }

        if (!isValidYoutubeUrl(url)) {
            showError("Link inválido. Use um endereço válido do YouTube.");
            urlInput.focus();
            return;
        }

        setState("loading");
        currentUrl = url;

        try {
            const res = await fetch("tools/download-video-youtube.php", {
                method: "POST",
                headers: { "Content-Type": "application/json" },
                body: JSON.stringify({ action: "info", url }),
                credentials: "same-origin"
            });

            const data = await res.json();

            if (!res.ok || data.error) {
                throw new Error(data.error || "Não foi possível obter informações do vídeo.");
            }

            renderResult(data);
            setState("result");
        } catch (err) {
            setState("form");
            showError(err.message || "Erro ao analisar o vídeo. Tente novamente.");
        }
    });

    changeBtn?.addEventListener("click", () => {
        currentVideoId = null;
        currentUrl = null;
        formatList.innerHTML = "";
        setState("form");
        urlInput.focus();
    });

    /* ========== Render ========== */

    function renderResult(data) {
        currentVideoId = data.id || null;

        thumbnail.src = data.thumbnail || "";
        thumbnail.alt = data.title || "Thumbnail";
        titleEl.textContent = data.title || "Sem título";
        channelEl.textContent = data.channel || "Canal desconhecido";
        durationEl.textContent = formatDuration(data.duration);
        viewsEl.textContent = formatViews(data.view_count);
        uploadEl.textContent = data.upload_date
            ? data.upload_date.replace(/(\d{4})(\d{2})(\d{2})/, "$3/$2/$1")
            : "—";

        formatList.innerHTML = "";

        const formats = Array.isArray(data.formats) ? data.formats : [];

        if (formats.length === 0) {
            formatList.innerHTML = `
                <div class="yt-downloader__format-item">
                    <div class="yt-downloader__format-info">
                        <span class="yt-downloader__format-label">Nenhum formato disponível</span>
                        <span class="yt-downloader__format-meta">Tente outro vídeo ou verifique as permissões.</span>
                    </div>
                </div>`;
            return;
        }

        formats.forEach((fmt) => {
            const item = document.createElement("div");
            item.className = "yt-downloader__format-item";

            const isAudio = fmt.type === "audio";
            const badgeClass = isAudio
                ? "yt-downloader__format-badge yt-downloader__format-badge--audio"
                : "yt-downloader__format-badge";

            const badgeText = isAudio ? "Áudio" : (fmt.ext || "Vídeo").toUpperCase();
            const sizeText = formatFilesize(fmt.filesize);
            const metaParts = [];

            if (fmt.resolution) metaParts.push(fmt.resolution);
            if (fmt.fps) metaParts.push(fmt.fps + " fps");
            if (fmt.abr) metaParts.push(fmt.abr + " kbps");
            if (sizeText) metaParts.push(sizeText);

            item.innerHTML = `
                <div class="yt-downloader__format-info">
                    <span class="yt-downloader__format-label">
                        ${escapeHtml(fmt.label || "Download")}
                        <span class="${badgeClass}">${escapeHtml(badgeText)}</span>
                    </span>
                    <span class="yt-downloader__format-meta">${escapeHtml(metaParts.join(" · "))}</span>
                </div>
                <button
                    type="button"
                    class="button button--primary yt-downloader__download-btn"
                    data-format-id="${escapeHtml(fmt.format_id)}"
                >
                    <span class="material-symbols-outlined" aria-hidden="true">download</span>
                    Baixar
                </button>
            `;

            const btn = item.querySelector("button");
            btn.addEventListener("click", () => startDownload(fmt.format_id, fmt.label));
            formatList.appendChild(item);
        });
    }

    function escapeHtml(str) {
        return String(str)
            .replace(/&/g, "&amp;")
            .replace(/</g, "&lt;")
            .replace(/>/g, "&gt;")
            .replace(/"/g, "&quot;");
    }

    /* ========== Download ========== */

    async function startDownload(formatId, label) {
        if (!currentUrl || !formatId) return;

        setState("downloading");
        statusEl.textContent = `Baixando: ${label || "arquivo"}...`;
        progressEl.style.width = "15%";

        try {
            const res = await fetch("tools/download-video-youtube.php", {
                method: "POST",
                headers: { "Content-Type": "application/json" },
                body: JSON.stringify({
                    action: "download",
                    url: currentUrl,
                    format_id: formatId
                }),
                credentials: "same-origin"
            });

            progressEl.style.width = "60%";

            if (!res.ok) {
                const err = await res.json().catch(() => ({}));
                throw new Error(err.error || "Falha no download.");
            }

            const blob = await res.blob();
            progressEl.style.width = "100%";

            if (!blob || blob.size === 0) {
                throw new Error("O arquivo retornado está vazio.");
            }

            // Extrai nome do header Content-Disposition se existir
            let filename = "youtube-video";
            const disposition = res.headers.get("Content-Disposition");
            if (disposition) {
                const match = disposition.match(/filename\*?=(?:UTF-8'')?["']?([^"';]+)/i);
                if (match) filename = decodeURIComponent(match[1]);
            } else {
                // Fallback a partir do content-type
                const type = res.headers.get("Content-Type") || "";
                if (type.includes("audio")) filename += ".mp3";
                else if (type.includes("webm")) filename += ".webm";
                else filename += ".mp4";
            }

            const objectUrl = URL.createObjectURL(blob);
            const a = document.createElement("a");
            a.href = objectUrl;
            a.download = filename;
            document.body.appendChild(a);
            a.click();
            a.remove();
            setTimeout(() => URL.revokeObjectURL(objectUrl), 2000);

            statusEl.textContent = "Download concluído!";
            setTimeout(() => setState("result"), 1200);
        } catch (err) {
            setState("result");
            showError(err.message || "Erro ao baixar o arquivo.");
            // Re-mostra o erro no painel de resultado
            const errBox = document.createElement("div");
            errBox.className = "yt-downloader__error";
            errBox.style.marginTop = "12px";
            errBox.textContent = err.message;
            formatList.prepend(errBox);
            setTimeout(() => errBox.remove(), 6000);
        }
    }

    // Estado inicial
    setState("form");
})();
