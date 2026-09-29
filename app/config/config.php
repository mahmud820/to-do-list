<?php

define('BASEURL', '');

// DB / koneksi ke database
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'daftarkegiatan');

// Task dianggap "mendekati deadline" jika deadline-nya dalam N hari ke depan
define('DEADLINE_SOON_DAYS', 3);

// Pagination: jumlah data per halaman
define('PER_PAGE_TASKS', 12);
define('PER_PAGE_AGENDA', 10);
define('PER_PAGE_NOTES', 12);
