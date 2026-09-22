<?php

class Agenda extends Controller
{
    private const FILTERS = ['today', 'upcoming', 'past', 'all'];

    public function index()
    {
        $q = trim($_GET['q'] ?? '');
        $filter = $_GET['filter'] ?? 'today';
        if (!in_array($filter, self::FILTERS, true)) {
            $filter = 'today';
        }

        $model = $this->model('M_Agenda');
        $today = date('Y-m-d');

        $data['judul'] = 'Agenda';
        $data['q'] = $q;
        $data['filter'] = $filter;
        $data['today'] = $today;
        $data['agendas'] = $model->getAllAgendas($q, $filter, $today);

        // Checklist untuk tiap agenda
        $data['items'] = [];
        foreach ($data['agendas'] as $agenda) {
            $data['items'][$agenda['id']] = $model->getItemsByAgenda($agenda['id']);
        }

        $this->view('templates/header', $data);
        $this->view('agenda/index', $data);
        $this->view('templates/footer');
    }

    // Tambah agenda (beserta item checklist awal) via AJAX
    public function add()
    {
        $this->requirePost();
        [$data, $error] = $this->validated();

        if ($error) {
            $this->json(['status' => 'error', 'message' => $error]);
        }

        // Item checklist awal: items[] (kosong diabaikan, >150 karakter ditolak
        // agar perilakunya sama dengan addItem() -- tidak dipotong diam-diam)
        $items = [];
        if (isset($_POST['items']) && is_array($_POST['items'])) {
            foreach ($_POST['items'] as $nama) {
                $nama = is_string($nama) ? trim($nama) : '';
                if ($nama === '') {
                    continue;
                }
                if (mb_strlen($nama) > 150) {
                    $this->json(['status' => 'error', 'message' => 'Nama item checklist maksimal 150 karakter.']);
                }
                $items[] = $nama;
            }
        }

        if ($this->model('M_Agenda')->addAgenda($data, $items) !== false) {
            $this->json(['status' => 'success', 'message' => 'Agenda berhasil ditambahkan!']);
        }
        $this->json(['status' => 'error', 'message' => 'Gagal menambahkan agenda.']);
    }

    // Update agenda via AJAX
    public function update()
    {
        $this->requirePost();
        [$data, $error] = $this->validated();
        $id = (int) $this->post('id');

        if ($id <= 0) {
            $this->json(['status' => 'error', 'message' => 'ID agenda tidak ditemukan!']);
        }
        if ($error) {
            $this->json(['status' => 'error', 'message' => $error]);
        }

        $data['id'] = $id;

        if ($this->model('M_Agenda')->updateAgenda($data)) {
            $this->json(['status' => 'success', 'message' => 'Agenda berhasil diupdate!']);
        }
        $this->json(['status' => 'error', 'message' => 'Gagal mengupdate agenda.']);
    }

    // Hapus agenda (beserta item-nya) via AJAX
    public function delete()
    {
        $this->requirePost();
        $id = (int) $this->post('id');

        if ($id <= 0) {
            $this->json(['status' => 'error', 'message' => 'ID agenda tidak ditemukan!']);
        }

        if ($this->model('M_Agenda')->deleteAgenda($id)) {
            $this->json(['status' => 'success', 'message' => 'Agenda berhasil dihapus!']);
        }
        $this->json(['status' => 'error', 'message' => 'Gagal menghapus agenda.']);
    }

    // Tambah item checklist ke agenda
    public function addItem()
    {
        $this->requirePost();
        $agendaId = (int) $this->post('agenda_id');
        $nama = $this->post('nama_item');

        if ($agendaId <= 0 || $nama === '') {
            $this->json(['status' => 'error', 'message' => 'Agenda dan nama item wajib diisi!']);
        }
        if (mb_strlen($nama) > 150) {
            $this->json(['status' => 'error', 'message' => 'Nama item maksimal 150 karakter.']);
        }
        if (!$this->model('M_Agenda')->getAgendaById($agendaId)) {
            $this->json(['status' => 'error', 'message' => 'Agenda tidak ditemukan!']);
        }

        if ($this->model('M_Agenda')->addItem($agendaId, $nama)) {
            $this->json(['status' => 'success', 'message' => 'Item berhasil ditambahkan!']);
        }
        $this->json(['status' => 'error', 'message' => 'Gagal menambahkan item.']);
    }

    // Toggle status selesai/belum sebuah item
    public function toggleItem()
    {
        $this->requirePost();
        $id = (int) $this->post('id');

        if ($id <= 0) {
            $this->json(['status' => 'error', 'message' => 'ID item tidak ditemukan!']);
        }

        if ($this->model('M_Agenda')->toggleItemStatus($id)) {
            $this->json(['status' => 'success', 'message' => 'Status item berhasil diubah!']);
        }
        $this->json(['status' => 'error', 'message' => 'Gagal mengubah status item.']);
    }

    public function deleteItem()
    {
        $this->requirePost();
        $id = (int) $this->post('id');

        if ($id <= 0) {
            $this->json(['status' => 'error', 'message' => 'ID item tidak ditemukan!']);
        }

        if ($this->model('M_Agenda')->deleteItem($id)) {
            $this->json(['status' => 'success', 'message' => 'Item berhasil dihapus!']);
        }
        $this->json(['status' => 'error', 'message' => 'Gagal menghapus item.']);
    }

    // Validasi & normalisasi input form agenda. Mengembalikan [data, pesanError]
    // Tanggal/waktu yang kosong disimpan sebagai NULL (bukan string kosong)
    private function validated(): array
    {
        $judul = $this->post('judul');
        $deskripsi = $this->post('deskripsi');
        $tanggal = $this->post('tanggal');
        $mulai = $this->post('waktu_mulai');
        $selesai = $this->post('waktu_selesai');

        if ($judul === '') {
            return [[], 'Judul agenda wajib diisi!'];
        }
        if (mb_strlen($judul) > 100) {
            return [[], 'Judul agenda maksimal 100 karakter.'];
        }

        if ($tanggal !== '') {
            $d = DateTime::createFromFormat('Y-m-d', $tanggal);
            if (!$d || $d->format('Y-m-d') !== $tanggal) {
                return [[], 'Tanggal tidak valid.'];
            }
        }

        foreach ([$mulai, $selesai] as $t) {
            if ($t !== '' && !preg_match('/^([01]\d|2[0-3]):[0-5]\d$/', $t)) {
                return [[], 'Format waktu tidak valid (gunakan HH:MM).'];
            }
        }
        if ($selesai !== '' && $mulai === '') {
            return [[], 'Isi waktu mulai jika waktu selesai diisi.'];
        }
        if ($mulai !== '' && $selesai !== '' && $selesai <= $mulai) {
            return [[], 'Waktu selesai harus lebih besar dari waktu mulai.'];
        }

        return [[
            'judul'         => $judul,
            'deskripsi'     => $deskripsi !== '' ? $deskripsi : null,
            'tanggal'       => $tanggal !== '' ? $tanggal : null,
            'waktu_mulai'   => $mulai !== '' ? $mulai : null,
            'waktu_selesai' => $selesai !== '' ? $selesai : null,
        ], null];
    }
}
