<div class="hero-kontak d-flex align-items-center">
    <div class="container-fluid">
        <div class="row w-100 flex-column-reverse flex-md-row">

            <!-- Form Kontak -->
            <div class="col-md-6 d-flex flex-column justify-content-center p-5">

                <h1 class="mb-3">Hubungi Kami</h1>

                <p class="lead">
                    Jika ada pertanyaan, saran, atau kendala silakan hubungi kami melalui form di bawah ini.
                </p>

                <form action="<?= BASEURL; ?>/kontak/kirim" method="POST" class="mt-4">

                    <div class="mb-3">
                        <label class="form-label">Nama</label>
                        <input type="text" name="nama" class="form-control" placeholder="Masukkan nama">
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Email</label>
                        <input type="email" name="email" class="form-control" placeholder="Masukkan email">
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Pesan</label>
                        <textarea 
                            name="pesan" 
                            rows="5" 
                            class="form-control"
                            placeholder="Tulis pesan..."></textarea>
                    </div>

                    <button type="submit" class="btn btn-primary w-100">
                        Kirim Pesan
                    </button>

                </form>

            </div>

            <!-- Gambar -->
            <div class="col-md-6 d-none d-md-flex justify-content-center align-items-center">
                <img src="<?= BASEURL; ?>/img/kontak.svg"
                    alt="Kontak"
                    class="img-fluid"
                    style="max-height: 80%; max-width: 80%; object-fit: contain;">
            </div>
        </div>
    </div>
</div>