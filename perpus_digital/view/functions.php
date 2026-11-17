<?php

/**
 * Fungsi bantu global yang dipakai di view.
 * Semuanya hanya pembungkus tipis ke class pada App\View dan App\Controllers (Csrf).
 */

use App\Controllers\Csrf;
use App\View\Format;
use App\View\Icon;
use App\View\View;

/** Escape output HTML. */
function e(mixed $value): string
{
    return htmlspecialchars((string) ($value ?? ''), ENT_QUOTES, 'UTF-8');
}

/** Bangun URL ke sebuah route: index.php?page=nama.route&param=nilai */
function url(string $route, array $params = []): string
{
    return 'index.php?' . http_build_query(['page' => $route] + $params);
}

/** URL file di folder assets/. */
function asset(string $path): string
{
    return 'assets/' . ltrim($path, '/');
}

/** URL gambar cover: boleh berupa URL penuh atau path relatif di assets/. */
function cover_url(?string $path): string
{
    return Format::coverUrl($path);
}

/** Path cover sebuah buku (atau null bila tidak ada). */
function book_cover(array $book): ?string
{
    return Format::bookCover($book['gambar'] ?? null, (string) ($book['judul'] ?? ''));
}

function csrf_field(): string
{
    return Csrf::field();
}

function icon(string $name, int $size = 20): string
{
    return Icon::render($name, $size);
}

function format_date(?string $date): string
{
    return Format::date($date);
}

function format_date_with_day(?string $date): string
{
    return Format::dateWithDay($date);
}

function status_label(string $status): string
{
    return Format::statusLabel($status);
}

function status_class(string $status): string
{
    return Format::statusClass($status);
}

function due_date(string $borrowDate): string
{
    return Format::dueDate($borrowDate);
}

/** Render potongan view (view/partials/*.php) dan kembalikan sebagai string. */
function partial(string $name, array $data = []): string
{
    return View::partial($name, $data);
}
