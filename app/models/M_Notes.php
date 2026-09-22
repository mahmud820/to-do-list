<?php

class M_Notes
{
    private Database $db;

    public function __construct()
    {
        $this->db = new Database;
    }

    // Semua catatan (terbaru diperbarui tampil lebih dulu), opsional dicari di judul/isi
    public function getAllNotes($q = null)
    {
        if ($q === null || $q === '') {
            $this->db->query("SELECT * FROM notes ORDER BY updated_at DESC, id DESC");
            return $this->db->resultSet();
        }

        $like = '%' . like_escape($q) . '%';
        $this->db->query("SELECT * FROM notes WHERE judul LIKE :q1 ESCAPE '!' OR isi LIKE :q2 ESCAPE '!' ORDER BY updated_at DESC, id DESC");
        $this->db->bind('q1', $like);
        $this->db->bind('q2', $like);
        return $this->db->resultSet();
    }

    public function getNoteById($id)
    {
        $this->db->query("SELECT * FROM notes WHERE id = :id");
        $this->db->bind('id', (int) $id);
        return $this->db->single();
    }

    public function addNote($data)
    {
        $this->db->query("INSERT INTO notes (judul, isi) VALUES (:judul, :isi)");
        $this->db->bind('judul', $data['judul']);
        $this->db->bind('isi', $data['isi']);
        return $this->db->execute();
    }

    public function updateNote($data)
    {
        $this->db->query("UPDATE notes SET judul = :judul, isi = :isi WHERE id = :id");
        $this->db->bind('id', (int) $data['id']);
        $this->db->bind('judul', $data['judul']);
        $this->db->bind('isi', $data['isi']);
        return $this->db->execute();
    }

    public function deleteNote($id)
    {
        $this->db->query("DELETE FROM notes WHERE id = :id");
        $this->db->bind('id', (int) $id);
        return $this->db->execute();
    }

    public function countAll()
    {
        $this->db->query("SELECT COUNT(*) AS total FROM notes");
        $row = $this->db->single();
        return (int) ($row['total'] ?? 0);
    }

    public function getLatest(int $limit = 3)
    {
        $this->db->query("SELECT * FROM notes ORDER BY updated_at DESC, id DESC LIMIT " . (int) $limit);
        return $this->db->resultSet();
    }
}
