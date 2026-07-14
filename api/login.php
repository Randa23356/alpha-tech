<?php
// api/login.php - Login endpoint for mobile app

require_once __DIR__ . '/config.php';

// Only allow POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendResponse(false, 'Method not allowed', null, 405);
}

// Get input
$input = getJsonInput();
$username = trim($input['username'] ?? '');
$password = $input['password'] ?? '';

// Validate
if (!$username || !$password) {
    sendResponse(false, 'Username dan password harus diisi', null, 400);
}

try {
    // Check if deleted_at column exists
    $hasDeletedAt = false;
    try {
        $check = $pdo->query("SHOW COLUMNS FROM users LIKE 'deleted_at'")->fetch();
        $hasDeletedAt = !empty($check);
    } catch (Exception $e) {}

    $deletedAtCondition = $hasDeletedAt ? 'AND deleted_at IS NULL' : '';

    // Find user
    $stmt = $pdo->prepare("
        SELECT id, username, email, full_name, password, role, profile_pic 
        FROM users 
        WHERE username = ? $deletedAtCondition
    ");
    $stmt->execute([$username]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    // Verify password
    if (!$user || !password_verify($password, $user['password'])) {
        sendResponse(false, 'Username atau password salah', null, 401);
    }

    // Generate API token
    $token = generateApiToken();

    // Check if api_token column exists, if not skip the update
    $hasApiToken = false;
    try {
        $check = $pdo->query("SHOW COLUMNS FROM users LIKE 'api_token'")->fetch();
        $hasApiToken = !empty($check);
    } catch (Exception $e) {}

    if ($hasApiToken) {
        $stmt = $pdo->prepare("UPDATE users SET api_token = ? WHERE id = ?");
        $stmt->execute([$token, $user['id']]);
    }

    // Prepare response
    unset($user['password']); // Don't send password
    $user['token'] = $token;

    sendResponse(true, 'Login berhasil', $user);

} catch (Exception $e) {
    error_log('Login error: ' . $e->getMessage());
    // Temporarily show actual error for debugging
    sendResponse(false, 'Error: ' . $e->getMessage(), null, 500);
}

