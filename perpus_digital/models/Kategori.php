<?php

namespace App\Models;

/**
 * Tabel `kategori`.
 */
final class Kategori extends Model
{
    public function find(int $id): ?array
    {
        return $this->db->fetchOne('SELECT * FROM kategori WHERE id = ? LIMIT 1', [$id]);
    }

    /** Untuk dropdown. */
    public function all(): array
    {
        return $this->db->fetchAll('SELECT id, Kategori FROM kategori ORDER BY Kategori');
    }

    /** Daftar kategori + jumlah buku (halaman admin, dengan pencarian). */
    public function search(string $keyword = ''): array
    {
        return $this->db->fetchAll(
            'SELECT k.*, COUNT(b.id) AS jumlah_buku
             FROM kategori k
             LEFT JOIN buku b ON b.kategori_id = k.id
             WHERE k.Kategori LIKE ?
             GROUP BY k.id
             ORDER BY k.id',
            ['%' . $keyword . '%']
        );
    }

    /** Daftar kategori + jumlah buku (halaman siswa). */
    public function allWithCount(): array
    {
        return $this->db->fetchAll(
            'SELECT k.id, k.Kategori, k.Deskripsi, k.gambar, COUNT(b.id) AS jumlah
             FROM kategori k
             LEFT JOIN buku b ON b.kategori_id = k.id
             GROUP BY k.id, k.Kategori, k.Deskripsi, k.gambar
             ORDER BY k.Kategori'
        );
    }

    public function create(string $nama, string $deskripsi, string $gambar = ''): int
    {
        return $this->db->insert(
            'INSERT INTO kategori (Kategori, Deskripsi, gambar) VALUES (?, ?, ?)',
            [$nama, $deskripsi, $gambar]
        );
    }

    public function update(int $id, string $nama, string $deskripsi, string $gambar = ''): void
    {
        $this->db->execute(
            'UPDATE kategori SET Kategori = ?, Deskripsi = ?, gambar = ? WHERE id = ?',
            [$nama, $deskripsi, $gambar, $id]
        );
    }

    public function delete(int $id): bool
    {
        return $this->db->execute('DELETE FROM kategori WHERE id = ?', [$id]) > 0;
    }
}
