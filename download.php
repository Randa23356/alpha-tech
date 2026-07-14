<?php
// download.php - Handle file downloads
session_start();
require_once __DIR__ . "/src/helpers/session.php";

// Proteksi: hanya user yang login bisa download
if (!isLoggedIn()) {
    header("Location: " . url('login'));
    exit();
}

// Validasi parameter
if (!isset($_GET['file']) || !isset($_GET['name'])) {
    die("File tidak ditemukan.");
}

$file_path = $_GET['file'];
$file_name = $_GET['name'];

// Security: Pastikan file path tidak mengandung directory traversal
if (strpos($file_path, '..') !== false || strpos($file_path, '/') === 0) {
    die("Akses ditolak.");
}

// Full path ke file
$full_path = __DIR__ . '/public/uploads/' . $file_path;

// Debug: Log request
error_log('Download request: file=' . $_GET['file'] . ', name=' . $_GET['name'] . ', full_path=' . $full_path);

// Get file info
$file_size = filesize($full_path);
$file_extension = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));

// Set content type berdasarkan extension
$content_types = [
    'pdf' => 'application/pdf',
    'doc' => 'application/msword',
    'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
    'xls' => 'application/vnd.ms-excel',
    'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
    'zip' => 'application/zip',
    'rar' => 'application/x-rar-compressed',
    'jpg' => 'image/jpeg',
    'jpeg' => 'image/jpeg',
    'png' => 'image/png',
    'gif' => 'image/gif'
];

$content_type = $content_types[$file_extension] ?? 'application/octet-stream';

// Set headers untuk download
header('Content-Description: File Transfer');
header('Content-Type: ' . $content_type);
header('Content-Disposition: attachment; filename="' . basename($file_name) . '"');
header('Content-Transfer-Encoding: binary');
header('Expires: 0');
header('Cache-Control: must-revalidate');
header('Pragma: public');
header('Content-Length: ' . $file_size);

// Clear output buffer
ob_clean();
flush();

// Read file dan output
readfile($full_path);
exit();
?>
