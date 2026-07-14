<?php
/**
 * Simple Base URL Configuration for PHP Project
 * Auto-detects environment and provides clean URL helpers
 */

// Prevent redefinition warnings
if (!defined('BASE_URL')) {
    // Environment detection
    $isLocal = isset($_SERVER['SERVER_NAME']) &&
              ($_SERVER['SERVER_NAME'] === 'localhost' || $_SERVER['SERVER_NAME'] === '127.0.0.1');

    // Base URL configuration
    define('BASE_URL', $isLocal ? '/informatics_a' : '');
    define('BASE_PATH', $_SERVER['DOCUMENT_ROOT'] . ($isLocal ? '/informatics_a' : ''));
}

/**
 * Generate clean URLs for both environments
 * @param string $path Optional path to append
 * @return string Complete URL
 */
function url($path = '') {
    $base = BASE_URL;
    $path = ltrim($path, '/');

    if (empty($path)) {
        return $base ?: '/';
    }

    return $base . '/' . $path;
}

/**
 * Generate asset URLs (CSS, JS, images)
 * @param string $path Asset path
 * @return string Complete asset URL
 */
function asset($path = '') {
    return url('public/' . ltrim($path, '/'));
}

/**
 * Generate upload URLs
 * @param string $path Upload path
 * @return string Complete upload URL
 */
function upload_url($path = '') {
    return url('public/uploads/' . ltrim($path, '/'));
}

/**
 * Get file path for assets (for file_exists checks)
 * @param string $path Asset path
 * @return string|null Full file path or null if doesn't exist
 */
function asset_path($path = '') {
    $full_path = BASE_PATH . '/public/' . ltrim($path, '/');
    return file_exists($full_path) ? $full_path : null;
}
?>
