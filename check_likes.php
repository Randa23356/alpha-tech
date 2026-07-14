<?php
require_once 'src/config/db.php';

// Check current likes in database
echo "=== Current Likes in Database ===\n\n";

try {
    $stmt = $pdo->query('SELECT COUNT(*) as total_likes FROM likes WHERE type = "post"');
    $totalLikes = $stmt->fetch()['total_likes'];
    echo "Total post likes in database: $totalLikes\n";

    $stmt = $pdo->query('SELECT post_id, COUNT(*) as likes_count FROM likes WHERE type = "post" GROUP BY post_id ORDER BY likes_count DESC LIMIT 10');
    $likesByPost = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo "\nLikes by post (top 10):\n";
    foreach ($likesByPost as $like) {
        echo "Post ID {$like['post_id']}: {$like['likes_count']} likes\n";
    }

    // Check if these posts still exist
    echo "\n=== Checking if posts exist ===\n";
    $stmt = $pdo->query('SELECT COUNT(*) as existing_posts FROM posts');
    $existingPosts = $stmt->fetch()['existing_posts'];
    echo "Total posts in database: $existingPosts\n";

    if ($existingPosts == 0 && $totalLikes > 0) {
        echo "\n⚠️  WARNING: There are $totalLikes orphaned likes from deleted posts!\n";
        echo "These should be cleaned up.\n";
    }

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
