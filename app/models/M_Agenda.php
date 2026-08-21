<?php

class M_Agenda
{
  private Database $db;

  public function __construct()
  {
    $this->db = new Database;
  }

  // Ambil semua agenda beserta jumlah item dan jumlah selesai
  // Jika parameter $q disediakan, lakukan pencarian pada judul agenda dan nama item
  public function getAllAgendas($q = null)
  {
    if ($q === null || $q === '') {
      $this->db->query("SELECT a.*, 
            (SELECT COUNT(*) FROM agenda_items ai WHERE ai.agenda_id = a.id) AS total_items,
            (SELECT COUNT(*) FROM agenda_items ai WHERE ai.agenda_id = a.id AND ai.status = 1) AS done_items
            FROM agenda a
            ORDER BY a.tanggal ASC, a.created_at DESC");
      return $this->db->resultSet();
    }

    // Pencarian (partial match) pada judul dan nama item
    $this->db->query("SELECT DISTINCT a.*, 
            (SELECT COUNT(*) FROM agenda_items ai WHERE ai.agenda_id = a.id) AS total_items,
            (SELECT COUNT(*) FROM agenda_items ai WHERE ai.agenda_id = a.id AND ai.status = 1) AS done_items
            FROM agenda a
            LEFT JOIN agenda_items ai ON ai.agenda_id = a.id
            WHERE a.judul LIKE :q OR ai.nama_item LIKE :q
            ORDER BY a.tanggal ASC, a.created_at DESC");
    $this->db->bind(':q', "%$q%");
    return $this->db->resultSet();
  }

  public function getAgendaById($id)
  {
    $this->db->query("SELECT * FROM agenda WHERE id = :id");
    $this->db->bind(':id', $id);
    return $this->db->single();
  }

  public function addAgenda($data)
  {
    $query = "INSERT INTO agenda (judul, tanggal) VALUES (:judul, :tanggal)";
    $this->db->query($query);
    $this->db->bind('judul', $data['judul']);
    $this->db->bind('tanggal', $data['tanggal'] ?? null);
    return $this->db->execute();
  }

  public function updateAgenda($data)
  {
    $query = "UPDATE agenda SET judul = :judul, tanggal = :tanggal, updated_at = NOW() WHERE id = :id";
    $this->db->query($query);
    $this->db->bind('id', $data['id']);
    $this->db->bind('judul', $data['judul']);
    $this->db->bind('tanggal', $data['tanggal'] ?? null);
    return $this->db->execute();
  }

  public function deleteAgenda($id)
  {
    $this->db->query("DELETE FROM agenda WHERE id = :id");
    $this->db->bind('id', $id);
    return $this->db->execute();
  }

  // Items
  public function getItemsByAgenda($agendaId, $q = null)
  {
    if ($q === null || $q === '') {
      $this->db->query("SELECT * FROM agenda_items WHERE agenda_id = :agenda_id ORDER BY created_at ASC");
      $this->db->bind('agenda_id', $agendaId);
      return $this->db->resultSet();
    }

    $this->db->query("SELECT * FROM agenda_items WHERE agenda_id = :agenda_id AND nama_item LIKE :q ORDER BY created_at ASC");
    $this->db->bind('agenda_id', $agendaId);
    $this->db->bind('q', "%$q%");
    return $this->db->resultSet();
  }

  public function addItem($agendaId, $nama_item)
  {
    $this->db->query("INSERT INTO agenda_items (agenda_id, nama_item) VALUES (:agenda_id, :nama_item)");
    $this->db->bind('agenda_id', $agendaId);
    $this->db->bind('nama_item', $nama_item);
    return $this->db->execute();
  }

  public function toggleItemStatus($itemId)
  {
    $this->db->query("UPDATE agenda_items SET status = 1 - status, updated_at = NOW() WHERE id = :id");
    $this->db->bind('id', $itemId);
    return $this->db->execute();
  }

  public function deleteItem($itemId)
  {
    $this->db->query("DELETE FROM agenda_items WHERE id = :id");
    $this->db->bind('id', $itemId);
    return $this->db->execute();
  }
}
