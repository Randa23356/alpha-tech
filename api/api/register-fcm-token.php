<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

// Database connection
require_once __DIR__ . "/../src/config/db.php";

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);

if (!$input || !isset($input['token'])) {
    echo json_encode(['success' => false, 'error' => 'Token required']);
    exit;
}

$token = $input['token'];
$device_type = $input['device_type'] ?? 'android';
$app_version = $input['app_version'] ?? '1.0.0';

try {
    // Check if token already exists
    $stmt = $pdo->prepare("SELECT id FROM fcm_tokens WHERE token = :token");
    $stmt->execute(['token' => $token]);
    
    if ($stmt->fetch()) {
        // Update existing token
        $stmt = $pdo->prepare("
            UPDATE fcm_tokens 
            SET device_type = :device_type, 
                app_version = :app_version,
                updated_at = NOW()
            WHERE token = :token
        ");
        $stmt->execute([
            'device_type' => $device_type,
            'app_version' => $app_version,
            'token' => $token
        ]);
    } else {
        // Insert new token
        $stmt = $pdo->prepare("
            INSERT INTO fcm_tokens (token, device_type, app_version, created_at, updated_at) 
            VALUES (:token, :device_type, :app_version, NOW(), NOW())
        ");
        $stmt->execute([
            'token' => $token,
            'device_type' => $device_type,
            'app_version' => $app_version
        ]);
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'FCM token registered successfully'
    ]);
    
} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'error' => 'Database error: ' . $e->getMessage()
    ]);
}
?>
