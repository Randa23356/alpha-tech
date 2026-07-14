<?php
// ajax/toggle_like.php - Handle like/unlike requests
require_once __DIR__ . '/../src/helpers/session.php';
require_once __DIR__ . '/../src/config/db.php';
require_once __DIR__ . '/../src/config/urls.php';

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit(json_encode(['success' => false, 'message' => 'Method not allowed']));
}

// Check if user is logged in
if (!isLoggedIn()) {
    http_response_code(401);
    exit(json_encode(['success' => false, 'message' => 'Authentication required']));
}

$targetId = isset($_POST['target_id']) ? intval($_POST['target_id']) : 0;
$type = isset($_POST['type']) ? $_POST['type'] : '';

if (!$targetId || !in_array($type, ['post', 'comment'])) {
    http_response_code(400);
    exit(json_encode(['success' => false, 'message' => 'Invalid parameters']));
}

try {
    $user = getCurrentUser();

    // Toggle the like
    $result = toggleLike($targetId, $type);

    if ($result === null) {
        throw new Exception('Failed to toggle like');
    }

    // Get updated like count
    $likeCount = getLikeCount($targetId, $type);

    // Check if now liked or unliked
    $isLiked = isLiked($targetId, $type);

    echo json_encode([
        'success' => true,
        'liked' => $isLiked,
        'like_count' => $likeCount,
        'message' => $isLiked ? 'Liked successfully' : 'Unliked successfully'
    ]);

} catch (Exception $e) {
    error_log('Toggle like error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Internal server error: ' . $e->getMessage()]);
}
?>
