<?php

// ---------------------------------------------------------------
// Helper global untuk view. Dimuat sekali dari init.php
// ---------------------------------------------------------------

// Escape output HTML
function e($value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

// Escape karakter khusus LIKE (% dan _) agar dicari sebagai teks biasa. Pakai bersama: LIKE ... ESCAPE '!'
function like_escape(string $s): string
{
    return str_replace(['!', '%', '_'], ['!!', '!%', '!_'], $s);
}

// Escape lalu beri <mark> pada kata yang dicari
// Escape lalu beri <mark> pada kata yang dicari (pencocokan dilakukan pada teks mentah)
function highlight($text, $q): string
{
    $text = (string) $text;
    $q = trim((string) $q);
    if ($q === '') {
        return e($text);
    }

    $parts = preg_split('/(' . preg_quote($q, '/') . ')/iu', $text, -1, PREG_SPLIT_DELIM_CAPTURE);
    if ($parts === false) {
        return e($text);
    }

    $out = '';
    foreach ($parts as $i => $part) {
        // indeks ganjil = potongan yang cocok dengan kata pencarian
        $out .= ($i % 2 === 1) ? '<mark>' . e($part) . '</mark>' : e($part);
    }
    return $out;
}

// URL file statis + versi (cache busting berdasarkan waktu ubah file)
function asset(string $path): string
{
    $file = __DIR__ . '/../../public/' . $path;
    $ver = file_exists($file) ? filemtime($file) : 1;
    return BASEURL . '/' . $path . '?v=' . $ver;
}

function bulan_indo(int $n): string
{
    static $bulan = [1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
    return $bulan[$n] ?? '';
}

// 2026-06-21 -> "21 Jun 2026" (atau "21 Juni 2026" jika $panjang)
function tanggal_indo(?string $date, bool $panjang = false): string
{
    if (!$date) {
        return '';
    }
    $ts = strtotime($date);
    if ($ts === false) {
        return '';
    }
    $nama = bulan_indo((int) date('n', $ts));
    if (!$panjang) {
        $nama = mb_substr($nama, 0, 3);
    }
    return date('j', $ts) . ' ' . $nama . ' ' . date('Y', $ts);
}

// "Sabtu, 19 September 2026"
function tanggal_lengkap(?string $date = null): string
{
    static $hari = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
    $ts = $date ? strtotime($date) : time();
    return $hari[(int) date('w', $ts)] . ', ' . tanggal_indo(date('Y-m-d', $ts), true);
}

// "09:00:00" -> "09:00"
function jam(?string $time): string
{
    return $time ? substr($time, 0, 5) : '';
}

// Rentang waktu agenda: "09:00 - 10:00 WIB", "09:00 WIB", atau "Waktu belum diatur"
function rentang_waktu(array $agenda): string
{
    $mulai = jam($agenda['waktu_mulai'] ?? null);
    $selesai = jam($agenda['waktu_selesai'] ?? null);

    if ($mulai && $selesai) {
        return "$mulai - $selesai WIB";
    }
    if ($mulai) {
        return "$mulai WIB";
    }
    return 'Waktu belum diatur';
}

// Label & kelas untuk status / prioritas task (nilai mengikuti enum di database)
function status_label(string $status): string
{
    return [
        'belum selesai'     => 'Belum Dikerjakan',
        'sedang dikerjakan' => 'Sedang Dikerjakan',
        'selesai'           => 'Selesai',
    ][$status] ?? ucfirst($status);
}

function status_class(string $status): string
{
    return [
        'belum selesai'     => 'badge--muted',
        'sedang dikerjakan' => 'badge--brand',
        'selesai'           => 'badge--ok',
    ][$status] ?? 'badge--muted';
}

function prioritas_class(string $prioritas): string
{
    return [
        'tinggi' => 'badge--danger',
        'sedang' => 'badge--warn',
        'rendah' => 'badge--muted',
    ][$prioritas] ?? 'badge--muted';
}

// Info deadline: ['label' => ..., 'state' => overdue|today|soon|normal|done]
function deadline_info(string $deadline, ?string $status, string $today): array
{
    if ($status === 'selesai') {
        return ['label' => tanggal_indo($deadline), 'state' => 'done'];
    }

    $selisih = (int) (new DateTime($today))->diff(new DateTime($deadline))->format('%r%a');

    if ($selisih < 0) {
        return ['label' => 'Terlambat ' . abs($selisih) . ' hari', 'state' => 'overdue'];
    }
    if ($selisih === 0) {
        return ['label' => 'Hari ini', 'state' => 'today'];
    }
    if ($selisih <= DEADLINE_SOON_DAYS) {
        return ['label' => $selisih . ' hari lagi', 'state' => 'soon'];
    }
    return ['label' => tanggal_indo($deadline), 'state' => 'normal'];
}

// Ikon SVG inline (gaya garis, tanpa library eksternal)
function icon(string $name, string $class = ''): string
{
    static $paths = [
        'dashboard'   => '<rect x="3" y="3" width="7" height="9" rx="1"/><rect x="14" y="3" width="7" height="5" rx="1"/><rect x="14" y="12" width="7" height="9" rx="1"/><rect x="3" y="16" width="7" height="5" rx="1"/>',
        'tasks'       => '<path d="m9 11 3 3L22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/>',
        'calendar'    => '<rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/>',
        'note'        => '<path d="M15.5 3H5a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V8.5L15.5 3Z"/><path d="M15 3v6h6"/>',
        'plus'        => '<path d="M5 12h14M12 5v14"/>',
        'edit'        => '<path d="M17 3a2.85 2.83 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5Z"/>',
        'trash'       => '<path d="M3 6h18"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6"/><path d="M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/>',
        'search'      => '<circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/>',
        'clock'       => '<circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/>',
        'x'           => '<path d="M18 6 6 18M6 6l12 12"/>',
        'menu'        => '<path d="M4 6h16M4 12h16M4 18h16"/>',
        'sun'         => '<circle cx="12" cy="12" r="4"/><path d="M12 2v2M12 20v2M4.93 4.93l1.41 1.41M17.66 17.66l1.41 1.41M2 12h2M20 12h2M6.34 17.66l-1.41 1.41M19.07 4.93l-1.41 1.41"/>',
        'moon'        => '<path d="M12 3a6 6 0 0 0 9 9 9 9 0 1 1-9-9Z"/>',
        'alert'       => '<circle cx="12" cy="12" r="10"/><path d="M12 8v4M12 16h.01"/>',
        'user'        => '<path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/>',
        'lock'        => '<rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/>',
        'logout'      => '<path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><path d="m16 17 5-5-5-5"/><path d="M21 12H9"/>',
        'layers'      => '<path d="m12 2 10 5-10 5L2 7l10-5Z"/><path d="m2 17 10 5 10-5"/><path d="m2 12 10 5 10-5"/>',
    ];

    $inner = $paths[$name] ?? '';
    $cls = trim('icon ' . $class);

    return '<svg class="' . e($cls) . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' . $inner . '</svg>';
}
