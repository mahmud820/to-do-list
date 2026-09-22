<?php

class Notes extends Controller
{
    public function index()
    {
        $q = trim($_GET['q'] ?? '');

        $data['judul'] = 'Notes';
        $data['q'] = $q;
        $data['notes'] = $this->model('M_Notes')->getAllNotes($q);

        $this->view('templates/header', $data);
        $this->view('notes/index', $data);
        $this->view('templates/footer');
    }

    public function add()
    {
        $this->requirePost();
        [$data, $error] = $this->validated();

        if ($error) {
            $this->json(['status' => 'error', 'message' => $error]);
        }

        if ($this->model('M_Notes')->addNote($data)) {
            $this->json(['status' => 'success', 'message' => 'Catatan berhasil ditambahkan!']);
        }
        $this->json(['status' => 'error', 'message' => 'Gagal menambahkan catatan.']);
    }

    public function update()
    {
        $this->requirePost();
        [$data, $error] = $this->validated();
        $id = (int) $this->post('id');

        if ($id <= 0) {
            $this->json(['status' => 'error', 'message' => 'ID catatan tidak ditemukan!']);
        }
        if ($error) {
            $this->json(['status' => 'error', 'message' => $error]);
        }

        $data['id'] = $id;

        if ($this->model('M_Notes')->updateNote($data)) {
            $this->json(['status' => 'success', 'message' => 'Catatan berhasil diupdate!']);
        }
        $this->json(['status' => 'error', 'message' => 'Gagal mengupdate catatan.']);
    }

    public function delete()
    {
        $this->requirePost();
        $id = (int) $this->post('id');

        if ($id <= 0) {
            $this->json(['status' => 'error', 'message' => 'ID catatan tidak ditemukan!']);
        }

        if ($this->model('M_Notes')->deleteNote($id)) {
            $this->json(['status' => 'success', 'message' => 'Catatan berhasil dihapus!']);
        }
        $this->json(['status' => 'error', 'message' => 'Gagal menghapus catatan.']);
    }

    private function validated(): array
    {
        $judul = $this->post('judul');
        $isi = $this->post('isi');

        if ($judul === '') {
            return [[], 'Judul catatan wajib diisi!'];
        }
        if (mb_strlen($judul) > 150) {
            return [[], 'Judul catatan maksimal 150 karakter.'];
        }

        return [['judul' => $judul, 'isi' => $isi !== '' ? $isi : null], null];
    }
}
