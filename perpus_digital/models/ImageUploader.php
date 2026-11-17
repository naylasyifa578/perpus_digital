<?php

namespace App\Models;

use RuntimeException;

/**
 * Upload dan hapus gambar (cover buku, foto profil, foto kategori).
 * File disimpan di assets/<subfolder>/ dengan nama acak.
 */
final class ImageUploader
{
    private const ALLOWED = [
        'image/jpeg' => 'jpg',
        'image/png'  => 'png',
        'image/webp' => 'webp',
    ];

    private int $maxBytes;

    public function __construct(?int $maxBytes = null)
    {
        $this->maxBytes = $maxBytes ?? (int) Config::get('app.max_upload', 2097152);
    }

    /**
     * Simpan gambar dan kembalikan path relatif terhadap assets/, mis. "covers/abc123.jpg".
     *
     * @throws RuntimeException bila file tidak valid
     */
    public function store(array $file, string $subdir): string
    {
        $error = (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE);
        if ($error !== UPLOAD_ERR_OK) {
            throw new RuntimeException('Upload gambar gagal.');
        }
        if ((int) $file['size'] > $this->maxBytes) {
            throw new RuntimeException('Ukuran gambar maksimal 2 MB.');
        }

        $info = @getimagesize((string) $file['tmp_name']);
        $mime = is_array($info) ? (string) ($info['mime'] ?? '') : '';
        if (!isset(self::ALLOWED[$mime])) {
            throw new RuntimeException('Format gambar harus JPG, PNG, atau WEBP.');
        }

        $directory = BASE_PATH . '/assets/' . $subdir;
        if (!is_dir($directory) && !mkdir($directory, 0755, true)) {
            throw new RuntimeException('Folder upload tidak dapat dibuat.');
        }

        $name = bin2hex(random_bytes(12)) . '.' . self::ALLOWED[$mime];
        if (!move_uploaded_file((string) $file['tmp_name'], $directory . '/' . $name)) {
            throw new RuntimeException('Gambar gagal disimpan.');
        }

        return $subdir . '/' . $name;
    }

    /** Hapus file lama; hanya file di dalam assets/<subdir>/ yang boleh dihapus. */
    public function delete(?string $relative, string $subdir): void
    {
        if (!$relative || preg_match('~^https?://~i', $relative)) {
            return;
        }

        $base = realpath(BASE_PATH . '/assets/' . $subdir);
        if ($base === false) {
            return;
        }

        $candidate = realpath($base . '/' . basename($relative));
        if ($candidate !== false && strpos($candidate, $base . DIRECTORY_SEPARATOR) === 0 && is_file($candidate)) {
            @unlink($candidate);
        }
    }
}
