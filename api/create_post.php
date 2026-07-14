<?php
// api/create_post.php - Create new post

require_once __DIR__ . '/config.php';

// Only allow POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendResponse(false, 'Method not allowed', null, 405);
}

// Require authentication
$user = requireAuth();

// Get input
$input = getJsonInput();
$title = trim($input['title'] ?? '');
$content = trim($input['content'] ?? '');
$date = $input['date'] ?? date('Y-m-d');

// Validate
if (empty($content)) {
    sendResponse(false, 'Konten tidak boleh kosong', null, 400);
}

// Default title if empty
if (empty($title)) {
    $title = substr($content, 0, 50) . (strlen($content) > 50 ? '...' : '');
}

try {
    // Insert post
    $stmt = $pdo->prepare("
        INSERT INTO posts (user_id, title, content, date, status) 
        VALUES (?, ?, ?, ?, 'pending')
    ");
    $stmt->execute([$user['id'], $title, $content, $date]);
    $postId = $pdo->lastInsertId();
    
    // Get created post
    $stmt = $pdo->prepare("
        SELECT 
            p.id,
            p.user_id,
            p.title,
            p.content,
            p.date,
            p.status,
            p.created_at,
            u.username,
            u.profile_pic as user_profile_picture
        FROM posts p
        JOIN users u ON p.user_id = u.id
        WHERE p.id = ?
    ");
    $stmt->execute([$postId]);
    $post = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Add full URL for profile picture
    if ($post['user_profile_picture']) {
        $post['user_profile_picture'] = 'https://alpha-tech-informatics.kesug.com/public/uploads/' . $post['user_profile_picture'];
    }
    
    $post['likes_count'] = 0;
    $post['comments_count'] = 0;
    $post['is_liked'] = false;
    $post['images'] = [];
    
    sendResponse(true, 'Post berhasil dibuat dan menunggu persetujuan', $post, 201);
    
} catch (Exception $e) {
    error_log('Create post error: ' . $e->getMessage());
    sendResponse(false, 'Terjadi kesalahan server', null, 500);
}
