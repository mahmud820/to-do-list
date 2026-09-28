<?php

class M_Tasks extends Model
{
    // Nilai enum sesuai kolom `status` & `prioritas` di tabel tasks
    public const STATUS = ['belum selesai', 'sedang dikerjakan', 'selesai'];
    public const PRIORITAS = ['rendah', 'sedang', 'tinggi'];
    // Kategori filter deadline (selaras dengan state dari deadline_info())
    public const DEADLINE_FILTERS = ['overdue', 'today', 'soon', 'upcoming'];

    // Semua task pada satu halaman, opsional dicari (judul/deskripsi) dan difilter
    // status/prioritas/deadline. Task yang belum selesai tampil lebih dulu,
    // diurutkan berdasarkan deadline terdekat.
    public function getAllTasks(?string $q = null, ?string $status = null, ?string $prioritas = null, ?string $deadlineFilter = null, ?int $limit = null, int $offset = 0)
    {
        [$whereSql, $params] = $this->buildFilter($q, $status, $prioritas, $deadlineFilter);

        $sql = 'SELECT * FROM tasks' . $whereSql
            . " ORDER BY (status <=> 'selesai') ASC, deadline ASC, id DESC";
        if ($limit !== null) {
            $sql .= ' LIMIT ' . (int) $limit . ' OFFSET ' . max(0, $offset);
        }

        $this->db->query($sql);
        $this->db->bindAll($params);
        return $this->db->resultSet();
    }

    // Jumlah task yang cocok dengan filter yang sama (untuk pagination)
    public function countTasks(?string $q = null, ?string $status = null, ?string $prioritas = null, ?string $deadlineFilter = null): int
    {
        [$whereSql, $params] = $this->buildFilter($q, $status, $prioritas, $deadlineFilter);

        $this->db->query('SELECT COUNT(*) AS total FROM tasks' . $whereSql);
        $this->db->bindAll($params);
        $row = $this->db->single();
        return (int) ($row['total'] ?? 0);
    }

    // Susun klausa WHERE + parameter dari filter. Mengembalikan [sqlWhere, params]
    private function buildFilter(?string $q, ?string $status, ?string $prioritas, ?string $deadlineFilter): array
    {
        $where = [];
        $params = [];

        if ($q !== null && $q !== '') {
            $like = '%' . like_escape($q) . '%';
            $where[] = "(judul LIKE :q1 ESCAPE '!' OR deskripsi LIKE :q2 ESCAPE '!')";
            $params['q1'] = $like;
            $params['q2'] = $like;
        }
        if (in_array($status, self::STATUS, true)) {
            $where[] = 'status = :status';
            $params['status'] = $status;
        }
        if (in_array($prioritas, self::PRIORITAS, true)) {
            $where[] = 'prioritas = :prioritas';
            $params['prioritas'] = $prioritas;
        }
        if (in_array($deadlineFilter, self::DEADLINE_FILTERS, true)) {
            $this->addDeadlineFilter($deadlineFilter, $where, $params);
        }

        return [$where ? ' WHERE ' . implode(' AND ', $where) : '', $params];
    }

    // Tambahkan kondisi filter deadline (hanya untuk task yang belum selesai)
    private function addDeadlineFilter(string $filter, array &$where, array &$params): void
    {
        $where[] = "NOT (status <=> 'selesai')";

        switch ($filter) {
            case 'overdue':
                $where[] = 'deadline < :d_today';
                $params['d_today'] = today_date();
                break;
            case 'today':
                $where[] = 'deadline = :d_today';
                $params['d_today'] = today_date();
                break;
            case 'soon':
                $where[] = 'deadline >= :d_today AND deadline <= :d_soon';
                $params['d_today'] = today_date();
                $params['d_soon'] = soon_date();
                break;
            case 'upcoming':
                $where[] = 'deadline > :d_soon';
                $params['d_soon'] = soon_date();
                break;
        }
    }

    // Tambah task baru. $data: judul, deskripsi, deadline, status, prioritas, progress
    public function addTask(array $data): bool
    {
        $this->db->query("INSERT INTO tasks (judul, deskripsi, deadline, status, prioritas, progress)
                          VALUES (:judul, :deskripsi, :deadline, :status, :prioritas, :progress)");
        $this->bindTask($data);

        return $this->db->execute();
    }

    // Update task (kolom updated_at diisi otomatis oleh database)
    public function updateTask(array $data): bool
    {
        $this->db->query("UPDATE tasks SET
                            judul = :judul,
                            deskripsi = :deskripsi,
                            deadline = :deadline,
                            status = :status,
                            prioritas = :prioritas,
                            progress = :progress
                          WHERE id = :id");
        $this->db->bind('id', (int) $data['id']);
        $this->bindTask($data);

        $this->db->execute();
        return $this->db->rowCount() > 0;
    }

    private function bindTask(array $data): void
    {
        $this->db->bindAll([
            'judul'     => $data['judul'],
            'deskripsi' => $data['deskripsi'],
            'deadline'  => $data['deadline'],
            'status'    => $data['status'],
            'prioritas' => $data['prioritas'],
            'progress'  => (int) $data['progress'],
        ]);
    }

    public function deleteTask(int $id): bool
    {
        $this->db->query("DELETE FROM tasks WHERE id = :id");
        $this->db->bind('id', $id);
        $this->db->execute();
        return $this->db->rowCount() > 0;
    }

    // Statistik untuk dashboard
    public function getStats(string $today, string $soon)
    {
        $this->db->query("SELECT
                COUNT(*) AS total,
                COALESCE(SUM(status <=> 'belum selesai'), 0) AS belum,
                COALESCE(SUM(status <=> 'sedang dikerjakan'), 0) AS berjalan,
                COALESCE(SUM(status <=> 'selesai'), 0) AS selesai,
                COALESCE(SUM(NOT (status <=> 'selesai') AND deadline < :today1), 0) AS terlambat,
                COALESCE(SUM(NOT (status <=> 'selesai') AND deadline BETWEEN :today2 AND :soon), 0) AS mendekati
            FROM tasks");
        $this->db->bindAll(['today1' => $today, 'today2' => $today, 'soon' => $soon]);
        return $this->db->single();
    }

    // Task belum selesai yang paling mendesak: terlambat dulu, lalu prioritas, lalu deadline
    public function getUrgent(string $today, int $limit = 5)
    {
        $this->db->query("SELECT * FROM tasks
            WHERE NOT (status <=> 'selesai')
            ORDER BY (deadline < :today) DESC, FIELD(prioritas, 'tinggi', 'sedang', 'rendah'), deadline ASC
            LIMIT " . (int) $limit);
        $this->db->bind('today', $today);
        return $this->db->resultSet();
    }
}
