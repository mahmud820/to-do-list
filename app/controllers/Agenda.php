<?php

class Agenda extends Controller
{
    public function index()
    {
        $data['judul'] = 'Agenda';
        $q = trim($_GET['q'] ?? '');
        $data['q'] = $q;

        // Ambil agenda, opsional filter dengan query pencarian
        $data['agendas'] = $this->model('M_Agenda')->getAllAgendas($q);

        // Ambil items untuk tiap agenda (jika ada query, items juga difilter)
        $data['items'] = [];
        foreach ($data['agendas'] as $agenda) {
            $data['items'][$agenda['id']] = $this->model('M_Agenda')->getItemsByAgenda($agenda['id'], $q);
        }

        $this->view('templates/header', $data);
        $this->view('agenda/index', $data);
        $this->view('templates/footer');
    }

    // Tambah agenda via AJAX
    public function add()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            header('Content-Type: application/json; charset=utf-8');

            $judul = trim($_POST['judul'] ?? '');
            $tanggal = trim($_POST['tanggal'] ?? null);

            if ($judul === '') {
                echo json_encode(['status' => 'error', 'message' => 'Judul wajib diisi!']);
                exit;
            }

            $data = ['judul' => $judul, 'tanggal' => $tanggal];

            if ($this->model('M_Agenda')->addAgenda($data)) {
                echo json_encode(['status' => 'success', 'message' => 'Agenda berhasil ditambahkan!']);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Gagal menambahkan agenda.']);
            }
            exit;
        }
    }

    // Update agenda via AJAX
    public function update()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            header('Content-Type: application/json; charset=utf-8');

            $id = $_POST['id'] ?? '';
            $judul = trim($_POST['judul'] ?? '');
            $tanggal = trim($_POST['tanggal'] ?? null);

            if (empty($id) || $judul === '') {
                echo json_encode(['status' => 'error', 'message' => 'ID dan judul wajib diisi!']);
                exit;
            }

            $data = ['id' => $id, 'judul' => $judul, 'tanggal' => $tanggal];

            if ($this->model('M_Agenda')->updateAgenda($data)) {
                echo json_encode(['status' => 'success', 'message' => 'Agenda berhasil diupdate!']);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Gagal mengupdate agenda.']);
            }
            exit;
        }
    }

    // Hapus agenda via AJAX
    public function delete()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            header('Content-Type: application/json; charset=utf-8');

            $id = $_POST['id'] ?? '';

            if (empty($id)) {
                echo json_encode(['status' => 'error', 'message' => 'ID agenda tidak ditemukan!']);
                exit;
            }

            if ($this->model('M_Agenda')->deleteAgenda($id)) {
                echo json_encode(['status' => 'success', 'message' => 'Agenda berhasil dihapus!']);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Gagal menghapus agenda.']);
            }
            exit;
        }
    }

    // Tambah item ke agenda
    public function addItem()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            header('Content-Type: application/json; charset=utf-8');

            $agenda_id = $_POST['agenda_id'] ?? '';
            $nama_item = trim($_POST['nama_item'] ?? '');

            if (empty($agenda_id) || $nama_item === '') {
                echo json_encode(['status' => 'error', 'message' => 'Agenda dan nama item wajib diisi!']);
                exit;
            }

            if ($this->model('M_Agenda')->addItem($agenda_id, $nama_item)) {
                echo json_encode(['status' => 'success', 'message' => 'Item berhasil ditambahkan!']);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Gagal menambahkan item.']);
            }
            exit;
        }
    }

    // Toggle status item
    public function toggleItem()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            header('Content-Type: application/json; charset=utf-8');

            $id = $_POST['id'] ?? '';
            if (empty($id)) {
                echo json_encode(['status' => 'error', 'message' => 'ID item tidak ditemukan!']);
                exit;
            }

            if ($this->model('M_Agenda')->toggleItemStatus($id)) {
                echo json_encode(['status' => 'success', 'message' => 'Status item berhasil diubah!']);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Gagal mengubah status item.']);
            }
            exit;
        }
    }

    public function deleteItem()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            header('Content-Type: application/json; charset=utf-8');

            $id = $_POST['id'] ?? '';
            if (empty($id)) {
                echo json_encode(['status' => 'error', 'message' => 'ID item tidak ditemukan!']);
                exit;
            }

            if ($this->model('M_Agenda')->deleteItem($id)) {
                echo json_encode(['status' => 'success', 'message' => 'Item berhasil dihapus!']);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Gagal menghapus item.']);
            }
            exit;
        }
    }
}
