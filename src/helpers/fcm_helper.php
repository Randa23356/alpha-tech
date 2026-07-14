<?php
// FCM Helper Functions - Firebase Cloud Messaging V1 API

// IMPORTANT: Download service account JSON from Firebase Console
// Location: Firebase Console → Project Settings → Service Accounts → Generate new private key
// Save file as: firebase-service-account.json in same directory as this file

// Firebase project ID from google-services.json
define('FIREBASE_PROJECT_ID', 'alpha-tech-9b9af');

/**
 * Get OAuth2 access token from service account
 */
function getFCMAccessToken() {
    $serviceAccountPath = __DIR__ . '/../../firebase-service-account.json';
    
    if (!file_exists($serviceAccountPath)) {
        error_log('ERROR: firebase-service-account.json not found at: ' . $serviceAccountPath);
        return null;
    }
    
    $serviceAccount = json_decode(file_get_contents($serviceAccountPath), true);
    
    // Create JWT for OAuth2
    $now = time();
    $payload = [
        'iss' => $serviceAccount['client_email'],
        'sub' => $serviceAccount['client_email'],
        'aud' => 'https://oauth2.googleapis.com/token',
        'iat' => $now,
        'exp' => $now + 3600,
        'scope' => 'https://www.googleapis.com/auth/firebase.messaging'
    ];
    
    // Base64url encode (URL-safe base64)
    $base64url_encode = function($data) {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    };
    
    // Simple JWT encode (for production, use firebase/php-jwt library)
    $header = $base64url_encode(json_encode(['alg' => 'RS256', 'typ' => 'JWT']));
    $payload = $base64url_encode(json_encode($payload));
    $signature = '';
    
    openssl_sign(
        $header . '.' . $payload,
        $signature,
        $serviceAccount['private_key'],
        'SHA256'
    );
    
    $jwt = $header . '.' . $payload . '.' . $base64url_encode($signature);
    
    // Exchange JWT for access token
    $ch = curl_init('https://oauth2.googleapis.com/token');
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
        'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
        'assertion' => $jwt
    ]));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode !== 200) {
        error_log('OAuth2 token error: ' . $response);
        return null;
    }
    
    $data = json_decode($response, true);
    return $data['access_token'] ?? null;
}

/**
 * Send FCM push notification to active devices (V1 API)
 * 
 * @param string $title Notification title
 * @param string $body Notification body/message
 * @param array $data Additional data (optional)
 * @param string|array $target Target audience ('all', 'logged_in', or array of user_ids)
 * @return array ['success' => bool, 'sent' => int, 'failed' => int, 'errors' => array]
 */
function sendFCMNotification($title, $body, $data = [], $target = 'all') {
    global $pdo;
    
    $result = [
        'success' => false,
        'sent' => 0,
        'failed' => 0,
        'errors' => []
    ];
    
    // Get access token
    $accessToken = getFCMAccessToken();
    if (!$accessToken) {
        $result['errors'][] = 'Failed to get FCM access token';
        error_log('Failed to get FCM access token');
        return $result;
    }
    
    // Build query based on target
    $sql = "SELECT token FROM fcm_tokens WHERE is_active = 1";
    $params = [];
    
    if ($target === 'logged_in') {
        $sql .= " AND user_id IS NOT NULL";
    } elseif (is_array($target)) {
        $placeholders = implode(',', array_fill(0, count($target), '?'));
        $sql .= " AND user_id IN ($placeholders)";
        $params = $target;
    }
    
    // Get tokens
    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $tokens = $stmt->fetchAll(PDO::FETCH_COLUMN);
    } catch (PDOException $e) {
        $result['errors'][] = 'Database error: ' . $e->getMessage();
        error_log('FCM DB Error: ' . $e->getMessage());
        return $result;
    }
    
    if (empty($tokens)) {
        $result['errors'][] = 'No active FCM tokens found for target: ' . (is_array($target) ? 'users' : $target);
        // Don't log as error if just no tokens found, it's common
        return $result;
    }
    
    // Send to each token individually (V1 API limitation)
    foreach ($tokens as $token) {
        $message = [
            'message' => [
                'token' => $token,
                'notification' => [
                    'title' => $title,
                    'body' => $body
                ],
                'data' => $data,
                'android' => [
                    'priority' => 'high',
                    'notification' => [
                        'sound' => 'default',
                        'click_action' => 'FLUTTER_NOTIFICATION_CLICK'
                    ]
                ]
            ]
        ];
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'https://fcm.googleapis.com/v1/projects/' . FIREBASE_PROJECT_ID . '/messages:send');
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $accessToken,
            'Content-Type: application/json'
        ]);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($message));
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode == 200) {
            $result['sent']++;
            error_log("✅ FCM sent to token: " . substr($token, 0, 20) . "...");
        } else {
            $result['failed']++;
            $errorResponse = json_decode($response, true);
            $errorMsg = $errorResponse['error']['message'] ?? $response;
            $errorDetail = "Token: " . substr($token, 0, 20) . "... | HTTP $httpCode | Error: $errorMsg";
            $result['errors'][] = $errorDetail;
            error_log("❌ FCM failed: " . $errorDetail);
        }
    }
    
    $result['success'] = $result['sent'] > 0;
    error_log("FCM notification sent to {$result['sent']} devices, {$result['failed']} failed");
    return $result;
}

/**
 * Send notification when new post is created
 * 
 * @param int $postId Post ID
 * @param string $postTitle Post title
 */
function notifyNewPost($postId, $postTitle) {
    // Posts are sent to ALL users (public)
    sendFCMNotification(
        'Kegiatan Baru! 🎉',
        $postTitle,
        [
            'type' => 'new_post',
            'post_id' => (string)$postId
        ],
        'all'
    );
}

/**
 * Send notification when new announcement is created
 * 
 * @param int $announcementId Announcement ID
 * @param string $announcementTitle Announcement title
 */
function notifyNewAnnouncement($announcementId, $announcementTitle) {
    // Announcements are sent ONLY to logged-in users
    sendFCMNotification(
        'Pengumuman Baru! 📢',
        $announcementTitle,
        [
            'type' => 'new_announcement',
            'announcement_id' => (string)$announcementId
        ],
        'logged_in'
    );
}

/**
 * Send bulk notification for multiple new items
 * 
 * @param int $newPosts Number of new posts
 * @param int $newAnnouncements Number of new announcements
 */
function notifyBulkUpdates($newPosts = 0, $newAnnouncements = 0) {
    if ($newPosts > 0 && $newAnnouncements > 0) {
        $message = "Ada {$newPosts} kegiatan baru dan {$newAnnouncements} pengumuman baru!";
    } elseif ($newPosts > 0) {
        $message = "Ada {$newPosts} kegiatan baru!";
    } elseif ($newAnnouncements > 0) {
        $message = "Ada {$newAnnouncements} pengumuman baru!";
    } else {
        return false;
    }
    
    sendFCMNotification(
        'Alpha Tech Informatics',
        $message,
        [
            'type' => 'bulk_update',
            'new_posts' => (string)$newPosts,
            'new_announcements' => (string)$newAnnouncements
        ],
        'all' // Bulk updates sent to all for engagement
    );
}

/**
 * Send personal notification to specific user (by user_id)
 * Only sends if user has registered FCM token
 * 
 * @param int $userId User ID
 * @param string $title Notification title
 * @param string $body Notification message
 * @param array $data Additional data (optional)
 * @return bool Success status
 */
function sendPersonalNotification($userId, $title, $body, $data = []) {
    $result = sendFCMNotification($title, $body, $data, [$userId]);
    return $result['success'];
}

/**
 * Notify user after successful login
 * 
 * @param int $userId User ID
 * @param string $username Username
 */
function notifyLogin($userId, $username) {
    sendPersonalNotification(
        $userId,
        'Login Berhasil! 👋',
        "Selamat datang kembali, {$username}!",
        [
            'type' => 'login',
            'user_id' => (string)$userId
        ]
    );
}

/**
 * Notify user after successful registration
 * 
 * @param int $userId User ID
 * @param string $username Username
 */
function notifyRegistration($userId, $username) {
    sendPersonalNotification(
        $userId,
        'Pendaftaran Berhasil! 🎉',
        "Selamat datang di Alpha Tech, {$username}!",
        [
            'type' => 'registration',
            'user_id' => (string)$userId
        ]
    );
}
?>
