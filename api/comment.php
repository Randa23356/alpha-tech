<?php
// api/comment.php - Add comment to post

require_once __DIR__ . '/config.php';

// Only allow POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendResponse(false, 'Method not allowed', null, 405);
}

// Require authentication
$user = requireAuth();

// Get input
$input = getJsonInput();
$postId = (int)($input['post_id'] ?? 0);
$content = trim($input['content'] ?? $input['comment'] ?? '');

// Validate
if (!$postId) {
    sendResponse(false, 'Post ID tidak valid', null, 400);
}

if (empty($content)) {
    sendResponse(false, 'Komentar tidak boleh kosong', null, 400);
}

if (strlen($content) < 3) {
    sendResponse(false, 'Komentar minimal 3 karakter', null, 400);
}

try {
    // Check if post exists
    $stmt = $pdo->prepare("
        SELECT id FROM posts 
        WHERE id = ? AND status = 'approved' AND deleted_at IS NULL
    ");
    $stmt->execute([$postId]);
    if (!$stmt->fetch()) {
        sendResponse(false, 'Post tidak ditemukan', null, 404);
    }
    
    // Insert comment
    $stmt = $pdo->prepare("
        INSERT INTO comments (post_id, user_id, comment) 
        VALUES (?, ?, ?)
    ");
    $stmt->execute([$postId, $user['id'], $content]);
    $commentId = $pdo->lastInsertId();
    
    // Get created comment
    $stmt = $pdo->prepare("
        SELECT 
            c.id,
            c.post_id,
            c.user_id,
            c.comment as content,
            c.created_at,
            u.username,
            u.profile_pic as user_profile_picture
        FROM comments c
        JOIN users u ON c.user_id = u.id
        WHERE c.id = ?
    ");
    $stmt->execute([$commentId]);
    $comment = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Add full URL for profile picture
    if ($comment['user_profile_picture']) {
        $comment['user_profile_picture'] = 'https://alpha-tech-informatics.kesug.com/public/uploads/' . $comment['user_profile_picture'];
    }
    
    sendResponse(true, 'Komentar berhasil ditambahkan', $comment, 201);
    
} catch (Exception $e) {
    error_log('Add comment error: ' . $e->getMessage());
    sendResponse(false, 'Terjadi kesalahan server', null, 500);
}
