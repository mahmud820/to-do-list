<?php

class M_Tasks
{
    // Nilai enum sesuai kolom `status` & `prioritas` di tabel tasks
    public const STATUS = ['belum selesai', 'sedang dikerjakan', 'selesai'];
    public const PRIORITAS = ['rendah', 'sedang', 'tinggi'];
    // Kategori filter deadline (selaras dengan state dari deadline_info())
    public const DEADLINE_FILTERS = ['overdue', 'today', 'soon', 'upcoming'];

    private Database $db;

    public function __construct()
    {
        $this->db = new Database;
    }

    // Semua task, opsional dicari (judul/deskripsi) dan difilter status/prioritas.
    // Task yang belum selesai tampil lebih dulu, diurutkan berdasarkan deadline terdekat.
    public function getAllTasks($q = null, $status = null, $prioritas = null, $deadlineFilter = null)
    {
        $where = [];
        $params = [];

        if ($q !== null && $q !== '') {
            $like = '%' . like_escape($q) . '%';
            $where[] = "(judul LIKE :q1 ESCAPE '!' OR deskripsi LIKE :q2 ESCAPE '!')";
            $params[':q1'] = $like;
            $params[':q2'] = $like;
        }
        if (in_array($status, self::STATUS, true)) {
            $where[] = 'status = :status';
            $params[':status'] = $status;
        }
        if (in_array($prioritas, self::PRIORITAS, true)) {
            $where[] = 'prioritas = :prioritas';
            $params[':prioritas'] = $prioritas;
        }
        if (in_array($deadlineFilter, self::DEADLINE_FILTERS, true)) {
            $today = date('Y-m-d');
            $soon = date('Y-m-d', strtotime('+' . DEADLINE_SOON_DAYS . ' days'));

            switch ($deadlineFilter) {
                case 'overdue':
                    $where[] = "NOT (status <=> 'selesai') AND deadline < :d_today";
                    $params[':d_today'] = $today;
                    break;
                case 'today':
                    $where[] = "NOT (status <=> 'selesai') AND deadline = :d_today";
                    $params[':d_today'] = $today;
                    break;
                case 'soon':
                    $where[] = "NOT (status <=> 'selesai') AND deadline > :d_today AND deadline <= :d_soon";
                    $params[':d_today'] = $today;
                    $params[':d_soon'] = $soon;
                    break;
                case 'upcoming':
                    $where[] = "NOT (status <=> 'selesai') AND deadline > :d_soon";
                    $params[':d_soon'] = $soon;
                    break;
            }
        }

        $sql = 'SELECT * FROM tasks';
        if ($where) {
            $sql .= ' WHERE ' . implode(' AND ', $where);
        }
        $sql .= " ORDER BY (status <=> 'selesai') ASC, deadline ASC, id DESC";

        $this->db->query($sql);
        foreach ($params as $key => $value) {
            $this->db->bind($key, $value);
        }
        return $this->db->resultSet();
    }

    public function getTaskById($id)
    {
        $this->db->query("SELECT * FROM tasks WHERE id = :id");
        $this->db->bind(':id', (int) $id);
        return $this->db->single();
    }

    // Tambah task baru. $data: judul, deskripsi, deadline, status, prioritas, progress
    public function addTask($data)
    {
        $query = "INSERT INTO tasks (judul, deskripsi, deadline, status, prioritas, progress)
              VALUES (:judul, :deskripsi, :deadline, :status, :prioritas, :progress)";

        $this->db->query($query);
        $this->db->bind('judul', $data['judul']);
        $this->db->bind('deskripsi', $data['deskripsi']);
        $this->db->bind('deadline', $data['deadline']);
        $this->db->bind('status', $data['status']);
        $this->db->bind('prioritas', $data['prioritas']);
        $this->db->bind('progress', (int) $data['progress']);

        return $this->db->execute();
    }

    // Update task (kolom updated_at diisi otomatis oleh database)
    public function updateTask($data)
    {
        $query = "UPDATE tasks SET
                    judul = :judul,
                    deskripsi = :deskripsi,
                    deadline = :deadline,
                    status = :status,
                    prioritas = :prioritas,
                    progress = :progress
                  WHERE id = :id";

        $this->db->query($query);
        $this->db->bind('id', (int) $data['id']);
        $this->db->bind('judul', $data['judul']);
        $this->db->bind('deskripsi', $data['deskripsi']);
        $this->db->bind('deadline', $data['deadline']);
        $this->db->bind('status', $data['status']);
        $this->db->bind('prioritas', $data['prioritas']);
        $this->db->bind('progress', (int) $data['progress']);

        return $this->db->execute();
    }

    public function deleteTask($id)
    {
        $this->db->query("DELETE FROM tasks WHERE id = :id");
        $this->db->bind('id', (int) $id);
        return $this->db->execute();
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
        $this->db->bind('today1', $today);
        $this->db->bind('today2', $today);
        $this->db->bind('soon', $soon);
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
