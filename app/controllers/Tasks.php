<?php

class Tasks extends Controller
{
    public function index()
    {
        $q = $this->queryParam('q');
        // Hanya terima nilai filter yang valid (selain itu dianggap "semua")
        $status = $this->queryChoice('status', M_Tasks::STATUS);
        $prioritas = $this->queryChoice('prioritas', M_Tasks::PRIORITAS);
        $deadline = $this->queryChoice('deadline', M_Tasks::DEADLINE_FILTERS);

        $data['judul'] = 'Tasks';
        $data['q'] = $q;
        $data['status'] = $status;
        $data['prioritas'] = $prioritas;
        $data['deadline'] = $deadline;
        $data['today'] = today_date();

        $model = $this->model('M_Tasks');
        $pager = paginate($model->countTasks($q, $status, $prioritas, $deadline), PER_PAGE_TASKS, $this->queryPage());
        $data['pager'] = $pager;
        $data['tasks'] = $model->getAllTasks($q, $status, $prioritas, $deadline, $pager['perPage'], $pager['offset']);

        $this->page('tasks/index', $data);
    }

    // Tambah task baru via AJAX
    public function add()
    {
        $this->requirePost();
        [$data, $error] = $this->validated();

        if ($error) {
            $this->fail($error);
        }

        // Task baru tidak boleh langsung terlambat (saat mengedit, deadline lama tetap boleh)
        if ($data['deadline'] < today_date()) {
            $this->fail('Deadline tidak boleh sebelum hari ini.');
        }

        $this->respond(
            $this->model('M_Tasks')->addTask($data),
            'Task berhasil ditambahkan!',
            'Gagal menambahkan task.'
        );
    }

    // Update task via AJAX
    public function update()
    {
        $this->requirePost();
        $id = $this->requireId('task');
        [$data, $error] = $this->validated();

        if ($error) {
            $this->fail($error);
        }

        $data['id'] = $id;

        $this->respond(
            $this->model('M_Tasks')->updateTask($data),
            'Task berhasil diupdate!',
            'Gagal mengupdate task.'
        );
    }

    // Hapus task via AJAX
    public function delete()
    {
        $this->requirePost();
        $id = $this->requireId('task');

        $this->respond(
            $this->model('M_Tasks')->deleteTask($id),
            'Task berhasil dihapus!',
            'Gagal menghapus task.'
        );
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

        if (mb_strlen($deskripsi) > 65535) {
            return [[], 'Deskripsi terlalu panjang (maksimal 65.535 karakter).'];
        }

        if (!is_valid_date($deadline)) {
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
