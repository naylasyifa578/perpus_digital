<?php

namespace App\Models;

/**
 * Tabel `koleksi_buku` (buku yang disimpan siswa).
 */
final class Koleksi extends Model
{
    public function add(int $userId, int $bukuId): void
    {
        $this->db->execute('INSERT IGNORE INTO koleksi_buku (user_id, buku_id) VALUES (?, ?)', [$userId, $bukuId]);
    }

    public function remove(int $userId, int $bukuId): void
    {
        $this->db->execute('DELETE FROM koleksi_buku WHERE user_id = ? AND buku_id = ?', [$userId, $bukuId]);
    }

    public function listForUser(int $userId, string $keyword = ''): array
    {
        $like = '%' . $keyword . '%';
        return $this->db->fetchAll(
            'SELECT b.*, k.Kategori, kb.created_at AS saved_at
             FROM koleksi_buku kb
             JOIN buku b ON b.id = kb.buku_id
             LEFT JOIN kategori k ON k.id = b.kategori_id
             WHERE kb.user_id = ? AND (b.judul LIKE ? OR b.pengarang LIKE ?)
             ORDER BY kb.created_at DESC',
            [$userId, $like, $like]
        );
    }
}
