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

require_once '../app/init.php';

$app = new App;
