<?php

/**
 * Rate limiter sederhana berbasis file JSON (tanpa dependency luar / tabel DB baru).
 * Dipakai untuk membatasi percobaan login yang gagal (brute force protection).
 *
 * Catatan: cocok untuk aplikasi single-user/single-server seperti ini. Jika suatu
 * saat di-deploy di banyak server sekaligus (load balancer), ganti storage ini
 * dengan sesuatu yang dibagi bersama (mis. Redis/APCu shared, atau tabel DB).
 */
class RateLimiter
{
    private string $dir;

    public function __construct()
    {
        $this->dir = __DIR__ . '/../storage/rate_limit';
        if (!is_dir($this->dir)) {
            @mkdir($this->dir, 0755, true);
        }
    }

    // Sisa detik pemblokiran untuk $key. 0 berarti tidak/tidak lagi diblokir.
    public function blockedFor(string $key): int
    {
        $data = $this->read($key);
        if ($data && $data['blocked_until'] > time()) {
            return $data['blocked_until'] - time();
        }
        return 0;
    }

    // Catat satu percobaan gagal. Jika mencapai $maxAttempts dalam $windowSeconds,
    // key diblokir selama $lockSeconds.
    public function hit(string $key, int $maxAttempts, int $windowSeconds, int $lockSeconds): void
    {
        $data = $this->read($key) ?: ['count' => 0, 'first' => time(), 'blocked_until' => 0];

        // Window waktu sudah lewat -> mulai hitung dari awal
        if (time() - $data['first'] > $windowSeconds) {
            $data = ['count' => 0, 'first' => time(), 'blocked_until' => 0];
        }

        // Masa kunci sudah selesai -> anggap ini percobaan pertama lagi,
        // supaya 1x salah tidak langsung mengunci ulang (bug B4)
        if ($data['blocked_until'] > 0 && $data['blocked_until'] <= time()) {
            $data = ['count' => 0, 'first' => time(), 'blocked_until' => 0];
        }

        $data['count']++;

        if ($data['count'] >= $maxAttempts) {
            $data['blocked_until'] = time() + $lockSeconds;
        }

        $this->write($key, $data);
    }

    // Hapus riwayat percobaan (dipanggil setelah login berhasil)
    public function reset(string $key): void
    {
        $file = $this->file($key);
        if (file_exists($file)) {
            @unlink($file);
        }
    }

    private function file(string $key): string
    {
        return $this->dir . '/' . hash('sha256', $key) . '.json';
    }

    private function read(string $key): ?array
    {
        $file = $this->file($key);
        if (!file_exists($file)) {
            return null;
        }
        $raw = @file_get_contents($file);
        $data = $raw ? json_decode($raw, true) : null;
        if (!is_array($data)) {
            return null;
        }

        // Lengkapi kunci yang hilang (file rusak/format lama) agar tidak memicu "undefined index"
        return $data + ['count' => 0, 'first' => time(), 'blocked_until' => 0];
    }

    private function write(string $key, array $data): void
    {
        @file_put_contents($this->file($key), json_encode($data), LOCK_EX);
    }
}
