<?php

/** @var array $data */

$ts = $data['taskStats'];
$as = $data['agendaStats'];

$totalTasks = (int) ($ts['total'] ?? 0);
$selesai    = (int) ($ts['selesai'] ?? 0);
$berjalan   = (int) ($ts['berjalan'] ?? 0);
$terlambat  = (int) ($ts['terlambat'] ?? 0);
$mendekati  = (int) ($ts['mendekati'] ?? 0);
$belumSelesai = $totalTasks - $selesai;

$agendaTotal = (int) ($as['total'] ?? 0);
$agendaHariIni = (int) ($as['hari_ini'] ?? 0);
$agendaMendatang = (int) ($as['mendatang'] ?? 0);
?>
<section class="page">
  <div class="page__head">
    <div>
      <h1 class="page__title">Halo, <?= e($_SESSION['user'] ?? ''); ?></h1>
      <p class="page__sub"><?= e(tanggal_lengkap()); ?> &middot; Ringkasan aktivitas, tenggat waktu, dan catatan Anda.</p>
    </div>
    <div class="page__actions">
      <a href="<?= BASEURL; ?>/tasks?new=1" class="btn btn--primary btn--sm"><?= icon('plus'); ?> Task Baru</a>
      <a href="<?= BASEURL; ?>/agenda?new=1" class="btn btn--agenda btn--sm"><?= icon('plus'); ?> Agenda Baru</a>
      <a href="<?= BASEURL; ?>/notes?new=1" class="btn btn--note btn--sm"><?= icon('plus'); ?> Note Baru</a>
    </div>
  </div>

  <!-- Metrics -->
  <div class="stats">
    <a class="stat" href="<?= BASEURL; ?>/tasks">
      <span class="stat__label">Total Tasks</span>
      <span class="stat__value"><?= $totalTasks; ?></span>
      <span class="stat__sub"><?= $belumSelesai; ?> belum selesai</span>
    </a>
    <a class="stat" href="<?= BASEURL; ?>/tasks?status=sedang+dikerjakan">
      <span class="stat__label">Sedang Dikerjakan</span>
      <span class="stat__value stat__value--brand"><?= $berjalan; ?></span>
      <span class="stat__sub">task berjalan</span>
    </a>
    <a class="stat" href="<?= BASEURL; ?>/tasks?status=selesai">
      <span class="stat__label">Selesai</span>
      <span class="stat__value stat__value--ok"><?= $selesai; ?></span>
      <span class="stat__sub">task tuntas</span>
    </a>
    <a class="stat" href="<?= BASEURL; ?>/tasks">
      <span class="stat__label">Mendekati Deadline</span>
      <span class="stat__value stat__value--warn"><?= $mendekati; ?></span>
      <span class="stat__sub<?= $terlambat > 0 ? ' stat__sub--danger' : ''; ?>">
        <?= $terlambat; ?> terlambat
      </span>
    </a>
    <a class="stat" href="<?= BASEURL; ?>/agenda">
      <span class="stat__label">Agenda Hari Ini</span>
      <span class="stat__value stat__value--agenda"><?= $agendaHariIni; ?></span>
      <span class="stat__sub"><?= $agendaMendatang; ?> mendatang &middot; <?= $agendaTotal; ?> total</span>
    </a>
    <a class="stat" href="<?= BASEURL; ?>/notes">
      <span class="stat__label">Catatan</span>
      <span class="stat__value stat__value--note"><?= (int) $data['notesTotal']; ?></span>
      <span class="stat__sub">tersimpan</span>
    </a>
  </div>

  <!-- Widgets -->
  <div class="widgets">
    <!-- Agenda hari ini -->
    <div class="panel">
      <div class="panel__head">
        <h2 class="panel__title"><?= icon('clock', 'text-agenda'); ?> Agenda Hari Ini</h2>
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
                <p class="list__meta"><?= icon('clock'); ?> <?= e(rentang_waktu($a)); ?></p>
              </div>
              <?php if ((int) $a['total_items'] > 0) : ?>
                <span class="badge badge--agenda"><?= (int) $a['done_items']; ?>/<?= (int) $a['total_items']; ?></span>
              <?php endif; ?>
            </li>
          <?php endforeach; ?>
        </ul>
      <?php endif; ?>
    </div>

    <!-- Tugas mendesak -->
    <div class="panel">
      <div class="panel__head">
        <h2 class="panel__title"><?= icon('alert', 'text-warn'); ?> Tugas Mendesak &amp; Penting</h2>
        <a href="<?= BASEURL; ?>/tasks" class="link">Lihat semua</a>
      </div>
      <?php if (empty($data['urgentTasks'])) : ?>
        <p class="empty empty--inline">Semua tugas sudah selesai. Kerja bagus!</p>
      <?php else : ?>
        <ul class="list">
          <?php foreach ($data['urgentTasks'] as $t) :
            $info = deadline_info($t['deadline'], $t['status'], $data['today']); ?>
            <li class="list__item">
              <div>
                <p class="list__title">
                  <?= e($t['judul']); ?>
                  <span class="badge <?= prioritas_class($t['prioritas']); ?>"><?= e(ucfirst($t['prioritas'])); ?></span>
                </p>
                <p class="list__meta deadline deadline--<?= $info['state']; ?>">
                  <?= icon('calendar'); ?> <?= e($info['label']); ?>
                </p>
              </div>
              <span class="list__value"><?= (int) $t['progress']; ?>%</span>
            </li>
          <?php endforeach; ?>
        </ul>
      <?php endif; ?>
    </div>

    <!-- Agenda mendatang -->
    <div class="panel">
      <div class="panel__head">
        <h2 class="panel__title"><?= icon('calendar', 'text-agenda'); ?> Agenda Berikutnya</h2>
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
                <p class="list__meta"><?= icon('clock'); ?> <?= e(tanggal_indo($a['tanggal'])); ?> &middot; <?= e(rentang_waktu($a)); ?></p>
              </div>
            </li>
          <?php endforeach; ?>
        </ul>
      <?php endif; ?>
    </div>

    <!-- Catatan terbaru -->
    <div class="panel">
      <div class="panel__head">
        <h2 class="panel__title"><?= icon('note', 'text-note'); ?> Catatan Terbaru</h2>
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