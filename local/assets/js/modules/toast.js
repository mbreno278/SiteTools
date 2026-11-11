/**
 * Feedback de sucesso / erro (toast acessível).
 */
(function (global) {
  "use strict";

  var region = null;

  function ensureRegion() {
    if (region && document.body.contains(region)) return region;
    region = document.createElement("div");
    region.className = "st-toast-region";
    region.setAttribute("aria-live", "polite");
    region.setAttribute("aria-relevant", "additions");
    region.setAttribute("role", "status");
    document.body.appendChild(region);
    return region;
  }

  function show(message, type) {
    type = type || "info";
    var host = ensureRegion();
    var el = document.createElement("div");
    el.className = "st-toast st-toast--" + type;
    el.setAttribute("role", type === "error" ? "alert" : "status");
    el.textContent = String(message || "");

    host.appendChild(el);

    // força reflow para animação
    void el.offsetWidth;
    el.classList.add("is-visible");

    var ttl = type === "error" ? 5000 : 3200;
    var timer = setTimeout(function () {
      el.classList.remove("is-visible");
      setTimeout(function () {
        if (el.parentNode) el.parentNode.removeChild(el);
      }, 250);
    }, ttl);

    el.addEventListener("click", function () {
      clearTimeout(timer);
      el.classList.remove("is-visible");
      setTimeout(function () {
        if (el.parentNode) el.parentNode.removeChild(el);
      }, 250);
    });
  }

  global.SiteToolsToast = {
    show: show,
    success: function (msg) {
      show(msg, "success");
    },
    error: function (msg) {
      show(msg, "error");
    },
    info: function (msg) {
      show(msg, "info");
    },
  };
})(window);
