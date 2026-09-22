<?php

class App
{
    protected $controller = 'Dashboard';
    protected $method = 'index';
    protected $params = [];

    public function __construct()
    {
        $url = $this->parseURL();

        // controller (nama file diawali huruf kapital agar aman di server Linux)
        if (!empty($url[0])) {
            $name = ucfirst(strtolower($url[0]));

            if (!preg_match('/^[A-Za-z_]+$/', $name) || !file_exists(__DIR__ . '/../controllers/' . $name . '.php')) {
                $this->notFound('Halaman tidak ditemukan.');
            }

            $this->controller = $name;
            unset($url[0]);
        }

        require_once __DIR__ . '/../controllers/' . $this->controller . '.php';
        $this->controller = new $this->controller;

        // method (hanya method public yang boleh diakses lewat URL)
        if (isset($url[1])) {
            if (preg_match('/^[A-Za-z_]+$/', $url[1]) && is_callable([$this->controller, $url[1]])) {
                $this->method = $url[1];
                unset($url[1]);
            } else {
                $this->notFound('Halaman tidak ditemukan.');
            }
        }

        // params
        $this->params = !empty($url) ? array_values($url) : [];

        // jalankan controller & method, serta kirimkan params jika ada
        call_user_func_array([$this->controller, $this->method], $this->params);
    }

    public function parseURL()
    {
        if (isset($_GET['url'])) {
            $url = rtrim($_GET['url'], '/');
            $url = filter_var($url, FILTER_SANITIZE_URL);
            return explode('/', $url);
        }
        return [];
    }

    private function notFound(string $message): void
    {
        // Guest tidak boleh melihat tampilan aplikasi (sidebar, menu) meski URL-nya salah
        if (empty($_SESSION['user_id'])) {
            header('Location: ' . BASEURL . '/auth/login');
            exit;
        }

        http_response_code(404);
        $data = ['judul' => '404', 'pesan' => $message];
        require __DIR__ . '/../views/templates/header.php';
        require __DIR__ . '/../views/templates/404.php';
        require __DIR__ . '/../views/templates/footer.php';
        exit;
    }
}
