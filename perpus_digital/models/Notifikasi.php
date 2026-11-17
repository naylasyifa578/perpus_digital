<?php

namespace App\Models;

/**
 * Tabel `notifikasi`.
 */
final class Notifikasi extends Model
{
    public function create(int $userId, string $type, string $title, string $message, ?int $refId = null): void
    {
        $this->db->insert(
            'INSERT INTO notifikasi (user_id, type, ref_id, title, message) VALUES (?, ?, ?, ?, ?)',
            [$userId, $type, $refId, $title, $message]
        );
    }

    /** Kirim notifikasi ke semua admin. */
    public function notifyAdmins(string $type, string $title, string $message, ?int $refId = null): void
    {
        $users = new User($this->db);
        foreach ($users->adminIds() as $adminId) {
            $this->create($adminId, $type, $title, $message, $refId);
        }
    }

    public function listForUser(int $userId, int $limit = 100): array
    {
        return $this->db->fetchAll(
            'SELECT * FROM notifikasi WHERE user_id = ? ORDER BY created_at DESC, id DESC LIMIT ' . max(1, $limit),
            [$userId]
        );
    }

    public function unreadCount(int $userId): int
    {
        return (int) $this->db->fetchValue('SELECT COUNT(*) FROM notifikasi WHERE user_id = ? AND is_read = 0', [$userId], 0);
    }

    public function markRead(int $id, int $userId): void
    {
        $this->db->execute('UPDATE notifikasi SET is_read = 1 WHERE id = ? AND user_id = ?', [$id, $userId]);
    }

    public function markAllRead(int $userId): void
    {
        $this->db->execute('UPDATE notifikasi SET is_read = 1 WHERE user_id = ?', [$userId]);
    }

    /** Notifikasi sanksi terbaru yang belum dibaca (untuk popup dashboard siswa). */
    public function latestUnread(int $userId, string $type): ?array
    {
        return $this->db->fetchOne(
            'SELECT id, title, message FROM notifikasi WHERE user_id = ? AND type = ? AND is_read = 0 ORDER BY created_at DESC, id DESC LIMIT 1',
            [$userId, $type]
        );
    }

    public function existsByRef(int $userId, string $type, int $refId): bool
    {
        return $this->db->fetchOne(
            'SELECT id FROM notifikasi WHERE user_id = ? AND type = ? AND ref_id = ? LIMIT 1',
            [$userId, $type, $refId]
        ) !== null;
    }
}
