<?php
// src/config/config.php

// Detect environment first so $is_local is available for error reporting
$http_host = $_SERVER['HTTP_HOST'] ?? 'localhost';
$is_local = ($http_host === 'localhost' || $http_host === '127.0.0.1' || strpos($http_host, '.local') !== false);

// Set default timezone
date_default_timezone_set('Asia/Jakarta');

// Enable error reporting based on environment
if ($is_local) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(E_ALL);
    ini_set('display_errors', 0);
    ini_set('log_errors', 1);
}

// Configure persistent session (1 year)
// Skip session for API endpoints (they use token auth, not sessions)
$is_api_request = strpos($_SERVER['SCRIPT_FILENAME'] ?? '', '/api/') !== false;

if (!$is_api_request && session_status() === PHP_SESSION_NONE) {
    $session_lifetime = 31536000; // 1 year in seconds
    ini_set('session.gc_maxlifetime', $session_lifetime);
    ini_set('session.cookie_lifetime', $session_lifetime);
    session_set_cookie_params($session_lifetime);
    session_start();
}

// Base URL settings
if (!defined('BASE_URL')) {
    define('BASE_URL', $is_local 
        ? 'http://localhost/informatics_a'  // Local development
        : 'https://alpha-tech-informatics.kesug.com'  // Production
    );
}

// Database settings
if ($is_local) {
    // ✅ Local XAMPP
    $db_host = 'localhost';
    $db_name = 'alpha_tech';
    $db_user = 'root';
    $db_pass = '';
} else {
    // ✅ InfinityFree Hosting
    $db_host = 'sql111.infinityfree.com';        // Cek di cPanel → MySQL Databases
    $db_name = 'if0_40177206_alpha_tech';        // Format: if0_XXXXX_namadb
    $db_user = 'if0_40177206';                   // Username InfinityFree kamu
    $db_pass = 'WgImWk8kPP';                    // Password yang kamu set di cPanel
}

// Debug log
if ($is_local) {
    error_log('Local environment detected');
    error_log("DB Config: Host=$db_host, DB=$db_name, User=$db_user");
}

// Other constants
if (!defined('SITE_NAME')) define('SITE_NAME', 'Informatics A');
if (!defined('UPLOAD_PATH')) define('UPLOAD_PATH', __DIR__ . '/../../public/uploads/');
if (!defined('GOOGLE_CLIENT_ID')) define('GOOGLE_CLIENT_ID', '729994754873-m7qbjk8jafgqvrvpmpspjnqpqaeuirj1.apps.googleusercontent.com');
if (!defined('GOOGLE_CLIENT_SECRET')) define('GOOGLE_CLIENT_SECRET', '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');
if (!defined('GOOGLE_REDIRECT_URI')) define('GOOGLE_REDIRECT_URI', 'https://alpha-tech-informatics.kesug.com/google-callback.php');
if (!defined('GOOGLE_SCOPES')) define('GOOGLE_SCOPES', ['email', 'profile']);
?>