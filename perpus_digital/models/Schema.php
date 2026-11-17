<?php

namespace App\Models;

use Throwable;

/**
 * Memastikan struktur database sesuai kebutuhan aplikasi.
 *
 * Idempotent dan hanya berjalan sekali per sesi, sehingga database lama
 * (yang belum punya kolom/tabel fitur terbaru) otomatis disesuaikan.
 * Untuk instalasi baru cukup import database.sql.
 */
final class Schema
{
    public static function ensure(Database $db): void
    {
        if (Session::get('_schema_ok')) {
            return;
        }

        try {
            self::createCollectionTable($db);
            self::createNotificationTable($db);
            self::ensureLoanStatusEnum($db);
            self::addColumn($db, 'buku', 'gambar', 'VARCHAR(255) NULL AFTER kategori_id');
            self::addColumn($db, 'kategori', 'gambar', 'VARCHAR(255) NULL AFTER Deskripsi');
            self::addColumn($db, 'users', 'blocked_until', 'DATE NULL AFTER role');
            self::addColumn($db, 'users', 'foto_profil', 'VARCHAR(255) NULL AFTER gmail');
            self::normalizeDueDates($db);
        } catch (Throwable $e) {
            error_log('Schema::ensure gagal: ' . $e->getMessage());
        }

        Session::set('_schema_ok', true);
    }

    private static function columnExists(Database $db, string $table, string $column): bool
    {
        return $db->fetchValue(
            'SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ? LIMIT 1',
            [$table, $column]
        ) !== null;
    }

    private static function addColumn(Database $db, string $table, string $column, string $definition): void
    {
        if (!self::columnExists($db, $table, $column)) {
            $db->statement('ALTER TABLE `' . $table . '` ADD COLUMN `' . $column . '` ' . $definition);
        }
    }

    private static function createCollectionTable(Database $db): void
    {
        $db->statement(
            'CREATE TABLE IF NOT EXISTS koleksi_buku (
                id INT NOT NULL AUTO_INCREMENT,
                user_id INT NOT NULL,
                buku_id INT NOT NULL,
                created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                UNIQUE KEY uq_koleksi_user_buku (user_id, buku_id),
                KEY idx_koleksi_buku (buku_id),
                CONSTRAINT fk_koleksi_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
                CONSTRAINT fk_koleksi_buku FOREIGN KEY (buku_id) REFERENCES buku(id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
        );
    }

    private static function createNotificationTable(Database $db): void
    {
        $db->statement(
            'CREATE TABLE IF NOT EXISTS notifikasi (
                id INT NOT NULL AUTO_INCREMENT,
                user_id INT NOT NULL,
                type VARCHAR(40) NOT NULL,
                ref_id INT NULL,
                title VARCHAR(160) NOT NULL,
                message TEXT NOT NULL,
                is_read TINYINT(1) NOT NULL DEFAULT 0,
                created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                KEY idx_notif_user (user_id, is_read, created_at)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
        );
    }

    /** Status 'dibatalkan' dibutuhkan agar pembatalan siswa tercatat di riwayat. */
    private static function ensureLoanStatusEnum(Database $db): void
    {
        $type = $db->fetchValue(
            "SELECT COLUMN_TYPE FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'peminjaman' AND COLUMN_NAME = 'status'"
        );
        if (is_string($type) && strpos($type, "'dibatalkan'") === false) {
            $db->statement(
                "ALTER TABLE peminjaman MODIFY status ENUM('pengajuan','disetujui','dipinjam','dikembalikan','ditolak','dibatalkan') NOT NULL DEFAULT 'pengajuan'"
            );
        }
    }

    /** Menjaga data lama agar jatuh tempo selalu sesuai lama peminjaman. */
    private static function normalizeDueDates(Database $db): void
    {
        $days = (int) Config::get('app.loan_days', 14);
        $db->execute(
            'UPDATE peminjaman SET tanggal_kembali = DATE_ADD(tanggal_pinjam, INTERVAL ' . $days . ' DAY)
             WHERE tanggal_kembali <> DATE_ADD(tanggal_pinjam, INTERVAL ' . $days . ' DAY)'
        );
    }
}
