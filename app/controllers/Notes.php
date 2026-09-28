<?php

class Notes extends Controller
{
    public function index()
    {
        $q = $this->queryParam('q');

        $data['judul'] = 'Notes';
        $data['q'] = $q;

        $model = $this->model('M_Notes');
        $pager = paginate($model->countFiltered($q), PER_PAGE_NOTES, $this->queryPage());
        $data['pager'] = $pager;
        $data['notes'] = $model->getAllNotes($q, $pager['perPage'], $pager['offset']);

        $this->page('notes/index', $data);
    }

    public function add()
    {
        $this->requirePost();
        [$data, $error] = $this->validated();

        if ($error) {
            $this->fail($error);
        }

        $this->respond(
            $this->model('M_Notes')->addNote($data),
            'Catatan berhasil ditambahkan!',
            'Gagal menambahkan catatan.'
        );
    }

    public function update()
    {
        $this->requirePost();
        $id = $this->requireId('catatan');
        [$data, $error] = $this->validated();

        if ($error) {
            $this->fail($error);
        }

        $data['id'] = $id;

        $this->respond(
            $this->model('M_Notes')->updateNote($data),
            'Catatan berhasil diupdate!',
            'Gagal mengupdate catatan.'
        );
    }

    public function delete()
    {
        $this->requirePost();
        $id = $this->requireId('catatan');

        $this->respond(
            $this->model('M_Notes')->deleteNote($id),
            'Catatan berhasil dihapus!',
            'Gagal menghapus catatan.'
        );
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

        if (mb_strlen($isi) > 65535) {
            return [[], 'Isi catatan terlalu panjang (maksimal 65.535 karakter).'];
        }

        return [['judul' => $judul, 'isi' => $isi !== '' ? $isi : null], null];
    }
}
