<?php
function upload_image(array $file, string $directory, int $maxBytes = 2097152): string
{
    if (!isset($file['error']) || $file['error'] === UPLOAD_ERR_NO_FILE) return '';
    if ($file['error'] !== UPLOAD_ERR_OK) throw new RuntimeException('Upload gambar gagal.');
    if ($file['size'] > $maxBytes) throw new RuntimeException('Ukuran gambar maksimal 2 MB.');
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = $finfo->file($file['tmp_name']);
    $allowed = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
    if (!isset($allowed[$mime])) throw new RuntimeException('Format gambar harus JPG, PNG, atau WEBP.');
    if (@getimagesize($file['tmp_name']) === false) throw new RuntimeException('File bukan gambar yang valid.');
    if (!is_dir($directory) && !mkdir($directory, 0755, true)) throw new RuntimeException('Folder upload tidak dapat dibuat.');
    $name = bin2hex(random_bytes(12)) . '.' . $allowed[$mime];
    $target = rtrim($directory, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $name;
    if (!move_uploaded_file($file['tmp_name'], $target)) throw new RuntimeException('Gambar gagal disimpan.');
    return $name;
}
function upload_book_cover(?array $file, ?string $oldCover = null): string
{
    if (!$file || ($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) return (string)($oldCover ?? '');
    $dir = __DIR__ . '/../assets/covers';
    $new = upload_image($file, $dir);
    if ($oldCover) delete_uploaded_image($oldCover, $dir);
    return 'covers/' . $new;
}
function upload_profile_photo(?array $file, ?string $oldPhoto = null): string
{
    if (!$file || ($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) return (string)($oldPhoto ?? '');
    $dir = __DIR__ . '/../assets/profiles';
    $new = upload_image($file, $dir);
    if ($oldPhoto) delete_uploaded_image($oldPhoto, $dir);
    return 'profiles/' . $new;
}
function delete_uploaded_image(?string $relative, string $baseDir): void
{
    if (!$relative) return;
    $base = realpath($baseDir);
    if (!$base) return;
    $candidate = realpath($baseDir . '/' . basename($relative));
    if ($candidate && strpos($candidate, $base . DIRECTORY_SEPARATOR) === 0 && is_file($candidate)) @unlink($candidate);
}
