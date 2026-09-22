<?php

class Auth extends Controller
{
  // Hash palsu (valid) agar waktu proses sama baik username ada maupun tidak
  private const DUMMY_HASH = '$2y$10$.vGA1O9wmRjrwAVXD98HNOgsNpDczlqm3Jq7KnEd1rVAGv3Fykk1a';

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
      $username = $this->post('username');
      $password = is_string($_POST['password'] ?? null) ? $_POST['password'] : '';

      if ($username === '' || $password === '') {
        $error = 'Username dan password wajib diisi.';
      } else {
        $model = $this->model('M_User');
        $user = $model->getByUsername($username);

        $valid = password_verify($password, $user ? $user['password'] : self::DUMMY_HASH);

        if ($user && $valid) {
          // Cegah session fixation: ganti ID session setelah login berhasil
          session_regenerate_id(true);
          $_SESSION['user_id'] = (int) $user['id'];
          $_SESSION['user'] = $user['nama'];

          if (password_needs_rehash($user['password'], PASSWORD_DEFAULT)) {
            $model->updatePassword((int) $user['id'], password_hash($password, PASSWORD_DEFAULT));
          }

          $this->redirect('/dashboard');
        }

        // Pesan sengaja sama agar tidak membocorkan username yang terdaftar
        $error = 'Username atau password salah.';
      }
    }

    $data['judul'] = 'Login';
    $data['error'] = $error;
    $data['username'] = $username;
    $this->view('auth/login', $data);
  }

  // Logout hanya lewat POST (tombol di sidebar)
  public function logout()
  {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
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
}
