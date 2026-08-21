<div class="container-fluid mt-3">
  <?php
  function highlight($text, $q)
  {
    if (empty($q)) return htmlspecialchars($text);
    $q_quoted = preg_quote($q, '/');
    return preg_replace('/(' . $q_quoted . ')/i', '<mark>$1</mark>', htmlspecialchars($text));
  }
  $q = $data['q'] ?? '';
  ?>
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0">Daftar Agenda</h4>
    <div class="d-flex align-items-center gap-2">
      <form class="flex-grow-1" method="get" action="">
        <input type="search" name="q" class="form-control" placeholder="Cari agenda..." value="<?= htmlspecialchars($q); ?>">
      </form>
    </div>
  </div>

  <hr class="mb-4" style="border-top: 2px solid #ccc;">

  <?php if (!empty($q)) : ?>
    <div class="mb-3">Hasil untuk: <strong><?= htmlspecialchars($q); ?></strong></div>
  <?php endif; ?>

  <div class="mb-3 text-end">
    <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#addAgendaModal">Tambah Agenda</button>
  </div>

  <div class="row g-4 align-items-stretch">
    <?php if (!empty($data['agendas'])) : ?>
      <?php foreach ($data['agendas'] as $agenda) : ?>
        <div class="col-md-6 d-flex">
          <div class="card w-100">
            <div class="card-body d-flex flex-column">

              <div class="d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0"><?= highlight($agenda['judul'], $q); ?></h5>
                <div>
                  <button class="btn btn-sm btn-primary me-1 btn-edit-agenda"
                    data-id="<?= $agenda['id']; ?>" data-judul="<?= htmlspecialchars($agenda['judul']); ?>" data-tanggal="<?= htmlspecialchars($agenda['tanggal']); ?>">Edit</button>
                  <button class="btn btn-sm btn-danger btn-delete-agenda" data-id="<?= $agenda['id']; ?>">Hapus</button>
                </div>
              </div>

              <hr>

              <div class="mb-3 agenda-list" id="agenda-items-<?= $agenda['id']; ?>">
                <?php $items = $data['items'][$agenda['id']] ?? []; ?>
                <?php if (!empty($items)) : ?>
                  <?php foreach ($items as $item) : ?>
                    <div class="d-flex align-items-center justify-content-between mb-1">
                      <div>
                        <input type="checkbox" class="form-check-input me-2 toggle-item" data-id="<?= $item['id']; ?>" <?= $item['status'] ? 'checked' : ''; ?>>
                        <?php $itemText = highlight($item['nama_item'], $q); ?>
                        <span <?= $item['status'] ? 'class="text-muted text-decoration-line-through"' : ''; ?>><?= $itemText; ?></span>
                      </div>
                      <div>
                        <button class="btn btn-sm btn-danger btn-delete-item" data-id="<?= $item['id']; ?>">Hapus</button>
                      </div>
                    </div>
                  <?php endforeach; ?>
                <?php else : ?>
                  <div class="text-muted">Belum ada item.</div>
                <?php endif; ?>
              </div>

              <form class="add-item-form mt-2" data-agenda-id="<?= $agenda['id']; ?>">
                <div class="input-group">
                  <input type="text" name="nama_item" class="form-control" placeholder="Tambahkan item..." required>
                  <button class="btn btn-outline-secondary" type="submit">Tambah</button>
                </div>
              </form>

              <div class="mt-auto d-flex justify-content-between align-items-center">
                <div>
                  <i class="bi bi-calendar-event"></i>
                  <?= $agenda['tanggal'] ? date('d M Y', strtotime($agenda['tanggal'])) : '<span class="text-muted">(Tanggal kosong)</span>'; ?>
                </div>
                <div>
                  <i class="bi bi-check2-square text-success"></i> Dilakukan: <strong><?= $agenda['done_items']; ?></strong>
                  &nbsp;&nbsp;
                  <i class="bi bi-x-circle text-danger"></i> Tidak dilakukan: <strong><?= max(0, $agenda['total_items'] - $agenda['done_items']); ?></strong>
                </div>
              </div>

            </div>
          </div>
        </div>
      <?php endforeach; ?>
    <?php else : ?>
      <div class="col-12">
        <?php if (!empty($q)) : ?>
          <div class="alert alert-warning">Tidak ada hasil untuk <strong><?= htmlspecialchars($q); ?></strong>.</div>
        <?php else : ?>
          <div class="alert alert-info">Belum ada agenda. Klik <strong>Tambah Agenda</strong> untuk mulai.</div>
        <?php endif; ?>
      </div>
    <?php endif; ?>
  </div>

</div>

<!-- Modal Tambah Agenda -->
<div class="modal fade" id="addAgendaModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <form id="addAgendaForm">
        <div class="modal-header">
          <h5 class="modal-title">Tambah Agenda</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <div class="mb-3">
            <label class="form-label">Judul</label>
            <input type="text" name="judul" class="form-control" required>
          </div>
          <div class="mb-3">
            <label class="form-label">Tanggal</label>
            <input type="date" name="tanggal" class="form-control">
          </div>
        </div>
        <div class="modal-footer">
          <button type="submit" class="btn btn-primary">Simpan</button>
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Modal Edit Agenda -->
<div class="modal fade" id="editAgendaModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <form id="editAgendaForm">
        <div class="modal-header">
          <h5 class="modal-title">Edit Agenda</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <input type="hidden" name="id" id="edit_id">
          <div class="mb-3">
            <label class="form-label">Judul</label>
            <input type="text" name="judul" id="edit_judul" class="form-control" required>
          </div>
          <div class="mb-3">
            <label class="form-label">Tanggal</label>
            <input type="date" name="tanggal" id="edit_tanggal" class="form-control">
          </div>
        </div>
        <div class="modal-footer">
          <button type="submit" class="btn btn-primary">Simpan</button>
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
        </div>
      </form>
    </div>
  </div>
</div>