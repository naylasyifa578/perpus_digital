<?php

namespace App\Models;

/**
 * Login, registrasi, dan penggantian password.
 */
final class AuthService
{
    private User $users;

    public function __construct(Database $db)
    {
        $this->users = new User($db);
    }

    /** Cek username/email + password. Mengembalikan data user atau null bila salah. */
    public function attempt(string $login, string $password): ?array
    {
        $user = $this->users->findByLogin($login);
        if ($user === null) {
            return null;
        }

        $stored = trim((string) $user['password']);
        $hashed = password_verify($password, $stored);
        // Akun lama yang password-nya masih teks biasa tetap bisa masuk, lalu otomatis di-hash.
        $legacy = !$hashed && hash_equals($stored, $password);

        if (!$hashed && !$legacy) {
            return null;
        }
        if ($legacy) {
            $this->users->updatePassword((int) $user['id'], password_hash($password, PASSWORD_DEFAULT));
        }

        return $user;
    }

    /**
     * Daftar akun baru. $role = 'siswa' (bebas) atau 'admin' (wajib memakai kode admin).
     */
    public function register(string $nama, string $gmail, string $username, string $password, string $role = 'siswa', string $adminCode = ''): Result
    {
        $role = $role === 'admin' ? 'admin' : 'siswa';

        if ($nama === '' || !filter_var($gmail, FILTER_VALIDATE_EMAIL) || $username === '' || strlen($password) < 6) {
            return Result::fail('Lengkapi data dengan benar. Password minimal 6 karakter.');
        }
        if (strlen($username) > 50) {
            return Result::fail('Username maksimal 50 karakter.');
        }
        if ($role === 'admin') {
            $expected = (string) Config::get('app.admin_code', '');
            if ($expected === '' || !hash_equals($expected, $adminCode)) {
                return Result::fail('Kode admin salah. Pendaftaran admin dibatalkan.');
            }
        }
        if ($this->users->isTaken($username, $gmail)) {
            return Result::fail('Username atau email sudah digunakan.');
        }

        try {
            $this->users->createUser($username, password_hash($password, PASSWORD_DEFAULT), $nama, $gmail, $role);
        } catch (\Throwable $e) {
            return Result::fail('Pendaftaran gagal: ' . $e->getMessage());
        }

        return Result::ok('Pendaftaran ' . ($role === 'admin' ? 'admin' : 'siswa') . ' berhasil. Silakan masuk.');
    }

    /** Ganti password setelah memverifikasi password lama. */
    public function changePassword(int $userId, string $old, string $new): Result
    {
        $user = $this->users->find($userId);
        if ($user === null) {
            return Result::fail('Akun tidak ditemukan.');
        }

        $stored = trim((string) $user['password']);
        if (!password_verify($old, $stored) && !hash_equals($stored, $old)) {
            return Result::fail('Password lama salah.');
        }
        if (strlen($new) < 6) {
            return Result::fail('Password baru minimal 6 karakter.');
        }

        $this->users->updatePassword($userId, password_hash($new, PASSWORD_DEFAULT));
        return Result::ok('Password berhasil diubah.');
    }
}
