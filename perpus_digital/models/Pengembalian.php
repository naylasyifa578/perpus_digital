<?php

namespace App\Models;

/**
 * Tabel `pengembalian`.
 */
final class Pengembalian extends Model
{
    public function create(int $peminjamanId, string $tanggal, float $denda, string $keterangan): int
    {
        // Kolom id_user adalah primary key AUTO_INCREMENT, jadi tidak diisi manual.
        return $this->db->insert(
            'INSERT INTO pengembalian (peminjaman_id, tanggal_pengembalian, denda, keterangan) VALUES (?, ?, ?, ?)',
            [$peminjamanId, $tanggal, $denda, $keterangan]
        );
    }

    public function latestFor(int $peminjamanId): ?array
    {
        return $this->db->fetchOne(
            'SELECT * FROM pengembalian WHERE peminjaman_id = ? ORDER BY tanggal_pengembalian DESC LIMIT 1',
            [$peminjamanId]
        );
    }
}
