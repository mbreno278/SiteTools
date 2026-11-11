"use strict";

(() => {
    const formPanel   = document.getElementById("ig-form-panel");
    const loadingEl   = document.getElementById("ig-loading");
    const resultEl    = document.getElementById("ig-result");
    const downloading = document.getElementById("ig-downloading");

    const urlInput    = document.getElementById("ig-url");
    const pasteBtn    = document.getElementById("ig-paste");
    const submitBtn   = document.getElementById("ig-submit");
    const errorEl     = document.getElementById("ig-error");
    const changeBtn   = document.getElementById("ig-change");

    const thumbnail   = document.getElementById("ig-thumbnail");
    const titleEl     = document.getElementById("ig-title");
    const channelEl   = document.getElementById("ig-channel");
    const durationEl  = document.getElementById("ig-duration");
    const typeBadge   = document.getElementById("ig-type-badge");
    const likesWrap   = document.getElementById("ig-likes-wrap");
    const likesEl     = document.getElementById("ig-likes");
    const viewsWrap   = document.getElementById("ig-views-wrap");
    const viewsEl     = document.getElementById("ig-views");
    const formatList  = document.getElementById("ig-format-list");
    const progressEl  = document.getElementById("ig-progress");
    const statusEl    = document.getElementById("ig-download-status");

    let currentUrl = null;

    function show(el) { if (el) el.hidden = false; }
    function hide(el) { if (el) el.hidden = true; }

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

        if (state === "form") show(formPanel);
        else if (state === "loading") show(loadingEl);
        else if (state === "result") show(resultEl);
        else if (state === "downloading") show(downloading);
    }

    function isValidInstagramUrl(url) {
        try {
            const u = new URL(url);
            const host = u.hostname.replace(/^www\./, "");
            if (host !== "instagram.com" && host !== "instagr.am") return false;
            const path = u.pathname;
            return (
                path.startsWith("/p/") ||
                path.startsWith("/reel/") ||
                path.startsWith("/reels/") ||
                path.startsWith("/tv/") ||
                path.startsWith("/stories/")
            );
        } catch {
            return false;
        }
    }

    function formatDuration(seconds) {
        if (!seconds && seconds !== 0) return "";
        const h = Math.floor(seconds / 3600);
        const m = Math.floor((seconds % 3600) / 60);
        const s = Math.floor(seconds % 60);
        if (h > 0) return `${h}:${String(m).padStart(2, "0")}:${String(s).padStart(2, "0")}`;
        return `${m}:${String(s).padStart(2, "0")}`;
    }

    function formatNumber(n) {
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

    function escapeHtml(str) {
        return String(str)
            .replace(/&/g, "&amp;")
            .replace(/</g, "&lt;")
            .replace(/>/g, "&gt;")
            .replace(/"/g, "&quot;");
    }

    function detectType(url) {
        if (!url) return "Post";
        if (url.includes("/reel") || url.includes("/reels/")) return "Reel";
        if (url.includes("/tv/")) return "IGTV";
        if (url.includes("/stories/")) return "Story";
        return "Post";
    }

    /* ========== Events ========== */

    pasteBtn?.addEventListener("click", async () => {
        try {
            const text = await navigator.clipboard.readText();
            if (text) {
                urlInput.value = text.trim();
                urlInput.focus();
            }
        } catch { /* clipboard bloqueado */ }
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
            showError("Cole um link do Instagram para continuar.");
            urlInput.focus();
            return;
        }

        if (!isValidInstagramUrl(url)) {
            showError("Link inválido. Use um endereço de Reel, Post, IGTV ou Story do Instagram.");
            urlInput.focus();
            return;
        }

        setState("loading");
        currentUrl = url;

        try {
            const res = await fetch("tools/download-video-instagram.php", {
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
        currentUrl = null;
        formatList.innerHTML = "";
        setState("form");
        urlInput.focus();
    });

    /* ========== Render ========== */

    function renderResult(data) {
        thumbnail.src = data.thumbnail || "";
        thumbnail.alt = data.title || "Preview";
        titleEl.textContent = data.title || data.description || "Vídeo do Instagram";
        channelEl.textContent = data.channel ? "@" + data.channel.replace(/^@/, "") : "Instagram";
        durationEl.textContent = formatDuration(data.duration);
        typeBadge.textContent = data.type || detectType(currentUrl);

        if (data.like_count != null) {
            likesEl.textContent = formatNumber(data.like_count);
            show(likesWrap);
        } else {
            hide(likesWrap);
        }

        if (data.view_count != null) {
            viewsEl.textContent = formatNumber(data.view_count);
            show(viewsWrap);
        } else {
            hide(viewsWrap);
        }

        formatList.innerHTML = "";
        const formats = Array.isArray(data.formats) ? data.formats : [];

        if (formats.length === 0) {
            formatList.innerHTML = `
                <div class="ig-downloader__format-item">
                    <div class="ig-downloader__format-info">
                        <span class="ig-downloader__format-label">Nenhum formato disponível</span>
                        <span class="ig-downloader__format-meta">O conteúdo pode ser privado ou indisponível.</span>
                    </div>
                </div>`;
            return;
        }

        formats.forEach((fmt) => {
            const item = document.createElement("div");
            item.className = "ig-downloader__format-item";

            const isAudio = fmt.type === "audio";
            const badgeClass = isAudio
                ? "ig-downloader__format-badge ig-downloader__format-badge--audio"
                : "ig-downloader__format-badge";
            const badgeText = isAudio ? "Áudio" : (fmt.ext || "MP4").toUpperCase();

            const metaParts = [];
            if (fmt.resolution) metaParts.push(fmt.resolution);
            if (fmt.fps) metaParts.push(fmt.fps + " fps");
            if (fmt.abr) metaParts.push(fmt.abr + " kbps");
            const sizeText = formatFilesize(fmt.filesize);
            if (sizeText) metaParts.push(sizeText);

            item.innerHTML = `
                <div class="ig-downloader__format-info">
                    <span class="ig-downloader__format-label">
                        ${escapeHtml(fmt.label || "Download")}
                        <span class="${badgeClass}">${escapeHtml(badgeText)}</span>
                    </span>
                    <span class="ig-downloader__format-meta">${escapeHtml(metaParts.join(" · ") || "Arquivo")}</span>
                </div>
                <button
                    type="button"
                    class="button button--primary ig-downloader__download-btn"
                    data-format-id="${escapeHtml(fmt.format_id)}"
                >
                    <span class="material-symbols-outlined" aria-hidden="true">download</span>
                    Baixar
                </button>
            `;

            const btn = item.querySelector("button");
            btn.addEventListener("click", () => startDownload(fmt.format_id, fmt.label, fmt.ext || "mp4"));
            formatList.appendChild(item);
        });
    }

    /* ========== Download ========== */

    async function startDownload(formatId, label, ext) {
        if (!currentUrl || !formatId) return;

        setState("downloading");
        statusEl.textContent = `Baixando: ${label || "arquivo"}...`;
        progressEl.style.width = "15%";

        try {
            const res = await fetch("tools/download-video-instagram.php", {
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

            let filename = "instagram-video." + (ext || "mp4");
            const disposition = res.headers.get("Content-Disposition");
            if (disposition) {
                const match = disposition.match(/filename\*?=(?:UTF-8'')?["']?([^"';]+)/i);
                if (match) filename = decodeURIComponent(match[1]);
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
            const errBox = document.createElement("div");
            errBox.className = "ig-downloader__error";
            errBox.style.marginTop = "12px";
            errBox.textContent = err.message || "Erro ao baixar o arquivo.";
            formatList.prepend(errBox);
            setTimeout(() => errBox.remove(), 6000);
        }
    }

    setState("form");
})();
