<?php

class Controller
{
    // Controller yang boleh diakses tanpa login (selain ini, semua halaman wajib login)
    protected array $publicControllers = ['Auth'];

    // Sesi dianggap habis jika tidak ada aktivitas selama ini (detik)
    private const SESSION_IDLE_TIMEOUT = 1800; // 30 menit

    // Regenerasi ID session berkala agar cookie yang bocor/lama tidak selamanya valid
    private const SESSION_REGEN_INTERVAL = 900; // 15 menit

    // Fase 3: pemeriksaan session dijalankan otomatis untuk SETIAP controller
    public function __construct()
    {
        if (in_array(static::class, $this->publicControllers, true)) {
            return;
        }

        $this->expireIdleSession();

        if (empty($_SESSION['user_id'])) {
            $this->denyAccess();
        }

        $_SESSION['last_activity'] = time();
        $this->rotateSessionId();

        // Halaman pribadi tidak boleh disimpan cache, agar tombol Back setelah logout tidak menampilkannya
        header('Cache-Control: no-store, no-cache, must-revalidate');
        header('Pragma: no-cache');
    }

    // ---------------------------------------------------------------
    // Session
    // ---------------------------------------------------------------

    // Fase 8: sesi tidak aktif terlalu lama -> paksa logout otomatis
    private function expireIdleSession(): void
    {
        if (
            !empty($_SESSION['user_id'])
            && !empty($_SESSION['last_activity'])
            && (time() - (int) $_SESSION['last_activity']) > self::SESSION_IDLE_TIMEOUT
        ) {
            $_SESSION = [];
            session_destroy();
        }
    }

    // Fase 8: rotasi ID session berkala (mitigasi session hijacking jangka panjang)
    private function rotateSessionId(): void
    {
        if (empty($_SESSION['regenerated_at']) || (time() - (int) $_SESSION['regenerated_at']) > self::SESSION_REGEN_INTERVAL) {
            session_regenerate_id(true);
            $_SESSION['regenerated_at'] = time();
        }
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

    // ---------------------------------------------------------------
    // Tampilan
    // ---------------------------------------------------------------

    protected function redirect(string $path): void
    {
        header('Location: ' . BASEURL . $path);
        exit;
    }

    protected function view(string $view, array $data = []): void
    {
        require __DIR__ . '/../views/' . $view . '.php';
    }

    // Halaman lengkap: header + isi + footer.
    // $data['nav'] menentukan menu sidebar yang aktif (default: nama controller).
    protected function page(string $view, array $data = []): void
    {
        $data['nav'] = $data['nav'] ?? strtolower(static::class);

        $this->view('templates/header', $data);
        $this->view($view, $data);
        $this->view('templates/footer', $data);
    }

    protected function model(string $model)
    {
        return new $model; // dimuat otomatis oleh autoloader (init.php)
    }

    // ---------------------------------------------------------------
    // Respons JSON (endpoint AJAX)
    // ---------------------------------------------------------------

    // Kirim respons JSON lalu hentikan eksekusi
    protected function json(array $payload, int $status = 200): void
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($payload, JSON_UNESCAPED_UNICODE);
        exit;
    }

    protected function fail(string $message): void
    {
        $this->json(['status' => 'error', 'message' => $message]);
    }

    protected function success(string $message): void
    {
        $this->json(['status' => 'success', 'message' => $message]);
    }

    // Jawab sukses/gagal sesuai hasil operasi model
    protected function respond($ok, string $successMessage, string $failMessage): void
    {
        if ($ok) {
            $this->success($successMessage);
        }
        $this->fail($failMessage);
    }

    // Endpoint AJAX hanya menerima POST + wajib menyertakan token CSRF yang valid
    protected function requirePost(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->json(['status' => 'error', 'message' => 'Metode request tidak diizinkan.'], 405);
        }

        if (!csrf_verify($_POST['csrf_token'] ?? null)) {
            $this->json([
                'status'  => 'error',
                'message' => 'Sesi keamanan (CSRF) tidak valid atau kedaluwarsa. Muat ulang halaman lalu coba lagi.',
            ], 403);
        }
    }

    // ---------------------------------------------------------------
    // Input
    // ---------------------------------------------------------------

    // Ambil input POST sebagai string yang sudah di-trim
    protected function post(string $key): string
    {
        $value = $_POST[$key] ?? '';
        return is_string($value) ? trim($value) : '';
    }

    // ID wajib dari POST (bilangan bulat > 0). Jika tidak valid, langsung dijawab error.
    protected function requireId(string $label, string $key = 'id'): int
    {
        $id = (int) $this->post($key);

        if ($id <= 0) {
            $this->fail('ID ' . $label . ' tidak ditemukan!');
        }
        return $id;
    }

    protected function queryParam(string $key): string
    {
        $v = $_GET[$key] ?? '';
        return is_string($v) ? trim($v) : '';
    }

    // Nomor halaman dari ?page= (minimal 1)
    protected function queryPage(): int
    {
        return max(1, (int) $this->queryParam('page'));
    }

    // Parameter GET yang hanya boleh bernilai salah satu dari $allowed (selain itu -> '')
    protected function queryChoice(string $key, array $allowed): string
    {
        $v = $this->queryParam($key);
        return in_array($v, $allowed, true) ? $v : '';
    }
}
