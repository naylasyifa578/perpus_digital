<?php

namespace App\Controllers;

/**
 * Front controller sederhana.
 * URL: index.php?page=nama.route  =>  controllers/routes.php  =>  NamaController::method()
 */
final class Router
{
    /** @param array<string, array{0: class-string, 1: string}> $routes */
    public function __construct(private array $routes)
    {
    }

    public function dispatch(Request $request): void
    {
        $page = $request->input('page', '');
        if (!is_string($page) || $page === '') {
            $page = Auth::check() ? 'dashboard' : 'login';
        }

        if (!isset($this->routes[$page])) {
            http_response_code(404);
            echo 'Halaman tidak ditemukan.';
            return;
        }

        [$class, $method] = $this->routes[$page];
        $controller = new $class();
        $controller->$method();
    }
}
