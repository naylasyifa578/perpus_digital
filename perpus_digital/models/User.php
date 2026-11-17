<?php

namespace App\Models;

/**
 * Tabel `users` (admin dan siswa).
 */
final class User extends Model
{
    private const COLUMNS = 'id, username, password, nama_lengkap, role, gmail, foto_profil, created_at, blocked_until';

    public function find(int $id): ?array
    {
        return $this->db->fetchOne('SELECT ' . self::COLUMNS . ' FROM users WHERE id = ? LIMIT 1', [$id]);
    }

    public function findSiswa(int $id): ?array
    {
        return $this->db->fetchOne(
            "SELECT " . self::COLUMNS . " FROM users WHERE id = ? AND role = 'siswa' LIMIT 1",
            [$id]
        );
    }

    /** Cari berdasarkan username ATAU email (untuk login). */
    public function findByLogin(string $login): ?array
    {
        return $this->db->fetchOne(
            'SELECT ' . self::COLUMNS . ' FROM users WHERE username = ? OR gmail = ? LIMIT 1',
            [$login, $login]
        );
    }

    /** Apakah username / email sudah dipakai akun lain? */
    public function isTaken(string $username, string $gmail, int $exceptId = 0): bool
    {
        $row = $this->db->fetchOne(
            'SELECT id FROM users WHERE (username = ? OR gmail = ?) AND id <> ? LIMIT 1',
            [$username, $gmail, $exceptId]
        );
        return $row !== null;
    }

    public function createSiswa(string $username, string $passwordHash, string $nama, string $gmail): int
    {
        return $this->createUser($username, $passwordHash, $nama, $gmail, 'siswa');
    }

    /** Buat akun baru dengan role 'siswa' atau 'admin'. */
    public function createUser(string $username, string $passwordHash, string $nama, string $gmail, string $role): int
    {
        $role = $role === 'admin' ? 'admin' : 'siswa';
        return $this->db->insert(
            'INSERT INTO users (username, password, nama_lengkap, role, gmail) VALUES (?, ?, ?, ?, ?)',
            [$username, $passwordHash, $nama, $role, $gmail]
        );
    }

    public function updateSiswa(int $id, string $nama, string $username, string $gmail, ?string $passwordHash = null): void
    {
        if ($passwordHash !== null) {
            $this->db->execute(
                "UPDATE users SET nama_lengkap = ?, username = ?, gmail = ?, password = ? WHERE id = ? AND role = 'siswa'",
                [$nama, $username, $gmail, $passwordHash, $id]
            );
            return;
        }
        $this->db->execute(
            "UPDATE users SET nama_lengkap = ?, username = ?, gmail = ? WHERE id = ? AND role = 'siswa'",
            [$nama, $username, $gmail, $id]
        );
    }

    public function updateProfile(int $id, string $nama, string $gmail): void
    {
        $this->db->execute('UPDATE users SET nama_lengkap = ?, gmail = ? WHERE id = ?', [$nama, $gmail, $id]);
    }

    public function updatePassword(int $id, string $passwordHash): void
    {
        $this->db->execute('UPDATE users SET password = ? WHERE id = ?', [$passwordHash, $id]);
    }

    public function updatePhoto(int $id, string $path): void
    {
        $this->db->execute('UPDATE users SET foto_profil = ? WHERE id = ?', [$path, $id]);
    }

    public function deleteSiswa(int $id): void
    {
        $this->db->execute("DELETE FROM users WHERE id = ? AND role = 'siswa'", [$id]);
    }

    public function blockedUntil(int $id): ?string
    {
        $value = $this->db->fetchValue('SELECT blocked_until FROM users WHERE id = ? LIMIT 1', [$id]);
        return $value === null ? null : (string) $value;
    }

    public function setBlockedUntil(int $id, string $date): void
    {
        $this->db->execute('UPDATE users SET blocked_until = ? WHERE id = ?', [$date, $id]);
    }

    /** Daftar siswa beserta jumlah transaksinya (dengan pencarian). */
    public function listSiswa(string $keyword = ''): array
    {
        $like = '%' . $keyword . '%';
        return $this->db->fetchAll(
            "SELECT u.id, u.username, u.nama_lengkap, u.gmail, u.created_at, COUNT(p.id) AS transaksi
             FROM users u
             LEFT JOIN peminjaman p ON p.user_id = u.id
             WHERE u.role = 'siswa'
               AND (u.nama_lengkap LIKE ? OR u.username LIKE ? OR u.gmail LIKE ?)
             GROUP BY u.id, u.username, u.nama_lengkap, u.gmail, u.created_at
             ORDER BY u.id DESC",
            [$like, $like, $like]
        );
    }

    public function countSiswa(): int
    {
        return (int) $this->db->fetchValue("SELECT COUNT(*) FROM users WHERE role = 'siswa'", [], 0);
    }

    /** @return int[] */
    public function adminIds(): array
    {
        $rows = $this->db->fetchAll("SELECT id FROM users WHERE role = 'admin'");
        return array_map(static fn(array $row): int => (int) $row['id'], $rows);
    }
}
