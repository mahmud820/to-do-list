<?php

class Database
{
    // Satu koneksi PDO dipakai bersama semua model dalam satu request
    private static ?PDO $pdo = null;

    private PDO $dbh;
    private PDOStatement $stmt;

    public function __construct()
    {
        $this->dbh = self::connect();
    }

    private static function connect(): PDO
    {
        if (self::$pdo !== null) {
            return self::$pdo;
        }

        $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4';

        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::MYSQL_ATTR_FOUND_ROWS => true,
            // Samakan zona waktu koneksi dengan PHP (lihat init.php) agar tanggal tidak selisih di server UTC
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET time_zone = '" . date('P') . "'",
        ];

        try {
            self::$pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            error_log('[Database connect] ' . $e->getMessage());
            http_response_code(500);
            die('Tidak dapat terhubung ke database.');
        }

        return self::$pdo;
    }

    public function query(string $query): void
    {
        $this->stmt = $this->dbh->prepare($query);
    }

    public function bind($param, $value, $type = null): void
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

    // Bind banyak parameter sekaligus: ['nama' => nilai, ':lain' => nilai]
    public function bindAll(array $params): void
    {
        foreach ($params as $key => $value) {
            $this->bind($key, $value);
        }
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

    // Jalankan $callback di dalam transaksi.
    // COMMIT jika hasilnya bukan false; ROLLBACK jika false atau terjadi exception.
    // Mengembalikan hasil $callback, atau false jika dibatalkan.
    public function transaction(callable $callback)
    {
        $this->dbh->beginTransaction();

        try {
            $result = $callback();
        } catch (Throwable $e) {
            error_log('[Database transaction] ' . $e->getMessage());
            $this->rollBack();
            return false;
        }

        if ($result === false) {
            $this->rollBack();
            return false;
        }

        $this->dbh->commit();
        return $result;
    }

    private function rollBack(): void
    {
        if ($this->dbh->inTransaction()) {
            $this->dbh->rollBack();
        }
    }
}
