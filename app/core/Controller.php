<?php

class Controller
{
    // Controller yang boleh diakses tanpa login (selain ini, semua halaman wajib login)
    protected array $publicControllers = ['Auth'];

    // Fase 3: pemeriksaan session dijalankan otomatis untuk SETIAP controller
    public function __construct()
    {
        if (in_array(static::class, $this->publicControllers, true)) {
            return;
        }

        if (empty($_SESSION['user_id'])) {
            $this->denyAccess();
        }

        // Halaman pribadi tidak boleh disimpan cache, agar tombol Back setelah logout tidak menampilkannya
        header('Cache-Control: no-store, no-cache, must-revalidate');
        header('Pragma: no-cache');
    }

    // Guest: request AJAX dijawab JSON 401, halaman biasa dialihkan ke login
    private function denyAccess(): void
    {
        $accept = $_SERVER['HTTP_ACCEPT'] ?? '';
        $isAjax = $_SERVER['REQUEST_METHOD'] === 'POST' || strpos($accept, 'application/json') !== false;

        if ($isAjax) {
            $this->json([
                'status'   => 'error',
                'message'  => 'Sesi berakhir. Silakan login kembali.',
                'redirect' => BASEURL . '/auth/login',
            ], 401);
        }

        $this->redirect('/auth/login');
    }

    protected function redirect(string $path): void
    {
        header('Location: ' . BASEURL . $path);
        exit;
    }

    protected function view($view, $data = [])
    {
        require __DIR__ . '/../views/' . $view . '.php';
    }

    protected function model($model)
    {
        require_once __DIR__ . '/../models/' . $model . '.php';
        return new $model;
    }

    // Kirim respons JSON lalu hentikan eksekusi
    protected function json(array $payload, int $status = 200): void
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($payload, JSON_UNESCAPED_UNICODE);
        exit;
    }

    // Endpoint AJAX hanya menerima POST
    protected function requirePost(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->json(['status' => 'error', 'message' => 'Metode request tidak diizinkan.'], 405);
        }
    }

    // Ambil input POST sebagai string yang sudah di-trim
    protected function post(string $key): string
    {
        $value = $_POST[$key] ?? '';
        return is_string($value) ? trim($value) : '';
    }
}
