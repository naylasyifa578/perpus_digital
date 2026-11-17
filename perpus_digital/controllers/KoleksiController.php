<?php

namespace App\Controllers;

use App\Models\Koleksi;
use Throwable;

/**
 * Koleksi buku yang disimpan siswa.
 */
final class KoleksiController extends Controller
{
    public function index(): void
    {
        $this->requireRole('siswa');

        $keyword = $this->request->str('q');

        $this->render('koleksi/index', [
            'title'   => 'Koleksi Saya',
            'active'  => 'koleksi',
            'keyword' => $keyword,
            'books'   => (new Koleksi($this->db))->listForUser(Auth::id(), $keyword),
        ]);
    }

    /** Simpan / hapus buku dari koleksi, lalu kembali ke halaman asal (katalog atau koleksi). */
    public function toggle(): void
    {
        $this->requireRole('siswa');
        $this->requirePost();

        $bukuId = $this->request->int('id');
        $mode = $this->request->str('mode', 'save');
        $collection = new Koleksi($this->db);

        if ($bukuId > 0) {
            try {
                if ($mode === 'remove') {
                    $collection->remove(Auth::id(), $bukuId);
                    $this->flash('success', 'Buku dihapus dari koleksi.');
                } else {
                    $collection->add(Auth::id(), $bukuId);
                    $this->flash('success', 'Buku disimpan ke koleksi.');
                }
            } catch (Throwable $e) {
                $this->flash('danger', 'Gagal memperbarui koleksi.');
            }
        }

        if ($this->request->str('redirect') === 'koleksi') {
            $this->redirect('koleksi');
        }

        $this->redirect('katalog', [
            'kategori' => $this->request->int('kategori') ?: null,
            'q'        => $this->request->str('q') ?: null,
        ]);
    }
}
