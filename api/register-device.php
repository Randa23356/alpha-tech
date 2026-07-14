<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../src/config/config.php';
require_once __DIR__ . '/../src/includes/Database.php';

$response = ['success' => false, 'message' => ''];

try {
    // Get POST data
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($data['token']) || empty($data['token'])) {
        throw new Exception('Device token is required');
    }
    
    $userId = $_SESSION['user_id'] ?? null;
    $deviceToken = $data['token'];
    $deviceType = $data['device_type'] ?? 'android'; // or 'ios'
    
    // Validate user session
    if (!$userId) {
        throw new Exception('User not authenticated');
    }
    
    $db = Database::getInstance()->getConnection();
    
    // Check if device is already registered
    $stmt = $db->prepare("SELECT id FROM user_devices WHERE user_id = ? AND device_token = ?");
    $stmt->execute([$userId, $deviceToken]);
    
    if ($stmt->rowCount() === 0) {
        // Register new device
        $stmt = $db->prepare("
            INSERT INTO user_devices (user_id, device_token, device_type, created_at, updated_at)
            VALUES (?, ?, ?, NOW(), NOW())
        ");
        $stmt->execute([$userId, $deviceToken, $deviceType]);
    } else {
        // Update last active time
        $stmt = $db->prepare("
            UPDATE user_devices 
            SET updated_at = NOW() 
            WHERE user_id = ? AND device_token = ?
        ");
        $stmt->execute([$userId, $deviceToken]);
    }
    
    $response = [
        'success' => true,
        'message' => 'Device registered successfully'
    ];
    
} catch (Exception $e) {
    http_response_code(400);
    $response['message'] = $e->getMessage();
}

echo json_encode($response);
