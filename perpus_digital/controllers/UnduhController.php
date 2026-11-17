<?php

namespace App\Controllers;

use App\Models\Peminjaman;
use App\Models\Pengembalian;
use App\Models\SanctionService;
use App\Models\SvgDocument;
use App\Models\User;
use App\View\Format;

/**
 * "Simpan ke Perangkat": unduh detail peminjaman / detail user sebagai file SVG.
 */
final class UnduhController extends Controller
{
    public function index(): void
    {
        $this->requireLogin();

        $type = $this->request->str('type');
        $id = $this->request->int('id');

        if (!in_array($type, ['loan', 'user'], true) || $id < 1) {
            $this->abort(400, 'Permintaan tidak valid.');
        }
        if ($type === 'user' && !Auth::isAdmin()) {
            $this->abort(403, 'Akses ditolak.');
        }

        $document = $type === 'loan' ? $this->loanDocument($id) : $this->userDocument($id);
        $svg = (new SvgDocument())->build($document['title'], $document['rows'], $document['cover']);

        header('Content-Type: image/svg+xml; charset=UTF-8');
        header('Content-Disposition: attachment; filename="' . str_replace('"', '', $document['filename']) . '"');
        header('Content-Length: ' . strlen($svg));
        echo $svg;
    }

    /** @return array{title: string, rows: array, filename: string, cover: ?string} */
    private function loanDocument(int $id): array
    {
        $loan = (new Peminjaman($this->db))->detail($id);
        if ($loan === null || (!Auth::isAdmin() && (int) $loan['user_id'] !== Auth::id())) {
            $this->abort(404, 'Data peminjaman tidak ditemukan.');
        }

        $code = 'PM-' . str_pad((string) $loan['id'], 4, '0', STR_PAD_LEFT);
        $dueDate = Format::dueDate((string) $loan['tanggal_pinjam']);

        $rows = [
            ['ID Peminjaman', $code],
            ['Peminjam', (string) $loan['nama_lengkap']],
            ['Username', (string) $loan['username']],
            ['Email', (string) ($loan['gmail'] ?? '-')],
            ['Buku', (string) $loan['judul']],
            ['Kategori', (string) ($loan['Kategori'] ?? '-')],
            ['Pengarang', (string) $loan['pengarang']],
            ['Penerbit', (string) $loan['penerbit']],
            ['Tanggal Pinjam', Format::shortDate($loan['tanggal_pinjam'])],
            ['Jatuh Tempo', Format::shortDate($dueDate)],
            ['Status', Format::statusLabel((string) $loan['status'])],
        ];

        $returned = (new Pengembalian($this->db))->latestFor($id);
        if ($returned !== null) {
            $lateDays = (int) ((strtotime((string) $returned['tanggal_pengembalian']) - strtotime($dueDate)) / 86400);
            $sanksi = $lateDays > 0
                ? 'Peminjaman dinonaktifkan ' . (new SanctionService($this->db))->days() . ' hari'
                : 'Tidak ada';

            $rows[] = ['Tanggal Pengembalian', Format::shortDate($returned['tanggal_pengembalian'])];
            $rows[] = ['Sanksi', $sanksi];
            $rows[] = ['Keterangan', (string) ($returned['keterangan'] ?? '-')];
        }

        return [
            'title'    => 'Detail Peminjaman',
            'rows'     => $rows,
            'filename' => 'Aksaraya_Detail_Peminjaman_' . $code . '.svg',
            'cover'    => $this->coverDataUri($loan['gambar'] ?? null),
        ];
    }

    /** @return array{title: string, rows: array, filename: string, cover: ?string} */
    private function userDocument(int $id): array
    {
        $member = (new User($this->db))->findSiswa($id);
        if ($member === null) {
            $this->abort(404, 'Data user tidak ditemukan.');
        }

        $blockedUntil = $member['blocked_until'] ?? null;
        $status = ($blockedUntil !== null && $blockedUntil !== '' && $blockedUntil >= date('Y-m-d'))
            ? 'Dibatasi sampai ' . Format::shortDate($blockedUntil)
            : 'Aktif';

        return [
            'title'    => 'Detail User',
            'rows'     => [
                ['Nama Lengkap', (string) $member['nama_lengkap']],
                ['Username', (string) $member['username']],
                ['Email', (string) ($member['gmail'] ?? '-')],
                ['Terdaftar', Format::shortDate($member['created_at'])],
                ['Status Peminjaman', $status],
            ],
            'filename' => 'Aksaraya_Detail_User_' . preg_replace('/[^A-Za-z0-9_-]+/', '_', (string) $member['username']) . '.svg',
            'cover'    => null,
        ];
    }

    /** Ubah file cover di assets/ menjadi data URI agar ikut tertanam di dalam SVG. */
    private function coverDataUri(?string $relative): ?string
    {
        if (!$relative || preg_match('~^https?://~i', $relative)) {
            return null;
        }

        $base = realpath(BASE_PATH . '/assets');
        $path = realpath(BASE_PATH . '/assets/' . ltrim($relative, '/'));
        if ($base === false || $path === false || strpos($path, $base . DIRECTORY_SEPARATOR) !== 0 || !is_file($path)) {
            return null;
        }

        $info = @getimagesize($path);
        $mime = is_array($info) ? (string) ($info['mime'] ?? 'image/jpeg') : 'image/jpeg';
        $content = file_get_contents($path);

        return $content === false ? null : 'data:' . $mime . ';base64,' . base64_encode($content);
    }
}
