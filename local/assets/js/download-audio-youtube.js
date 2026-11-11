"use strict";

(() => {
    const formPanel   = document.getElementById("yta-form-panel");
    const loadingEl   = document.getElementById("yta-loading");
    const resultEl    = document.getElementById("yta-result");
    const downloading = document.getElementById("yta-downloading");

    const urlInput    = document.getElementById("yta-url");
    const pasteBtn    = document.getElementById("yta-paste");
    const submitBtn   = document.getElementById("yta-submit");
    const errorEl     = document.getElementById("yta-error");
    const changeBtn   = document.getElementById("yta-change");

    const thumbnail   = document.getElementById("yta-thumbnail");
    const titleEl     = document.getElementById("yta-title");
    const channelEl   = document.getElementById("yta-channel");
    const durationEl  = document.getElementById("yta-duration");
    const viewsEl     = document.getElementById("yta-views");
    const uploadEl    = document.getElementById("yta-upload");
    const formatList  = document.getElementById("yta-format-list");
    const progressEl  = document.getElementById("yta-progress");
    const statusEl    = document.getElementById("yta-download-status");

    let currentUrl = null;

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
                       u.pathname.startsWith("/live/") ||
                       u.pathname.startsWith("/watch");
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

    function escapeHtml(str) {
        return String(str)
            .replace(/&/g, "&amp;")
            .replace(/</g, "&lt;")
            .replace(/>/g, "&gt;")
            .replace(/"/g, "&quot;");
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
            const res = await fetch("tools/download-audio-youtube.php", {
                method: "POST",
                headers: { "Content-Type": "application/json" },
                body: JSON.stringify({ action: "info", url }),
                credentials: "same-origin"
            });

            const data = await res.json();

            if (!res.ok || data.error) {
                throw new Error(data.error || "Não foi possível obter informações do áudio.");
            }

            renderResult(data);
            setState("result");
        } catch (err) {
            setState("form");
            showError(err.message || "Erro ao analisar o áudio. Tente novamente.");
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
        thumbnail.alt = data.title || "Capa do áudio";
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
                <div class="yt-audio__format-item">
                    <div class="yt-audio__format-info">
                        <span class="yt-audio__format-label">Nenhum formato de áudio disponível</span>
                        <span class="yt-audio__format-meta">Tente outro vídeo ou verifique as permissões.</span>
                    </div>
                </div>`;
            return;
        }

        formats.forEach((fmt) => {
            const item = document.createElement("div");
            item.className = "yt-audio__format-item";

            const ext = (fmt.ext || fmt.output || "mp3").toLowerCase();
            const output = (fmt.output || ext || "mp3").toLowerCase();
            let badgeClass = "yt-audio__format-badge";
            if (ext === "mp3") badgeClass += " yt-audio__format-badge--mp3";
            else if (ext === "m4a") badgeClass += " yt-audio__format-badge--m4a";
            else if (ext === "opus" || ext === "ogg") badgeClass += " yt-audio__format-badge--opus";

            const sizeText = formatFilesize(fmt.filesize);
            const metaParts = [];

            if (fmt.abr) metaParts.push(fmt.abr + " kbps");
            if (fmt.acodec) metaParts.push(fmt.acodec);
            if (sizeText) metaParts.push(sizeText);

            item.innerHTML = `
                <div class="yt-audio__format-info">
                    <span class="yt-audio__format-label">
                        ${escapeHtml(fmt.label || "Áudio")}
                        <span class="${badgeClass}">${escapeHtml(ext.toUpperCase())}</span>
                    </span>
                    <span class="yt-audio__format-meta">${escapeHtml(metaParts.join(" · ") || "Áudio")}</span>
                </div>
                <button
                    type="button"
                    class="button button--primary yt-audio__download-btn"
                    data-format-id="${escapeHtml(fmt.format_id)}"
                    data-output="${escapeHtml(output)}"
                >
                    <span class="material-symbols-outlined" aria-hidden="true">download</span>
                    Baixar
                </button>
            `;

            const btn = item.querySelector("button");
            btn.addEventListener("click", () => startDownload(fmt.format_id, fmt.label, output));
            formatList.appendChild(item);
        });
    }

    /* ========== Download ========== */

    async function startDownload(formatId, label, output) {
        if (!currentUrl || !formatId) return;

        const outExt = output || "mp3";

        setState("downloading");
        statusEl.textContent = `Extraindo e convertendo: ${label || "áudio"}...`;
        progressEl.style.width = "15%";

        try {
            const res = await fetch("tools/download-audio-youtube.php", {
                method: "POST",
                headers: { "Content-Type": "application/json" },
                body: JSON.stringify({
                    action: "download",
                    url: currentUrl,
                    format_id: formatId,
                    output: outExt
                }),
                credentials: "same-origin"
            });

            progressEl.style.width = "60%";

            if (!res.ok) {
                const err = await res.json().catch(() => ({}));
                throw new Error(err.error || "Falha no download do áudio.");
            }

            const blob = await res.blob();
            progressEl.style.width = "100%";

            if (!blob || blob.size === 0) {
                throw new Error("O arquivo retornado está vazio.");
            }

            let filename = "youtube-audio." + (outExt || "mp3");
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
            errBox.className = "yt-audio__error";
            errBox.style.marginTop = "12px";
            errBox.textContent = err.message || "Erro ao baixar o áudio.";
            formatList.prepend(errBox);
            setTimeout(() => errBox.remove(), 6000);
        }
    }

    // Estado inicial
    setState("form");
})();
