<?php

class Database
{
    private string $host = DB_HOST;
    private string $user = DB_USER;
    private string $pass = DB_PASS;
    private string $db_name = DB_NAME;

    private PDO $dbh;
    private PDOStatement $stmt;

    public function __construct()
    {
        // data source name
        $dsn = 'mysql:host=' . $this->host . ';dbname=' . $this->db_name . ';charset=utf8mb4';

        $option = [
            PDO::ATTR_PERSISTENT => true,
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            // Samakan zona waktu koneksi dengan PHP (Asia/Jakarta) agar tanggal tidak selisih di server UTC
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET time_zone = '+07:00'"
        ];

        try {
            $this->dbh = new PDO($dsn, $this->user, $this->pass, $option);
        } catch (PDOException $e) {
            error_log('[Database connect] ' . $e->getMessage());
            http_response_code(500);
            die('Tidak dapat terhubung ke database.');
        }
    }

    public function query(string $query): void
    {
        $this->stmt = $this->dbh->prepare($query);
    }

    public function bind($param, $value, $type = null)
    {
        // normalisasi nama parameter: tambahkan ':' jika tidak ada
        if (is_string($param) && strpos($param, ':') !== 0) {
            $param = ':' . $param;
        }

        if (is_null($type)) {
            switch (true) {
                case is_int($value):
                    $type = PDO::PARAM_INT;
                    break;

                case is_bool($value):
                    $type = PDO::PARAM_BOOL;
                    break;

                case is_null($value):
                    $type = PDO::PARAM_NULL;
                    break;

                default:
                    $type = PDO::PARAM_STR;
            }
        }

        $this->stmt->bindValue($param, $value, $type);
    }

    public function execute(): bool
    {
        try {
            return $this->stmt->execute();
        } catch (PDOException $e) {

            // log agar mudah didiagnosa tanpa menampilkan ke user
            error_log('[Database execute] ' . $e->getMessage());

            return false;
        }
    }

    public function resultSet()
    {
        if (!$this->execute()) {
            return [];
        }

        return $this->stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function single()
    {
        if (!$this->execute()) {
            return false;
        }

        return $this->stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function rowCount(): int
    {
        return $this->stmt->rowCount();
    }

    public function lastInsertId(): string
    {
        return $this->dbh->lastInsertId();
    }

    public function beginTransaction(): bool
    {
        return $this->dbh->beginTransaction();
    }

    public function commit(): bool
    {
        return $this->dbh->commit();
    }

    public function rollBack(): bool
    {
        return $this->dbh->inTransaction() ? $this->dbh->rollBack() : false;
    }
}
