<?php

namespace App\Models;

/**
 * Hasil sebuah operasi bisnis (berhasil / gagal + pesan).
 * Dipakai service agar controller cukup menampilkan pesan dan mengarahkan halaman.
 */
final class Result
{
    private function __construct(
        private bool $success,
        private string $message,
        private array $data = []
    ) {
    }

    public static function ok(string $message, array $data = []): self
    {
        return new self(true, $message, $data);
    }

    public static function fail(string $message, array $data = []): self
    {
        return new self(false, $message, $data);
    }

    public function isOk(): bool
    {
        return $this->success;
    }

    public function message(): string
    {
        return $this->message;
    }

    /** Tipe flash message: success / danger. */
    public function type(): string
    {
        return $this->success ? 'success' : 'danger';
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return $this->data[$key] ?? $default;
    }
}
