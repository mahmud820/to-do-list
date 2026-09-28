<?php

// Pengaturan session yang lebih aman (harus sebelum session_start)
ini_set('session.use_strict_mode', '1');
ini_set('session.use_only_cookies', '1');

session_set_cookie_params([
  'lifetime' => 0,          // hilang saat browser ditutup
  'path'     => '/',
  'httponly' => true,       // tidak bisa dibaca JavaScript
  'samesite' => 'Lax',      // cookie tidak ikut dikirim pada POST dari situs lain
  'secure'   => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off', // otomatis aktif saat HTTPS
]);

if (session_status() !== PHP_SESSION_ACTIVE) {
  session_start();
}

// Fase 8: header keamanan (berlaku untuk seluruh response, termasuk endpoint JSON)
$isHttps = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
define('CSP_NONCE', bin2hex(random_bytes(16)));

header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('Referrer-Policy: strict-origin-when-cross-origin');
header('Permissions-Policy: geolocation=(), microphone=(), camera=()');
header(
  "Content-Security-Policy: default-src 'self'; " .
  "script-src 'self' 'nonce-" . CSP_NONCE . "'; " .
  "style-src 'self' 'unsafe-inline'; " . // style="width:..%" dipakai untuk progress bar
  "img-src 'self' data:; " .
  "font-src 'self'; " .
  "object-src 'none'; base-uri 'self'; form-action 'self'; frame-ancestors 'none'"
);

if ($isHttps) {
  // Hanya kirim jika sudah HTTPS, agar tidak memaksa HTTPS saat masih di localhost/dev HTTP
  header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
}

require_once __DIR__ . '/../app/init.php';

$app = new App;
