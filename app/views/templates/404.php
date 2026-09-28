<section class="page">
  <div class="empty empty--page">
    <?= icon('alert'); ?>
    <h2>404</h2>
    <p><?= e($data['pesan'] ?? 'Halaman tidak ditemukan.'); ?></p>
    <a class="btn btn--primary" href="<?= BASEURL; ?>/dashboard">Kembali ke Dashboard</a>
  </div>
</section>
