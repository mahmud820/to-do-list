<?php

class Auth extends Controller
{
    // Hash palsu (valid) agar waktu proses sama baik username ada maupun tidak
    private const DUMMY_HASH = '$2y$10$.vGA1O9wmRjrwAVXD98HNOgsNpDczlqm3Jq7KnEd1rVAGv3Fykk1a';

    // Rate limiting percobaan login: maksimal 5 kali gagal / 15 menit, lalu dikunci 5 menit
    private const LOGIN_MAX_ATTEMPTS = 5;
    private const LOGIN_WINDOW = 900;
    private const LOGIN_LOCK = 300;

    public function index()
    {
        $this->redirect('/auth/login');
    }

    public function login()
    {
        // Sudah login: tidak perlu melihat form login lagi
        if (!empty($_SESSION['user_id'])) {
            $this->redirect('/dashboard');
        }

        $error = '';
        $username = '';

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            [$error, $username] = $this->attemptLogin();
        }

        $this->view('auth/login', [
            'judul'    => 'Login',
            'error'    => $error,
            'username' => $username,
        ]);
    }

    // Logout hanya lewat POST (tombol di sidebar) + wajib token CSRF valid
    public function logout()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !csrf_verify($_POST['csrf_token'] ?? null)) {
            $this->redirect('/dashboard');
        }

        $_SESSION = [];

        if (ini_get('session.use_cookies')) {
            $p = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
        }

        session_destroy();
        $this->redirect('/auth/login');
    }

    // Proses form login. Jika berhasil langsung redirect ke dashboard;
    // jika gagal mengembalikan [pesanError, usernameYangDiketik]
    private function attemptLogin(): array
    {
        $limiter = new RateLimiter();
        $limiterKey = 'login:' . $this->clientIp();

        $wait = $limiter->blockedFor($limiterKey);
        if ($wait > 0) {
            return ['Terlalu banyak percobaan login gagal. Coba lagi dalam ' . (int) ceil($wait / 60) . ' menit.', ''];
        }

        if (!csrf_verify($_POST['csrf_token'] ?? null)) {
            return ['Sesi form sudah kedaluwarsa. Muat ulang halaman lalu coba lagi.', ''];
        }

        $username = $this->post('username');
        $password = is_string($_POST['password'] ?? null) ? $_POST['password'] : '';

        if ($username === '' || $password === '') {
            return ['Username dan password wajib diisi.', $username];
        }

        $model = $this->model('M_User');
        $user = $model->getByUsername($username);

        // password_verify tetap dijalankan walau user tidak ada (waktu proses sama)
        $valid = password_verify($password, $user ? $user['password'] : self::DUMMY_HASH);

        if ($user && $valid) {
            $limiter->reset($limiterKey);
            $this->startSession($user);

            if (password_needs_rehash($user['password'], PASSWORD_DEFAULT)) {
                $model->updatePassword((int) $user['id'], password_hash($password, PASSWORD_DEFAULT));
            }

            $this->redirect('/dashboard');
        }

        $limiter->hit($limiterKey, self::LOGIN_MAX_ATTEMPTS, self::LOGIN_WINDOW, self::LOGIN_LOCK);

        // Pesan sengaja sama agar tidak membocorkan username yang terdaftar
        return ['Username atau password salah.', $username];
    }

    private function startSession(array $user): void
    {
        // Cegah session fixation: ganti ID session setelah login berhasil
        session_regenerate_id(true);

        $_SESSION['user_id'] = (int) $user['id'];
        $_SESSION['user'] = $user['nama'];
        $_SESSION['last_activity'] = time();
        $_SESSION['regenerated_at'] = time();
        unset($_SESSION['csrf_token']); // token baru dipakai setelah login
    }

    // Ambil IP client untuk key rate limiter
    private function clientIp(): string
    {
        return $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    }
}
