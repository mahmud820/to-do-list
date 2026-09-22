/* Halaman Tasks: tambah, edit, hapus (AJAX) */
(function () {
  "use strict";

  var modal = document.getElementById("taskModal");
  if (!modal) return;

  var form = document.getElementById("taskForm");
  var el = function (id) {
    return document.getElementById(id);
  };
  var range = el("task_progress");
  var rangeVal = el("task_progress_val");
  var statusSel = el("task_status");

  function setProgress(v) {
    range.value = v;
    rangeVal.textContent = v;
  }

  // Progress 100% hanya boleh untuk status Selesai (selaras dengan validasi server).
  // Saat status Selesai, slider dikunci di 100% agar tidak ada tampilan yang menyesatkan.
  function syncProgressLock() {
    var locked = statusSel.value === "selesai";
    range.disabled = locked;
    if (locked) setProgress(100);
  }

  function openNew() {
    form.reset();
    el("task_id").value = "";
    el("taskModalTitle").textContent = "Tambah Task Baru";
    el("task_deadline").value = App.today();
    setProgress(0);
    syncProgressLock();
    App.openModal(modal);
  }

  function openEdit(t) {
    form.reset();
    el("task_id").value = t.id;
    el("taskModalTitle").textContent = "Edit Task";
    el("task_judul").value = t.judul || "";
    el("task_deskripsi").value = t.deskripsi || "";
    el("task_status").value = t.status;
    el("task_prioritas").value = t.prioritas;
    el("task_deadline").value = t.deadline;
    setProgress(t.progress);
    syncProgressLock();
    App.openModal(modal);
  }

  range.addEventListener("input", function () {
    rangeVal.textContent = range.value;
    // Digeser sampai 100% tanpa disengaja -> ikut set status Selesai, bukan menunggu error dari server
    if (range.value === "100" && statusSel.value !== "selesai") {
      statusSel.value = "selesai";
      syncProgressLock();
    }
  });

  // Status "Selesai" otomatis mengisi & mengunci progress 100%; status lain membuka kunci lagi
  statusSel.addEventListener("change", syncProgressLock);

  document.addEventListener("click", function (e) {
    if (e.target.closest("[data-task-new]")) {
      openNew();
      return;
    }

    var edit = e.target.closest("[data-task-edit]");
    if (edit) {
      openEdit(JSON.parse(edit.getAttribute("data-task-edit")));
      return;
    }

    var del = e.target.closest("[data-task-delete]");
    if (del) {
      var id = del.getAttribute("data-task-delete");
      App.confirm(
        "Yakin ingin menghapus?",
        "Task yang dihapus tidak bisa dikembalikan!",
      ).then(function (ok) {
        if (ok)
          App.post(BASEURL + "/tasks/delete", { id: id }).then(App.handle);
      });
    }
  });

  form.addEventListener("submit", function (e) {
    e.preventDefault();
    var isEdit = el("task_id").value !== "";
    App.submitForm(form, BASEURL + (isEdit ? "/tasks/update" : "/tasks/add"));
  });

  App.openIfNew(openNew);
})();
