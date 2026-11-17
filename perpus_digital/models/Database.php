<?php

namespace App\Models;

use mysqli;
use mysqli_stmt;
use RuntimeException;

/**
 * Pembungkus koneksi mysqli. Semua query memakai prepared statement.
 * Tipe parameter (i / d / s) ditentukan otomatis dari tipe nilai PHP.
 */
final class Database
{
    private static ?Database $instance = null;

    private mysqli $conn;

    private function __construct(array $config)
    {
        mysqli_report(MYSQLI_REPORT_OFF);

        $conn = @new mysqli(
            (string) $config['host'],
            (string) $config['user'],
            (string) $config['password'],
            (string) $config['database'],
            (int) $config['port']
        );

        if ($conn->connect_errno) {
            throw new RuntimeException('Koneksi database gagal: ' . $conn->connect_error);
        }

        $conn->set_charset((string) ($config['charset'] ?? 'utf8mb4'));
        $this->conn = $conn;
    }

    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self(Config::load('database'));
        }
        return self::$instance;
    }

    /** Ambil semua baris hasil SELECT sebagai array asosiatif. */
    public function fetchAll(string $sql, array $params = []): array
    {
        $stmt = $this->run($sql, $params);
        $result = $stmt->get_result();
        $rows = [];
        if ($result !== false) {
            $rows = $result->fetch_all(MYSQLI_ASSOC);
            $result->free();
        }
        $stmt->close();
        return $rows;
    }

    /** Ambil satu baris pertama (atau null). */
    public function fetchOne(string $sql, array $params = []): ?array
    {
        $rows = $this->fetchAll($sql, $params);
        return $rows[0] ?? null;
    }

    /** Ambil nilai kolom pertama dari baris pertama. */
    public function fetchValue(string $sql, array $params = [], mixed $default = null): mixed
    {
        $row = $this->fetchOne($sql, $params);
        if ($row === null) {
            return $default;
        }
        $value = reset($row);
        return $value === null ? $default : $value;
    }

    /** Jalankan INSERT/UPDATE/DELETE, kembalikan jumlah baris terpengaruh. */
    public function execute(string $sql, array $params = []): int
    {
        $stmt = $this->run($sql, $params);
        $affected = (int) $stmt->affected_rows;
        $stmt->close();
        return $affected;
    }

    /** Jalankan INSERT, kembalikan ID baru. */
    public function insert(string $sql, array $params = []): int
    {
        $stmt = $this->run($sql, $params);
        $id = (int) $this->conn->insert_id;
        $stmt->close();
        return $id;
    }

    /** Jalankan SQL mentah tanpa parameter (DDL / migrasi). Mengembalikan true bila berhasil. */
    public function statement(string $sql): bool
    {
        return $this->conn->query($sql) !== false;
    }

    public function beginTransaction(): void
    {
        $this->conn->begin_transaction();
    }

    public function commit(): void
    {
        $this->conn->commit();
    }

    public function rollback(): void
    {
        $this->conn->rollback();
    }

    private function run(string $sql, array $params): mysqli_stmt
    {
        $stmt = $this->conn->prepare($sql);
        if ($stmt === false) {
            throw new RuntimeException('Query gagal disiapkan: ' . $this->conn->error, (int) $this->conn->errno);
        }

        if ($params !== []) {
            $values = [];
            $types = '';
            foreach (array_values($params) as $value) {
                if (is_bool($value)) {
                    $value = (int) $value;
                }
                $types .= is_int($value) ? 'i' : (is_float($value) ? 'd' : 's');
                $values[] = $value;
            }
            $stmt->bind_param($types, ...$values);
        }

        if (!$stmt->execute()) {
            $message = $stmt->error;
            $code = (int) $stmt->errno;
            $stmt->close();
            throw new RuntimeException('Query gagal dijalankan: ' . $message, $code);
        }

        return $stmt;
    }
}
