<?php

class M_Agenda
{
    private Database $db;

    public function __construct()
    {
        $this->db = new Database;
    }

    // Semua agenda beserta jumlah item & item selesai.
    // $q      : cari di judul, deskripsi, dan nama item
    // $filter : today | upcoming | past | all
    public function getAllAgendas($q = null, string $filter = 'all', ?string $today = null, ?int $limit = null)
    {
        $today = $today ?? date('Y-m-d');
        $where = [];
        $params = [];

        if ($q !== null && $q !== '') {
            $like = '%' . like_escape($q) . '%';
            $where[] = "(a.judul LIKE :q1 ESCAPE '!' OR a.deskripsi LIKE :q2 ESCAPE '!'
                OR EXISTS (SELECT 1 FROM agenda_items x WHERE x.agenda_id = a.id AND x.nama_item LIKE :q3 ESCAPE '!'))";
            $params[':q1'] = $like;
            $params[':q2'] = $like;
            $params[':q3'] = $like;
        }

        switch ($filter) {
            case 'today':
                $where[] = 'a.tanggal = :today';
                $params[':today'] = $today;
                break;
            case 'upcoming':
                $where[] = 'a.tanggal > :today';
                $params[':today'] = $today;
                break;
            case 'past':
                $where[] = 'a.tanggal < :today';
                $params[':today'] = $today;
                break;
        }

        $sql = "SELECT a.*,
                    (SELECT COUNT(*) FROM agenda_items ai WHERE ai.agenda_id = a.id) AS total_items,
                    (SELECT COUNT(*) FROM agenda_items ai WHERE ai.agenda_id = a.id AND ai.status = 1) AS done_items
                FROM agenda a";
        if ($where) {
            $sql .= ' WHERE ' . implode(' AND ', $where);
        }

        if ($filter === 'today' || $filter === 'upcoming') {
            $sql .= ' ORDER BY a.tanggal ASC, (a.waktu_mulai IS NULL) ASC, a.waktu_mulai ASC, a.id ASC';
        } else {
            $sql .= ' ORDER BY (a.tanggal IS NULL) ASC, a.tanggal DESC, a.waktu_mulai ASC, a.id DESC';
        }
        if ($limit) {
            $sql .= ' LIMIT ' . (int) $limit;
        }

        $this->db->query($sql);
        foreach ($params as $key => $value) {
            $this->db->bind($key, $value);
        }
        return $this->db->resultSet();
    }

    public function getAgendaById($id)
    {
        $this->db->query("SELECT * FROM agenda WHERE id = :id");
        $this->db->bind(':id', (int) $id);
        return $this->db->single();
    }

    // Tambah agenda (+ item checklist awal jika ada). Mengembalikan id agenda baru, atau false jika gagal.
    // $data: judul, deskripsi, tanggal, waktu_mulai, waktu_selesai (kosong = null)
    public function addAgenda($data, array $items = [])
    {
        $this->db->beginTransaction();

        $this->db->query("INSERT INTO agenda (judul, deskripsi, tanggal, waktu_mulai, waktu_selesai)
                          VALUES (:judul, :deskripsi, :tanggal, :waktu_mulai, :waktu_selesai)");
        $this->bindAgenda($data);

        if (!$this->db->execute()) {
            $this->db->rollBack();
            return false;
        }

        $agendaId = (int) $this->db->lastInsertId();

        foreach ($items as $nama) {
            $this->db->query("INSERT INTO agenda_items (agenda_id, nama_item) VALUES (:agenda_id, :nama_item)");
            $this->db->bind('agenda_id', $agendaId);
            $this->db->bind('nama_item', $nama);
            if (!$this->db->execute()) {
                $this->db->rollBack();
                return false;
            }
        }

        $this->db->commit();
        return $agendaId;
    }

    public function updateAgenda($data)
    {
        $this->db->query("UPDATE agenda SET
                            judul = :judul,
                            deskripsi = :deskripsi,
                            tanggal = :tanggal,
                            waktu_mulai = :waktu_mulai,
                            waktu_selesai = :waktu_selesai
                          WHERE id = :id");
        $this->db->bind('id', (int) $data['id']);
        $this->bindAgenda($data);
        return $this->db->execute();
    }

    private function bindAgenda(array $data): void
    {
        $this->db->bind('judul', $data['judul']);
        $this->db->bind('deskripsi', $data['deskripsi'] ?? null);
        $this->db->bind('tanggal', $data['tanggal'] ?? null);
        $this->db->bind('waktu_mulai', $data['waktu_mulai'] ?? null);
        $this->db->bind('waktu_selesai', $data['waktu_selesai'] ?? null);
    }

    // Item agenda ikut terhapus otomatis (FK ON DELETE CASCADE)
    public function deleteAgenda($id)
    {
        $this->db->query("DELETE FROM agenda WHERE id = :id");
        $this->db->bind('id', (int) $id);
        return $this->db->execute();
    }

    // Items (checklist)
    public function getItemsByAgenda($agendaId)
    {
        $this->db->query("SELECT * FROM agenda_items WHERE agenda_id = :agenda_id ORDER BY id ASC");
        $this->db->bind('agenda_id', (int) $agendaId);
        return $this->db->resultSet();
    }

    public function addItem($agendaId, $nama_item)
    {
        $this->db->query("INSERT INTO agenda_items (agenda_id, nama_item) VALUES (:agenda_id, :nama_item)");
        $this->db->bind('agenda_id', (int) $agendaId);
        $this->db->bind('nama_item', $nama_item);
        return $this->db->execute();
    }

    public function toggleItemStatus($itemId)
    {
        $this->db->query("UPDATE agenda_items SET status = 1 - COALESCE(status, 0) WHERE id = :id");
        $this->db->bind('id', (int) $itemId);
        return $this->db->execute();
    }

    public function deleteItem($itemId)
    {
        $this->db->query("DELETE FROM agenda_items WHERE id = :id");
        $this->db->bind('id', (int) $itemId);
        return $this->db->execute();
    }

    // Statistik untuk dashboard
    public function getStats(string $today)
    {
        $this->db->query("SELECT
                COUNT(*) AS total,
                COALESCE(SUM(tanggal = :today1), 0) AS hari_ini,
                COALESCE(SUM(tanggal > :today2), 0) AS mendatang
            FROM agenda");
        $this->db->bind('today1', $today);
        $this->db->bind('today2', $today);
        return $this->db->single();
    }
}
