<?php

class Tasks extends Controller
{
    public function index()
    {
        $q = trim($_GET['q'] ?? '');
        $status = $_GET['status'] ?? '';
        $prioritas = $_GET['prioritas'] ?? '';
        $deadline = $_GET['deadline'] ?? '';

        // Hanya terima nilai filter yang valid
        if (!in_array($status, M_Tasks::STATUS, true)) {
            $status = '';
        }
        if (!in_array($prioritas, M_Tasks::PRIORITAS, true)) {
            $prioritas = '';
        }
        if (!in_array($deadline, M_Tasks::DEADLINE_FILTERS, true)) {
            $deadline = '';
        }

        $data['judul'] = 'Tasks';
        $data['q'] = $q;
        $data['status'] = $status;
        $data['prioritas'] = $prioritas;
        $data['deadline'] = $deadline;
        $data['today'] = date('Y-m-d');
        $data['tasks'] = $this->model('M_Tasks')->getAllTasks($q, $status, $prioritas, $deadline);

        $this->view('templates/header', $data);
        $this->view('tasks/index', $data);
        $this->view('templates/footer');
    }

    // Tambah task baru via AJAX
    public function add()
    {
        $this->requirePost();
        [$data, $error] = $this->validated();

        if ($error) {
            $this->json(['status' => 'error', 'message' => $error]);
        }

        if ($this->model('M_Tasks')->addTask($data)) {
            $this->json(['status' => 'success', 'message' => 'Task berhasil ditambahkan!']);
        }
        $this->json(['status' => 'error', 'message' => 'Gagal menambahkan task.']);
    }

    // Update task via AJAX
    public function update()
    {
        $this->requirePost();
        [$data, $error] = $this->validated();
        $id = (int) $this->post('id');

        if ($id <= 0) {
            $this->json(['status' => 'error', 'message' => 'ID task tidak ditemukan!']);
        }
        if ($error) {
            $this->json(['status' => 'error', 'message' => $error]);
        }

        $data['id'] = $id;

        if ($this->model('M_Tasks')->updateTask($data)) {
            $this->json(['status' => 'success', 'message' => 'Task berhasil diupdate!']);
        }
        $this->json(['status' => 'error', 'message' => 'Gagal mengupdate task.']);
    }

    // Hapus task via AJAX
    public function delete()
    {
        $this->requirePost();
        $id = (int) $this->post('id');

        if ($id <= 0) {
            $this->json(['status' => 'error', 'message' => 'ID task tidak ditemukan!']);
        }

        if ($this->model('M_Tasks')->deleteTask($id)) {
            $this->json(['status' => 'success', 'message' => 'Task berhasil dihapus!']);
        }
        $this->json(['status' => 'error', 'message' => 'Gagal menghapus task.']);
    }

    // Validasi & normalisasi input form task. Mengembalikan [data, pesanError]
    private function validated(): array
    {
        $judul = $this->post('judul');
        $deskripsi = $this->post('deskripsi');
        $deadline = $this->post('deadline');
        $status = $this->post('status') ?: 'belum selesai';
        $prioritas = $this->post('prioritas') ?: 'sedang';
        $progress = $this->post('progress');

        if ($judul === '') {
            return [[], 'Judul task wajib diisi!'];
        }
        if (mb_strlen($judul) > 255) {
            return [[], 'Judul task maksimal 255 karakter.'];
        }

        $d = DateTime::createFromFormat('Y-m-d', $deadline);
        if (!$d || $d->format('Y-m-d') !== $deadline) {
            return [[], 'Deadline wajib diisi dengan tanggal yang valid.'];
        }

        if (!in_array($status, M_Tasks::STATUS, true)) {
            return [[], 'Status tidak valid.'];
        }
        if (!in_array($prioritas, M_Tasks::PRIORITAS, true)) {
            return [[], 'Prioritas tidak valid.'];
        }

        if ($progress === '') {
            $progress = '0';
        }
        if (!ctype_digit($progress) || (int) $progress > 100) {
            return [[], 'Progress harus berupa angka 0 sampai 100.'];
        }
        $progress = (int) $progress;

        // Task yang sudah selesai otomatis 100%
        if ($status === 'selesai') {
            $progress = 100;
        }

        // Progress 100% hanya boleh untuk task berstatus Selesai
        if ($progress === 100 && $status !== 'selesai') {
            return [[], 'Progress 100% hanya untuk task berstatus Selesai. Ubah status ke Selesai atau turunkan progress.'];
        }

        return [[
            'judul'     => $judul,
            'deskripsi' => $deskripsi !== '' ? $deskripsi : null,
            'deadline'  => $deadline,
            'status'    => $status,
            'prioritas' => $prioritas,
            'progress'  => $progress,
        ], null];
    }
}
