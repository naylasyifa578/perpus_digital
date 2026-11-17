<?php

namespace App\Controllers;

use App\Models\Config;
use App\Models\Peminjaman;
use App\Models\PeminjamanService;

/**
 * Pengembalian buku.
 *  - Admin : mencatat pengembalian semua peminjaman aktif.
 *  - Siswa : melihat buku yang sedang dipinjam dan mengembalikannya sendiri.
 */
final class PengembalianController extends Controller
{
    public function index(): void
    {
        $this->requireLogin();

        if (!Auth::isAdmin()) {
            $this->siswa();
            return;
        }

        $this->render('pengembalian/index', [
            'title'        => 'Pengembalian',
            'active'       => 'pengembalian',
            'borrowed'     => (new Peminjaman($this->db))->borrowed(),
            'loanDays'     => (int) Config::get('app.loan_days', 14),
            'sanctionDays' => (int) Config::get('app.sanction_days', 14),
        ]);
    }

    /** Halaman Pengembalian untuk siswa (flowmap: menu Pengembalian buku). */
    private function siswa(): void
    {
        $this->requireRole('siswa');

        $loans = new Peminjaman($this->db);
        $userId = Auth::id();

        $this->render('pengembalian/siswa', [
            'title'    => 'Pengembalian',
            'active'   => 'pengembalian',
            'borrowed' => $loans->search('', 'dipinjam', $userId),
            'returned' => $loans->search('', 'dikembalikan', $userId),
            'loanDays' => (int) Config::get('app.loan_days', 14),
        ]);
    }

    public function process(): void
    {
        $this->requireRole('admin');
        $this->requirePost();

        $result = (new PeminjamanService($this->db))->returnLoan(
            $this->request->int('peminjaman_id'),
            $this->request->str('tanggal_pengembalian', date('Y-m-d')),
            $this->request->str('keterangan')
        );

        $this->flashResult($result);
        $this->redirect('pengembalian');
    }
}
