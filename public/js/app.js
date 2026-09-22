/* Inti JS aplikasi: tema, sidebar, modal, request AJAX, notifikasi */
(function () {
  "use strict";

  // ---------- Util ----------
  function cssVar(name) {
    return getComputedStyle(document.documentElement)
      .getPropertyValue(name)
      .trim();
  }

  // Tanggal lokal hari ini (YYYY-MM-DD), bukan UTC
  function today() {
    var d = new Date();
    var m = String(d.getMonth() + 1).padStart(2, "0");
    var day = String(d.getDate()).padStart(2, "0");
    return d.getFullYear() + "-" + m + "-" + day;
  }

  // ---------- Toast notifikasi ----------
  var TOAST = { duration: 4000, errorDuration: 6000, max: 4 };

  var TOAST_TITLES = {
    success: "Berhasil",
    error: "Gagal",
    warning: "Peringatan",
    info: "Informasi",
    delete: "Dihapus",
  };

  function svgIcon(inner) {
    return (
      '<svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" ' +
      'stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' +
      inner +
      "</svg>"
    );
  }

  var TOAST_ICONS = {
    success: svgIcon('<path d="M20 6 9 17l-5-5"/>'),
    error: svgIcon('<path d="M18 6 6 18M6 6l12 12"/>'),
    warning: svgIcon(
      '<path d="m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3"/><path d="M12 9v4M12 17h.01"/>',
    ),
    info: svgIcon(
      '<circle cx="12" cy="12" r="10"/><path d="M12 16v-4M12 8h.01"/>',
    ),
    delete: svgIcon(
      '<path d="M3 6h18"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6"/><path d="M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/>',
    ),
  };
  var ICON_CLOSE = svgIcon('<path d="M18 6 6 18M6 6l12 12"/>');
  var ICON_UNDO = svgIcon(
    '<path d="M3 7v6h6"/><path d="M21 17a9 9 0 0 0-9-9 9 9 0 0 0-6 2.3L3 13"/>',
  );

  var toastBox = null;

  function toastContainer() {
    if (!toastBox) {
      toastBox = document.createElement("div");
      toastBox.className = "toast-container";
      toastBox.setAttribute("aria-live", "polite");
      document.body.appendChild(toastBox);
    }
    return toastBox;
  }

  function dismissToast(el) {
    if (!el || el.classList.contains("is-leaving")) return;
    clearTimeout(el._timer);
    el.classList.add("is-leaving");
    // Pakai timeout (bukan animationend) agar tetap terhapus bila animasi dimatikan
    setTimeout(function () {
      el.remove();
    }, 260);
  }

  // type: success | error | warning | info | delete
  // opts: { title, duration, action: { text, onClick } }
  function toast(message, type, opts) {
    opts = opts || {};
    if (!TOAST_ICONS[type]) type = "success";

    var duration =
      opts.duration ||
      (type === "error" ? TOAST.errorDuration : TOAST.duration);
    var box = toastContainer();

    var el = document.createElement("div");
    el.className = "toast toast--" + type;
    el.setAttribute("role", type === "error" ? "alert" : "status");
    el.innerHTML =
      '<div class="toast__body">' +
      '<span class="toast__icon">' +
      TOAST_ICONS[type] +
      "</span>" +
      '<div class="toast__text"><strong class="toast__title"></strong><p class="toast__msg"></p></div>' +
      '<button type="button" class="toast__close" aria-label="Tutup notifikasi">' +
      ICON_CLOSE +
      "</button>" +
      "</div>" +
      '<div class="toast__bar"><span></span></div>';

    // textContent: pesan dari server tidak pernah dibaca sebagai HTML
    el.querySelector(".toast__title").textContent =
      opts.title || TOAST_TITLES[type];
    el.querySelector(".toast__msg").textContent = message || "";
    el.querySelector(".toast__bar > span").style.animationDuration =
      duration + "ms";

    // Tombol aksi opsional (misal "Batal" untuk undo)
    if (opts.action && opts.action.text) {
      var act = document.createElement("button");
      act.type = "button";
      act.className = "toast__action";
      act.innerHTML = ICON_UNDO;
      act.appendChild(document.createTextNode(opts.action.text));
      act.addEventListener("click", function () {
        if (typeof opts.action.onClick === "function") opts.action.onClick();
        dismissToast(el);
      });
      el.querySelector(".toast__body").insertBefore(
        act,
        el.querySelector(".toast__close"),
      );
    }

    el.querySelector(".toast__close").addEventListener("click", function () {
      dismissToast(el);
    });

    // Auto-dismiss; berhenti sementara saat kursor mouse di atas toast
    var remaining = duration;
    var startedAt = 0;
    function start() {
      startedAt = Date.now();
      el._timer = setTimeout(function () {
        dismissToast(el);
      }, remaining);
    }
    el.addEventListener("pointerenter", function (e) {
      if (e.pointerType !== "mouse") return;
      clearTimeout(el._timer);
      remaining = Math.max(remaining - (Date.now() - startedAt), 800);
    });
    el.addEventListener("pointerleave", function (e) {
      if (e.pointerType !== "mouse" || el.classList.contains("is-leaving"))
        return;
      start();
    });

    box.appendChild(el);
    start();

    // Batasi jumlah toast yang tampil bersamaan
    var live = box.querySelectorAll(".toast:not(.is-leaving)");
    for (var i = 0; i < live.length - TOAST.max; i++) dismissToast(live[i]);

    return el;
  }

  // Toast yang harus tampil SETELAH halaman dimuat ulang (aksi sukses -> reload)
  var FLASH_KEY = "th_flash";

  function flash(message, type) {
    try {
      sessionStorage.setItem(
        FLASH_KEY,
        JSON.stringify({ m: message, t: type }),
      );
    } catch (e) {}
  }

  function showFlash() {
    try {
      var raw = sessionStorage.getItem(FLASH_KEY);
      if (!raw) return;
      sessionStorage.removeItem(FLASH_KEY);
      var f = JSON.parse(raw);
      toast(f.m, f.t);
    } catch (e) {}
  }

  // ---------- Konfirmasi (SweetAlert2) ----------
  function swalTheme() {
    return { background: cssVar("--surface"), color: cssVar("--text") };
  }

  function confirmBox(title, text) {
    return Swal.fire(
      Object.assign(
        {
          title: title,
          text: text,
          icon: "warning",
          showCancelButton: true,
          confirmButtonText: "Ya, hapus",
          cancelButtonText: "Batal",
          confirmButtonColor: cssVar("--danger"),
          reverseButtons: true,
          focusCancel: true,
        },
        swalTheme(),
      ),
    ).then(function (r) {
      return r.isConfirmed;
    });
  }

  // ---------- Request AJAX ----------
  // data: FormData atau object biasa. Selalu mengembalikan {status, message}
  function post(url, data) {
    var body =
      data instanceof FormData ? data : new URLSearchParams(data || {});
    return fetch(url, {
      method: "POST",
      body: body,
      headers: { Accept: "application/json" },
    })
      .then(function (res) {
        return res.json().then(function (data) {
          // Sesi habis: arahkan ke halaman login
          if (res.status === 401) {
            setTimeout(function () {
              location.href = data.redirect || BASEURL + "/auth/login";
            }, 900);
          }
          return data;
        });
      })
      .catch(function () {
        return {
          status: "error",
          message: "Terjadi kesalahan pada server. Coba lagi.",
        };
      });
  }

  // Tampilkan hasil; jika sukses muat ulang halaman agar data terbaru tampil.
  // opts.type: jenis toast saat sukses (default "success", hapus pakai "delete")
  // opts.silent: reload tanpa toast (untuk centang checklist dsb.)
  function handle(result, opts) {
    opts = opts || {};
    if (result && result.status === "success") {
      if (!opts.silent) flash(result.message, opts.type || "success");
      location.reload();
      return true;
    }
    toast((result && result.message) || "Terjadi kesalahan.", "error");
    return false;
  }

  // Kirim form modal: cegah klik ganda, tutup modal jika sukses
  function submitForm(form, url) {
    var btn = form.querySelector('[type="submit"]');
    if (btn) btn.disabled = true;
    return post(url, new FormData(form)).then(function (result) {
      var ok = handle(result);
      if (ok) {
        closeModal(form.closest(".modal"));
      } else if (btn) {
        btn.disabled = false;
      }
      return ok;
    });
  }

  // ---------- Modal ----------
  var lastFocus = null;

  function openModal(id) {
    var modal = typeof id === "string" ? document.getElementById(id) : id;
    if (!modal) return;
    lastFocus = document.activeElement;
    modal.hidden = false;
    document.body.classList.add("is-locked");
    var first = modal.querySelector(
      'input:not([type="hidden"]), textarea, select',
    );
    if (first)
      setTimeout(function () {
        first.focus();
      }, 30);
  }

  function closeModal(modal) {
    if (!modal) return;
    modal.hidden = true;
    document.body.classList.remove("is-locked");
    var btn = modal.querySelector('[type="submit"]');
    if (btn) btn.disabled = false;
    if (lastFocus && lastFocus.focus) lastFocus.focus();
  }

  document.addEventListener("click", function (e) {
    // tombol tutup / klik latar gelap
    if (e.target.closest("[data-modal-close]")) {
      closeModal(e.target.closest(".modal"));
      return;
    }
    if (e.target.classList && e.target.classList.contains("modal")) {
      closeModal(e.target);
    }
  });

  document.addEventListener("keydown", function (e) {
    if (e.key !== "Escape") return;
    var open = document.querySelector(".modal:not([hidden])");
    if (open) closeModal(open);
    closeSidebar();
  });

  // Buka modal otomatis jika URL memuat ?new=1 (tombol "Baru" di dashboard)
  function openIfNew(callback) {
    var params = new URLSearchParams(location.search);
    if (!params.has("new")) return;
    params.delete("new");
    var qs = params.toString();
    history.replaceState(null, "", location.pathname + (qs ? "?" + qs : ""));
    callback();
  }

  // ---------- Sidebar (mobile) ----------
  var sidebar = document.getElementById("sidebar");
  var scrim = document.querySelector(".scrim");

  function openSidebar() {
    if (!sidebar) return;
    sidebar.classList.add("is-open");
    if (scrim) scrim.classList.add("is-open");
  }

  function closeSidebar() {
    if (!sidebar) return;
    sidebar.classList.remove("is-open");
    if (scrim) scrim.classList.remove("is-open");
  }

  var openBtn = document.getElementById("sidebarOpen");
  if (openBtn) openBtn.addEventListener("click", openSidebar);
  document.querySelectorAll("[data-sidebar-close]").forEach(function (el) {
    el.addEventListener("click", closeSidebar);
  });

  // ---------- Tema terang/gelap ----------
  document.querySelectorAll(".js-theme").forEach(function (btn) {
    btn.addEventListener("click", function () {
      var root = document.documentElement;
      var dark = root.getAttribute("data-theme") !== "dark";
      if (dark) root.setAttribute("data-theme", "dark");
      else root.removeAttribute("data-theme");
      try {
        localStorage.setItem("th_theme", dark ? "dark" : "light");
      } catch (e) {}
    });
  });

  // ---------- Filter otomatis ----------
  document.querySelectorAll("select[data-autosubmit]").forEach(function (sel) {
    sel.addEventListener("change", function () {
      sel.form.submit();
    });
  });

  showFlash();

  window.App = {
    today: today,
    toast: toast,
    flash: flash,
    confirm: confirmBox,
    post: post,
    handle: handle,
    submitForm: submitForm,
    openModal: openModal,
    closeModal: closeModal,
    openIfNew: openIfNew,
  };
})();
