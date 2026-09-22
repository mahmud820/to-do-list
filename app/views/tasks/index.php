<?php

/** @var array $data */
$q = $data['q'];
$filterAktif = $q !== '' || $data['status'] !== '' || $data['prioritas'] !== '' || $data['deadline'] !== '';
$labelDeadline = [
  'overdue'  => 'Terlambat',
  'today'    => 'Hari ini',
  'soon'     => 'Segera',
  'upcoming' => 'Mendatang',
];
?>

<section class="page">
  <div class="page__head">
    <div>
      <h1 class="page__title">Manajemen Tasks</h1>
      <p class="page__sub">Apa yang harus saya kerjakan? Kelola dan pantau progress pekerjaan Anda.</p>
    </div>
    <div class="page__actions">
      <button type="button" class="btn btn--primary" data-task-new>
        <?= icon('plus'); ?> <span>Tambah Task</span>
      </button>
    </div>
  </div>

  <!-- Pencarian & filter -->
  <form class="toolbar" method="get" action="<?= BASEURL; ?>/tasks">
    <div class="search">
      <?= icon('search'); ?>
      <input type="search" name="q" class="input" placeholder="Cari judul atau deskripsi task..." value="<?= e($q); ?>" aria-label="Cari task">
    </div>
    <select name="status" class="select" data-autosubmit aria-label="Filter status">
      <option value="">Semua Status</option>
      <?php foreach (M_Tasks::STATUS as $s) : ?>
        <option value="<?= e($s); ?>" <?= $data['status'] === $s ? 'selected' : ''; ?>><?= e(status_label($s)); ?></option>
      <?php endforeach; ?>
    </select>
    <select name="prioritas" class="select" data-autosubmit aria-label="Filter prioritas">
      <option value="">Semua Prioritas</option>
      <?php foreach (M_Tasks::PRIORITAS as $p) : ?>
        <option value="<?= e($p); ?>" <?= $data['prioritas'] === $p ? 'selected' : ''; ?>>Prioritas <?= e(ucfirst($p)); ?></option>
      <?php endforeach; ?>
    </select>
    <select name="deadline" class="select" data-autosubmit aria-label="Filter deadline">
      <option value="">Semua Deadline</option>
      <?php foreach ($labelDeadline as $key => $label) : ?>
        <option value="<?= e($key); ?>" <?= $data['deadline'] === $key ? 'selected' : ''; ?>><?= e($label) ?></option>
      <?php endforeach; ?>
    </select>
    <?php if ($filterAktif) : ?>
      <a class="btn btn--ghost" href="<?= BASEURL; ?>/tasks">Reset</a>
    <?php endif; ?>
  </form>

  <?php if (empty($data['tasks'])) : ?>
    <div class="empty">
      <?= icon('tasks'); ?>
      <?php if ($filterAktif) : ?>
        <p>Tidak ada task yang cocok dengan pencarian/filter.</p>
      <?php else : ?>
        <p>Belum ada task. Klik <strong>Tambah Task</strong> untuk mulai.</p>
      <?php endif; ?>
    </div>
  <?php else : ?>
    <div class="grid grid--cards">
      <?php foreach ($data['tasks'] as $task) :
        $info = deadline_info($task['deadline'], $task['status'], $data['today']);
        $payload = [
          'id'        => (int) $task['id'],
          'judul'     => $task['judul'],
          'deskripsi' => $task['deskripsi'],
          'deadline'  => $task['deadline'],
          'status'    => $task['status'],
          'prioritas' => $task['prioritas'],
          'progress'  => (int) $task['progress'],
        ]; ?>
        <article class="card<?= $info['state'] === 'overdue' ? ' card--danger' : ''; ?><?= $task['status'] === 'selesai' ? ' card--done' : ''; ?>">
          <div class="card__badges">
            <span class="badge <?= status_class($task['status']); ?>"><?= e(status_label($task['status'])); ?></span>
            <span class="badge <?= prioritas_class($task['prioritas']); ?>"><?= e(ucfirst($task['prioritas'])); ?></span>
          </div>

          <h3 class="card__title"><?= highlight($task['judul'], $q); ?></h3>
          <p class="card__text"><?= $task['deskripsi'] ? nl2br(highlight($task['deskripsi'], $q)) : '<em class="muted">Tidak ada deskripsi.</em>'; ?></p>

          <div class="progress-wrap">
            <div class="progress-wrap__row"><span>Progress</span><strong><?= (int) $task['progress']; ?>%</strong></div>
            <div class="progress" role="progressbar" aria-valuenow="<?= (int) $task['progress']; ?>" aria-valuemin="0" aria-valuemax="100">
              <div class="progress__bar" style="width: <?= (int) $task['progress']; ?>%"></div>
            </div>
          </div>

          <div class="card__foot">
            <span class="deadline deadline--<?= $info['state']; ?>" title="Deadline: <?= e(tanggal_indo($task['deadline'], true)); ?>">
              <?= icon('calendar'); ?>
              <?php if ($info['state'] === 'normal' || $info['state'] === 'done') : ?>
                <?= e($info['label']); ?>
              <?php else : ?>
                <?= e(tanggal_indo($task['deadline'])); ?> &middot; <?= e($info['label']); ?>
              <?php endif; ?>
            </span>
            <span class="card__actions">
              <button type="button" class="btn btn--ghost btn--icon btn--sm" data-task-edit="<?= e(json_encode($payload, JSON_UNESCAPED_UNICODE)); ?>" aria-label="Edit task" title="Edit"><?= icon('edit'); ?></button>
              <button type="button" class="btn btn--ghost btn--icon btn--sm btn--danger-hover" data-task-delete="<?= (int) $task['id']; ?>" aria-label="Hapus task" title="Hapus"><?= icon('trash'); ?></button>
            </span>
          </div>
        </article>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</section>

<!-- Modal tambah/edit task -->
<div class="modal" id="taskModal" hidden role="dialog" aria-modal="true" aria-labelledby="taskModalTitle">
  <div class="modal__box">
    <div class="modal__head">
      <h2 id="taskModalTitle">Tambah Task Baru</h2>
      <button type="button" class="btn btn--ghost btn--icon" data-modal-close aria-label="Tutup"><?= icon('x'); ?></button>
    </div>
    <form id="taskForm" class="modal__body">
      <input type="hidden" name="id" id="task_id">

      <div class="field">
        <label for="task_judul">Judul Task</label>
        <input type="text" class="input" id="task_judul" name="judul" maxlength="255" required>
      </div>

      <div class="field">
        <label for="task_deskripsi">Deskripsi</label>
        <textarea class="textarea" id="task_deskripsi" name="deskripsi" rows="3"></textarea>
      </div>

      <div class="field-row">
        <div class="field">
          <label for="task_status">Status</label>
          <select class="select" id="task_status" name="status">
            <?php foreach (M_Tasks::STATUS as $s) : ?>
              <option value="<?= e($s); ?>"><?= e(status_label($s)); ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="field">
          <label for="task_prioritas">Prioritas</label>
          <select class="select" id="task_prioritas" name="prioritas">
            <?php foreach (M_Tasks::PRIORITAS as $p) : ?>
              <option value="<?= e($p); ?>" <?= $p === 'sedang' ? 'selected' : ''; ?>><?= e(ucfirst($p)); ?></option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>

      <div class="field-row">
        <div class="field">
          <label for="task_deadline">Deadline</label>
          <input type="date" class="input" id="task_deadline" name="deadline" required>
        </div>
        <div class="field">
          <label for="task_progress">Progress (<span id="task_progress_val">0</span>%)</label>
          <input type="range" class="range" id="task_progress" name="progress" min="0" max="100" step="1" value="0">
        </div>
      </div>

      <div class="modal__foot">
        <button type="button" class="btn btn--ghost" data-modal-close>Batal</button>
        <button type="submit" class="btn btn--primary">Simpan Task</button>
      </div>
    </form>
  </div>
</div>