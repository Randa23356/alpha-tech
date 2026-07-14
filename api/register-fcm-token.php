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

// Get user_id from input (preferred) or session (fallback)
session_start();
$user_id = $input['user_id'] ?? $_SESSION['user_id'] ?? null;

// Log for debugging
$logFile = __DIR__ . '/../debug_fcm_requests.log';
$logEntry = date('Y-m-d H:i:s') . " | IP: " . $_SERVER['REMOTE_ADDR'] . " | Token: " . substr($token, 0, 10) . "... | UserID: " . ($user_id ?? 'NULL') . " | SessionID: " . session_id() . "\n";
file_put_contents($logFile, $logEntry, FILE_APPEND);
error_log("FCM Register: " . trim($logEntry));

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
                user_id = :user_id,
                updated_at = NOW()
            WHERE token = :token
        ");
        $stmt->execute([
            'device_type' => $device_type,
            'app_version' => $app_version,
            'user_id' => $user_id,
            'token' => $token
        ]);
    } else {
        // Insert new token
        $stmt = $pdo->prepare("
            INSERT INTO fcm_tokens (token, device_type, app_version, user_id, created_at, updated_at) 
            VALUES (:token, :device_type, :app_version, :user_id, NOW(), NOW())
        ");
        $stmt->execute([
            'token' => $token,
            'device_type' => $device_type,
            'app_version' => $app_version,
            'user_id' => $user_id
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
