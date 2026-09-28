<?php

/** @var array $data */

$q = $data['q'];
$filter = $data['filter'];
$tabs = [
  'today'    => 'Hari Ini',
  'upcoming' => 'Mendatang',
  'past'     => 'Lewat',
  'all'      => 'Semua Agenda',
];
?>
<section class="page">
  <div class="page__head">
    <div>
      <h1 class="page__title">Agenda &amp; Jadwal</h1>
      <p class="page__sub">Kapan saya harus melakukan kegiatan? Atur jadwal beserta checklist detailnya.</p>
    </div>
    <div class="page__actions">
      <button type="button" class="btn btn--agenda" data-agenda-new>
        <?= icon('plus'); ?> <span>Tambah Agenda</span>
      </button>
    </div>
  </div>

  <!-- Tab filter -->
  <nav class="tabs" aria-label="Filter agenda">
    <?php foreach ($tabs as $key => $label) : ?>
      <a class="tabs__item<?= $filter === $key ? ' tabs__item--active' : ''; ?>"
        href="<?= BASEURL; ?>/agenda?filter=<?= $key; ?><?= $q !== '' ? '&amp;q=' . urlencode($q) : ''; ?>"
        <?= $filter === $key ? 'aria-current="page"' : ''; ?>><?= e($label); ?></a>
    <?php endforeach; ?>
  </nav>

  <form class="toolbar" method="get" action="<?= BASEURL; ?>/agenda">
    <input type="hidden" name="filter" value="<?= e($filter); ?>">
    <div class="search">
      <?= icon('search'); ?>
      <input type="search" name="q" class="input" placeholder="Cari agenda atau item checklist..." value="<?= e($q); ?>" aria-label="Cari agenda">
    </div>
    <?php if ($q !== '') : ?>
      <a class="btn btn--ghost" href="<?= BASEURL; ?>/agenda?filter=<?= e($filter); ?>">Reset</a>
    <?php endif; ?>
  </form>

  <?php if (empty($data['agendas'])) : ?>
    <div class="empty">
      <?= icon('calendar'); ?>
      <?php if ($q !== '') : ?>
        <p>Tidak ada agenda yang cocok dengan <strong><?= e($q); ?></strong>.</p>
      <?php else : ?>
        <p>Tidak ada agenda pada kategori ini.</p>
      <?php endif; ?>
    </div>
  <?php else : ?>
    <div class="grid grid--wide">
      <?php foreach ($data['agendas'] as $a) :
        $items = $data['items'][$a['id']] ?? [];
        $total = (int) $a['total_items'];
        $done = (int) $a['done_items'];
        $persen = $total > 0 ? (int) round($done / $total * 100) : 0;
        $payload = [
          'id'            => (int) $a['id'],
          'judul'         => $a['judul'],
          'deskripsi'     => $a['deskripsi'],
          'tanggal'       => $a['tanggal'],
          'waktu_mulai'   => jam($a['waktu_mulai']),
          'waktu_selesai' => jam($a['waktu_selesai']),
        ]; ?>
        <article class="card card--agenda">
          <div class="card__row">
            <div>
              <span class="badge badge--agenda">
                <?php if (!$a['tanggal']) : ?>Tanpa tanggal
                <?php elseif ($a['tanggal'] === $data['today']) : ?>Hari Ini
                <?php else : ?><?= e(tanggal_indo($a['tanggal'])); ?><?php endif; ?>
              </span>
              <h3 class="card__title"><?= highlight($a['judul'], $q); ?></h3>
              <p class="meta"><?= icon('clock'); ?> <?= e(rentang_waktu($a)); ?></p>
            </div>
            <span class="card__actions">
              <button type="button" class="btn btn--ghost btn--icon btn--sm" data-agenda-edit="<?= e(json_encode($payload, JSON_UNESCAPED_UNICODE)); ?>" aria-label="Edit agenda" title="Edit"><?= icon('edit'); ?></button>
              <button type="button" class="btn btn--ghost btn--icon btn--sm btn--danger-hover" data-agenda-delete="<?= (int) $a['id']; ?>" aria-label="Hapus agenda" title="Hapus"><?= icon('trash'); ?></button>
            </span>
          </div>

          <?php if (!empty($a['deskripsi'])) : ?>
            <p class="note-box"><?= nl2br(highlight($a['deskripsi'], $q)); ?></p>
          <?php endif; ?>

          <div class="checklist">
            <div class="checklist__head">
              <span>Checklist (<?= $done; ?>/<?= $total; ?>)</span>
              <?php if ($total > 0) : ?><strong class="text-agenda"><?= $persen; ?>%</strong><?php endif; ?>
            </div>
            <?php if ($total > 0) : ?>
              <div class="progress progress--agenda" role="progressbar" aria-valuenow="<?= $persen; ?>" aria-valuemin="0" aria-valuemax="100">
                <div class="progress__bar" style="width: <?= $persen; ?>%"></div>
              </div>
            <?php endif; ?>

            <ul class="checklist__items">
              <?php foreach ($items as $item) : ?>
                <li class="check<?= $item['status'] ? ' check--done' : ''; ?>">
                  <label>
                    <input type="checkbox" class="checkbox" data-toggle-item="<?= (int) $item['id']; ?>" <?= $item['status'] ? 'checked' : ''; ?>>
                    <span><?= highlight($item['nama_item'], $q); ?></span>
                  </label>
                  <button type="button" class="btn btn--ghost btn--icon btn--xs btn--danger-hover" data-delete-item="<?= (int) $item['id']; ?>" aria-label="Hapus item" title="Hapus item"><?= icon('x'); ?></button>
                </li>
              <?php endforeach; ?>
              <?php if (!$items) : ?>
                <li class="muted small">Belum ada item.</li>
              <?php endif; ?>
            </ul>

            <form class="add-item" data-add-item="<?= (int) $a['id']; ?>">
              <input type="text" name="nama_item" class="input input--sm" placeholder="Tambahkan item..." maxlength="150" required aria-label="Nama item baru">
              <button type="submit" class="btn btn--ghost btn--sm">Tambah</button>
            </form>
          </div>
        </article>
      <?php endforeach; ?>
    </div>

    <?= pagination($data['pager'], '/agenda', ['filter' => $filter, 'q' => $q]); ?>
  <?php endif; ?>
</section>

<!-- Modal tambah/edit agenda -->
<div class="modal" id="agendaModal" hidden role="dialog" aria-modal="true" aria-labelledby="agendaModalTitle">
  <div class="modal__box">
    <div class="modal__head">
      <h2 id="agendaModalTitle">Tambah Agenda Baru</h2>
      <button type="button" class="btn btn--ghost btn--icon" data-modal-close aria-label="Tutup"><?= icon('x'); ?></button>
    </div>
    <form id="agendaForm" class="modal__body">
      <input type="hidden" name="id" id="agenda_id">

      <div class="field">
        <label for="agenda_judul">Judul Agenda</label>
        <input type="text" class="input" id="agenda_judul" name="judul" maxlength="100" required>
      </div>

      <div class="field">
        <label for="agenda_deskripsi">Deskripsi</label>
        <textarea class="textarea" id="agenda_deskripsi" name="deskripsi" rows="2"></textarea>
      </div>

      <div class="field">
        <label for="agenda_tanggal">Tanggal</label>
        <input type="date" class="input" id="agenda_tanggal" name="tanggal" required>
      </div>

      <div class="field-row">
        <div class="field">
          <label for="agenda_mulai">Waktu Mulai</label>
          <input type="time" class="input" id="agenda_mulai" name="waktu_mulai">
        </div>
        <div class="field">
          <label for="agenda_selesai">Waktu Selesai</label>
          <input type="time" class="input" id="agenda_selesai" name="waktu_selesai">
        </div>
      </div>

      <!-- Checklist awal (hanya saat tambah agenda; untuk agenda yang ada, ubah item lewat kartu) -->
      <div class="field" id="agendaItemsField">
        <label>Checklist Item (opsional)</label>
        <div id="agendaItemsBuilder" class="builder"></div>
        <button type="button" class="link link--btn" id="agendaItemAdd"><?= icon('plus'); ?> Tambah item checklist</button>
      </div>
      <p class="muted small" id="agendaEditItemsNote" hidden>Checklist agenda ini bisa ditambah, dicentang, atau dihapus langsung lewat kartu agenda setelah modal ini ditutup.</p>

      <div class="modal__foot">
        <button type="button" class="btn btn--ghost" data-modal-close>Batal</button>
        <button type="submit" class="btn btn--agenda">Simpan Agenda</button>
      </div>
    </form>
  </div>
</div>