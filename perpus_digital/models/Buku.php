<?php

namespace App\Models;

/**
 * Tabel `buku`.
 */
final class Buku extends Model
{
    public function find(int $id): ?array
    {
        return $this->db->fetchOne('SELECT * FROM buku WHERE id = ? LIMIT 1', [$id]);
    }

    /** Daftar buku untuk admin (cari judul/pengarang/ISBN + filter kategori). */
    public function search(string $keyword = '', int $kategoriId = 0): array
    {
        $sql = 'SELECT b.*, k.Kategori FROM buku b LEFT JOIN kategori k ON k.id = b.kategori_id WHERE 1';
        $params = [];

        if ($keyword !== '') {
            $like = '%' . $keyword . '%';
            $sql .= ' AND (b.judul LIKE ? OR b.pengarang LIKE ? OR b.isbn LIKE ?)';
            array_push($params, $like, $like, $like);
        }
        if ($kategoriId > 0) {
            $sql .= ' AND b.kategori_id = ?';
            $params[] = $kategoriId;
        }

        return $this->db->fetchAll($sql . ' ORDER BY b.id DESC', $params);
    }

    /** Katalog untuk siswa, lengkap dengan penanda apakah buku sudah di koleksinya. */
    public function catalog(int $userId, string $keyword = '', int $kategoriId = 0): array
    {
        $sql = 'SELECT b.*, k.Kategori, CASE WHEN kb.id IS NULL THEN 0 ELSE 1 END AS in_collection
                FROM buku b
                LEFT JOIN kategori k ON k.id = b.kategori_id
                LEFT JOIN koleksi_buku kb ON kb.buku_id = b.id AND kb.user_id = ?
                WHERE 1';
        $params = [$userId];

        if ($keyword !== '') {
            $sql .= ' AND b.judul LIKE ?';
            $params[] = '%' . $keyword . '%';
        }
        if ($kategoriId > 0) {
            $sql .= ' AND b.kategori_id = ?';
            $params[] = $kategoriId;
        }

        return $this->db->fetchAll($sql . ' ORDER BY b.judul ASC', $params);
    }

    public function create(array $data): int
    {
        return $this->db->insert(
            'INSERT INTO buku (judul, pengarang, penerbit, tahun_terbit, isbn, jumlah_stok, kategori_id, gambar)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)',
            [
                $data['judul'], $data['pengarang'], $data['penerbit'], (int) $data['tahun_terbit'],
                $data['isbn'], (int) $data['jumlah_stok'], (int) $data['kategori_id'], $data['gambar'],
            ]
        );
    }

    public function update(int $id, array $data): void
    {
        $this->db->execute(
            'UPDATE buku SET judul = ?, pengarang = ?, penerbit = ?, tahun_terbit = ?, isbn = ?,
                    jumlah_stok = ?, kategori_id = ?, gambar = ? WHERE id = ?',
            [
                $data['judul'], $data['pengarang'], $data['penerbit'], (int) $data['tahun_terbit'],
                $data['isbn'], (int) $data['jumlah_stok'], (int) $data['kategori_id'], $data['gambar'], $id,
            ]
        );
    }

    public function delete(int $id): bool
    {
        return $this->db->execute('DELETE FROM buku WHERE id = ?', [$id]) > 0;
    }

    public function count(): int
    {
        return (int) $this->db->fetchValue('SELECT COUNT(*) FROM buku', [], 0);
    }

    public function totalStock(): int
    {
        return (int) $this->db->fetchValue('SELECT COALESCE(SUM(jumlah_stok), 0) FROM buku', [], 0);
    }

    public function stockOf(int $id): ?int
    {
        $value = $this->db->fetchValue('SELECT jumlah_stok FROM buku WHERE id = ?', [$id]);
        return $value === null ? null : (int) $value;
    }

    /** Kurangi stok 1; false bila stok sudah habis. */
    public function decrementStock(int $id): bool
    {
        return $this->db->execute('UPDATE buku SET jumlah_stok = jumlah_stok - 1 WHERE id = ? AND jumlah_stok > 0', [$id]) === 1;
    }

    public function incrementStock(int $id): void
    {
        $this->db->execute('UPDATE buku SET jumlah_stok = jumlah_stok + 1 WHERE id = ?', [$id]);
    }
}
