<?php

class M_Agenda extends Model
{
    // Agenda pada satu halaman, beserta jumlah item & item selesai.
    // $q      : cari di judul, deskripsi, dan nama item
    // $filter : today | upcoming | past | all
    public function getAllAgendas(?string $q = null, string $filter = 'all', ?string $today = null, ?int $limit = null, int $offset = 0)
    {
        [$whereSql, $params] = $this->buildFilter($q, $filter, $today ?? today_date());

        $sql = "SELECT a.*,
                    (SELECT COUNT(*) FROM agenda_items ai WHERE ai.agenda_id = a.id) AS total_items,
                    (SELECT COUNT(*) FROM agenda_items ai WHERE ai.agenda_id = a.id AND ai.status = 1) AS done_items
                FROM agenda a" . $whereSql;

        if ($filter === 'today' || $filter === 'upcoming') {
            $sql .= ' ORDER BY a.tanggal ASC, (a.waktu_mulai IS NULL) ASC, a.waktu_mulai ASC, a.id ASC';
        } else {
            $sql .= ' ORDER BY (a.tanggal IS NULL) ASC, a.tanggal DESC, a.waktu_mulai ASC, a.id DESC';
        }
        if ($limit) {
            $sql .= ' LIMIT ' . (int) $limit . ' OFFSET ' . max(0, $offset);
        }

        $this->db->query($sql);
        $this->db->bindAll($params);
        return $this->db->resultSet();
    }

    // Jumlah agenda yang cocok dengan filter yang sama (untuk pagination)
    public function countAgendas(?string $q = null, string $filter = 'all', ?string $today = null): int
    {
        [$whereSql, $params] = $this->buildFilter($q, $filter, $today ?? today_date());

        $this->db->query('SELECT COUNT(*) AS total FROM agenda a' . $whereSql);
        $this->db->bindAll($params);
        $row = $this->db->single();
        return (int) ($row['total'] ?? 0);
    }

    // Susun klausa WHERE + parameter. Mengembalikan [sqlWhere, params]
    private function buildFilter(?string $q, string $filter, string $today): array
    {
        $where = [];
        $params = [];

        if ($q !== null && $q !== '') {
            $like = '%' . like_escape($q) . '%';
            $where[] = "(a.judul LIKE :q1 ESCAPE '!' OR a.deskripsi LIKE :q2 ESCAPE '!'
                OR EXISTS (SELECT 1 FROM agenda_items x WHERE x.agenda_id = a.id AND x.nama_item LIKE :q3 ESCAPE '!'))";
            $params['q1'] = $like;
            $params['q2'] = $like;
            $params['q3'] = $like;
        }

        switch ($filter) {
            case 'today':
                $where[] = 'a.tanggal = :today';
                $params['today'] = $today;
                break;
            case 'upcoming':
                $where[] = 'a.tanggal > :today';
                $params['today'] = $today;
                break;
            case 'past':
                $where[] = 'a.tanggal < :today';
                $params['today'] = $today;
                break;
        }

        return [$where ? ' WHERE ' . implode(' AND ', $where) : '', $params];
    }

    public function getAgendaById(int $id)
    {
        $this->db->query("SELECT * FROM agenda WHERE id = :id");
        $this->db->bind('id', $id);
        return $this->db->single();
    }

    // Tambah agenda (+ item checklist awal jika ada) dalam satu transaksi.
    // Mengembalikan id agenda baru, atau false jika gagal (semua perubahan dibatalkan).
    // $data: judul, deskripsi, tanggal, waktu_mulai, waktu_selesai (kosong = null)
    public function addAgenda(array $data, array $items = [])
    {
        return $this->db->transaction(function () use ($data, $items) {
            $this->db->query("INSERT INTO agenda (judul, deskripsi, tanggal, waktu_mulai, waktu_selesai)
                              VALUES (:judul, :deskripsi, :tanggal, :waktu_mulai, :waktu_selesai)");
            $this->bindAgenda($data);

            if (!$this->db->execute()) {
                return false;
            }

            $agendaId = (int) $this->db->lastInsertId();

            foreach ($items as $nama) {
                if (!$this->addItem($agendaId, $nama)) {
                    return false;
                }
            }

            return $agendaId;
        });
    }

    public function updateAgenda(array $data): bool
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

        $this->db->execute();
        return $this->db->rowCount() > 0;
    }

    private function bindAgenda(array $data): void
    {
        $this->db->bindAll([
            'judul'         => $data['judul'],
            'deskripsi'     => $data['deskripsi'] ?? null,
            'tanggal'       => $data['tanggal'] ?? null,
            'waktu_mulai'   => $data['waktu_mulai'] ?? null,
            'waktu_selesai' => $data['waktu_selesai'] ?? null,
        ]);
    }

    // Item agenda ikut terhapus otomatis (FK ON DELETE CASCADE)
    public function deleteAgenda(int $id): bool
    {
        $this->db->query("DELETE FROM agenda WHERE id = :id");
        $this->db->bind('id', $id);
        $this->db->execute();
        return $this->db->rowCount() > 0;
    }

    // ---------------------------------------------------------------
    // Items (checklist)
    // ---------------------------------------------------------------

    // Item untuk banyak agenda sekaligus (1 query), dikelompokkan per agenda_id:
    // [agenda_id => [item, item, ...]]
    public function getItemsGroupedByAgenda(array $agendaIds): array
    {
        $ids = array_values(array_unique(array_map('intval', $agendaIds)));
        if (!$ids) {
            return [];
        }

        $holders = [];
        $params = [];
        foreach ($ids as $i => $id) {
            $holders[] = ':id' . $i;
            $params['id' . $i] = $id;
        }

        $this->db->query('SELECT * FROM agenda_items WHERE agenda_id IN (' . implode(',', $holders) . ') ORDER BY id ASC');
        $this->db->bindAll($params);

        $grouped = [];
        foreach ($this->db->resultSet() as $row) {
            $grouped[(int) $row['agenda_id']][] = $row;
        }
        return $grouped;
    }

    public function addItem(int $agendaId, string $namaItem): bool
    {
        $this->db->query("INSERT INTO agenda_items (agenda_id, nama_item) VALUES (:agenda_id, :nama_item)");
        $this->db->bindAll(['agenda_id' => $agendaId, 'nama_item' => $namaItem]);
        return $this->db->execute();
    }

    public function toggleItemStatus(int $itemId): bool
    {
        $this->db->query("UPDATE agenda_items SET status = 1 - COALESCE(status, 0) WHERE id = :id");
        $this->db->bind('id', $itemId);
        $this->db->execute();
        return $this->db->rowCount() > 0;
    }

    public function deleteItem(int $itemId): bool
    {
        $this->db->query("DELETE FROM agenda_items WHERE id = :id");
        $this->db->bind('id', $itemId);
        $this->db->execute();
        return $this->db->rowCount() > 0;
    }

    // Statistik untuk dashboard
    public function getStats(string $today)
    {
        $this->db->query("SELECT
                COUNT(*) AS total,
                COALESCE(SUM(tanggal = :today1), 0) AS hari_ini,
                COALESCE(SUM(tanggal > :today2), 0) AS mendatang
            FROM agenda");
        $this->db->bindAll(['today1' => $today, 'today2' => $today]);
        return $this->db->single();
    }
}
