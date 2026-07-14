<?php
// trash.php - Main entry point for trash functionality
// Add proper authentication check here since .htaccess doesn't handle PHP auth
require_once __DIR__ . '/src/helpers/session.php';

if (!isLoggedIn() || !isUser()) {
    header("Location: " . url('login'));
    exit();
}

require_once __DIR__ . '/views/user/trash.php';
?>
