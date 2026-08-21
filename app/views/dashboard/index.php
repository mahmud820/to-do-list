<div class="hero-todolist d-flex align-items-center">
  <div class="container-fluid">
    <div class="row w-100 flex-column flex-md-row">

      <!-- Konten Kiri -->
      <div class="col-md-6 d-flex flex-column justify-content-center content p-5">
        <h1 class="mb-1">Selamat Datang Mahmud</h1>
        <p class="lead">Kelola dan pantau kegiatan harianmu dengan lebih mudah!</p>

        <div class="row g-3 mt-4">
          <div class="col-6 col-md-6">
            <a href="<?= BASEURL; ?>/tasks" class="btn btn-outline-primary w-100">Tasks</a>
          </div>
          <div class="col-6 col-md-6">
            <a href="<?= BASEURL; ?>/agenda" class="btn btn-outline-primary w-100">Agenda</a>
          </div>
          <div class="col-6 col-md-6">
            <a href="<?= BASEURL; ?>/about" class="btn btn-outline-primary w-100">About</a>
          </div>
          <div class="col-6 col-md-6">
            <a href="<?= BASEURL; ?>/kontak" class="btn btn-outline-primary w-100">Kontak</a>
          </div>
        </div>
      </div>

      <!-- Gambar Kanan -->
      <div class="col-md-6 d-none d-md-flex justify-content-center align-items-center content">
        <img src="<?= BASEURL; ?>/img/todolist-banner.svg"
          alt="To-Do List"
          class="img-fluid"
          style="max-height: 80%; max-width: 80%; object-fit: contain;">
      </div>


    </div>
  </div>
</div>