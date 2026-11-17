<?php

namespace App\Controllers;

use App\Models\Buku;
use App\Models\ImageUploader;
use App\Models\Kategori;
use Throwable;

/**
 * Kelola data buku (khusus admin).
 */
final class BukuController extends Controller
{
    private Buku $books;

    private Kategori $categories;

    private ImageUploader $uploader;

    public function __construct()
    {
        parent::__construct();
        $this->books = new Buku($this->db);
        $this->categories = new Kategori($this->db);
        $this->uploader = new ImageUploader();
    }

    public function index(): void
    {
        $this->requireRole('admin');

        $keyword = $this->request->str('q');
        $kategoriId = $this->request->int('kategori');

        $this->render('buku/index', [
            'title'      => 'Data Buku',
            'active'     => 'buku',
            'keyword'    => $keyword,
            'kategoriId' => $kategoriId,
            'books'      => $this->books->search($keyword, $kategoriId),
            'categories' => $this->categories->all(),
        ]);
    }

    /** Form tambah (tanpa id) atau edit (dengan id). */
    public function form(): void
    {
        $this->requireRole('admin');

        $id = $this->request->int('id');
        $book = [];

        if ($id > 0) {
            $found = $this->books->find($id);
            if ($found === null) {
                $this->flash('danger', 'Data buku tidak ditemukan.');
                $this->redirect('buku');
            }
            $book = $found;
        }

        $this->renderForm($id, $book);
    }

    public function save(): void
    {
        $this->requireRole('admin');
        $this->requirePost();

        $id = $this->request->int('id');
        $oldCover = null;

        if ($id > 0) {
            $existing = $this->books->find($id);
            if ($existing === null) {
                $this->flash('danger', 'Data buku tidak ditemukan.');
                $this->redirect('buku');
            }
            $oldCover = $existing['gambar'] ?? null;
        }

        $data = [
            'judul'        => $this->request->str('judul'),
            'pengarang'    => $this->request->str('pengarang'),
            'penerbit'     => $this->request->str('penerbit'),
            'tahun_terbit' => $this->request->int('tahun_terbit'),
            'isbn'         => $this->request->str('isbn'),
            'jumlah_stok'  => max(0, $this->request->int('jumlah_stok')),
            'kategori_id'  => $this->request->int('kategori_id'),
            'gambar'       => $oldCover,
        ];

        if ($data['judul'] === '' || $data['pengarang'] === '' || $data['penerbit'] === ''
            || $data['tahun_terbit'] < 1000 || $data['kategori_id'] < 1
        ) {
            $this->flash('danger', 'Data buku belum lengkap.');
            $this->renderForm($id, $data);
            return;
        }

        $newCover = $oldCover;
        try {
            $file = $this->request->file('cover');
            if ($file !== null) {
                $newCover = $this->uploader->store($file, 'covers');
                $data['gambar'] = $newCover;
            }

            if ($id > 0) {
                $this->books->update($id, $data);
            } else {
                $this->books->create($data);
            }
        } catch (Throwable $e) {
            if ($newCover !== $oldCover) {
                $this->uploader->delete($newCover, 'covers');
            }
            $data['gambar'] = $oldCover;
            $this->flash('danger', 'Gagal menyimpan buku: ' . $e->getMessage());
            $this->renderForm($id, $data);
            return;
        }

        if ($id > 0 && $newCover !== $oldCover) {
            $this->uploader->delete($oldCover, 'covers');
        }

        $this->flash('success', $id > 0 ? 'Data buku berhasil diperbarui.' : 'Data buku berhasil ditambahkan.');
        $this->redirect('buku');
    }

    public function delete(): void
    {
        $this->requireRole('admin');
        $this->requirePost();

        $id = $this->request->int('id');
        $book = $id > 0 ? $this->books->find($id) : null;

        if ($book !== null) {
            try {
                $this->books->delete($id);
                $this->uploader->delete($book['gambar'] ?? null, 'covers');
                $this->flash('success', 'Buku berhasil dihapus.');
            } catch (Throwable $e) {
                $this->flash('danger', 'Buku tidak dapat dihapus.');
            }
        }

        $this->redirect('buku');
    }

    private function renderForm(int $id, array $book): void
    {
        $this->render('buku/form', [
            'title'      => $id > 0 ? 'Edit Buku' : 'Tambah Buku',
            'active'     => 'buku',
            'id'         => $id,
            'book'       => $book,
            'categories' => $this->categories->all(),
        ]);
    }
}
