<?php

class Tasks extends Controller
{
    public function index()
    {
        $data['judul'] = 'Tasks';
        $q = trim($_GET['q'] ?? '');
        $data['q'] = $q;
        $data['tasks'] = $this->model('M_Tasks')->getAllTasks($q);
        $this->view('templates/header', $data);
        $this->view('tasks/index', $data);
        $this->view('templates/footer');
    }

    // Tambah task baru via AJAX
    public function add()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            header('Content-Type: application/json; charset=utf-8');

            $judul = trim($_POST['judul'] ?? '');
            $deskripsi = trim($_POST['deskripsi'] ?? '');
            $deadline = trim($_POST['deadline'] ?? '');

            if ($judul === '' || $deskripsi === '' || $deadline === '') {
                echo json_encode(['status' => 'error', 'message' => 'Semua field wajib diisi!']);
                exit;
            }

            $data = ['judul' => $judul, 'deskripsi' => $deskripsi, 'deadline' => $deadline];

            if ($this->model('M_Tasks')->addTask($data)) {
                echo json_encode(['status' => 'success', 'message' => 'Task berhasil ditambahkan!']);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Gagal menambahkan task.']);
            }
            exit;
        }
    }

    // Update task via AJAX
    public function update()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            header('Content-Type: application/json; charset=utf-8');

            $id = $_POST['id'] ?? '';
            $judul = trim($_POST['judul'] ?? '');
            $deskripsi = trim($_POST['deskripsi'] ?? '');
            $deadline = trim($_POST['deadline'] ?? '');

            if (empty($id) || $judul === '' || $deskripsi === '' || $deadline === '') {
                echo json_encode(['status' => 'error', 'message' => 'Semua field wajib diisi!']);
                exit;
            }

            $data = ['id' => $id, 'judul' => $judul, 'deskripsi' => $deskripsi, 'deadline' => $deadline];

            if ($this->model('M_Tasks')->updateTask($data)) {
                echo json_encode(['status' => 'success', 'message' => 'Task berhasil diupdate!']);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Gagal mengupdate task.']);
            }
            exit;
        }
    }

    // Hapus task via AJAX
    public function delete()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            header('Content-Type: application/json; charset=utf-8');

            $id = $_POST['id'] ?? '';

            if (empty($id)) {
                echo json_encode(['status' => 'error', 'message' => 'ID task tidak ditemukan!']);
                exit;
            }

            if ($this->model('M_Tasks')->deleteTask($id)) {
                echo json_encode(['status' => 'success', 'message' => 'Task berhasil dihapus!']);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Gagal menghapus task.']);
            }
            exit;
        }
    }
}
