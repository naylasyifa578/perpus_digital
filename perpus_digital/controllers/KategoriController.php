<?php

namespace App\Controllers;

use App\Models\ImageUploader;
use App\Models\Kategori;
use Throwable;

/**
 * Kelola kategori buku (khusus admin).
 */
final class KategoriController extends Controller
{
    private Kategori $categories;

    private ImageUploader $uploader;

    public function __construct()
    {
        parent::__construct();
        $this->categories = new Kategori($this->db);
        $this->uploader = new ImageUploader();
    }

    public function index(): void
    {
        $this->requireRole('admin');

        $keyword = $this->request->str('q');
        $editId = $this->request->int('edit');

        $this->render('kategori/index', [
            'title'      => 'Kategori',
            'active'     => 'kategori',
            'keyword'    => $keyword,
            'categories' => $this->categories->search($keyword),
            'edit'       => $editId > 0 ? $this->categories->find($editId) : null,
        ]);
    }

    public function save(): void
    {
        $this->requireRole('admin');
        $this->requirePost();

        $id = $this->request->int('id');
        $nama = $this->request->str('Kategori');
        $deskripsi = $this->request->str('Deskripsi');
        $backParams = $id > 0 ? ['edit' => $id] : [];

        $oldImage = '';
        if ($id > 0) {
            $existing = $this->categories->find($id);
            if ($existing === null) {
                $this->flash('danger', 'Kategori tidak ditemukan.');
                $this->redirect('kategori');
            }
            $oldImage = (string) ($existing['gambar'] ?? '');
        }

        if ($nama === '') {
            $this->flash('danger', 'Nama kategori wajib diisi.');
            $this->redirect('kategori', $backParams);
        }

        $newImage = $oldImage;
        try {
            $file = $this->request->file('gambar');
            if ($file !== null) {
                $newImage = $this->uploader->store($file, 'kategori');
            }

            if ($id > 0) {
                $this->categories->update($id, $nama, $deskripsi, $newImage);
            } else {
                $this->categories->create($nama, $deskripsi, $newImage);
            }
        } catch (Throwable $e) {
            if ($newImage !== $oldImage) {
                $this->uploader->delete($newImage, 'kategori');
            }
            $this->flash('danger', $e->getMessage());
            $this->redirect('kategori', $backParams);
        }

        if ($id > 0 && $newImage !== $oldImage) {
            $this->uploader->delete($oldImage, 'kategori');
        }

        $this->flash('success', $id > 0 ? 'Kategori diperbarui.' : 'Kategori ditambahkan.');
        $this->redirect('kategori');
    }

    public function delete(): void
    {
        $this->requireRole('admin');
        $this->requirePost();

        $id = $this->request->int('id');
        $category = $id > 0 ? $this->categories->find($id) : null;

        if ($category !== null) {
            try {
                $this->categories->delete($id);
                $this->uploader->delete((string) ($category['gambar'] ?? ''), 'kategori');
                $this->flash('success', 'Kategori dihapus.');
            } catch (Throwable $e) {
                $this->flash('danger', 'Kategori masih dipakai oleh buku.');
            }
        }

        $this->redirect('kategori');
    }
}
