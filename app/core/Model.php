<?php

// Induk semua model: menyediakan koneksi database yang sama untuk seluruh model.
abstract class Model
{
    protected Database $db;

    public function __construct()
    {
        $this->db = new Database;
    }
}
