/* Halaman Agenda: CRUD agenda + checklist item (AJAX) */
(function () {
  "use strict";

  var modal = document.getElementById("agendaModal");
  if (!modal) return;

  var form = document.getElementById("agendaForm");
  var el = function (id) {
    return document.getElementById(id);
  };
  var builder = el("agendaItemsBuilder");
  var itemsField = el("agendaItemsField");
  var editNote = el("agendaEditItemsNote");

  // Baris input checklist pada form tambah agenda
  function addBuilderRow() {
    var row = document.createElement("div");
    row.className = "builder__row";

    var input = document.createElement("input");
    input.type = "text";
    input.name = "items[]";
    input.maxLength = 150;
    input.placeholder = "Nama item checklist...";
    input.className = "input input--sm";

    var remove = document.createElement("button");
    remove.type = "button";
    remove.className = "btn btn--ghost btn--icon btn--sm btn--danger-hover";
    remove.setAttribute("aria-label", "Hapus baris");
    remove.textContent = "\u00d7";
    remove.addEventListener("click", function () {
      row.remove();
    });

    row.appendChild(input);
    row.appendChild(remove);
    builder.appendChild(row);
    return input;
  }

  function openNew() {
    form.reset();
    builder.innerHTML = "";
    el("agenda_id").value = "";
    el("agendaModalTitle").textContent = "Tambah Agenda Baru";
    el("agenda_tanggal").value = App.today();
    itemsField.hidden = false;
    editNote.hidden = true;
    addBuilderRow();
    App.openModal(modal);
  }

  function openEdit(a) {
    form.reset();
    builder.innerHTML = "";
    itemsField.hidden = true;
    editNote.hidden = false;
    el("agenda_id").value = a.id;
    el("agendaModalTitle").textContent = "Edit Agenda";
    el("agenda_judul").value = a.judul || "";
    el("agenda_deskripsi").value = a.deskripsi || "";
    el("agenda_tanggal").value = a.tanggal || "";
    el("agenda_mulai").value = a.waktu_mulai || "";
    el("agenda_selesai").value = a.waktu_selesai || "";
    App.openModal(modal);
  }

  el("agendaItemAdd").addEventListener("click", function () {
    addBuilderRow().focus();
  });

  form.addEventListener("submit", function (e) {
    e.preventDefault();
    var isEdit = el("agenda_id").value !== "";
    App.submitForm(form, BASEURL + (isEdit ? "/agenda/update" : "/agenda/add"));
  });

  document.addEventListener("click", function (e) {
    if (e.target.closest("[data-agenda-new]")) {
      openNew();
      return;
    }

    var edit = e.target.closest("[data-agenda-edit]");
    if (edit) {
      openEdit(JSON.parse(edit.getAttribute("data-agenda-edit")));
      return;
    }

    var del = e.target.closest("[data-agenda-delete]");
    if (del) {
      App.confirmDelete({
        url: BASEURL + "/agenda/delete",
        id: del.getAttribute("data-agenda-delete"),
        text: "Agenda akan dihapus beserta seluruh itemnya!",
      });
      return;
    }

    var delItem = e.target.closest("[data-delete-item]");
    if (delItem) {
      App.confirmDelete({
        url: BASEURL + "/agenda/deleteItem",
        id: delItem.getAttribute("data-delete-item"),
        title: "Hapus item ini?",
        text: "Item checklist akan dihapus permanen.",
        silent: true,
      });
    }
  });

  // Centang / batal centang item checklist
  document.addEventListener("change", function (e) {
    var box = e.target.closest("[data-toggle-item]");
    if (!box) return;
    App.post(BASEURL + "/agenda/toggleItem", {
      id: box.getAttribute("data-toggle-item"),
    }).then(function (r) {
      if (!App.handle(r, { silent: true })) box.checked = !box.checked;
    });
  });

  // Tambah item checklist langsung dari kartu agenda
  document.addEventListener("submit", function (e) {
    var f = e.target.closest("[data-add-item]");
    if (!f) return;
    e.preventDefault();
    var btn = f.querySelector('[type="submit"]');
    if (btn) btn.disabled = true;
    App.post(BASEURL + "/agenda/addItem", {
      agenda_id: f.getAttribute("data-add-item"),
      nama_item: f.elements["nama_item"].value,
    }).then(function (r) {
      if (!App.handle(r, { silent: true }) && btn) btn.disabled = false;
    });
  });

  // Klik tab filter: bawa kata yang sedang diketik di kotak pencarian (walau belum tekan Enter)
  document.querySelectorAll(".tabs__item").forEach(function (tab) {
    tab.addEventListener("click", function (e) {
      var input = document.querySelector('.toolbar input[name="q"]');
      if (!input) return;

      e.preventDefault();
      var url = new URL(tab.href, location.href);
      var q = input.value.trim();
      if (q) url.searchParams.set("q", q);
      else url.searchParams.delete("q");
      location.href = url.toString();
    });
  });

  App.openIfNew(openNew);
})();
