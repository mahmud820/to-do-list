<?php $q = $data['q']; ?>
<section class="page">
  <div class="page__head">
    <div>
      <h1 class="page__title">Catatan Pribadi</h1>
      <p class="page__sub">Apa yang ingin saya ingat? Notepad bebas, tidak terikat tugas atau jadwal.</p>
    </div>
    <div class="page__actions">
      <button type="button" class="btn btn--note" data-note-new>
        <?= icon('plus'); ?> <span>Buat Catatan</span>
      </button>
    </div>
  </div>

  <form class="toolbar" method="get" action="<?= BASEURL; ?>/notes">
    <div class="search">
      <?= icon('search'); ?>
      <input type="search" name="q" class="input" placeholder="Cari dalam catatan..." value="<?= e($q); ?>" aria-label="Cari catatan">
    </div>
    <?php if ($q !== '') : ?>
      <a class="btn btn--ghost" href="<?= BASEURL; ?>/notes">Reset</a>
    <?php endif; ?>
  </form>

  <?php if (empty($data['notes'])) : ?>
    <div class="empty">
      <?= icon('note'); ?>
      <?php if ($q !== '') : ?>
        <p>Tidak ada catatan yang cocok dengan <strong><?= e($q); ?></strong>.</p>
      <?php else : ?>
        <p>Belum ada catatan. Klik <strong>Buat Catatan</strong> untuk mulai.</p>
      <?php endif; ?>
    </div>
  <?php else : ?>
    <div class="grid grid--cards">
      <?php foreach ($data['notes'] as $n) :
        $payload = ['id' => (int) $n['id'], 'judul' => $n['judul'], 'isi' => $n['isi']]; ?>
        <article class="card card--note">
          <h3 class="card__title"><?= highlight($n['judul'], $q); ?></h3>
          <p class="card__text card__text--clamp"><?= $n['isi'] ? nl2br(highlight($n['isi'], $q)) : '<em class="muted">Catatan kosong.</em>'; ?></p>
          <div class="card__foot">
            <span class="muted small">Diperbarui <?= e(tanggal_indo($n['updated_at'])); ?></span>
            <span class="card__actions">
              <button type="button" class="btn btn--ghost btn--icon btn--sm" data-note-edit="<?= e(json_encode($payload, JSON_UNESCAPED_UNICODE)); ?>" aria-label="Edit catatan" title="Edit"><?= icon('edit'); ?></button>
              <button type="button" class="btn btn--ghost btn--icon btn--sm btn--danger-hover" data-note-delete="<?= (int) $n['id']; ?>" aria-label="Hapus catatan" title="Hapus"><?= icon('trash'); ?></button>
            </span>
          </div>
        </article>
      <?php endforeach; ?>
    </div>

    <?= pagination($data['pager'], '/notes', ['q' => $q]); ?>
  <?php endif; ?>
</section>

<!-- Modal tambah/edit catatan -->
<div class="modal" id="noteModal" hidden role="dialog" aria-modal="true" aria-labelledby="noteModalTitle">
  <div class="modal__box">
    <div class="modal__head">
      <h2 id="noteModalTitle">Catatan Baru</h2>
      <button type="button" class="btn btn--ghost btn--icon" data-modal-close aria-label="Tutup"><?= icon('x'); ?></button>
    </div>
    <form id="noteForm" class="modal__body">
      <input type="hidden" name="id" id="note_id">

      <div class="field">
        <label for="note_judul">Judul Catatan</label>
        <input type="text" class="input" id="note_judul" name="judul" maxlength="150" required>
      </div>

      <div class="field">
        <label for="note_isi">Isi Catatan</label>
        <textarea class="textarea" id="note_isi" name="isi" rows="8"></textarea>
      </div>

      <div class="modal__foot">
        <button type="button" class="btn btn--ghost" data-modal-close>Batal</button>
        <button type="submit" class="btn btn--note">Simpan Catatan</button>
      </div>
    </form>
  </div>
</div>
