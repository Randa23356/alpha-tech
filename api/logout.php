<?php
// api/logout.php - Logout endpoint

require_once __DIR__ . '/config.php';

// Only allow POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendResponse(false, 'Method not allowed', null, 405);
}

// Require authentication
$user = requireAuth();

try {
    // Clear API token
    $stmt = $pdo->prepare("UPDATE users SET api_token = NULL WHERE id = ?");
    $stmt->execute([$user['id']]);
    
    sendResponse(true, 'Logout berhasil');
    
} catch (Exception $e) {
    error_log('Logout error: ' . $e->getMessage());
    sendResponse(false, 'Terjadi kesalahan server', null, 500);
}
