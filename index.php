<?php
// Root index.php - Simple router for PHP built-in server
require_once __DIR__ . "/src/config/db.php";
require_once __DIR__ . "/src/config/urls.php";

// Get the requested path
$request_uri = $_SERVER['REQUEST_URI'] ?? '/';

// Remove query string
$path = parse_url($request_uri, PHP_URL_PATH);

// Remove leading slash
$path = ltrim($path, '/');

// Route to appropriate files
if (preg_match('/^informatics_a\/public\/(.+)$/', $path, $matches)) {
    $file = $matches[1];
    if (file_exists(__DIR__ . '/public/' . $file)) {
        require __DIR__ . '/public/' . $file;
        exit();
    }
} elseif (preg_match('/^informatics_a\/ajax\/(.+)$/', $path, $matches)) {
    $file = $matches[1];
    if (file_exists(__DIR__ . '/ajax/' . $file)) {
        require __DIR__ . '/ajax/' . $file;
        exit();
    }
} elseif (preg_match('/^informatics_a\/admin\/(.+)$/', $path, $matches)) {
    $file = $matches[1];
    if (file_exists(__DIR__ . '/admin/' . $file)) {
        require __DIR__ . '/admin/' . $file;
        exit();
    }
} elseif (preg_match('/^informatics_a\/korti\/(.+)$/', $path, $matches)) {
    $file = $matches[1];
    if (file_exists(__DIR__ . '/korti/' . $file)) {
        require __DIR__ . '/korti/' . $file;
        exit();
    }
}

// Check for direct file access with informatics_a prefix
$direct_files = [
    'login.php', 'register.php', 'logout.php', 'google-callback.php',
    'activity_detail.php', 'user_profile.php', 'gallery.php', 'contact.php',
    'announcement.php', 'download.php', 'trash.php'
];

foreach ($direct_files as $file) {
    if ($path === 'informatics_a/' . $file || $path === 'informatics_a/' . basename($file, '.php')) {
        require __DIR__ . '/' . $file;
        exit();
    }
}

// Default: serve public index
require __DIR__ . '/public/index.php';
exit();
?>
