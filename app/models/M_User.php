<?php

class M_User
{
  private Database $db;

  public function __construct()
  {
    $this->db = new Database;
  }

  // Ambil user berdasarkan username (false jika tidak ada)
  public function getByUsername(string $username)
  {
    $this->db->query("SELECT id, nama, username, password FROM users WHERE username = :username LIMIT 1");
    $this->db->bind('username', $username);
    return $this->db->single();
  }

  // Simpan ulang hash jika algoritma/cost bawaan PHP sudah lebih baru
  public function updatePassword(int $id, string $hash)
  {
    $this->db->query("UPDATE users SET password = :password WHERE id = :id");
    $this->db->bind('password', $hash);
    $this->db->bind('id', $id);
    return $this->db->execute();
  }
}
