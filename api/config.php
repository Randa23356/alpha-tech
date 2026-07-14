<?php
// api/config.php - API Configuration

// Enable error reporting for development
error_reporting(E_ALL);
ini_set('display_errors', 0); // Don't display errors in production
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../logs/api_errors.log');

// CORS Headers
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
header('Content-Type: application/json; charset=utf-8');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Include database connection
require_once __DIR__ . '/../src/config/db.php';

/**
 * Send JSON response
 */
function sendResponse($success, $message = '', $data = null, $statusCode = 200) {
    http_response_code($statusCode);
    echo json_encode([
        'success' => $success,
        'message' => $message,
        'data' => $data
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit();
}

/**
 * Get JSON input from request body
 */
function getJsonInput() {
    $input = file_get_contents('php://input');
    return json_decode($input, true) ?? [];
}

/**
 * Get Authorization token from header
 */
function getAuthToken() {
    // getallheaders() only works with Apache module, not CGI/FastCGI
    // Use $_SERVER fallback for compatibility with InfinityFree & similar hosts
    $authHeader = '';

    if (isset($_SERVER['HTTP_AUTHORIZATION'])) {
        $authHeader = $_SERVER['HTTP_AUTHORIZATION'];
    } elseif (isset($_SERVER['REDIRECT_HTTP_AUTHORIZATION'])) {
        $authHeader = $_SERVER['REDIRECT_HTTP_AUTHORIZATION'];
    } elseif (function_exists('getallheaders')) {
        $headers = getallheaders();
        $authHeader = $headers['Authorization'] ?? $headers['authorization'] ?? '';
    }

    if (preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
        return $matches[1];
    }

    return null;
}

/**
 * Verify API token and get user
 */
function verifyToken($token) {
    global $pdo;
    
    if (!$token) {
        return null;
    }
    
    try {
        $stmt = $pdo->prepare("
            SELECT id, username, email, full_name, role, profile_pic 
            FROM users 
            WHERE api_token = ? AND deleted_at IS NULL
        ");
        $stmt->execute([$token]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        error_log('Token verification error: ' . $e->getMessage());
        return null;
    }
}

/**
 * Require authentication
 */
function requireAuth() {
    $token = getAuthToken();
    $user = verifyToken($token);
    
    if (!$user) {
        sendResponse(false, 'Authentication required', null, 401);
    }
    
    return $user;
}

/**
 * Generate random API token
 */
function generateApiToken() {
    return bin2hex(random_bytes(32));
}

/**
 * Validate required fields
 */
function validateRequired($data, $fields) {
    $missing = [];
    foreach ($fields as $field) {
        if (!isset($data[$field]) || trim($data[$field]) === '') {
            $missing[] = $field;
        }
    }
    return $missing;
}
