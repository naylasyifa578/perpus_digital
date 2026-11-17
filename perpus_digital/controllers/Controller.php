<?php

namespace App\Controllers;

use App\Models\Database;
use App\Models\Notifikasi;
use App\Models\Result;
use App\Models\Session;
use App\View\View;

/**
 * Induk semua controller: menyediakan render view, redirect, dan guard akses.
 */
abstract class Controller
{
    protected Request $request;

    protected Database $db;

    public function __construct()
    {
        $this->request = new Request();
        $this->db = Database::getInstance();
    }

    /**
     * Render view di dalam layout.
     * Layout 'app' = halaman setelah login, layout 'auth' = login/register.
     */
    protected function render(string $view, array $data = [], string $layout = 'app'): void
    {
        $shared = ['flash' => Session::consumeFlash()];

        if ($layout === 'app') {
            $notifications = new Notifikasi($this->db);
            $shared['role'] = Auth::role();
            $shared['userName'] = Auth::name();
            $shared['userPhoto'] = Auth::photo();
            $shared['notifCount'] = $notifications->unreadCount(Auth::id());
        }

        echo View::render($view, $data + $shared, $layout);
    }

    protected function redirect(string $route, array $params = []): never
    {
        header('Location: ' . url($route, $params));
        exit;
    }

    protected function flash(string $type, string $message): void
    {
        Session::flash($type, $message);
    }

    protected function flashResult(Result $result): void
    {
        Session::flash($result->type(), $result->message());
    }

    protected function abort(int $status, string $message): never
    {
        http_response_code($status);
        exit(htmlspecialchars($message, ENT_QUOTES, 'UTF-8'));
    }

    protected function requireLogin(): void
    {
        if (!Auth::check()) {
            $this->redirect('login');
        }
    }

    /** Hanya role tertentu yang boleh; role lain diarahkan ke dashboard miliknya. */
    protected function requireRole(string $role): void
    {
        $this->requireLogin();
        if (Auth::role() !== $role) {
            $this->redirect('dashboard');
        }
    }

    /** Pastikan request POST dengan token CSRF valid. */
    protected function requirePost(): void
    {
        if (!$this->request->isPost()) {
            $this->abort(405, 'Metode request tidak diizinkan.');
        }
        if (!Csrf::isValid($this->request->raw('csrf'))) {
            $this->abort(419, 'Permintaan tidak valid. Silakan kembali dan coba lagi.');
        }
    }
}
