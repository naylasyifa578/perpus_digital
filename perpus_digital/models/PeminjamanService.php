<?php

namespace App\Models;

use App\View\Format;
use DateTime;
use Throwable;

/**
 * Aturan bisnis peminjaman: pengajuan, pembatalan, perubahan status, dan pengembalian.
 */
final class PeminjamanService
{
    private Database $db;

    private Peminjaman $loans;

    private Pengembalian $returns;

    private Buku $books;

    private Notifikasi $notifications;

    private SanctionService $sanctions;

    public function __construct(Database $db)
    {
        $this->db = $db;
        $this->loans = new Peminjaman($db);
        $this->returns = new Pengembalian($db);
        $this->books = new Buku($db);
        $this->notifications = new Notifikasi($db);
        $this->sanctions = new SanctionService($db);
    }

    /** Siswa mengajukan peminjaman buku. */
    public function request(int $userId, string $userName, int $bukuId, string $tanggalPinjam): Result
    {
        $blocked = $this->sanctions->blockedUntil($userId);
        if ($blocked !== null) {
            return Result::fail('Peminjaman Anda sedang dibatasi sampai ' . Format::date($blocked) . '.');
        }
        if (!self::isValidDate($tanggalPinjam)) {
            return Result::fail('Tanggal pinjam tidak valid.', ['redirect' => 'katalog']);
        }
        if ($tanggalPinjam < date('Y-m-d')) {
            return Result::fail('Tanggal pinjam tidak boleh sebelum hari ini.', ['redirect' => 'katalog']);
        }

        $stock = $this->books->stockOf($bukuId);
        if ($stock === null || $stock < 1) {
            return Result::fail('Stok buku tidak tersedia.');
        }
        if ($this->loans->hasActiveRequest($userId, $bukuId)) {
            return Result::fail('Anda sudah memiliki pengajuan aktif untuk buku ini.');
        }

        try {
            $loanId = $this->loans->create($userId, $bukuId, $tanggalPinjam, Format::dueDate($tanggalPinjam));
        } catch (Throwable $e) {
            return Result::fail('Pengajuan gagal: ' . $e->getMessage());
        }

        $this->notifications->create(
            $userId,
            'peminjaman',
            'Pengajuan Peminjaman Dikirim',
            'Pengajuan peminjaman buku berhasil dikirim dan menunggu proses admin.',
            $loanId
        );
        $this->notifications->notifyAdmins(
            'peminjaman',
            'Pengajuan Peminjaman Baru',
            'Ada pengajuan peminjaman baru dari ' . ($userName !== '' ? $userName : 'siswa') . '.',
            $loanId
        );

        return Result::ok('Pengajuan peminjaman dikirim.');
    }

    /**
     * Admin menambah transaksi untuk seorang siswa.
     * Tanggal pinjam <= hari ini  => langsung berstatus 'dipinjam' (stok berkurang).
     * Tanggal pinjam di masa depan => berstatus 'disetujui' (stok berkurang saat ditandai dipinjam).
     */
    public function createByAdmin(int $userId, int $bukuId, string $tanggalPinjam): Result
    {
        if (!self::isValidDate($tanggalPinjam)) {
            return Result::fail('Tanggal pinjam tidak valid.');
        }

        $member = (new User($this->db))->findSiswa($userId);
        if ($member === null) {
            return Result::fail('Pilih anggota yang valid.');
        }
        if ($this->sanctions->blockedUntil($userId) !== null) {
            return Result::fail('Peminjaman anggota ini sedang dibatasi (sanksi keterlambatan).');
        }

        $this->db->beginTransaction();
        try {
            $stock = $this->books->stockOf($bukuId);
            if ($stock === null) {
                $this->db->rollback();
                return Result::fail('Pilih buku yang valid.');
            }
            if ($stock < 1) {
                $this->db->rollback();
                return Result::fail('Stok buku tidak tersedia.');
            }
            if ($this->loans->hasActiveRequest($userId, $bukuId)) {
                $this->db->rollback();
                return Result::fail('Anggota ini sudah memiliki transaksi aktif untuk buku tersebut.');
            }

            $status = $tanggalPinjam <= date('Y-m-d') ? 'dipinjam' : 'disetujui';
            $loanId = $this->loans->createWithStatus($userId, $bukuId, $tanggalPinjam, Format::dueDate($tanggalPinjam), $status);

            if ($status === 'dipinjam' && !$this->books->decrementStock($bukuId)) {
                $this->db->rollback();
                return Result::fail('Stok buku tidak mencukupi.');
            }
            $this->db->commit();
        } catch (Throwable $e) {
            $this->db->rollback();
            return Result::fail('Transaksi gagal disimpan: ' . $e->getMessage());
        }

        $this->notifications->create(
            $userId,
            'peminjaman',
            'Peminjaman Dicatat Admin',
            'Admin mencatat peminjaman buku atas nama kamu (status: ' . Format::statusLabel($status) . ').',
            $loanId
        );

        return Result::ok('Transaksi berhasil ditambahkan dengan status ' . Format::statusLabel($status) . '.');
    }

    /**
     * Hapus transaksi.
     *  - Admin ($userId = null): boleh menghapus semua, kecuali yang masih 'dipinjam'
     *    (buku belum kembali, stok akan tidak sinkron).
     *  - Siswa: hanya riwayat miliknya yang berstatus 'dibatalkan' atau 'ditolak'.
     */
    public function delete(int $loanId, ?int $userId = null): Result
    {
        $this->db->beginTransaction();
        try {
            $loan = $this->loans->findForUpdate($loanId, $userId);

            if ($loan === null) {
                $this->db->rollback();
                return Result::fail('Transaksi tidak ditemukan.');
            }
            if ($loan['status'] === 'dipinjam') {
                $this->db->rollback();
                return Result::fail('Transaksi berstatus Dipinjam tidak dapat dihapus. Proses pengembalian terlebih dahulu.');
            }
            if ($userId !== null && !in_array($loan['status'], ['dibatalkan', 'ditolak'], true)) {
                $this->db->rollback();
                return Result::fail('Siswa hanya dapat menghapus riwayat yang dibatalkan atau ditolak.');
            }

            $this->loans->delete($loanId);
            $this->db->commit();
        } catch (Throwable $e) {
            $this->db->rollback();
            return Result::fail('Transaksi gagal dihapus: ' . $e->getMessage());
        }

        return Result::ok('Transaksi berhasil dihapus.');
    }

    /** Siswa membatalkan pengajuan miliknya. */
    public function cancel(int $loanId, int $userId): Result
    {
        $loan = $this->loans->find($loanId);
        if ($loan === null || (int) $loan['user_id'] !== $userId) {
            return Result::fail('Transaksi tidak ditemukan.');
        }
        if (!in_array($loan['status'], ['pengajuan', 'disetujui'], true)) {
            return Result::fail('Peminjaman ini tidak dapat dibatalkan.');
        }
        if (!$this->loans->cancel($loanId, $userId)) {
            return Result::fail('Gagal membatalkan peminjaman.');
        }
        return Result::ok('Pengajuan peminjaman berhasil dibatalkan.');
    }

    /** Admin mengubah status: disetujui / dipinjam / ditolak. */
    public function changeStatus(int $loanId, string $newStatus): Result
    {
        $allowed = ['disetujui', 'dipinjam', 'ditolak'];
        $transitions = [
            'pengajuan' => ['disetujui', 'dipinjam', 'ditolak'],
            'disetujui' => ['dipinjam', 'ditolak'],
        ];

        $this->db->beginTransaction();
        try {
            $loan = $this->loans->findForUpdate($loanId);

            if ($loan === null
                || !in_array($newStatus, $allowed, true)
                || !in_array($newStatus, $transitions[$loan['status']] ?? [], true)
            ) {
                $this->db->rollback();
                return Result::fail('Perubahan status tidak dapat dilakukan.');
            }

            if ($newStatus === 'dipinjam') {
                if ($loan['tanggal_pinjam'] > date('Y-m-d')) {
                    $this->db->rollback();
                    return Result::fail('Buku baru dapat ditandai dipinjam mulai tanggal pinjam.');
                }
                if (!$this->books->decrementStock((int) $loan['buku_id'])) {
                    $this->db->rollback();
                    return Result::fail('Stok buku tidak mencukupi.');
                }
            }

            $this->loans->updateStatus($loanId, $newStatus);
            $this->db->commit();
        } catch (Throwable $e) {
            $this->db->rollback();
            return Result::fail('Perubahan status gagal: ' . $e->getMessage());
        }

        $userId = (int) $loan['user_id'];
        if ($newStatus === 'disetujui') {
            $this->notifications->create($userId, 'peminjaman', 'Peminjaman Disetujui', 'Pengajuan peminjaman kamu telah disetujui.', $loanId);
        } elseif ($newStatus === 'ditolak') {
            $this->notifications->create($userId, 'peminjaman', 'Peminjaman Ditolak', 'Pengajuan peminjaman kamu ditolak oleh admin.', $loanId);
        } else {
            $this->notifications->create($userId, 'peminjaman', 'Buku Ditandai Dipinjam', 'Status peminjaman buku kamu telah diperbarui menjadi dipinjam.', $loanId);
        }

        return Result::ok('Status peminjaman diperbarui.');
    }

    /**
     * Catat pengembalian buku.
     * $userId diisi bila siswa mengembalikan sendiri (hanya boleh miliknya); null bila dicatat admin.
     */
    public function returnLoan(int $loanId, string $tanggal, string $keterangan, ?int $userId = null): Result
    {
        $byAdmin = $userId === null;

        if (!self::isValidDate($tanggal)) {
            return Result::fail('Tanggal pengembalian tidak valid.');
        }
        if ($tanggal > date('Y-m-d')) {
            return Result::fail('Tanggal pengembalian tidak boleh melebihi hari ini.');
        }

        $this->db->beginTransaction();
        try {
            $loan = $this->loans->findForUpdate($loanId, $userId, 'dipinjam');

            if ($loan === null) {
                $this->db->rollback();
                return Result::fail($byAdmin
                    ? 'Peminjaman tidak aktif atau sudah dikembalikan.'
                    : 'Transaksi tidak ditemukan atau sudah dikembalikan.');
            }
            if ($tanggal < $loan['tanggal_pinjam']) {
                $this->db->rollback();
                return Result::fail($byAdmin
                    ? 'Tanggal pengembalian tidak boleh sebelum tanggal pinjam.'
                    : 'Buku belum masuk tanggal pinjam yang dipilih.');
            }

            $this->returns->create($loanId, $tanggal, 0.0, $keterangan);
            $this->loans->updateStatus($loanId, 'dikembalikan');
            $this->books->incrementStock((int) $loan['buku_id']);
            $lateDays = $this->sanctions->applyIfLate(
                (int) $loan['user_id'],
                Format::dueDate((string) $loan['tanggal_pinjam']),
                $tanggal
            );
            $this->db->commit();
        } catch (Throwable $e) {
            $this->db->rollback();
            return Result::fail('Gagal mencatat pengembalian: ' . $e->getMessage());
        }

        $borrowerId = (int) $loan['user_id'];
        $days = $this->sanctions->days();

        if ($lateDays > 0) {
            $this->notifications->create(
                $borrowerId,
                'sanksi',
                'Terdeteksi Keterlambatan',
                $this->sanctions->lateMessage($tanggal),
                $loanId
            );
            return Result::ok($byAdmin
                ? 'Pengembalian berhasil. Peminjam terlambat ' . $lateDays . ' hari dan otomatis dikenai sanksi peminjaman selama ' . $days . ' hari.'
                : 'Buku berhasil dikembalikan. Sanksi peminjaman aktif selama ' . $days . ' hari.');
        }

        $this->notifications->create($borrowerId, 'pengembalian', 'Buku Berhasil Dikembalikan', 'Pengembalian buku berhasil dicatat.', $loanId);
        return Result::ok($byAdmin ? 'Pengembalian berhasil dicatat.' : 'Buku berhasil dikembalikan.');
    }

    private static function isValidDate(string $date): bool
    {
        $parsed = DateTime::createFromFormat('Y-m-d', $date);
        return $parsed !== false && $parsed->format('Y-m-d') === $date;
    }
}
