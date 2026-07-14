<?php
// cleanup_orphaned_images.php - Script to clean up orphaned image records
require_once __DIR__ . '/src/config/db.php';

echo "Starting cleanup of orphaned image records...\n";

try {
    // Get all posts with image references
    $stmt = $pdo->query("SELECT id, image, thumbnail_image FROM posts WHERE status = 'approved' AND deleted_at IS NULL");
    $posts = $stmt->fetchAll();

    $cleanedCount = 0;
    $uploadsDir = __DIR__ . '/public/uploads/';

    foreach ($posts as $post) {
        $hasValidImage = false;

        // Check main image
        if (!empty($post['image']) && file_exists($uploadsDir . $post['image'])) {
            $hasValidImage = true;
        }

        // Check thumbnail image
        if (!empty($post['thumbnail_image']) && file_exists($uploadsDir . $post['thumbnail_image'])) {
            $hasValidImage = true;
        }

        // Check post_images table
        $stmt = $pdo->prepare("SELECT image_path FROM post_images WHERE post_id = ?");
        $stmt->execute([$post['id']]);
        $postImages = $stmt->fetchAll();

        foreach ($postImages as $postImage) {
            if (!empty($postImage['image_path']) && file_exists($uploadsDir . $postImage['image_path'])) {
                $hasValidImage = true;
                break;
            }
        }

        // If no valid images found, we could either delete the post or clear image references
        // For now, let's clear the image references to keep the posts but without broken images
        if (!$hasValidImage) {
            echo "Cleaning post ID {$post['id']} - no valid images found\n";

            // Clear main image reference
            $stmt = $pdo->prepare("UPDATE posts SET image = NULL WHERE id = ?");
            $stmt->execute([$post['id']]);

            // Clear thumbnail image reference
            $stmt = $pdo->prepare("UPDATE posts SET thumbnail_image = NULL WHERE id = ?");
            $stmt->execute([$post['id']]);

            // Remove all post_images entries for this post
            $stmt = $pdo->prepare("DELETE FROM post_images WHERE post_id = ?");
            $stmt->execute([$post['id']]);

            $cleanedCount++;
        }
    }

    echo "Cleanup completed. Cleaned {$cleanedCount} posts with orphaned image references.\n";

} catch (Exception $e) {
    echo "Error during cleanup: " . $e->getMessage() . "\n";
}
?>
