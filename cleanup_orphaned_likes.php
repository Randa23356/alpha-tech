<?php
require_once 'src/config/db.php';

echo "=== Cleaning Up Orphaned Likes ===\n\n";

try {
    // Find all likes for posts that don't exist
    $stmt = $pdo->query('
        SELECT l.post_id, COUNT(*) as likes_count
        FROM likes l
        LEFT JOIN posts p ON l.post_id = p.id AND l.type = "post"
        WHERE p.id IS NULL
        GROUP BY l.post_id
    ');

    $orphanedLikes = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (count($orphanedLikes) > 0) {
        echo "Found orphaned likes for deleted posts:\n";
        foreach ($orphanedLikes as $orphan) {
            echo "Post ID {$orphan['post_id']}: {$orphan['likes_count']} orphaned likes\n";
        }

        echo "\nCleaning up orphaned likes...\n";

        // Delete orphaned likes
        $stmt = $pdo->prepare('DELETE FROM likes WHERE post_id NOT IN (SELECT id FROM posts) AND type = "post"');
        $deletedCount = $stmt->execute();

        echo "✅ Deleted $deletedCount orphaned likes\n";
    } else {
        echo "✅ No orphaned likes found!\n";
    }

    // Check final count
    $stmt = $pdo->query('SELECT COUNT(*) as total_likes FROM likes WHERE type = "post"');
    $remainingLikes = $stmt->fetch()['total_likes'];
    echo "\nRemaining post likes in database: $remainingLikes\n";

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
