<?php

/** @var array $data */

$ts = $data['taskStats'] ?? [];
$as = $data['agendaStats'] ?? [];

$totalTasks = (int) ($ts['total'] ?? 0);
$selesai = (int) ($ts['selesai'] ?? 0);
$berjalan = (int) ($ts['berjalan'] ?? 0);
$terlambat = (int) ($ts['terlambat'] ?? 0);
$mendekati = (int) ($ts['mendekati'] ?? 0);

$belumSelesai = max(0, $totalTasks - $selesai);

$agendaTotal = (int) ($as['total'] ?? 0);
$agendaHariIni = (int) ($as['hari_ini'] ?? 0);
$agendaMendatang = (int) ($as['mendatang'] ?? 0);

$notesTotal = (int) ($data['notesTotal'] ?? 0);

?>

<section class="page">
  <!-- HEADER -->
  <div class="page__head">
    <div>
      <h1 class="page__title">
        Halo, <?= e($_SESSION['user'] ?? ''); ?>
      </h1>
      <p class="page__sub">
        <?= e(tanggal_lengkap()); ?>
        &middot;
        Ringkasan tugas, agenda, dan catatan Anda.
      </p>
    </div>

    <div class="page__actions">
      <a href="<?= BASEURL; ?>/tasks?new=1" class="btn btn--primary btn--sm">
        <?= icon('plus'); ?>
        Tugas Baru
      </a>
      <a href="<?= BASEURL; ?>/agenda?new=1" class="btn btn--agenda btn--sm">
        <?= icon('plus'); ?>
        Agenda Baru
      </a>
      <a href="<?= BASEURL; ?>/notes?new=1" class="btn btn--note btn--sm">
        <?= icon('plus'); ?>
        Catatan Baru
      </a>
    </div>
  </div>

  <!-- STATISTIK UTAMA -->
  <div class="stats">
    <!-- Total Tugas -->
    <a class="stat" href="<?= BASEURL; ?>/tasks">
      <span class="stat__label">Total Tugas</span>
      <span class="stat__value"><?= $totalTasks; ?></span>
      <span class="stat__sub"><?= $belumSelesai; ?> belum selesai</span>
    </a>

    <!-- Sedang Dikerjakan -->
    <a class="stat" href="<?= BASEURL; ?>/tasks?status=sedang+dikerjakan">
      <span class="stat__label">Sedang Dikerjakan</span>
      <span class="stat__value stat__value--brand"><?= $berjalan; ?></span>
      <span class="stat__sub">tugas berjalan</span>
    </a>

    <!-- Selesai -->
    <a class="stat" href="<?= BASEURL; ?>/tasks?status=selesai">
      <span class="stat__label">Selesai</span>
      <span class="stat__value stat__value--ok"><?= $selesai; ?></span>
      <span class="stat__sub">tugas tuntas</span>
    </a>

    <!-- Mendekati Deadline -->
    <div class="stat">
      <a class="stat__link" href="<?= BASEURL; ?>/tasks?deadline=soon">
        <span class="stat__label">Mendekati Deadline</span>
        <span class="stat__value stat__value--warn"><?= $mendekati; ?></span>
      </a>
      <a class="stat__sub stat__sub--link<?= $terlambat > 0 ? ' stat__sub--danger' : ''; ?>" href="<?= BASEURL; ?>/tasks?deadline=overdue">
        <?= $terlambat; ?> terlambat
      </a>
    </div>

    <!-- Agenda -->
    <a class="stat" href="<?= BASEURL; ?>/agenda">
      <span class="stat__label">Agenda Hari Ini</span>
      <span class="stat__value stat__value--agenda"><?= $agendaHariIni; ?></span>
      <span class="stat__sub"><?= $agendaMendatang; ?> mendatang &middot; <?= $agendaTotal; ?> total</span>
    </a>

    <!-- Catatan -->
    <a class="stat" href="<?= BASEURL; ?>/notes">
      <span class="stat__label">Catatan</span>
      <span class="stat__value stat__value--note"><?= $notesTotal; ?></span>
      <span class="stat__sub">tersimpan</span>
    </a>
  </div>

  <!-- WIDGET / PANEL -->
  <div class="widgets">
    <!-- AGENDA HARI INI -->
    <div class="panel">
      <div class="panel__head">
        <h2 class="panel__title">
          <?= icon('clock', 'text-agenda'); ?>
          Agenda Hari Ini
        </h2>
        <a href="<?= BASEURL; ?>/agenda" class="link">Lihat semua</a>
      </div>

      <?php if (empty($data['agendaToday'])) : ?>
        <p class="empty empty--inline">Tidak ada agenda untuk hari ini.</p>
      <?php else : ?>
        <ul class="list">
          <?php foreach ($data['agendaToday'] as $a) : ?>
            <li class="list__item">
              <div>
                <p class="list__title"><?= e($a['judul']); ?></p>
                <p class="list__meta">
                  <?= icon('clock'); ?>
                  <?= e(rentang_waktu($a)); ?>
                </p>
              </div>

              <?php if ((int) $a['total_items'] > 0) : ?>
                <span class="badge badge--agenda">
                  <?= (int) $a['done_items']; ?> / <?= (int) $a['total_items']; ?>
                </span>
              <?php endif; ?>
            </li>
          <?php endforeach; ?>
        </ul>
      <?php endif; ?>
    </div>

    <!-- TUGAS MENDESAK -->
    <div class="panel">
      <div class="panel__head">
        <h2 class="panel__title">
          <?= icon('alert', 'text-warn'); ?>
          Tugas Mendesak
        </h2>
        <a href="<?= BASEURL; ?>/tasks" class="link">Lihat semua</a>
      </div>

      <?php if (empty($data['urgentTasks'])) : ?>
        <p class="empty empty--inline">Tidak ada tugas yang perlu segera ditangani.</p>
      <?php else : ?>
        <ul class="list">
          <?php foreach ($data['urgentTasks'] as $t) :
            $info = deadline_info(
              $t['deadline'],
              $t['status'],
              $data['today']
            );
          ?>
            <li class="list__item">
              <div>
                <p class="list__title">
                  <?= e($t['judul']); ?>
                  <span class="badge <?= prioritas_class($t['prioritas']); ?>">
                    <?= e(ucfirst($t['prioritas'])); ?>
                  </span>
                </p>
                <p class="list__meta deadline deadline--<?= e($info['state']); ?>">
                  <?= icon('calendar'); ?>
                  <?= e($info['label']); ?>
                </p>
              </div>
              <span class="list__value"><?= (int) $t['progress']; ?>%</span>
            </li>
          <?php endforeach; ?>
        </ul>
      <?php endif; ?>
    </div>

    <!-- AGENDA BERIKUTNYA -->
    <div class="panel">
      <div class="panel__head">
        <h2 class="panel__title">
          <?= icon('calendar', 'text-agenda'); ?>
          Agenda Berikutnya
        </h2>
        <a href="<?= BASEURL; ?>/agenda?filter=upcoming" class="link">Lihat semua</a>
      </div>

      <?php if (empty($data['agendaUpcoming'])) : ?>
        <p class="empty empty--inline">Belum ada agenda mendatang.</p>
      <?php else : ?>
        <ul class="list">
          <?php foreach ($data['agendaUpcoming'] as $a) : ?>
            <li class="list__item">
              <div>
                <p class="list__title"><?= e($a['judul']); ?></p>
                <p class="list__meta">
                  <?= icon('clock'); ?>
                  <?= e(tanggal_indo($a['tanggal'])); ?> &middot; <?= e(rentang_waktu($a)); ?>
                </p>
              </div>
            </li>
          <?php endforeach; ?>
        </ul>
      <?php endif; ?>
    </div>

    <!-- CATATAN TERBARU -->
    <div class="panel">
      <div class="panel__head">
        <h2 class="panel__title">
          <?= icon('note', 'text-note'); ?>
          Catatan Terbaru
        </h2>
        <a href="<?= BASEURL; ?>/notes" class="link">Lihat semua</a>
      </div>

      <?php if (empty($data['latestNotes'])) : ?>
        <p class="empty empty--inline">Belum ada catatan.</p>
      <?php else : ?>
        <ul class="list">
          <?php foreach ($data['latestNotes'] as $n) : ?>
            <li class="list__item">
              <div>
                <p class="list__title"><?= e($n['judul']); ?></p>
                <p class="list__meta"><?= e(tanggal_indo($n['updated_at'])); ?></p>
              </div>
            </li>
          <?php endforeach; ?>
        </ul>
      <?php endif; ?>
    </div>
  </div>
</section>