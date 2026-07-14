<?php
// ajax/get_likers.php - Get list of users who liked a post/comment
require_once __DIR__ . '/../src/helpers/session.php';
require_once __DIR__ . '/../src/config/db.php';
require_once __DIR__ . '/../src/config/urls.php';

// Only allow GET requests
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    exit(json_encode(['success' => false, 'message' => 'Method not allowed']));
}

$targetId = isset($_GET['target_id']) ? intval($_GET['target_id']) : 0;
$type = isset($_GET['type']) ? $_GET['type'] : '';

if (!$targetId || !in_array($type, ['post', 'comment'])) {
    http_response_code(400);
    exit(json_encode(['success' => false, 'message' => 'Invalid parameters']));
}

try {
    // Get likers using the existing function
    $likers = getUsersWhoLiked($targetId, $type, 50); // Get up to 50 likers

    // Process profile pictures to include full URL path
    foreach ($likers as &$liker) {
        if (!empty($liker['profile_pic'])) {
            $liker['profile_pic'] = url('public/uploads/' . $liker['profile_pic']);
        }
    }

    echo json_encode([
        'success' => true,
        'likers' => $likers,
        'count' => count($likers)
    ]);

} catch (Exception $e) {
    error_log('Get likers error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Internal server error']);
}
?>
