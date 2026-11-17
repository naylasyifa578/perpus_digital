<?php

namespace App\Models;

use App\View\Format;

/**
 * Aturan sanksi keterlambatan: peminjaman dibekukan beberapa hari.
 */
final class SanctionService
{
    private User $users;

    private Notifikasi $notifications;

    public function __construct(Database $db)
    {
        $this->users = new User($db);
        $this->notifications = new Notifikasi($db);
    }

    public function days(): int
    {
        return (int) Config::get('app.sanction_days', 14);
    }

    /** Tanggal sanksi berakhir; null bila siswa tidak sedang disanksi. */
    public function blockedUntil(int $userId): ?string
    {
        $until = $this->users->blockedUntil($userId);
        if ($until === null || $until === '') {
            return null;
        }
        return $until < date('Y-m-d') ? null : $until;
    }

    /** Tanggal berakhirnya sanksi bila dikenakan pada $returnDate. */
    public function endDate(string $returnDate): string
    {
        return date('Y-m-d', strtotime($returnDate . ' +' . $this->days() . ' days'));
    }

    /**
     * Beri sanksi jika pengembalian melewati jatuh tempo.
     *
     * @return int jumlah hari terlambat (0 = tidak terlambat)
     */
    public function applyIfLate(int $userId, string $dueDate, string $returnDate): int
    {
        $lateDays = max(0, (int) ((strtotime($returnDate) - strtotime($dueDate)) / 86400));
        if ($lateDays > 0) {
            $this->users->setBlockedUntil($userId, $this->endDate($returnDate));
        }
        return $lateDays;
    }

    /** Buat notifikasi "sanksi berakhir" satu kali setelah masa sanksi lewat. */
    public function notifyExpiredIfNeeded(int $userId): void
    {
        $until = $this->users->blockedUntil($userId);
        if ($until === null || $until === '' || $until >= date('Y-m-d')) {
            return;
        }

        $refId = (int) strtotime($until);
        if ($this->notifications->existsByRef($userId, 'sanksi_berakhir', $refId)) {
            return;
        }

        $this->notifications->create(
            $userId,
            'sanksi_berakhir',
            'Sanksi Berakhir',
            'Masa sanksi telah berakhir. Anda sudah dapat meminjam buku kembali.',
            $refId
        );
    }

    /** Teks notifikasi sanksi. */
    public function lateMessage(string $returnDate): string
    {
        return 'Pengembalian buku kamu terdeteksi terlambat, peminjaman dinonaktifkan selama '
            . $this->days() . ' hari sampai ' . Format::date($this->endDate($returnDate)) . '.';
    }
}
