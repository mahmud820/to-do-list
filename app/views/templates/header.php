<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta http-equiv="X-UA-Compatible" content="ie=edge">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= isset($data['judul']) ? $data['judul'] : 'To Do List'; ?></title>
  
  <!-- Bootstrap CSS -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

  <!-- Bootstrap Icons -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css">
  <!-- SweetAlert2 CSS -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">

  <!-- Custom CSS -->
  <link rel="stylesheet" href="/css/tasks.css">
  <link rel="stylesheet" href="/css/agenda.css?v=<?= time(); ?>">
  <link rel="stylesheet" href="/css/kontak.css">
  <link rel="stylesheet" href="/css/about.css">
</head>
<body class="d-flex flex-column min-vh-100">

<!-- Navbar -->
<nav class="navbar navbar-expand-lg navbar-dark bg-primary">
  <div class="container-fluid">
    <a class="navbar-brand" href="<?= BASEURL; ?>">To Do List</a>

    <!-- Toggle untuk mobile -->
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarContent">
      <span class="navbar-toggler-icon"></span>
    </button>

    <!-- Semua menu pindah ke kanan -->
    <div class="collapse navbar-collapse justify-content-end" id="navbarContent">
      <ul class="navbar-nav">
        <!-- Menu Navigasi -->
         <li class="nav-item">
          <a class="nav-link" href="<?= BASEURL; ?>/dashboard">Dashboard</a>
        </li>
        <li class="nav-item">
          <a class="nav-link" href="<?= BASEURL; ?>/tasks">Tasks</a>
        </li>
        <li class="nav-item">
          <a class="nav-link" href="<?= BASEURL; ?>/agenda">Agenda</a>
        </li>
        <li class="nav-item">
          <a class="nav-link" href="<?= BASEURL; ?>/kontak">Kontak</a>
        </li>
        <li class="nav-item">
          <a class="nav-link" href="<?= BASEURL; ?>/about">About</a>
        </li>

        <!-- Profile Dropdown -->
        <li class="nav-item dropdown">
          <a class="nav-link dropdown-toggle d-flex align-items-center" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
            <img src="<?= BASEURL; ?>/img/profile.jpg" alt="Profile" class="rounded-circle me-2" width="32" height="32">
            <span><?= $_SESSION['user'] ?? 'User'; ?></span>
          </a>
          <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="navbarDropdown">
            <li><a class="dropdown-item" href="<?= BASEURL; ?>/profile">Lihat Profil</a></li>
            <li><hr class="dropdown-divider"></li>
            <li><a class="dropdown-item text-danger" href="<?= BASEURL; ?>/logout">Logout</a></li>
          </ul>
        </li>
      </ul>
    </div>
  </div>
</nav>
