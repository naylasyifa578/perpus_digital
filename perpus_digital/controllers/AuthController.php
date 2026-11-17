<?php

namespace App\Controllers;

use App\Models\AuthService;

/**
 * Login, registrasi, dan logout.
 */
final class AuthController extends Controller
{
    public function login(): void
    {
        if (Auth::check()) {
            $this->redirect('dashboard');
        }

        $error = '';
        $login = '';
        $peran = $this->request->str('peran', 'siswa');
        if (!in_array($peran, ['admin', 'siswa'], true)) {
            $peran = 'siswa';
        }

        if ($this->request->isPost()) {
            $this->requirePost();
            $login = $this->request->str('username');
            $user = (new AuthService($this->db))->attempt($login, $this->request->raw('password'));

            if ($user === null) {
                $error = 'Username atau password salah.';
            } elseif ((string) $user['role'] !== $peran) {
                // Akun benar, tetapi dipakai di pilihan peran yang salah (flowmap: login sebagai admin / siswa).
                $error = 'Akun ini bukan akun ' . $peran . '. Pilih peran "' . ((string) $user['role'] === 'admin' ? 'Admin' : 'Siswa') . '" lalu coba lagi.';
            } else {
                Auth::login($user);
                $this->redirect('dashboard');
            }
        }

        $this->render('auth/login', [
            'title'    => 'Masuk',
            'subtitle' => 'Silahkan masuk untuk melanjutkan',
            'error'    => $error,
            'login'    => $login,
            'peran'    => $peran,
        ], 'auth');
    }

    public function register(): void
    {
        if (Auth::check()) {
            $this->redirect('dashboard');
        }

        $error = '';
        $old = ['nama_lengkap' => '', 'gmail' => '', 'username' => '', 'peran' => 'siswa'];

        if ($this->request->isPost()) {
            $this->requirePost();
            $peran = $this->request->str('peran', 'siswa');
            $old = [
                'nama_lengkap' => $this->request->str('nama_lengkap'),
                'gmail'        => $this->request->str('gmail'),
                'username'     => $this->request->str('username'),
                'peran'        => in_array($peran, ['admin', 'siswa'], true) ? $peran : 'siswa',
            ];

            $result = (new AuthService($this->db))->register(
                $old['nama_lengkap'],
                $old['gmail'],
                $old['username'],
                $this->request->raw('password'),
                $old['peran'],
                $this->request->raw('kode_admin')
            );

            if ($result->isOk()) {
                $this->flashResult($result);
                $this->redirect('login');
            }
            $error = $result->message();
        }

        $this->render('auth/register', [
            'title'    => 'Daftar',
            'subtitle' => 'Silahkan buat akun untuk melanjutkan',
            'error'    => $error,
            'old'      => $old,
        ], 'auth');
    }

    public function logout(): void
    {
        Auth::logout();
        header('Location: ' . url('login'));
        exit;
    }
}
