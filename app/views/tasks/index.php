<!-- Halaman Utama -->
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
  <!-- Judul dan Pencarian -->
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0">Daftar Tasks</h4>
    <div class="d-flex align-items-center">
      <form class="me-2" method="get" action="">
        <input type="search" name="q" class="form-control w-100" placeholder="Cari task..." value="<?= htmlspecialchars($q); ?>">
      </form>
      <!-- tombol tambah ada di bawah sesuai layout existing -->
    </div>
  </div>

  <!-- Garis pemisah -->
  <hr class="mb-4" style="border-top: 2px solid #ccc;">

  <?php if (!empty($q)) : ?>
    <div class="mb-3">Hasil untuk: <strong><?= htmlspecialchars($q); ?></strong></div>
  <?php endif; ?>

  <div class="mb-3 text-end">
    <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#addTaskModal">
      Tambah Task
    </button>
  </div>

  <!-- Task Cards -->
  <div class="row">
    <?php if (!empty($data['tasks'])) : ?>
      <?php foreach ($data['tasks'] as $task) : ?>
        <div class="col-md-4 mb-4">
          <div class="card h-100 shadow-sm">
            <div class="card-body">
              <h5 class="card-title d-flex justify-content-between align-items-center">
                <span><?= highlight($task['judul'], $q); ?></span>
                <small class="text-muted"><?= date('d-m-Y', strtotime($task['tanggal_dibuat'])); ?>
                </small>
              </h5>

              <!-- Checkbox status -->
              <!-- <div class="form-check my-2">
                <input class="form-check-input toggle-status" type="checkbox"
                  data-id="<?= $task['id']; ?>"
                  <?= ($task['status'] === 'selesai') ? 'checked' : ''; ?>>
                <label class="form-check-label">
                  <?= ucfirst($task['status']); ?>
                </label>
              </div> -->


              <!-- Deskripsi -->
              <?php $desc = highlight($task['deskripsi'], $q); ?>
              <p class="card-text"><?= nl2br($desc); ?></p>

              <!-- Deadline -->
              <p>
                <small class="text-muted">
                  Deadline: <?= date('d F Y', strtotime($task['deadline'])); ?>
                </small>
              </p>
            </div>

            <!-- Footer actions -->
            <div class="card-footer d-flex justify-content-between">
              <button href="<?= BASEURL; ?>/tasks/edit/<?= $task['id']; ?>"
                class="btn btn-sm btn-primary btn-edit"
                data-id="<?= $task['id']; ?>"
                data-judul="<?= htmlspecialchars($task['judul']); ?>"
                data-deskripsi="<?= htmlspecialchars($task['deskripsi']); ?>"
                data-deadline="<?= $task['deadline']; ?>">Edit</button>
              <button href="<?= BASEURL; ?>/tasks/delete/<?= $task['id']; ?>"
                class="btn btn-sm btn-danger btn-delete" data-id="<?= $task['id']; ?>">
                Hapus
              </button>
            </div>
          </div>
        </div>
      <?php endforeach; ?>
    <?php else : ?>
      <div class="col-12">
        <?php if (!empty($q)) : ?>
          <div class="alert alert-warning">Tidak ada hasil untuk <strong><?= htmlspecialchars($q); ?></strong>.</div>
        <?php else : ?>
          <div class="alert alert-info">Belum ada task. Klik <strong>Tambah Task</strong> untuk mulai.</div>
        <?php endif; ?>
      </div>
    <?php endif; ?>
  </div>
</div>

<!-- Modal Tambah Task -->
<div class="modal fade" id="addTaskModal" tabindex="-1" aria-labelledby="addTaskModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-md">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="addTaskModalLabel">Tambah Task Baru</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <form id="addTaskForm">
          <div class="mb-3">
            <label for="judul" class="form-label">Judul Task</label>
            <input type="text" class="form-control" id="judul" name="judul" required>
          </div>

          <div class="mb-3">
            <label for="deskripsi" class="form-label">Deskripsi</label>
            <textarea class="form-control" id="deskripsi" name="deskripsi" rows="3" required></textarea>
          </div>

          <div class="mb-3">
            <label for="deadline" class="form-label">Deadline</label>
            <input type="date" class="form-control" id="deadline" name="deadline" required>
          </div>

          <button type="submit" class="btn btn-primary w-100">Simpan</button>
        </form>
      </div>
    </div>
  </div>
</div>

<!-- Modal Edit Task (Akan diisi via JS) -->
<div class="modal fade" id="editTaskModal" tabindex="-1" aria-labelledby="editTaskModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-md">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="editTaskModalLabel">Edit Task</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>

      <div class="modal-body">
        <form id="editTaskForm">
          <input type="hidden" id="edit_id" name="id">
          <div class="mb-3">
            <label class="form-label">Judul</label>
            <input type="text" class="form-control" id="edit_judul" name="judul" required>
          </div>
          <div class="mb-3">
            <label class="form-label">Deskripsi</label>
            <textarea class="form-control" id="edit_deskripsi" name="deskripsi" rows="3" required></textarea>
          </div>
          <div class="mb-3">
            <label class="form-label">Deadline</label>
            <input type="date" class="form-control" id="edit_deadline" name="deadline" required>
          </div>
          <button type="submit" class="btn btn-primary w-100">Update</button>
        </form>
      </div>
    </div>
  </div>
</div>