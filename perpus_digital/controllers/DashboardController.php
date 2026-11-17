<?php

namespace App\Controllers;

use App\Models\Buku;
use App\Models\Notifikasi;
use App\Models\Peminjaman;
use App\Models\SanctionService;
use App\Models\User;

/**
 * Dashboard admin dan dashboard siswa.
 */
final class DashboardController extends Controller
{
    public function index(): void
    {
        $this->requireLogin();

        if (Auth::isAdmin()) {
            $this->admin();
            return;
        }
        $this->siswa();
    }

    private function admin(): void
    {
        $books = new Buku($this->db);
        $loans = new Peminjaman($this->db);
        $users = new User($this->db);

        $this->render('dashboard/admin', [
            'title'  => 'Dashboard',
            'active' => 'dashboard',
            'stats'  => [
                'buku'      => $books->count(),
                'stok'      => $books->totalStock(),
                'anggota'   => $users->countSiswa(),
                'transaksi' => $loans->count(),
                'aktif'     => $loans->countActive(),
            ],
            'recent' => $loans->recent(5),
        ]);
    }

    private function siswa(): void
    {
        $userId = Auth::id();
        $sanctions = new SanctionService($this->db);
        $sanctions->notifyExpiredIfNeeded($userId);

        $blockedUntil = $sanctions->blockedUntil($userId);
        $sanctionNotif = (new Notifikasi($this->db))->latestUnread($userId, 'sanksi');

        $this->render('dashboard/siswa', [
            'title'         => 'Dashboard',
            'active'        => 'dashboard',
            'blockedUntil'  => $blockedUntil,
            'sanctionDays'  => $sanctions->days(),
            'sanctionNotif' => $blockedUntil !== null ? $sanctionNotif : null,
        ]);
    }
}
