<?php

namespace App\Models;

/**
 * Tabel `peminjaman`.
 */
final class Peminjaman extends Model
{
    public function find(int $id): ?array
    {
        return $this->db->fetchOne('SELECT * FROM peminjaman WHERE id = ? LIMIT 1', [$id]);
    }

    /** Baca baris peminjaman dan kunci (FOR UPDATE) di dalam transaksi. */
    public function findForUpdate(int $id, ?int $userId = null, ?string $status = null): ?array
    {
        $sql = 'SELECT * FROM peminjaman WHERE id = ?';
        $params = [$id];
        if ($userId !== null) {
            $sql .= ' AND user_id = ?';
            $params[] = $userId;
        }
        if ($status !== null) {
            $sql .= ' AND status = ?';
            $params[] = $status;
        }
        return $this->db->fetchOne($sql . ' LIMIT 1 FOR UPDATE', $params);
    }

    /** Detail lengkap (peminjam + buku + kategori). */
    public function detail(int $id): ?array
    {
        return $this->db->fetchOne(
            'SELECT p.*, u.nama_lengkap, u.username, u.gmail,
                    b.judul, b.pengarang, b.penerbit, b.gambar, k.Kategori
             FROM peminjaman p
             JOIN users u ON u.id = p.user_id
             JOIN buku b ON b.id = p.buku_id
             LEFT JOIN kategori k ON k.id = b.kategori_id
             WHERE p.id = ?
             LIMIT 1',
            [$id]
        );
    }

    /** Daftar transaksi. $userId null = semua (admin). */
    public function search(string $keyword = '', string $status = '', ?int $userId = null): array
    {
        $where = '1';
        $params = [];

        if ($userId !== null) {
            $where = 'p.user_id = ?';
            $params[] = $userId;
        }
        if ($keyword !== '') {
            $like = '%' . $keyword . '%';
            $where .= ' AND (u.nama_lengkap LIKE ? OR b.judul LIKE ?)';
            array_push($params, $like, $like);
        }
        if ($status !== '') {
            $where .= ' AND p.status = ?';
            $params[] = $status;
        }

        return $this->db->fetchAll(
            "SELECT p.*, u.nama_lengkap, b.judul, pg.tanggal_pengembalian AS tanggal_pengembalian_aktual
             FROM peminjaman p
             JOIN users u ON u.id = p.user_id
             JOIN buku b ON b.id = p.buku_id
             LEFT JOIN (
                 SELECT peminjaman_id, MAX(tanggal_pengembalian) AS tanggal_pengembalian
                 FROM pengembalian
                 GROUP BY peminjaman_id
             ) pg ON pg.peminjaman_id = p.id
             WHERE {$where}
             ORDER BY p.id DESC",
            $params
        );
    }

    public function recent(int $limit = 5): array
    {
        return $this->db->fetchAll(
            'SELECT p.*, u.nama_lengkap, b.judul
             FROM peminjaman p
             JOIN users u ON u.id = p.user_id
             JOIN buku b ON b.id = p.buku_id
             ORDER BY p.id DESC
             LIMIT ' . max(1, $limit)
        );
    }

    public function historyForUser(int $userId, int $limit = 10): array
    {
        return $this->db->fetchAll(
            'SELECT p.*, b.judul
             FROM peminjaman p
             JOIN buku b ON b.id = p.buku_id
             WHERE p.user_id = ?
             ORDER BY p.id DESC
             LIMIT ' . max(1, $limit),
            [$userId]
        );
    }

    /** Buku yang sedang dipinjam (menunggu dikembalikan). */
    public function borrowed(): array
    {
        return $this->db->fetchAll(
            "SELECT p.id, p.tanggal_pinjam, p.tanggal_kembali, u.nama_lengkap, b.judul
             FROM peminjaman p
             JOIN users u ON u.id = p.user_id
             JOIN buku b ON b.id = p.buku_id
             WHERE p.status = 'dipinjam'
             ORDER BY p.tanggal_kembali ASC"
        );
    }

    public function hasActiveRequest(int $userId, int $bukuId): bool
    {
        return $this->db->fetchOne(
            "SELECT id FROM peminjaman WHERE user_id = ? AND buku_id = ? AND status IN ('pengajuan','disetujui','dipinjam') LIMIT 1",
            [$userId, $bukuId]
        ) !== null;
    }

    public function hasActiveByUser(int $userId): bool
    {
        return $this->db->fetchOne(
            "SELECT id FROM peminjaman WHERE user_id = ? AND status IN ('pengajuan','disetujui','dipinjam') LIMIT 1",
            [$userId]
        ) !== null;
    }

    public function create(int $userId, int $bukuId, string $tanggalPinjam, string $tanggalKembali): int
    {
        return $this->db->insert(
            "INSERT INTO peminjaman (user_id, buku_id, tanggal_pinjam, tanggal_kembali, status) VALUES (?, ?, ?, ?, 'pengajuan')",
            [$userId, $bukuId, $tanggalPinjam, $tanggalKembali]
        );
    }

    public function updateStatus(int $id, string $status): void
    {
        $this->db->execute('UPDATE peminjaman SET status = ? WHERE id = ?', [$status, $id]);
    }

    /** Batalkan pengajuan milik siswa (hanya jika masih pengajuan / disetujui). */
    public function cancel(int $id, int $userId): bool
    {
        return $this->db->execute(
            "UPDATE peminjaman SET status = 'dibatalkan' WHERE id = ? AND user_id = ? AND status IN ('pengajuan','disetujui')",
            [$id, $userId]
        ) > 0;
    }

    /** Hapus satu transaksi (data pengembalian ikut terhapus lewat foreign key CASCADE). */
    public function delete(int $id): bool
    {
        return $this->db->execute('DELETE FROM peminjaman WHERE id = ?', [$id]) > 0;
    }

    /** Buat transaksi langsung dengan status tertentu (dipakai admin saat menambah transaksi). */
    public function createWithStatus(int $userId, int $bukuId, string $tanggalPinjam, string $tanggalKembali, string $status): int
    {
        return $this->db->insert(
            'INSERT INTO peminjaman (user_id, buku_id, tanggal_pinjam, tanggal_kembali, status) VALUES (?, ?, ?, ?, ?)',
            [$userId, $bukuId, $tanggalPinjam, $tanggalKembali, $status]
        );
    }

    public function count(): int
    {
        return (int) $this->db->fetchValue('SELECT COUNT(*) FROM peminjaman', [], 0);
    }

    public function countActive(): int
    {
        return (int) $this->db->fetchValue("SELECT COUNT(*) FROM peminjaman WHERE status IN ('disetujui','dipinjam')", [], 0);
    }
}
