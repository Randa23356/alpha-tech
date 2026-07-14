<?php
require_once 'src/config/db.php';

echo "=== Orphaned Files Report ===\n\n";

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

echo "Files referenced in database: " . count($referencedFiles) . "\n";

// Get all files on disk
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
echo "Total files on disk: " . count($allFiles) . "\n";

// Find orphaned files
$orphanedFiles = [];
foreach ($allFiles as $file) {
    if (!in_array($file, $referencedFiles)) {
        $orphanedFiles[] = $file;
    }
}

$totalOrphanedSize = 0;
echo "\n=== ORPHANED FILES (Not referenced in database) ===\n";
if (count($orphanedFiles) > 0) {
    foreach ($orphanedFiles as $file) {
        $fullPath = $uploadDir . $file;
        $size = filesize($fullPath);
        $totalOrphanedSize += $size;
        echo "$file (" . number_format($size) . " bytes)\n";
    }

    echo "\n=== SUMMARY ===\n";
    echo "Orphaned files: " . count($orphanedFiles) . "\n";
    echo "Total size: " . number_format($totalOrphanedSize) . " bytes (" . number_format($totalOrphanedSize / 1024 / 1024, 2) . " MB)\n";
    echo "\n⚠️  WARNING: These files can be safely deleted if you're sure they're not needed.\n";
    echo "   Make a backup before deleting!\n";
} else {
    echo "✅ No orphaned files found! All files are properly referenced.\n";
}
?>
