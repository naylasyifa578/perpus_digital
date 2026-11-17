<?php

namespace App\Controllers;

use App\Models\Session;

/**
 * Status login pengguna saat ini (disimpan di session).
 */
final class Auth
{
    public static function check(): bool
    {
        return self::id() > 0;
    }

    public static function id(): int
    {
        return (int) Session::get('id', 0);
    }

    public static function role(): string
    {
        return (string) Session::get('role', '');
    }

    public static function isAdmin(): bool
    {
        return self::role() === 'admin';
    }

    public static function name(): string
    {
        return (string) Session::get('nama_lengkap', 'User');
    }

    public static function photo(): ?string
    {
        $photo = Session::get('foto_profil');
        return $photo ? (string) $photo : null;
    }

    public static function login(array $user): void
    {
        Session::regenerate();
        Session::set('id', (int) $user['id']);
        Session::set('username', (string) $user['username']);
        Session::set('nama_lengkap', (string) $user['nama_lengkap']);
        Session::set('role', (string) $user['role']);
        Session::set('foto_profil', $user['foto_profil'] ?? null);
    }

    public static function logout(): void
    {
        Session::destroy();
    }
}
