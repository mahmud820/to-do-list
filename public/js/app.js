/* Inti JS aplikasi: tema, sidebar, modal, request AJAX, notifikasi */
(function () {
  "use strict";

  // Tanggal lokal hari ini (YYYY-MM-DD), bukan UTC
  function today() {
    var d = new Date();
    var m = String(d.getMonth() + 1).padStart(2, "0");
    var day = String(d.getDate()).padStart(2, "0");
    return d.getFullYear() + "-" + m + "-" + day;
  }

  // ---------- Notifikasi & konfirmasi (native, tanpa dependency luar) ----------
  function toastStack() {
    var stack = document.getElementById("toastStack");
    if (!stack) {
      stack = document.createElement("div");
      stack.id = "toastStack";
      stack.className = "toast-stack";
      stack.setAttribute("aria-live", "polite");
      document.body.appendChild(stack);
    }
    return stack;
  }

  function toast(message, type) {
    var stack = toastStack();
    var item = document.createElement("div");
    item.className = "toast toast--" + (type === "error" ? "error" : "success");
    item.textContent = message;
    stack.appendChild(item);

    requestAnimationFrame(function () {
      item.classList.add("is-visible");
    });

    setTimeout(function () {
      item.classList.remove("is-visible");
      setTimeout(function () {
        item.remove();
      }, 250);
    }, 2200);
  }

  // Dialog konfirmasi (dipakai sebelum aksi hapus). Mengembalikan Promise<boolean>.
  function confirmBox(title, text) {
    return new Promise(function (resolve) {
      var overlay = document.createElement("div");
      overlay.className = "modal";
      overlay.innerHTML =
        '<div class="modal__box confirm-box" role="alertdialog" aria-modal="true" aria-labelledby="confirmBoxTitle">' +
        '<div class="modal__body">' +
        '<h2 id="confirmBoxTitle" class="confirm-box__title"></h2>' +
        '<p class="confirm-box__text muted"></p>' +
        "</div>" +
        '<div class="modal__foot">' +
        '<button type="button" class="btn btn--ghost" data-confirm-cancel>Batal</button>' +
        '<button type="button" class="btn btn--danger" data-confirm-ok>Ya, hapus</button>' +
        "</div>" +
        "</div>";

      overlay.querySelector(".confirm-box__title").textContent = title || "";
      var textEl = overlay.querySelector(".confirm-box__text");
      if (text) {
        textEl.textContent = text;
      } else {
        textEl.remove();
      }

      document.body.appendChild(overlay);
      document.body.classList.add("is-locked");

      var okBtn = overlay.querySelector("[data-confirm-ok]");
      var cancelBtn = overlay.querySelector("[data-confirm-cancel]");

      function finish(result) {
        document.removeEventListener("keydown", onKeydown);
        document.body.classList.remove("is-locked");
        overlay.remove();
        resolve(result);
      }

      function onKeydown(e) {
        if (e.key === "Escape") finish(false);
      }

      okBtn.addEventListener("click", function (e) {
        e.stopPropagation();
        finish(true);
      });
      cancelBtn.addEventListener("click", function (e) {
        e.stopPropagation();
        finish(false);
      });
      overlay.addEventListener("click", function (e) {
        if (e.target === overlay) {
          e.stopPropagation();
          finish(false);
        }
      });
      document.addEventListener("keydown", onKeydown);

      setTimeout(function () {
        cancelBtn.focus();
      }, 30);
    });
  }

  // ---------- Request AJAX ----------
  // data: FormData atau object biasa. Selalu mengembalikan {status, message}
  // Token CSRF (CSRF_TOKEN, didefinisikan di footer.php) selalu disertakan otomatis.
  function post(url, data) {
    var body;
    if (data instanceof FormData) {
      if (!data.has("csrf_token") && typeof CSRF_TOKEN !== "undefined") {
        data.append("csrf_token", CSRF_TOKEN);
      }
      body = data;
    } else {
      var params = Object.assign({}, data || {});
      if (!("csrf_token" in params) && typeof CSRF_TOKEN !== "undefined") {
        params.csrf_token = CSRF_TOKEN;
      }
      body = new URLSearchParams(params);
    }
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
          // Token CSRF kedaluwarsa (mis. tab dibiarkan terbuka lama): muat ulang agar dapat token baru
          if (res.status === 403) {
            setTimeout(function () {
              location.reload();
            }, 1500);
          }
          return data;
        });
      })
      .catch(function () {
        return {
          status: "error",
          message: "Permintaan gagal. Periksa koneksi internet Anda, lalu coba lagi.",
        };
      });
  }

  // Tampilkan hasil; jika sukses muat ulang halaman agar data terbaru tampil
  function handle(result, opts) {
    opts = opts || {};
    if (result && result.status === "success") {
      if (opts.silent) {
        location.reload();
      } else {
        toast(result.message);
        setTimeout(function () {
          location.reload();
        }, 1500);
      }
      return true;
    }
    toast((result && result.message) || "Terjadi kesalahan.", "error");
    return false;
  }

  // Konfirmasi lalu hapus lewat POST {id}. opts: {url, id, title, text, silent}
  function confirmDelete(opts) {
    return confirmBox(opts.title || "Yakin ingin menghapus?", opts.text).then(
      function (ok) {
        if (!ok) return false;
        return post(opts.url, { id: opts.id }).then(function (result) {
          return handle(result, { silent: !!opts.silent });
        });
      },
    );
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

  window.App = {
    today: today,
    toast: toast,
    confirm: confirmBox,
    confirmDelete: confirmDelete,
    post: post,
    handle: handle,
    submitForm: submitForm,
    openModal: openModal,
    closeModal: closeModal,
    openIfNew: openIfNew,
  };
})();
