<?php

namespace App\View;

use App\Models\Config;

/**
 * Format tampilan: tanggal, label status, URL gambar.
 */
final class Format
{
    public static function date(?string $date): string
    {
        if (!$date || $date === '0000-00-00') {
            return '-';
        }
        $time = strtotime($date);
        return $time ? date('d M Y', $time) : (string) $date;
    }

    /** Tanggal dengan nama hari bahasa Indonesia, mis. "Senin, 16 Nov 2026". */
    public static function dateWithDay(?string $date): string
    {
        if (!$date || $date === '0000-00-00') {
            return '-';
        }
        $time = strtotime($date);
        if (!$time) {
            return (string) $date;
        }

        $days = [
            'Sunday'    => 'Minggu',
            'Monday'    => 'Senin',
            'Tuesday'   => 'Selasa',
            'Wednesday' => 'Rabu',
            'Thursday'  => 'Kamis',
            'Friday'    => 'Jumat',
            'Saturday'  => 'Sabtu',
        ];

        return $days[date('l', $time)] . ', ' . date('d M Y', $time);
    }

    public static function dateTime(?string $date): string
    {
        $time = $date ? strtotime($date) : false;
        return $time ? date('d M Y', $time) . ' · ' . date('H:i', $time) : '-';
    }

    /** Format dd/mm/yyyy untuk dokumen unduhan. */
    public static function shortDate(?string $date): string
    {
        $time = $date ? strtotime($date) : false;
        return $time ? date('d/m/Y', $time) : '-';
    }

    public static function statusLabel(string $status): string
    {
        return match ($status) {
            'pengajuan'    => 'Menunggu',
            'disetujui'    => 'Disetujui',
            'dipinjam'     => 'Dipinjam',
            'dikembalikan' => 'Dikembalikan',
            'ditolak'      => 'Ditolak',
            'dibatalkan'   => 'Dibatalkan',
            default        => ucfirst($status),
        };
    }

    public static function statusClass(string $status): string
    {
        return match ($status) {
            'dikembalikan'          => 'success',
            'dipinjam', 'disetujui' => 'info',
            'ditolak', 'dibatalkan' => 'danger',
            default                 => 'warning',
        };
    }

    /** Tanggal jatuh tempo = tanggal pinjam + lama peminjaman. */
    public static function dueDate(string $borrowDate): string
    {
        $days = (int) Config::get('app.loan_days', 14);
        return date('Y-m-d', strtotime($borrowDate . ' +' . $days . ' days'));
    }

    public static function coverUrl(?string $path): string
    {
        $path = (string) $path;
        // Aplikasi berjalan offline: URL internet tidak dimuat, diganti gambar bawaan lokal.
        if ($path === '' || preg_match('~^https?://~i', $path)) {
            return 'assets/no-cover.svg';
        }
        return 'assets/' . ltrim($path, '/');
    }

    /** Cover bawaan untuk buku contoh yang belum punya gambar (berdasarkan judul). */
    private const DEFAULT_COVERS = [
        'laskar pelangi'         => 'covers/laskar-pelangi.jpg',
        'hujan'                  => 'covers/hujan.jpg',
        'cinta yang mekar'       => 'covers/cinta-yang-mekar.jpg',
        'konsep ayah sepatu ibu' => 'covers/konsep-ayah-sepatu-ibu.jpg',
        'kala itu langit biru'   => 'covers/kala-itu-langit-biru.jpg',
        'bandung after rain'     => 'covers/bandung-after-rain.jpg',
        'senja'                  => 'covers/senja.jpg',
        'angin berhembus'        => 'covers/angin-berhembus.jpg',
        'seporis mie ayam'       => 'covers/seporis-mie-ayam.jpg',
    ];

    /** Path cover buku: gambar milik buku, atau cover bawaan berdasarkan judul, atau null. */
    public static function bookCover(?string $gambar, string $judul): ?string
    {
        if ($gambar !== null && $gambar !== '' && !preg_match('~^https?://~i', $gambar)) {
            return $gambar;
        }
        return self::DEFAULT_COVERS[strtolower(trim($judul))] ?? null;
    }

    /** Huruf pertama (untuk avatar / cover kosong). */
    public static function initial(?string $text): string
    {
        $text = trim((string) $text);
        return $text === '' ? '?' : mb_strtoupper(mb_substr($text, 0, 1));
    }
}
