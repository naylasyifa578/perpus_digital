<?php

namespace App\Controllers;

use App\Models\AuthService;
use App\Models\ImageUploader;
use App\Models\Session;
use App\Models\User;
use Throwable;

/**
 * Profil pengguna: foto, info akun, dan password.
 */
final class ProfilController extends Controller
{
    private User $users;

    public function __construct()
    {
        parent::__construct();
        $this->users = new User($this->db);
    }

    public function index(): void
    {
        $this->requireLogin();

        $user = $this->users->find(Auth::id());
        if ($user === null) {
            Auth::logout();
            $this->redirect('login');
        }

        $this->render('profil/index', [
            'title'  => 'Profil',
            'active' => 'profil',
            'user'   => $user,
        ]);
    }

    public function foto(): void
    {
        $this->requireLogin();
        $this->requirePost();

        $file = $this->request->file('foto_profil');
        if ($file === null) {
            $this->flash('danger', 'Pilih foto profil terlebih dahulu.');
            $this->redirect('profil');
        }

        $uploader = new ImageUploader();
        $userId = Auth::id();
        $newPhoto = null;

        try {
            $oldPhoto = $this->users->find($userId)['foto_profil'] ?? null;
            $newPhoto = $uploader->store($file, 'profiles');
            $this->users->updatePhoto($userId, $newPhoto);
            Session::set('foto_profil', $newPhoto);
            $uploader->delete($oldPhoto, 'profiles');
            $this->flash('success', 'Foto profil berhasil diperbarui.');
        } catch (Throwable $e) {
            if ($newPhoto !== null) {
                $uploader->delete($newPhoto, 'profiles');
            }
            $this->flash('danger', $e->getMessage());
        }

        $this->redirect('profil');
    }

    public function update(): void
    {
        $this->requireLogin();
        $this->requirePost();

        $nama = $this->request->str('nama_lengkap');
        $gmail = $this->request->str('gmail');

        if ($nama === '' || !filter_var($gmail, FILTER_VALIDATE_EMAIL)) {
            $this->flash('danger', 'Nama dan email harus valid.');
        } elseif ($this->users->isTaken('', $gmail, Auth::id())) {
            $this->flash('danger', 'Email sudah digunakan akun lain.');
        } else {
            $this->users->updateProfile(Auth::id(), $nama, $gmail);
            Session::set('nama_lengkap', $nama);
            $this->flash('success', 'Profil diperbarui.');
        }

        $this->redirect('profil');
    }

    public function password(): void
    {
        $this->requireLogin();
        $this->requirePost();

        $result = (new AuthService($this->db))->changePassword(
            Auth::id(),
            $this->request->raw('old_password'),
            $this->request->raw('new_password')
        );

        $this->flashResult($result);
        $this->redirect('profil');
    }
}
