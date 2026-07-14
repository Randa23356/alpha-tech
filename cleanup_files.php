<?php
require_once 'src/config/db.php';

echo "=== Cleaning Up Orphaned Files ===\n\n";

// Get all files referenced in database
$referencedFiles = [];

// Posts main images
$stmt = $pdo->query('SELECT DISTINCT image FROM posts WHERE image IS NOT NULL');
while ($row = $stmt->fetch()) {
    if (!empty($row['image'])) {
        $referencedFiles[] = $row['image'];
    }
}

// Post images
$stmt = $pdo->query('SELECT DISTINCT image_path FROM post_images WHERE image_path IS NOT NULL');
while ($row = $stmt->fetch()) {
    if (!empty($row['image_path'])) {
        $referencedFiles[] = $row['image_path'];
    }
}

// Hero slider images
$stmt = $pdo->query('SELECT DISTINCT background_image FROM hero_slides WHERE background_image IS NOT NULL');
while ($row = $stmt->fetch()) {
    if (!empty($row['background_image'])) {
        $referencedFiles[] = 'hero/' . $row['background_image'];
    }
}

// Announcement files
$stmt = $pdo->query('SELECT DISTINCT file_path FROM announcements WHERE file_path IS NOT NULL');
while ($row = $stmt->fetch()) {
    if (!empty($row['file_path'])) {
        $referencedFiles[] = $row['file_path'];
    }
}

// Profile pictures
$stmt = $pdo->query('SELECT DISTINCT profile_pic FROM users WHERE profile_pic IS NOT NULL');
while ($row = $stmt->fetch()) {
    if (!empty($row['profile_pic'])) {
        $referencedFiles[] = str_replace('public/uploads/', '', $row['profile_pic']);
    }
}

$uploadDir = __DIR__ . '/public/uploads/';
$allFiles = [];

function scanDirectory($dir, &$files, $baseDir = '') {
    $items = scandir($dir);
    foreach ($items as $item) {
        if ($item === '.' || $item === '..') continue;

        $fullPath = $dir . '/' . $item;
        $relativePath = ($baseDir ? $baseDir . '/' : '') . $item;

        if (is_dir($fullPath)) {
            scanDirectory($fullPath, $files, $relativePath);
        } else {
            $files[] = $relativePath;
        }
    }
}

scanDirectory($uploadDir, $allFiles);

// Find and delete orphaned files
$orphanedFiles = [];
$deletedCount = 0;
$totalSizeFreed = 0;

foreach ($allFiles as $file) {
    if (!in_array($file, $referencedFiles)) {
        $orphanedFiles[] = $file;
    }
}

echo "Found " . count($orphanedFiles) . " orphaned files to delete.\n";
echo "Starting cleanup...\n\n";

foreach ($orphanedFiles as $file) {
    $fullPath = $uploadDir . $file;
    $size = filesize($fullPath);

    if (unlink($fullPath)) {
        echo "✓ Deleted: $file (" . number_format($size) . " bytes)\n";
        $deletedCount++;
        $totalSizeFreed += $size;
    } else {
        echo "✗ Failed: $file\n";
    }
}

echo "\n=== CLEANUP COMPLETE ===\n";
echo "Files deleted: $deletedCount\n";
echo "Space freed: " . number_format($totalSizeFreed) . " bytes (" . number_format($totalSizeFreed / 1024 / 1024, 2) . " MB)\n";
echo "Remaining orphaned files: " . (count($orphanedFiles) - $deletedCount) . "\n";
?>
