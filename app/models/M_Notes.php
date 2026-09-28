<?php

class M_Notes extends Model
{
    // Catatan pada satu halaman (terbaru diperbarui tampil lebih dulu), opsional dicari di judul/isi
    public function getAllNotes(?string $q = null, ?int $limit = null, int $offset = 0)
    {
        [$whereSql, $params] = $this->buildFilter($q);

        $sql = 'SELECT * FROM notes' . $whereSql . ' ORDER BY updated_at DESC, id DESC';
        if ($limit !== null) {
            $sql .= ' LIMIT ' . (int) $limit . ' OFFSET ' . max(0, $offset);
        }

        $this->db->query($sql);
        $this->db->bindAll($params);
        return $this->db->resultSet();
    }

    // Jumlah catatan yang cocok dengan pencarian (untuk pagination)
    public function countFiltered(?string $q = null): int
    {
        [$whereSql, $params] = $this->buildFilter($q);

        $this->db->query('SELECT COUNT(*) AS total FROM notes' . $whereSql);
        $this->db->bindAll($params);
        $row = $this->db->single();
        return (int) ($row['total'] ?? 0);
    }

    private function buildFilter(?string $q): array
    {
        if ($q === null || $q === '') {
            return ['', []];
        }

        $like = '%' . like_escape($q) . '%';
        return [" WHERE (judul LIKE :q1 ESCAPE '!' OR isi LIKE :q2 ESCAPE '!')", ['q1' => $like, 'q2' => $like]];
    }

    public function addNote(array $data): bool
    {
        $this->db->query("INSERT INTO notes (judul, isi) VALUES (:judul, :isi)");
        $this->db->bindAll(['judul' => $data['judul'], 'isi' => $data['isi']]);
        return $this->db->execute();
    }

    public function updateNote(array $data): bool
    {
        $this->db->query("UPDATE notes SET judul = :judul, isi = :isi WHERE id = :id");
        $this->db->bindAll(['id' => (int) $data['id'], 'judul' => $data['judul'], 'isi' => $data['isi']]);
        $this->db->execute();
        return $this->db->rowCount() > 0;
    }

    public function deleteNote(int $id): bool
    {
        $this->db->query("DELETE FROM notes WHERE id = :id");
        $this->db->bind('id', $id);
        $this->db->execute();
        return $this->db->rowCount() > 0;
    }

    public function countAll(): int
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
