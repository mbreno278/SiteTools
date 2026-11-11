"use strict";

(() => {
    const formPanel   = document.getElementById("tt-form-panel");
    const loadingEl   = document.getElementById("tt-loading");
    const resultEl    = document.getElementById("tt-result");
    const downloading = document.getElementById("tt-downloading");

    const urlInput    = document.getElementById("tt-url");
    const pasteBtn    = document.getElementById("tt-paste");
    const submitBtn   = document.getElementById("tt-submit");
    const errorEl     = document.getElementById("tt-error");
    const changeBtn   = document.getElementById("tt-change");

    const thumbnail   = document.getElementById("tt-thumbnail");
    const titleEl     = document.getElementById("tt-title");
    const channelEl   = document.getElementById("tt-channel");
    const durationEl  = document.getElementById("tt-duration");
    const likesWrap   = document.getElementById("tt-likes-wrap");
    const likesEl     = document.getElementById("tt-likes");
    const viewsWrap   = document.getElementById("tt-views-wrap");
    const viewsEl     = document.getElementById("tt-views");
    const commentsWrap = document.getElementById("tt-comments-wrap");
    const commentsEl  = document.getElementById("tt-comments");
    const formatList  = document.getElementById("tt-format-list");
    const progressEl  = document.getElementById("tt-progress");
    const statusEl    = document.getElementById("tt-download-status");

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

    function isValidTikTokUrl(url) {
        try {
            const u = new URL(url);
            const host = u.hostname.replace(/^www\./, "");
            return (
                host === "tiktok.com" ||
                host === "vm.tiktok.com" ||
                host === "vt.tiktok.com" ||
                host === "m.tiktok.com" ||
                host.endsWith(".tiktok.com")
            );
        } catch {
            return false;
        }
    }

    function formatDuration(seconds) {
        if (!seconds && seconds !== 0) return "";
        const m = Math.floor(seconds / 60);
        const s = Math.floor(seconds % 60);
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

    pasteBtn?.addEventListener("click", async () => {
        try {
            const text = await navigator.clipboard.readText();
            if (text) {
                urlInput.value = text.trim();
                urlInput.focus();
            }
        } catch { /* ignore */ }
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
            showError("Cole um link do TikTok para continuar.");
            urlInput.focus();
            return;
        }

        if (!isValidTikTokUrl(url)) {
            showError("Link inválido. Use um endereço válido do TikTok.");
            urlInput.focus();
            return;
        }

        setState("loading");
        currentUrl = url;

        try {
            const res = await fetch("tools/download-video-tiktok.php", {
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

    function renderResult(data) {
        thumbnail.src = data.thumbnail || "";
        thumbnail.alt = data.title || "Preview";
        titleEl.textContent = data.title || "Vídeo do TikTok";
        channelEl.textContent = data.channel
            ? "@" + String(data.channel).replace(/^@/, "")
            : "TikTok";
        durationEl.textContent = formatDuration(data.duration);

        if (data.like_count != null) {
            likesEl.textContent = formatNumber(data.like_count);
            show(likesWrap);
        } else hide(likesWrap);

        if (data.view_count != null) {
            viewsEl.textContent = formatNumber(data.view_count);
            show(viewsWrap);
        } else hide(viewsWrap);

        if (data.comment_count != null) {
            commentsEl.textContent = formatNumber(data.comment_count);
            show(commentsWrap);
        } else hide(commentsWrap);

        formatList.innerHTML = "";
        const formats = Array.isArray(data.formats) ? data.formats : [];

        if (formats.length === 0) {
            formatList.innerHTML = `
                <div class="tt-downloader__format-item">
                    <div class="tt-downloader__format-info">
                        <span class="tt-downloader__format-label">Nenhum formato disponível</span>
                        <span class="tt-downloader__format-meta">O vídeo pode ser privado ou indisponível.</span>
                    </div>
                </div>`;
            return;
        }

        formats.forEach((fmt) => {
            const item = document.createElement("div");
            item.className = "tt-downloader__format-item";

            const isAudio = fmt.type === "audio";
            const badgeClass = isAudio
                ? "tt-downloader__format-badge tt-downloader__format-badge--audio"
                : "tt-downloader__format-badge";
            const badgeText = isAudio ? "Áudio" : (fmt.ext || "MP4").toUpperCase();

            const metaParts = [];
            if (fmt.resolution) metaParts.push(fmt.resolution);
            if (fmt.abr) metaParts.push(fmt.abr + " kbps");
            const sizeText = formatFilesize(fmt.filesize);
            if (sizeText) metaParts.push(sizeText);

            item.innerHTML = `
                <div class="tt-downloader__format-info">
                    <span class="tt-downloader__format-label">
                        ${escapeHtml(fmt.label || "Download")}
                        <span class="${badgeClass}">${escapeHtml(badgeText)}</span>
                    </span>
                    <span class="tt-downloader__format-meta">${escapeHtml(metaParts.join(" · ") || "Arquivo")}</span>
                </div>
                <button
                    type="button"
                    class="button button--primary tt-downloader__download-btn"
                >
                    <span class="material-symbols-outlined" aria-hidden="true">download</span>
                    Baixar
                </button>
            `;

            item.querySelector("button").addEventListener("click", () => {
                startDownload(fmt.format_id, fmt.label, fmt.ext || "mp4");
            });
            formatList.appendChild(item);
        });
    }

    async function startDownload(formatId, label, ext) {
        if (!currentUrl || !formatId) return;

        setState("downloading");
        statusEl.textContent = `Baixando: ${label || "arquivo"}...`;
        progressEl.style.width = "15%";

        try {
            const res = await fetch("tools/download-video-tiktok.php", {
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

            let filename = "tiktok-video." + (ext || "mp4");
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
            errBox.className = "tt-downloader__error";
            errBox.style.marginTop = "12px";
            errBox.textContent = err.message || "Erro ao baixar o arquivo.";
            formatList.prepend(errBox);
            setTimeout(() => errBox.remove(), 6000);
        }
    }

    setState("form");
})();
