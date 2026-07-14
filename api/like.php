<?php
// api/like.php - Toggle like on post or comment

require_once __DIR__ . '/config.php';

// Only allow POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendResponse(false, 'Method not allowed', null, 405);
}

// Require authentication
$user = requireAuth();

// Get input
$input = getJsonInput();
$targetId = (int)($input['post_id'] ?? $input['target_id'] ?? 0);
$type = $input['type'] ?? 'post'; // 'post' or 'comment'

// Validate
if (!$targetId || !in_array($type, ['post', 'comment'])) {
    sendResponse(false, 'Parameter tidak valid', null, 400);
}

try {
    // Check if already liked
    $stmt = $pdo->prepare("
        SELECT id FROM likes 
        WHERE user_id = ? AND target_id = ? AND type = ?
    ");
    $stmt->execute([$user['id'], $targetId, $type]);
    $existingLike = $stmt->fetch();
    
    if ($existingLike) {
        // Unlike - remove the like
        $stmt = $pdo->prepare("DELETE FROM likes WHERE id = ?");
        $stmt->execute([$existingLike['id']]);
        $isLiked = false;
        $message = 'Like dibatalkan';
    } else {
        // Like - add the like
        $stmt = $pdo->prepare("
            INSERT INTO likes (user_id, target_id, type) 
            VALUES (?, ?, ?)
        ");
        $stmt->execute([$user['id'], $targetId, $type]);
        $isLiked = true;
        $message = 'Post disukai';
    }
    
    // Get updated like count
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as count 
        FROM likes 
        WHERE target_id = ? AND type = ?
    ");
    $stmt->execute([$targetId, $type]);
    $likesCount = (int)$stmt->fetch()['count'];
    
    sendResponse(true, $message, [
        'is_liked' => $isLiked,
        'likes_count' => $likesCount
    ]);
    
} catch (Exception $e) {
    error_log('Toggle like error: ' . $e->getMessage());
    sendResponse(false, 'Terjadi kesalahan server', null, 500);
}
