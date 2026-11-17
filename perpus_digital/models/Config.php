<?php

namespace App\Models;

/**
 * Pembaca konfigurasi.
 *   Config::get('app.loan_days')   -> database/config.php, bagian 'app'
 *   Config::load('database')       -> database/config.php, bagian 'database'
 *   Config::load('routes')         -> controllers/routes.php
 */
final class Config
{
    private const FILES = [
        'database' => 'database/config.php',
        'app'      => 'database/config.php',
        'routes'   => 'controllers/routes.php',
    ];

    /** Nama yang merupakan satu bagian di dalam file database/config.php. */
    private const SECTIONS = ['database', 'app'];

    /** @var array<string, array> */
    private static array $items = [];

    public static function load(string $name): array
    {
        if (!isset(self::$items[$name])) {
            $file = isset(self::FILES[$name]) ? BASE_PATH . '/' . self::FILES[$name] : null;
            $loaded = ($file !== null && is_file($file)) ? require $file : [];

            if (is_array($loaded) && in_array($name, self::SECTIONS, true)) {
                $loaded = $loaded[$name] ?? [];
            }
            self::$items[$name] = is_array($loaded) ? $loaded : [];
        }
        return self::$items[$name];
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        $parts = explode('.', $key, 2);
        $items = self::load($parts[0]);
        if (!isset($parts[1])) {
            return $items;
        }
        return $items[$parts[1]] ?? $default;
    }
}
