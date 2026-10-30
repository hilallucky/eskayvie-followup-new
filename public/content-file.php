<?php
require __DIR__ . '/../src/bootstrap.php';
$file = ContentLibrary::file((string)($_GET['id'] ?? ''), !Auth::canManageContent());
if ($file === null) {
    http_response_code(404);
    header('Content-Type: text/plain; charset=UTF-8');
    exit('File not found.');
}

$download = isset($_GET['download']) && $_GET['download'] === '1';
header('X-Content-Type-Options: nosniff');
header('Cache-Control: public, max-age=300');

if (!$download && in_array($file['extension'], ['csv', 'xlsx', 'docx'], true)) {
    header('Content-Type: text/html; charset=UTF-8');
    header("Content-Security-Policy: default-src 'none'; style-src 'unsafe-inline'; sandbox");
    echo ContentLibrary::renderPreview($file);
    exit;
}

$inlineTypes = ['gif', 'jpeg', 'jpg', 'pdf', 'png', 'txt', 'webp'];
$disposition = $download || !in_array($file['extension'], $inlineTypes, true) ? 'attachment' : 'inline';
$fallbackName = preg_replace('/[^A-Za-z0-9._-]/', '_', $file['name']);
header('Content-Type: ' . $file['mime']);
header("Content-Disposition: $disposition; filename=\"$fallbackName\"; filename*=UTF-8''" . rawurlencode($file['name']));
header('Content-Length: ' . $file['size']);
readfile($file['path']);
