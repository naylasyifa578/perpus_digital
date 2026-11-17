<?php

namespace App\View;

use RuntimeException;
use Throwable;

/**
 * Merender file template di folder view/ (dengan layout opsional).
 */
final class View
{
    public static function render(string $view, array $data = [], ?string $layout = null): string
    {
        $content = self::capture(BASE_PATH . '/view/' . $view . '.php', $data);
        if ($layout === null) {
            return $content;
        }
        return self::capture(BASE_PATH . '/view/layouts/' . $layout . '.php', $data + ['content' => $content]);
    }

    public static function partial(string $name, array $data = []): string
    {
        return self::capture(BASE_PATH . '/view/partials/' . $name . '.php', $data);
    }

    private static function capture(string $__file, array $__data): string
    {
        if (!is_file($__file)) {
            throw new RuntimeException('View tidak ditemukan: ' . $__file);
        }

        extract($__data, EXTR_SKIP);
        ob_start();
        try {
            include $__file;
        } catch (Throwable $e) {
            ob_end_clean();
            throw $e;
        }
        return (string) ob_get_clean();
    }
}
