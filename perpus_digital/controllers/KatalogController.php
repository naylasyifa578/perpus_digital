<?php

namespace App\Controllers;

use App\Models\Buku;
use App\Models\Config;
use App\Models\Kategori;
use App\Models\SanctionService;

/**
 * Katalog buku per kategori untuk siswa (sekaligus form pengajuan peminjaman).
 */
final class KatalogController extends Controller
{
    public function index(): void
    {
        $this->requireRole('siswa');

        $userId = Auth::id();
        $keyword = $this->request->str('q');
        $kategoriId = $this->request->int('kategori');

        $categories = (new Kategori($this->db))->allWithCount();

        $selectedName = '';
        foreach ($categories as $category) {
            if ((int) $category['id'] === $kategoriId) {
                $selectedName = (string) $category['Kategori'];
                break;
            }
        }

        // Buku tampil bila siswa memilih kategori atau mengetik kata kunci pencarian.
        $showBooks = $kategoriId > 0 || $keyword !== '';
        $books = $showBooks ? (new Buku($this->db))->catalog($userId, $keyword, $kategoriId) : [];

        $this->render('katalog/index', [
            'title'        => 'Kategori',
            'active'       => 'katalog',
            'keyword'      => $keyword,
            'kategoriId'   => $kategoriId,
            'selectedName' => $selectedName,
            'categories'   => $categories,
            'showBooks'    => $showBooks,
            'books'        => $books,
            'blockedUntil' => (new SanctionService($this->db))->blockedUntil($userId),
            'loanDays'     => (int) Config::get('app.loan_days', 14),
        ]);
    }
}
