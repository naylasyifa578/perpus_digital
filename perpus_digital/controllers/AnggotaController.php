<?php

namespace App\Controllers;

use App\Models\Peminjaman;
use App\Models\User;
use Throwable;

/**
 * Kelola akun siswa (khusus admin).
 */
final class AnggotaController extends Controller
{
    private User $users;

    public function __construct()
    {
        parent::__construct();
        $this->users = new User($this->db);
    }

    public function index(): void
    {
        $this->requireRole('admin');

        $keyword = $this->request->str('q');

        $this->render('anggota/index', [
            'title'   => 'Kelola User',
            'active'  => 'anggota',
            'keyword' => $keyword,
            'members' => $this->users->listSiswa($keyword),
        ]);
    }

    public function detail(): void
    {
        $this->requireRole('admin');

        $id = $this->request->int('id');
        $member = $id > 0 ? $this->users->findSiswa($id) : null;
        if ($member === null) {
            $this->abort(404, 'Data user tidak ditemukan.');
        }

        $blockedUntil = $member['blocked_until'] ?? null;
        $isBlocked = $blockedUntil !== null && $blockedUntil !== '' && $blockedUntil >= date('Y-m-d');

        $this->render('anggota/detail', [
            'title'        => 'Detail User',
            'active'       => 'anggota',
            'member'       => $member,
            'isBlocked'    => $isBlocked,
            'history'      => (new Peminjaman($this->db))->historyForUser($id, 10),
        ]);
    }

    /** Form tambah (tanpa id) atau edit (dengan id). */
    public function form(): void
    {
        $this->requireRole('admin');

        $id = $this->request->int('id');
        $member = [];

        if ($id > 0) {
            $found = $this->users->findSiswa($id);
            if ($found === null) {
                $this->flash('danger', 'Data anggota tidak ditemukan.');
                $this->redirect('anggota');
            }
            $member = $found;
        }

        $this->renderForm($id, $member);
    }

    public function save(): void
    {
        $this->requireRole('admin');
        $this->requirePost();

        $id = $this->request->int('id');
        $nama = $this->request->str('nama_lengkap');
        $username = $this->request->str('username');
        $gmail = $this->request->str('gmail');
        $password = $this->request->raw('password');
        $confirm = $this->request->raw('password_confirm');

        $member = ['id' => $id, 'nama_lengkap' => $nama, 'username' => $username, 'gmail' => $gmail];

        if ($id > 0 && $this->users->findSiswa($id) === null) {
            $this->flash('danger', 'Data anggota tidak ditemukan.');
            $this->redirect('anggota');
        }

        $error = $this->validate($id, $nama, $username, $gmail, $password, $confirm);
        if ($error === null && $this->users->isTaken($username, $gmail, $id)) {
            $error = 'Username atau email sudah digunakan oleh anggota lain.';
        }
        if ($error !== null) {
            $this->flash('danger', $error);
            $this->renderForm($id, $member);
            return;
        }

        try {
            if ($id > 0) {
                $hash = $password !== '' ? password_hash($password, PASSWORD_DEFAULT) : null;
                $this->users->updateSiswa($id, $nama, $username, $gmail, $hash);
                $message = 'Data anggota berhasil diperbarui.';
            } else {
                $this->users->createSiswa($username, password_hash($password, PASSWORD_DEFAULT), $nama, $gmail);
                $message = 'Anggota berhasil ditambahkan.';
            }
        } catch (Throwable $e) {
            $this->flash('danger', 'Data anggota gagal disimpan: ' . $e->getMessage());
            $this->renderForm($id, $member);
            return;
        }

        $this->flash('success', $message);
        $this->redirect('anggota');
    }

    public function delete(): void
    {
        $this->requireRole('admin');
        $this->requirePost();

        $id = $this->request->int('id');

        if ($id > 0) {
            if ((new Peminjaman($this->db))->hasActiveByUser($id)) {
                $this->flash('danger', 'Anggota masih memiliki transaksi aktif.');
            } else {
                try {
                    $this->users->deleteSiswa($id);
                    $this->flash('success', 'Anggota dihapus.');
                } catch (Throwable $e) {
                    $this->flash('danger', 'Anggota tidak dapat dihapus.');
                }
            }
        }

        $this->redirect('anggota');
    }

    /** Mengembalikan pesan error validasi, atau null bila data sudah benar. */
    private function validate(int $id, string $nama, string $username, string $gmail, string $password, string $confirm): ?string
    {
        if ($nama === '' || $username === '' || !filter_var($gmail, FILTER_VALIDATE_EMAIL)) {
            return 'Nama lengkap, username, dan email wajib diisi dengan benar.';
        }
        if (strlen($username) > 50) {
            return 'Username maksimal 50 karakter.';
        }
        if ($id === 0 && strlen($password) < 6) {
            return 'Password anggota baru minimal 6 karakter.';
        }
        if ($password !== '' && strlen($password) < 6) {
            return 'Password minimal 6 karakter.';
        }
        if ($password !== '' && $password !== $confirm) {
            return 'Konfirmasi password tidak sama.';
        }
        return null;
    }

    private function renderForm(int $id, array $member): void
    {
        $this->render('anggota/form', [
            'title'  => $id > 0 ? 'Edit Anggota' : 'Tambah Anggota',
            'active' => 'anggota',
            'id'     => $id,
            'member' => $member,
        ]);
    }
}
