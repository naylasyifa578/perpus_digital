<?php

namespace App\Controllers;

use App\Models\Buku;
use App\Models\Config;
use App\Models\Peminjaman;
use App\Models\PeminjamanService;
use App\Models\Pengembalian;
use App\Models\SanctionService;
use App\Models\User;
use App\View\Format;

/**
 * Peminjaman buku: daftar (admin), riwayat (siswa), detail, dan aksi pengajuan/pembatalan/status.
 */
final class PeminjamanController extends Controller
{
    private const STATUSES = ['pengajuan', 'disetujui', 'dipinjam', 'dikembalikan', 'ditolak', 'dibatalkan'];

    private Peminjaman $loans;

    private PeminjamanService $service;

    public function __construct()
    {
        parent::__construct();
        $this->loans = new Peminjaman($this->db);
        $this->service = new PeminjamanService($this->db);
    }

    /** Daftar seluruh transaksi (admin). */
    public function admin(): void
    {
        $this->requireRole('admin');
        $this->listing('admin');
    }

    /** Riwayat peminjaman milik siswa. */
    public function riwayat(): void
    {
        $this->requireRole('siswa');
        $this->listing('siswa');
    }

    public function detail(): void
    {
        $this->requireLogin();

        $id = $this->request->int('id');
        $loan = $id > 0 ? $this->loans->detail($id) : null;

        if ($loan === null || (!Auth::isAdmin() && (int) $loan['user_id'] !== Auth::id())) {
            $this->abort(404, 'Data peminjaman tidak ditemukan.');
        }

        $returned = (new Pengembalian($this->db))->latestFor($id);

        $sanction = 'Tidak ada';
        if ($returned !== null) {
            $dueDate = Format::dueDate((string) $loan['tanggal_pinjam']);
            $lateDays = (int) ((strtotime((string) $returned['tanggal_pengembalian']) - strtotime($dueDate)) / 86400);
            if ($lateDays > 0) {
                $sanction = 'Peminjaman dinonaktifkan ' . (new SanctionService($this->db))->days() . ' hari';
            }
        }

        $this->render('peminjaman/detail', [
            'title'    => 'Detail Peminjaman',
            'active'   => Auth::isAdmin() ? 'peminjaman' : 'riwayat',
            'loan'     => $loan,
            'returned' => $returned,
            'sanction' => $sanction,
            'backRoute' => Auth::isAdmin() ? 'peminjaman' : 'riwayat',
        ]);
    }

    /** Siswa mengajukan peminjaman. */
    public function request(): void
    {
        $this->requireRole('siswa');
        $this->requirePost();

        $result = $this->service->request(
            Auth::id(),
            Auth::name(),
            $this->request->int('buku_id'),
            $this->request->str('tanggal_pinjam', date('Y-m-d'))
        );

        $this->flashResult($result);
        $this->redirect((string) $result->get('redirect', 'riwayat'));
    }

    /** Form tambah transaksi (admin). */
    public function form(): void
    {
        $this->requireRole('admin');
        $this->renderForm(['user_id' => 0, 'buku_id' => 0, 'tanggal_pinjam' => date('Y-m-d')]);
    }

    /** Simpan transaksi baru dari admin. */
    public function save(): void
    {
        $this->requireRole('admin');
        $this->requirePost();

        $old = [
            'user_id'        => $this->request->int('user_id'),
            'buku_id'        => $this->request->int('buku_id'),
            'tanggal_pinjam' => $this->request->str('tanggal_pinjam', date('Y-m-d')),
        ];

        if ($old['user_id'] < 1 || $old['buku_id'] < 1) {
            $this->flash('danger', 'Pilih anggota dan buku terlebih dahulu.');
            $this->renderForm($old);
            return;
        }

        $result = $this->service->createByAdmin($old['user_id'], $old['buku_id'], $old['tanggal_pinjam']);
        if (!$result->isOk()) {
            $this->flashResult($result);
            $this->renderForm($old);
            return;
        }

        $this->flashResult($result);
        $this->redirect('peminjaman');
    }

    /** Hapus transaksi: admin (semua yang tidak sedang dipinjam) atau siswa (riwayat miliknya). */
    public function delete(): void
    {
        $this->requireLogin();
        $this->requirePost();

        $isAdmin = Auth::isAdmin();
        $this->flashResult($this->service->delete($this->request->int('id'), $isAdmin ? null : Auth::id()));
        $this->redirect($isAdmin ? 'peminjaman' : 'riwayat');
    }

    /** Siswa membatalkan pengajuannya. */
    public function cancel(): void
    {
        $this->requireRole('siswa');
        $this->requirePost();

        $this->flashResult($this->service->cancel($this->request->int('id'), Auth::id()));
        $this->redirect('riwayat');
    }

    /** Siswa mengembalikan buku yang sedang dipinjam. */
    public function giveBack(): void
    {
        $this->requireRole('siswa');
        $this->requirePost();

        $result = $this->service->returnLoan(
            $this->request->int('id'),
            date('Y-m-d'),
            'Pengembalian oleh siswa',
            Auth::id()
        );

        $this->flashResult($result);
        // Kembali ke halaman asal: menu Pengembalian atau Riwayat.
        $back = $this->request->str('back');
        $this->redirect($back === 'pengembalian' ? 'pengembalian' : 'riwayat');
    }

    /** Admin mengubah status peminjaman. */
    public function status(): void
    {
        $this->requireRole('admin');
        $this->requirePost();

        $this->flashResult($this->service->changeStatus(
            $this->request->int('id'),
            $this->request->str('status')
        ));
        $this->redirect('peminjaman');
    }

    private function renderForm(array $old): void
    {
        $this->render('peminjaman/form', [
            'title'   => 'Tambah Transaksi',
            'active'  => 'peminjaman',
            'old'     => $old,
            'members' => (new User($this->db))->listSiswa(),
            'books'   => array_values(array_filter(
                (new Buku($this->db))->search(),
                static fn(array $book): bool => (int) $book['jumlah_stok'] > 0
            )),
            'loanDays' => (int) Config::get('app.loan_days', 14),
        ]);
    }

    private function listing(string $role): void
    {
        $keyword = $this->request->str('q');
        $status = $this->request->str('status');
        if (!in_array($status, self::STATUSES, true)) {
            $status = '';
        }

        $this->render('peminjaman/index', [
            'title'    => $role === 'admin' ? 'Peminjaman' : 'Riwayat',
            'active'   => $role === 'admin' ? 'peminjaman' : 'riwayat',
            'pageRole' => $role,
            'keyword'  => $keyword,
            'status'   => $status,
            'statuses' => self::STATUSES,
            'loans'    => $this->loans->search($keyword, $status, $role === 'admin' ? null : Auth::id()),
        ]);
    }
}
