<?php

namespace App\Controllers;

use App\Models\Session;

/**
 * Perlindungan CSRF untuk semua form POST.
 */
final class Csrf
{
    public static function token(): string
    {
        $token = Session::get('csrf');
        if (empty($token)) {
            $token = bin2hex(random_bytes(32));
            Session::set('csrf', $token);
        }
        return (string) $token;
    }

    public static function field(): string
    {
        return '<input type="hidden" name="csrf" value="' . htmlspecialchars(self::token(), ENT_QUOTES, 'UTF-8') . '">';
    }

    public static function isValid(?string $token): bool
    {
        $stored = (string) Session::get('csrf', '');
        return $token !== null && $token !== '' && $stored !== '' && hash_equals($stored, $token);
    }
}
