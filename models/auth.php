<?php
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

function e($value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function redirect(string $url): never
{
    header('Location: ' . $url);
    exit;
}

function require_login(): void
{
    if (empty($_SESSION['id'])) {
        redirect('login.php');
    }
}

function require_role(string $role): void
{
    require_login();
    if (($_SESSION['role'] ?? '') !== $role) {
        redirect($role === 'admin' ? 'dashboard_siswa.php' : 'dashboard_admin.php');
    }
}

function flash(string $type, string $message): void
{
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

function consume_flash(): ?array
{
    $flash = $_SESSION['flash'] ?? null;
    unset($_SESSION['flash']);
    return $flash;
}

function csrf_token(): string
{
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

function stmt_bind(mysqli_stmt $stmt, string $types, array &$params): void
{
    if ($types === '') return;
    $bind = [$stmt, $types];
    foreach ($params as $key => &$value) {
        $bind[] = &$value;
    }
    call_user_func_array('mysqli_stmt_bind_param', $bind);
    unset($value);
}

function verify_csrf(): void
{
    $token = $_POST['csrf'] ?? '';
    if (!$token || !hash_equals($_SESSION['csrf'] ?? '', $token)) {
        http_response_code(419);
        exit('Permintaan tidak valid. Silakan kembali dan coba lagi.');
    }
}

function format_date(?string $date): string
{
    if (!$date || $date === '0000-00-00') return '-';
    $time = strtotime($date);
    return $time ? date('d M Y', $time) : e($date);
}

function status_label(string $status): string
{
    return match ($status) {
        'pengajuan' => 'Menunggu',
        'disetujui' => 'Disetujui',
        'dipinjam' => 'Dipinjam',
        'dikembalikan' => 'Dikembalikan',
        'ditolak' => 'Ditolak',
        'dibatalkan' => 'Dibatalkan',
        default => ucfirst($status),
    };
}

function status_class(string $status): string
{
    return match ($status) {
        'dikembalikan' => 'success',
        'dipinjam', 'disetujui' => 'info',
        'ditolak', 'dibatalkan' => 'danger',
        default => 'warning',
    };
}

function icon(string $name, int $size = 20): string
{
    $paths = [
        'menu' => '<path d="M4 6h16M4 12h16M4 18h16"/>',
        'search' => '<circle cx="11" cy="11" r="7"/><path d="m20 20-4-4"/>',
        'bell' => '<path d="M18 8a6 6 0 0 0-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9M10 21h4"/>',
        'home' => '<path d="m3 10 9-7 9 7v10H3z"/><path d="M9 21v-6h6v6"/>',
        'book' => '<path d="M4 5.5A2.5 2.5 0 0 1 6.5 3H20v16H6.5A2.5 2.5 0 0 0 4 21z"/><path d="M4 5.5v15A2.5 2.5 0 0 1 6.5 18H20"/>',
        'tag' => '<path d="M20 12 12 20 3 11V4h7z"/><circle cx="7.5" cy="7.5" r="1"/>',
        'users' => '<path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75"/>',
        'swap' => '<path d="m17 3 4 4-4 4M21 7H9a4 4 0 0 0-4 4v1M7 21l-4-4 4-4M3 17h12a4 4 0 0 0 4-4v-1"/>',
        'return' => '<path d="M3 12a9 9 0 1 0 3-6.7"/><path d="M3 4v6h6"/>',
        'chart' => '<path d="M4 19V5M4 19h16"/><path d="m7 15 3-4 3 2 5-7"/>',
        'settings' => '<circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.8 1.8 0 0 0 .36 2l.06.06-1.9 1.9-.06-.06a1.8 1.8 0 0 0-2-.36 1.8 1.8 0 0 0-1.1 1.65V21h-2.7v-.81A1.8 1.8 0 0 0 11 18.54a1.8 1.8 0 0 0-2 .36l-.06.06-1.9-1.9.06-.06a1.8 1.8 0 0 0 .36-2A1.8 1.8 0 0 0 5.81 14H5v-2.7h.81A1.8 1.8 0 0 0 7.46 10a1.8 1.8 0 0 0-.36-2l-.06-.06 1.9-1.9.06.06a1.8 1.8 0 0 0 2 .36A1.8 1.8 0 0 0 12 4.81V4h2.7v.81A1.8 1.8 0 0 0 15.8 6.46a1.8 1.8 0 0 0 2-.36l.06-.06 1.9 1.9-.06.06a1.8 1.8 0 0 0-.36 2A1.8 1.8 0 0 0 21.19 12H22v2.7h-.81A1.8 1.8 0 0 0 19.4 15Z"/>',
        'user' => '<circle cx="12" cy="8" r="4"/><path d="M4 21a8 8 0 0 1 16 0"/>',
        'logout' => '<path d="M10 17l5-5-5-5M15 12H3M21 19V5a2 2 0 0 0-2-2h-5"/>',
        'plus' => '<path d="M12 5v14M5 12h14"/>',
        'edit' => '<path d="m4 16-.7 4.7L8 20l11-11a2.8 2.8 0 0 0-4-4z"/><path d="m14 6 4 4"/>',
        'trash' => '<path d="M4 7h16M10 11v6M14 11v6M6 7l1 14h10l1-14M9 7V4h6v3"/>',
        'eye' => '<path d="M2 12s3.5-6 10-6 10 6 10 6-3.5 6-10 6S2 12 2 12Z"/><circle cx="12" cy="12" r="2.5"/>',
        'arrow' => '<path d="m9 18 6-6-6-6"/>',
        'back' => '<path d="m15 18-6-6 6-6"/>',
        'calendar' => '<rect x="3" y="4" width="18" height="17" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/>',
        'file' => '<path d="M6 2h9l5 5v15H6z"/><path d="M14 2v6h6M9 13h6M9 17h6"/>',
        'save' => '<path d="M5 3h12l2 2v16H5z"/><path d="M8 3v6h8V3M8 21v-7h8v7"/>',
        'print' => '<path d="M6 9V3h12v6M6 17H4a2 2 0 0 1-2-2v-4a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v4a2 2 0 0 1-2 2h-2"/><path d="M6 14h12v7H6z"/>',
        'heart' => '<path d="M20.8 8.7c0 5.4-8.8 10.3-8.8 10.3S3.2 14.1 3.2 8.7A4.7 4.7 0 0 1 12 6.3a4.7 4.7 0 0 1 8.8 2.4Z"/>',
        'alert' => '<path d="M10.3 3.7 2.5 17a2 2 0 0 0 1.7 3h15.6a2 2 0 0 0 1.7-3L13.7 3.7a2 2 0 0 0-3.4 0Z"/><path d="M12 9v4M12 17h.01"/>',
    ];
    $path = $paths[$name] ?? $paths['file'];
    return '<svg class="icon" width="' . $size . '" height="' . $size . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' . $path . '</svg>';
}
