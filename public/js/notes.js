/* Halaman Notes: tambah, edit, hapus (AJAX) */
(function () {
  "use strict";

  var modal = document.getElementById("noteModal");
  if (!modal) return;

  var form = document.getElementById("noteForm");
  var el = function (id) {
    return document.getElementById(id);
  };

  function openNew() {
    form.reset();
    el("note_id").value = "";
    el("noteModalTitle").textContent = "Catatan Baru";
    App.openModal(modal);
  }

  function openEdit(n) {
    form.reset();
    el("note_id").value = n.id;
    el("noteModalTitle").textContent = "Edit Catatan";
    el("note_judul").value = n.judul || "";
    el("note_isi").value = n.isi || "";
    App.openModal(modal);
  }

  document.addEventListener("click", function (e) {
    if (e.target.closest("[data-note-new]")) {
      openNew();
      return;
    }

    var edit = e.target.closest("[data-note-edit]");
    if (edit) {
      openEdit(JSON.parse(edit.getAttribute("data-note-edit")));
      return;
    }

    var del = e.target.closest("[data-note-delete]");
    if (del) {
      App.confirmDelete({
        url: BASEURL + "/notes/delete",
        id: del.getAttribute("data-note-delete"),
        text: "Catatan yang dihapus tidak bisa dikembalikan!",
      });
    }
  });

  form.addEventListener("submit", function (e) {
    e.preventDefault();
    var isEdit = el("note_id").value !== "";
    App.submitForm(form, BASEURL + (isEdit ? "/notes/update" : "/notes/add"));
  });

  App.openIfNew(openNew);
})();
