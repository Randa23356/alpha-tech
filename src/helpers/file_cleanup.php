<?php
// src/helpers/file_cleanup.php

/**
 * File Cleanup Utility Functions
 * Handles deletion of unused files when records are deleted or updated
 */

/**
 * Delete a file if it exists and is not referenced elsewhere
 * @param string $filePath The path to the file to delete
 * @return bool True if file was deleted, false if not found or couldn't be deleted
 */
function deleteFileIfExists($filePath) {
    if (empty($filePath) || !file_exists($filePath)) {
        return false;
    }

    return unlink($filePath);
}

/**
 * Check if a file is referenced by any other records
 * @param string $fileName The filename to check
 * @param string $column The database column to check (e.g., 'image', 'profile_pic')
 * @param string $excludeTable Table to exclude from the check (for updates)
 * @param int $excludeId ID to exclude from the check (for updates)
 * @return bool True if file is referenced elsewhere, false if safe to delete
 */
function isFileReferenced($fileName, $column, $excludeTable = null, $excludeId = null) {
    global $pdo;

    if (empty($fileName)) {
        return false;
    }

    $tables = [
        'posts' => 'image',
        'users' => 'profile_pic',
        'gallery' => 'image',
        'announcements' => 'file_path',
        'post_images' => 'image_path'
    ];

    foreach ($tables as $table => $col) {
        if ($table === $excludeTable && !empty($excludeId)) {
            continue; // Skip the current record being updated
        }

        if ($col === $column) {
            try {
                $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM $table WHERE $column = ?");
                $stmt->execute([$fileName]);
                $result = $stmt->fetch();

                if ($result['count'] > 0) {
                    return true; // File is still referenced
                }
            } catch (Exception $e) {
                // Continue checking other tables
                continue;
            }
        }
    }

    return false; // File is not referenced elsewhere
}

/**
 * Clean up files for a specific post
 * @param int $postId The post ID
 * @return bool True if cleanup was successful
 */
function cleanupPostFiles($postId) {
    global $pdo;

    try {
        // Get post data before deletion
        $stmt = $pdo->prepare("SELECT image FROM posts WHERE id = ?");
        $stmt->execute([$postId]);
        $post = $stmt->fetch();

        if ($post && !empty($post['image'])) {
            $filePath = __DIR__ . '/../public/uploads/' . $post['image'];

            // Check if file is referenced elsewhere before deleting
            if (!isFileReferenced($post['image'], 'image', 'posts', $postId)) {
                deleteFileIfExists($filePath);
            }
        }

        // Also clean up post_images table entries
        $stmt = $pdo->prepare("SELECT image_path FROM post_images WHERE post_id = ?");
        $stmt->execute([$postId]);
        $postImages = $stmt->fetchAll();

        foreach ($postImages as $postImage) {
            if (!empty($postImage['image_path'])) {
                $imageFilePath = __DIR__ . '/../public/uploads/' . $postImage['image_path'];

                // Check if image file is referenced elsewhere before deleting
                if (!isFileReferenced($postImage['image_path'], 'image_path', 'post_images', $postId)) {
                    deleteFileIfExists($imageFilePath);
                }
            }
        }

        // Delete all post_images entries for this post
        $stmt = $pdo->prepare("DELETE FROM post_images WHERE post_id = ?");
        $stmt->execute([$postId]);

        return true;
    } catch (Exception $e) {
        return false;
    }
}

/**
 * Clean up files for a specific user
 * @param int $userId The user ID
 * @return bool True if cleanup was successful
 */
function cleanupUserFiles($userId) {
    global $pdo;

    try {
        // Get user data before deletion
        $stmt = $pdo->prepare("SELECT profile_pic FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        $user = $stmt->fetch();

        if ($user && !empty($user['profile_pic'])) {
            // Handle both relative and absolute paths
            $fileName = basename($user['profile_pic']);
            $filePath = __DIR__ . '/../public/uploads/profiles/' . $fileName;

            // Check if profile picture is referenced elsewhere before deleting
            if (!isFileReferenced($fileName, 'profile_pic', 'users', $userId)) {
                deleteFileIfExists($filePath);
            }
        }

        // Also clean up all posts by this user
        $stmt = $pdo->prepare("SELECT id FROM posts WHERE user_id = ?");
        $stmt->execute([$userId]);
        $posts = $stmt->fetchAll();

        foreach ($posts as $post) {
            cleanupPostFiles($post['id']);
        }

        return true;
    } catch (Exception $e) {
        return false;
    }
}

/**
 * Clean up old profile picture when updating
 * @param string $oldProfilePic The old profile picture filename
 * @param int $userId The user ID being updated
 * @return bool True if cleanup was successful
 */
function cleanupOldProfilePic($oldProfilePic, $userId) {
    if (empty($oldProfilePic)) {
        return true;
    }

    $fileName = basename($oldProfilePic);
    $filePath = __DIR__ . '/../public/uploads/profiles/' . $fileName;

    // Check if old profile picture is referenced elsewhere before deleting
    if (!isFileReferenced($fileName, 'profile_pic', 'users', $userId)) {
        return deleteFileIfExists($filePath);
    }

    return true; // File is still referenced, don't delete
}

/**
 * Clean up files for gallery items
 * @param int $galleryId The gallery item ID
 * @return bool True if cleanup was successful
 */
function cleanupGalleryFiles($galleryId) {
    global $pdo;

    try {
        // Get gallery data before deletion
        $stmt = $pdo->prepare("SELECT image FROM gallery WHERE id = ?");
        $stmt->execute([$galleryId]);
        $gallery = $stmt->fetch();

        if ($gallery && !empty($gallery['image'])) {
            $filePath = __DIR__ . '/../public/uploads/' . $gallery['image'];

            // Check if file is referenced elsewhere before deleting
            if (!isFileReferenced($gallery['image'], 'image', 'gallery', $galleryId)) {
                deleteFileIfExists($filePath);
            }
        }

        return true;
    } catch (Exception $e) {
        return false;
    }
}

/**
 * Clean up files for announcements
 * @param int $announcementId The announcement ID
 * @return bool True if cleanup was successful
 */
function cleanupAnnouncementFiles($announcementId) {
    global $pdo;

    try {
        // Get announcement data before deletion
        $stmt = $pdo->prepare("SELECT file_path, file_name FROM announcements WHERE id = ?");
        $stmt->execute([$announcementId]);
        $announcement = $stmt->fetch();

        if ($announcement && !empty($announcement['file_path'])) {
            $filePath = __DIR__ . '/../public/uploads/announcements/' . $announcement['file_path'];

            // Check if file is referenced elsewhere before deleting
            if (!isFileReferenced($announcement['file_path'], 'file_path', 'announcements', $announcementId)) {
                deleteFileIfExists($filePath);
            }
        }

        return true;
    } catch (Exception $e) {
        return false;
    }
}
