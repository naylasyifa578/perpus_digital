<?php
require_once __DIR__ . '/../models/koneksi.php';
require_once __DIR__ . '/../models/auth.php';
require_once __DIR__ . '/../models/migrate.php';
require_login();
ensure_due_dates_14_days($conn);

$type = $_GET['type'] ?? '';
$id = (int)($_GET['id'] ?? 0);
$role = $_SESSION['role'] ?? '';

if (!in_array($type, ['loan', 'user'], true) || $id < 1) {
    http_response_code(400);
    exit('Permintaan tidak valid.');
}
if ($type === 'user' && $role !== 'admin') {
    http_response_code(403);
    exit('Akses ditolak.');
}

function svg_escape($v): string
{
    return htmlspecialchars((string)$v, ENT_QUOTES | ENT_XML1, 'UTF-8');
}
function svg_date($v): string
{
    return $v ? date('d/m/Y', strtotime($v)) : '-';
}
function svg_status($v): string
{
    $map = ['pengajuan' => 'Pengajuan', 'disetujui' => 'Disetujui', 'dipinjam' => 'Dipinjam', 'dikembalikan' => 'Dikembalikan', 'ditolak' => 'Ditolak', 'dibatalkan' => 'Dibatalkan'];
    return $map[$v] ?? (string)$v;
}
function wrap_text(string $text, int $max = 42): array
{
    $text = trim($text);
    if ($text === '') return ['-'];
    $words = preg_split('/\s+/', $text);
    $lines = [];
    $line = '';
    foreach ($words as $word) {
        $test = $line === '' ? $word : $line . ' ' . $word;
        if (mb_strlen($test) > $max && $line !== '') {
            $lines[] = $line;
            $line = $word;
        } else $line = $test;
    }
    if ($line !== '') $lines[] = $line;
    return $lines;
}

$rows = [];
$title = '';
$filename = '';
if ($type === 'loan') {
    $s = mysqli_prepare($conn, "SELECT p.*,u.nama_lengkap,u.username,u.gmail,b.judul,b.pengarang,b.penerbit,b.gambar,k.Kategori 
    FROM peminjaman p JOIN users u ON u.id=p.user_id JOIN buku b ON b.id=p.buku_id LEFT JOIN kategori k ON k.id=b.kategori_id WHERE p.id=? LIMIT 1");
    mysqli_stmt_bind_param($s, 'i', $id);
    mysqli_stmt_execute($s);
    $r = mysqli_fetch_assoc(mysqli_stmt_get_result($s));
    mysqli_stmt_close($s);
    if (!$r || ($role === 'siswa' && (int)$r['user_id'] !== (int)$_SESSION['id'])) {
        http_response_code(404);
        exit('Data peminjaman tidak ditemukan.');
    }
    $coverData = null;
    if (!empty($r['gambar'])) {
        $coverPath = __DIR__ . '/../assets/' . ltrim($r['gambar'], '/');
        if (is_file($coverPath)) {
            $mime = function_exists('mime_content_type') ? mime_content_type($coverPath) : 'image/jpeg';
            $coverData = 'data:' . $mime . ';base64,' . base64_encode(file_get_contents($coverPath));
        }
    }
    $rows = [
        ['ID Peminjaman', 'PM-' . str_pad((string)$r['id'], 4, '0', STR_PAD_LEFT)],
        ['Peminjam', $r['nama_lengkap']],
        ['Username', $r['username']],
        ['Email', $r['gmail'] ?? '-'],
        ['Buku', $r['judul']],
        ['Kategori', $r['Kategori'] ?? '-'],
        ['Pengarang', $r['pengarang']],
        ['Penerbit', $r['penerbit']],
        ['Tanggal Pinjam', svg_date($r['tanggal_pinjam'])],
        ['Jatuh Tempo', svg_date(calculate_due_date($r['tanggal_pinjam']))],
        ['Status', svg_status($r['status'])]
    ];
    $s = mysqli_prepare($conn, 'SELECT * FROM pengembalian WHERE peminjaman_id=? ORDER BY tanggal_pengembalian DESC LIMIT 1');
    mysqli_stmt_bind_param($s, 'i', $id);
    mysqli_stmt_execute($s);
    $ret = mysqli_fetch_assoc(mysqli_stmt_get_result($s));
    mysqli_stmt_close($s);
    if ($ret) {
        $rows[] = ['Tanggal Pengembalian', svg_date($ret['tanggal_pengembalian'])];
        $rows[] = ['Sanksi', ((int)((strtotime($ret['tanggal_pengembalian']) - strtotime(calculate_due_date($r['tanggal_pinjam']))) / 86400) > 0 ? 'Peminjaman dinonaktifkan 14 hari' : 'Tidak ada')];
        $rows[] = ['Keterangan', $ret['keterangan'] ?? '-'];
    }
    $title = 'Detail Peminjaman';
    $filename = 'Aksaraya_Detail_Peminjaman_PM-' . str_pad((string)$r['id'], 4, '0', STR_PAD_LEFT) . '.svg';
} else {
    $s = mysqli_prepare($conn, "SELECT id,username,nama_lengkap,role,created_at,gmail,blocked_until FROM users WHERE id=? AND role='siswa' LIMIT 1");
    mysqli_stmt_bind_param($s, 'i', $id);
    mysqli_stmt_execute($s);
    $r = mysqli_fetch_assoc(mysqli_stmt_get_result($s));
    mysqli_stmt_close($s);
    if (!$r) {
        http_response_code(404);
        exit('Data user tidak ditemukan.');
    }
    $rows = [['Nama Lengkap', $r['nama_lengkap']], ['Username', $r['username']], ['Email', $r['gmail'] ?? '-'], ['Terdaftar', svg_date($r['created_at'])], ['Status Peminjaman', ($r['blocked_until'] && $r['blocked_until'] >= date('Y-m-d') ? 'Dibatasi sampai ' . svg_date($r['blocked_until']) : 'Aktif')]];
    $title = 'Detail User';
    $filename = 'Aksaraya_Detail_User_' . preg_replace('/[^A-Za-z0-9_-]+/', '_', $r['username']) . '.svg';
}

$width = 1000;
$x = 70;
$y = 60;
$rowY = 245;
$rowH = 58;
$height = max(610, $rowY + count($rows) * $rowH + 90);
$svg = [];
$svg[] = '<?xml version="1.0" encoding="UTF-8"?>';
$svg[] = '<svg xmlns="http://www.w3.org/2000/svg" width="' . $width . '" height="' . $height . '" viewBox="0 0 ' . $width . ' ' . $height . '">';
$svg[] = '<defs><linearGradient id="bg" x1="0" y1="0" x2="1" y2="1"><stop offset="0" stop-color="#eef1ff"/><stop offset="1" stop-color="#f8f9ff"/></linearGradient>
<filter id="shadow" x="-20%" y="-20%" width="140%" height="140%"><feDropShadow dx="0" dy="10" stdDeviation="14" flood-color="#5269cf" flood-opacity=".13"/></filter></defs>';
$svg[] = '<rect width="1000" height="' . $height . '" fill="url(#bg)"/>';
$svg[] = '<rect x="48" y="38" width="904" height="' . ($height - 76) . '" rx="28" fill="#fff" filter="url(#shadow)"/>';
$svg[] = '<rect x="48" y="38" width="904" height="12" rx="6" fill="#5269cf"/>';
$svg[] = '<text x="80" y="105" font-family="Poppins,Arial,sans-serif" font-size="30" font-weight="700" fill="#20242a">Aksaraya</text>';
$svg[] = '<text x="80" y="130" font-family="Poppins,Arial,sans-serif" font-size="14" fill="#8a90a0">Perpustakaan Digital</text>';
$svg[] = '<text x="80" y="190" font-family="Poppins,Arial,sans-serif" font-size="25" font-weight="700" fill="#20242a">' . svg_escape($title) . '</text>';
$svg[] = '<text x="80" y="215" font-family="Poppins,Arial,sans-serif" font-size="12" fill="#8a90a0">Dokumen digital dari aplikasi Aksaraya</text>';
if ($type === 'loan' && $coverData) {
    $svg[] = '<defs><clipPath id="coverClip"><rect x="785" y="78" width="115" height="145" rx="12"/></clipPath></defs>';
    $svg[] = '<rect x="785" y="78" width="115" height="145" rx="12" fill="#eef1ff"/>';
    $svg[] = '<image href="' . svg_escape($coverData) . '" x="785" y="78" width="115" height="145" preserveAspectRatio="xMidYMid slice" clip-path="url(#coverClip)"/>';
}
$idx = 0;
foreach ($rows as $pair) {
    $yy = $rowY + $idx * $rowH;
    $fill = ($idx % 2 === 0) ? '#f4f6ff' : '#ffffff';
    $svg[] = '<rect x="70" y="' . $yy . '" width="860" height="48" rx="10" fill="' . $fill . '"/>';
    $svg[] = '<text x="90" y="' . ($yy + 29) . '" font-family="Poppins,Arial,sans-serif" font-size="13" font-weight="600" fill="#333947">' . svg_escape($pair[0]) . '</text>';
    $lines = wrap_text((string)$pair[1], 52);
    foreach (array_slice($lines, 0, 2) as $li => $line) {
        $svg[] = '<text x="360" y="' . ($yy + 23 + $li * 15) . '" font-family="Poppins,Arial,sans-serif" font-size="13" fill="#5d6472">' . svg_escape($line) . '</text>';
    }
    $idx++;
}
$footerY = $rowY + $idx * $rowH + 35;
$svg[] = '<line x1="80" y1="' . $footerY . '" x2="920" y2="' . $footerY . '" stroke="#e2e4eb"/>';
$svg[] = '<text x="80" y="' . ($footerY + 28) . '" font-family="Poppins,Arial,sans-serif" font-size="11" fill="#9aa0ae">Disimpan ke perangkat • ' . date('d/m/Y H:i') . '</text>';
$svg[] = '<text x="920" y="' . ($footerY + 28) . '" text-anchor="end" font-family="Poppins,Arial,sans-serif" font-size="11" font-weight="600" fill="#5269cf">AKSARAYA</text>';
$svg[] = '</svg>';

header('Content-Type: image/svg+xml; charset=UTF-8');
header('Content-Disposition: attachment; filename="' . str_replace('"', '', $filename) . '"');
header('Content-Length: ' . strlen(implode('', $svg)));
echo implode('', $svg);
