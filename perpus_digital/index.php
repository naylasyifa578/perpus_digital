<?php

/**
 * Front controller Aksaraya (satu-satunya pintu masuk aplikasi).
 * Semua halaman diakses lewat: index.php?page=nama.route (lihat controllers/routes.php)
 *
 * Folder:
 *   assets/       CSS, JS, gambar
 *   controllers/  Controller + routes + kelas pendukung request
 *   models/       Model, service (aturan bisnis), Database, Config, Session
 *   view/         Template tampilan + kelas pendukung tampilan
 *   database/     database.sql dan config.php
 */

use App\Models\Config;
use App\Models\Database;
use App\Models\Schema;
use App\Models\Session;
use App\Controllers\Request;
use App\Controllers\Router;

define('BASE_PATH', __DIR__);

// Autoloader sederhana: namespace App\<Folder>\Kelas => <folder>/Kelas.php
spl_autoload_register(static function (string $class): void {
    $map = [
        'App\\Models\\'      => BASE_PATH . '/models/',
        'App\\Controllers\\' => BASE_PATH . '/controllers/',
        'App\\View\\'        => BASE_PATH . '/view/',
    ];
    foreach ($map as $prefix => $directory) {
        if (strncmp($class, $prefix, strlen($prefix)) === 0) {
            $file = $directory . str_replace('\\', '/', substr($class, strlen($prefix))) . '.php';
            if (is_file($file)) {
                require $file;
            }
            return;
        }
    }
});

require_once BASE_PATH . '/view/functions.php';

try {
    date_default_timezone_set((string) Config::get('app.timezone', 'Asia/Jakarta'));

    if (Config::get('app.debug', false)) {
        ini_set('display_errors', '1');
        error_reporting(E_ALL);
    } else {
        ini_set('display_errors', '0');
    }

    Session::start();

    // Pastikan struktur database sesuai (hanya dijalankan sekali per sesi).
    Schema::ensure(Database::getInstance());

    (new Router(Config::load('routes')))->dispatch(new Request());
} catch (Throwable $e) {
    error_log((string) $e);
    http_response_code(500);

    $debug = class_exists(Config::class) && Config::get('app.debug', false);

    echo '<!doctype html><meta charset="utf-8"><title>Terjadi kesalahan</title>';
    echo '<body style="font-family:Arial,sans-serif;padding:40px">';
    echo '<h2>Terjadi kesalahan pada aplikasi</h2>';
    if ($debug) {
        echo '<pre>' . htmlspecialchars((string) $e, ENT_QUOTES, 'UTF-8') . '</pre>';
    } else {
        echo '<p>' . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8') . '</p>';
        echo '<p><small>Periksa pengaturan di database/config.php atau hubungi admin.</small></p>';
    }
    echo '</body>';
}
