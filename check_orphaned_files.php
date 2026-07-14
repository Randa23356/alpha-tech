<?php
require_once 'src/config/db.php';

// Check which files are referenced in the database
echo "=== Checking Database References ===\n\n";

// Check posts table for image references
$stmt = $pdo->query('SELECT DISTINCT image FROM posts WHERE image IS NOT NULL');
$dbImages = $stmt->fetchAll(PDO::FETCH_COLUMN);
echo 'Post images in DB: ' . count($dbImages) . "\n";

// Check post_images table
$stmt = $pdo->query('SELECT DISTINCT image_path FROM post_images WHERE image_path IS NOT NULL');
$dbPostImages = $stmt->fetchAll(PDO::FETCH_COLUMN);
echo 'Post image paths in DB: ' . count($dbPostImages) . "\n";

// Check hero_slides table
$stmt = $pdo->query('SELECT DISTINCT background_image FROM hero_slides WHERE background_image IS NOT NULL');
$dbHeroImages = $stmt->fetchAll(PDO::FETCH_COLUMN);
echo 'Hero background images in DB: ' . count($dbHeroImages) . "\n";

// Check announcements table
$stmt = $pdo->query('SELECT DISTINCT file_path FROM announcements WHERE file_path IS NOT NULL');
$dbAnnouncementFiles = $stmt->fetchAll(PDO::FETCH_COLUMN);
echo 'Announcement files in DB: ' . count($dbAnnouncementFiles) . "\n";

// Check users table for profile pictures
$stmt = $pdo->query('SELECT DISTINCT profile_pic FROM users WHERE profile_pic IS NOT NULL');
$dbProfilePics = $stmt->fetchAll(PDO::FETCH_COLUMN);
echo 'Profile pictures in DB: ' . count($dbProfilePics) . "\n";

echo "\n=== Summary ===\n";
echo 'Total referenced files in DB: ' . (count($dbImages) + count($dbPostImages) + count($dbHeroImages) + count($dbAnnouncementFiles) + count($dbProfilePics)) . "\n";
?>
