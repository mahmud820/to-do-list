<?php
$judul = $data['judul'] ?? 'To Do List';
$navAktif = strtolower($judul);
$namaUser = $_SESSION['user'] ?? '';

$menu = [
  'dashboard' => ['Dashboard', 'dashboard'],
  'tasks'     => ['Tasks (Tugas)', 'tasks'],
  'agenda'    => ['Agenda & Jadwal', 'calendar'],
  'notes'     => ['Notes (Catatan)', 'note'],
];
?>
<!DOCTYPE html>
<html lang="id">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= e($judul); ?> - Personal Task Hub</title>

  <!-- Terapkan tema sebelum halaman digambar agar tidak berkedip -->
  <script>
    (function() {
      try {
        var t = localStorage.getItem('th_theme');
        if (!t && window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches) t = 'dark';
        if (t === 'dark') document.documentElement.setAttribute('data-theme', 'dark');
      } catch (e) {}
    })();
  </script>

  <link rel="stylesheet" href="<?= asset('css/style.css'); ?>">
</head>

<body>

  <div class="app">
    <div class="scrim" data-sidebar-close></div>

    <!-- Sidebar -->
    <aside class="sidebar" id="sidebar" aria-label="Navigasi utama">
      <div class="sidebar__top">
        <a class="brand" href="<?= BASEURL; ?>/dashboard">
          <span class="brand__logo"><?= icon('layers'); ?></span>
          <span>
            <strong class="brand__name">TaskHub</strong>
            <small class="brand__sub">Personal Workspace</small>
          </span>
        </a>
        <button type="button" class="btn btn--ghost btn--icon sidebar__close" data-sidebar-close aria-label="Tutup menu">
          <?= icon('x'); ?>
        </button>
      </div>

      <nav class="nav">
        <?php foreach ($menu as $slug => [$label, $ikon]) : ?>
          <a class="nav__link<?= $navAktif === $slug ? ' nav__link--active' : ''; ?>"
            href="<?= BASEURL; ?>/<?= $slug; ?>"
            <?= $navAktif === $slug ? 'aria-current="page"' : ''; ?>>
            <?= icon($ikon); ?>
            <span><?= e($label); ?></span>
          </a>
        <?php endforeach; ?>
      </nav>

      <div class="sidebar__footer">
        <div class="user">
          <span class="user__avatar"><?= e(mb_strtoupper(mb_substr($namaUser, 0, 2))); ?></span>
          <span class="user__meta">
            <strong><?= e($namaUser); ?></strong>
            <small>Single User</small>
          </span>
        </div>
        <div class="sidebar__actions">
          <button type="button" class="btn btn--ghost btn--icon js-theme" aria-label="Ganti tema terang/gelap" title="Ganti tema">
            <?= icon('moon', 'icon--moon'); ?><?= icon('sun', 'icon--sun'); ?>
          </button>
          <form method="post" action="<?= BASEURL; ?>/auth/logout">
            <button type="submit" class="btn btn--ghost btn--icon btn--danger-hover" aria-label="Keluar" title="Keluar">
              <?= icon('logout'); ?>
            </button>
          </form>
        </div>
      </div>
    </aside>

    <div class="content">
      <!-- Topbar (tampil di layar kecil) -->
      <header class="topbar">
        <button type="button" class="btn btn--ghost btn--icon" id="sidebarOpen" aria-label="Buka menu" aria-controls="sidebar">
          <?= icon('menu'); ?>
        </button>
        <strong class="topbar__title"><?= e($judul); ?></strong>
        <button type="button" class="btn btn--ghost btn--icon js-theme" aria-label="Ganti tema terang/gelap">
          <?= icon('moon', 'icon--moon'); ?><?= icon('sun', 'icon--sun'); ?>
        </button>
      </header>

      <main class="main">