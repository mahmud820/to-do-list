<?php

class M_Tasks
{
    private Database $db;

    public function __construct()
    {
        $this->db = new Database; // pakai class Database yang sudah kamu buat
    }

    // ambil semua data tasks (opsional dengan pencarian q)
    public function getAllTasks($q = null)
    {
        if ($q === null || $q === '') {
            $this->db->query("SELECT * FROM tasks ORDER BY deadline ASC");
            return $this->db->resultSet();
        }

        $this->db->query("SELECT * FROM tasks WHERE judul LIKE :q OR deskripsi LIKE :q ORDER BY deadline ASC");
        $this->db->bind(':q', "%$q%");
        return $this->db->resultSet();
    }

    // ambil task berdasarkan id
    public function getTaskById($id)
    {
        $this->db->query("SELECT * FROM tasks WHERE id = :id");
        $this->db->bind(':id', $id);
        return $this->db->single();
    }

    // tambah task baru
    public function addTask($data)
    {
        $query = "INSERT INTO tasks (judul, deskripsi, deadline, status, tanggal_dibuat)
                VALUES (:judul, :deskripsi, :deadline, 'belum selesai', CURDATE())";

        $this->db->query($query);
        $this->db->bind('judul', $data['judul']);
        $this->db->bind('deskripsi', $data['deskripsi']);
        $this->db->bind('deadline', $data['deadline']);

        return $this->db->execute();
    }

    // update task
    public function updateTask($data)
    {
        $query = "UPDATE tasks SET 
                    judul = :judul, 
                    deskripsi = :deskripsi, 
                    deadline = :deadline, 
                    updated_at = NOW()
                WHERE id = :id";

        $this->db->query($query);
        $this->db->bind('id', $data['id']);
        $this->db->bind('judul', $data['judul']);
        $this->db->bind('deskripsi', $data['deskripsi']);
        $this->db->bind('deadline', $data['deadline']);
        return $this->db->execute();
    }

    // hapus task
    public function deleteTask($id)
    {
        $query = "DELETE FROM tasks WHERE id = :id";
        $this->db->query($query);
        $this->db->bind('id', $id);
        return $this->db->execute();
    }
}
