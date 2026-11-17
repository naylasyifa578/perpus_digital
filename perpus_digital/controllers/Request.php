<?php

namespace App\Controllers;

/**
 * Pembungkus $_GET, $_POST, dan $_FILES.
 */
final class Request
{
    public function isPost(): bool
    {
        return ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST';
    }

    /** Nilai mentah: POST diutamakan, lalu GET. */
    public function input(string $key, mixed $default = null): mixed
    {
        if (isset($_POST[$key])) {
            return $_POST[$key];
        }
        return $_GET[$key] ?? $default;
    }

    /** Nilai string yang sudah di-trim. */
    public function str(string $key, string $default = ''): string
    {
        $value = $this->input($key, $default);
        return is_scalar($value) ? trim((string) $value) : $default;
    }

    /** Nilai string apa adanya (untuk password: tidak di-trim). */
    public function raw(string $key, string $default = ''): string
    {
        $value = $this->input($key, $default);
        return is_scalar($value) ? (string) $value : $default;
    }

    public function int(string $key, int $default = 0): int
    {
        $value = $this->input($key, $default);
        return is_scalar($value) ? (int) $value : $default;
    }

    /** Data file upload, atau null jika tidak ada file yang dipilih. */
    public function file(string $key): ?array
    {
        $file = $_FILES[$key] ?? null;
        if (!is_array($file) || !isset($file['error']) || $file['error'] === UPLOAD_ERR_NO_FILE) {
            return null;
        }
        return $file;
    }
}
