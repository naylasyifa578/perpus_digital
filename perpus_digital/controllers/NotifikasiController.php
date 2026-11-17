<?php

namespace App\Controllers;

use App\Models\Notifikasi;

/**
 * Daftar notifikasi pengguna.
 */
final class NotifikasiController extends Controller
{
    private Notifikasi $notifications;

    public function __construct()
    {
        parent::__construct();
        $this->notifications = new Notifikasi($this->db);
    }

    public function index(): void
    {
        $this->requireLogin();

        $this->render('notifikasi/index', [
            'title'         => 'Notifikasi',
            'active'        => 'notifikasi',
            'notifications' => $this->notifications->listForUser(Auth::id(), 100),
        ]);
    }

    public function read(): void
    {
        $this->requireLogin();
        $this->requirePost();

        $this->notifications->markRead($this->request->int('id'), Auth::id());
        $this->redirect('notifikasi');
    }

    public function readAll(): void
    {
        $this->requireLogin();
        $this->requirePost();

        $this->notifications->markAllRead(Auth::id());
        $this->redirect('notifikasi');
    }
}
