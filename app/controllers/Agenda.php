<?php

class Agenda extends Controller
{
    private const FILTERS = ['today', 'upcoming', 'past', 'all'];
    private const ITEM_MAX_LENGTH = 150;

    public function index()
    {
        $q = $this->queryParam('q');
        $filter = $this->queryChoice('filter', self::FILTERS) ?: 'today';

        $model = $this->model('M_Agenda');
        $today = today_date();
        $pager = paginate($model->countAgendas($q, $filter, $today), PER_PAGE_AGENDA, $this->queryPage());
        $agendas = $model->getAllAgendas($q, $filter, $today, $pager['perPage'], $pager['offset']);

        $data['judul'] = 'Agenda';
        $data['q'] = $q;
        $data['filter'] = $filter;
        $data['today'] = $today;
        $data['pager'] = $pager;
        $data['agendas'] = $agendas;
        // Checklist semua agenda diambil sekaligus (1 query), bukan satu query per agenda
        $data['items'] = $model->getItemsGroupedByAgenda(array_column($agendas, 'id'));

        $this->page('agenda/index', $data);
    }

    // Tambah agenda (beserta item checklist awal) via AJAX
    public function add()
    {
        $this->requirePost();
        [$data, $error] = $this->validated();

        if ($error) {
            $this->fail($error);
        }

        [$items, $error] = $this->initialItems();
        if ($error) {
            $this->fail($error);
        }

        $this->respond(
            $this->model('M_Agenda')->addAgenda($data, $items) !== false,
            'Agenda berhasil ditambahkan!',
            'Gagal menambahkan agenda.'
        );
    }

    // Update agenda via AJAX
    public function update()
    {
        $this->requirePost();
        $id = $this->requireId('agenda');
        [$data, $error] = $this->validated();

        if ($error) {
            $this->fail($error);
        }

        $data['id'] = $id;

        $this->respond(
            $this->model('M_Agenda')->updateAgenda($data),
            'Agenda berhasil diupdate!',
            'Gagal mengupdate agenda.'
        );
    }

    // Hapus agenda (beserta item-nya) via AJAX
    public function delete()
    {
        $this->requirePost();
        $id = $this->requireId('agenda');

        $this->respond(
            $this->model('M_Agenda')->deleteAgenda($id),
            'Agenda berhasil dihapus!',
            'Gagal menghapus agenda.'
        );
    }

    // Tambah item checklist ke agenda
    public function addItem()
    {
        $this->requirePost();
        $agendaId = (int) $this->post('agenda_id');
        $nama = $this->post('nama_item');

        if ($agendaId <= 0 || $nama === '') {
            $this->fail('Agenda dan nama item wajib diisi!');
        }
        if (mb_strlen($nama) > self::ITEM_MAX_LENGTH) {
            $this->fail('Nama item maksimal ' . self::ITEM_MAX_LENGTH . ' karakter.');
        }

        $model = $this->model('M_Agenda');
        if (!$model->getAgendaById($agendaId)) {
            $this->fail('Agenda tidak ditemukan!');
        }

        $this->respond(
            $model->addItem($agendaId, $nama),
            'Item berhasil ditambahkan!',
            'Gagal menambahkan item.'
        );
    }

    // Toggle status selesai/belum sebuah item
    public function toggleItem()
    {
        $this->requirePost();
        $id = $this->requireId('item');

        $this->respond(
            $this->model('M_Agenda')->toggleItemStatus($id),
            'Status item berhasil diubah!',
            'Gagal mengubah status item.'
        );
    }

    public function deleteItem()
    {
        $this->requirePost();
        $id = $this->requireId('item');

        $this->respond(
            $this->model('M_Agenda')->deleteItem($id),
            'Item berhasil dihapus!',
            'Gagal menghapus item.'
        );
    }

    // Item checklist awal dari items[] (kosong diabaikan, terlalu panjang ditolak
    // agar perilakunya sama dengan addItem() -- tidak dipotong diam-diam).
    // Mengembalikan [daftarItem, pesanError]
    private function initialItems(): array
    {
        $items = [];

        if (isset($_POST['items']) && is_array($_POST['items'])) {
            foreach ($_POST['items'] as $nama) {
                $nama = is_string($nama) ? trim($nama) : '';

                if ($nama === '') {
                    continue;
                }
                if (mb_strlen($nama) > self::ITEM_MAX_LENGTH) {
                    return [[], 'Nama item checklist maksimal ' . self::ITEM_MAX_LENGTH . ' karakter.'];
                }
                $items[] = $nama;
            }
        }

        return [$items, null];
    }

    // Validasi & normalisasi input form agenda. Mengembalikan [data, pesanError]
    // Tanggal wajib; waktu mulai/selesai opsional dan yang kosong disimpan sebagai NULL
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

        if (mb_strlen($deskripsi) > 65535) {
            return [[], 'Deskripsi terlalu panjang (maksimal 65.535 karakter).'];
        }

        if ($tanggal === '') {
            return [[], 'Tanggal agenda wajib diisi!'];
        }
        if (!is_valid_date($tanggal)) {
            return [[], 'Tanggal tidak valid.'];
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
            'tanggal'       => $tanggal,
            'waktu_mulai'   => $mulai !== '' ? $mulai : null,
            'waktu_selesai' => $selesai !== '' ? $selesai : null,
        ], null];
    }
}
