<?php
// api/posts.php - Get all approved posts

require_once __DIR__ . '/config.php';

// Only allow GET
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    sendResponse(false, 'Method not allowed', null, 405);
}

// Require authentication
$user = requireAuth();

try {
    // Get all approved posts with user info and images
    $stmt = $pdo->prepare("
        SELECT 
            p.id,
            p.user_id,
            p.title,
            p.content,
            p.date,
            p.created_at,
            u.username,
            u.profile_pic as user_profile_picture,
            (SELECT COUNT(*) FROM likes WHERE target_id = p.id AND type = 'post') as likes_count,
            (SELECT COUNT(*) FROM comments WHERE post_id = p.id AND deleted_at IS NULL) as comments_count,
            EXISTS(SELECT 1 FROM likes WHERE target_id = p.id AND type = 'post' AND user_id = ?) as is_liked
        FROM posts p
        JOIN users u ON p.user_id = u.id
        WHERE p.status = 'approved' AND p.deleted_at IS NULL
        ORDER BY p.created_at DESC
    ");
    $stmt->execute([$user['id']]);
    $posts = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get images for each post
    foreach ($posts as &$post) {
        $stmt = $pdo->prepare("
            SELECT image_path, image_order 
            FROM post_images 
            WHERE post_id = ? 
            ORDER BY image_order
        ");
        $stmt->execute([$post['id']]);
        $images = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Add full URL for images
        $post['images'] = array_map(function($img) {
            return [
                'image_path' => $img['image_path'],
                'image_url' => 'https://alpha-tech-informatics.kesug.com/public/uploads/' . $img['image_path'],
                'image_order' => $img['image_order']
            ];
        }, $images);
        
        // Add full URL for profile picture
        if ($post['user_profile_picture']) {
            $post['user_profile_picture'] = 'https://alpha-tech-informatics.kesug.com/public/uploads/' . $post['user_profile_picture'];
        }
        
        // Convert boolean
        $post['is_liked'] = (bool)$post['is_liked'];
        
        // Convert counts to int
        $post['likes_count'] = (int)$post['likes_count'];
        $post['comments_count'] = (int)$post['comments_count'];
    }
    
    sendResponse(true, 'Posts berhasil dimuat', $posts);
    
} catch (Exception $e) {
    error_log('Get posts error: ' . $e->getMessage());
    sendResponse(false, 'Terjadi kesalahan server', null, 500);
}
