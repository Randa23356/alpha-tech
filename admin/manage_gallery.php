<?php
ini_set("display_errors", 1);
ini_set("display_startup_errors", 1);
error_reporting(E_ALL);
// informatics_a/admin/manage_gallery.php
require_once __DIR__ . "/../src/helpers/session.php";
require_once __DIR__ . "/../src/config/db.php";
require_once __DIR__ . "/../src/config/urls.php";

// Proteksi: hanya admin yang bisa akses
if (!isLoggedIn() || !isAdmin()) {
    header("Location: " . url('login'));
    exit();
}

// Handle aksi approve, delete
if (
    $_SERVER["REQUEST_METHOD"] === "POST" &&
    isset($_POST["action"], $_POST["gallery_id"], $_POST["source_type"])
) {
    $gallery_id = intval($_POST["gallery_id"]);
    $source_type = $_POST["source_type"];
    $action = $_POST["action"];

    if ($action === "approve" && $source_type === "gallery") {
        // Approve gallery item
        $stmt = $pdo->prepare("UPDATE gallery SET status = 'approved' WHERE id = ?");
        $stmt->execute([$gallery_id]);
    } elseif ($action === "delete") {
        if ($source_type === "gallery") {
            // Delete from gallery table
            $stmt = $pdo->prepare("SELECT image FROM gallery WHERE id = ?");
            $stmt->execute([$gallery_id]);
            $image = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($image && !empty($image['image'])) {
                $image_path = __DIR__ . "/../public/uploads/" . $image['image'];
                if (file_exists($image_path)) {
                    unlink($image_path);
                }
            }

            $stmt = $pdo->prepare("DELETE FROM gallery WHERE id = ?");
            $stmt->execute([$gallery_id]);

        } elseif ($source_type === "post_image") {
            // Delete from post_images table
            $stmt = $pdo->prepare("SELECT image_path FROM post_images WHERE id = ?");
            $stmt->execute([$gallery_id]);
            $image = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($image && !empty($image['image_path'])) {
                $image_path = __DIR__ . "/../public/uploads/" . $image['image_path'];
                if (file_exists($image_path)) {
                    unlink($image_path);
                }
            }

            $stmt = $pdo->prepare("DELETE FROM post_images WHERE id = ?");
            $stmt->execute([$gallery_id]);

        } elseif ($source_type === "post_thumbnail") {
            // Delete from posts table (thumbnail images)
            $stmt = $pdo->prepare("
                SELECT DISTINCT image_path FROM (
                    SELECT COALESCE(thumbnail_image, image) as image_path FROM posts WHERE id = ?
                    UNION
                    SELECT image_path FROM post_images WHERE post_id = ?
                ) as all_images
                WHERE image_path IS NOT NULL
            ");
            $stmt->execute([$gallery_id, $gallery_id]);
            $images = $stmt->fetchAll(PDO::FETCH_ASSOC);

            foreach ($images as $image) {
                $image_path = __DIR__ . "/../public/uploads/" . $image['image_path'];
                if (file_exists($image_path)) {
                    unlink($image_path);
                }
            }

            // Delete from post_images first
            $stmt = $pdo->prepare("DELETE FROM post_images WHERE post_id = ?");
            $stmt->execute([$gallery_id]);

            // Delete from posts
            $stmt = $pdo->prepare("DELETE FROM posts WHERE id = ?");
            $stmt->execute([$gallery_id]);
        }
    }
}

// Ambil semua foto dari berbagai sumber (gallery, post_images, posts thumbnails)
$gallery_items = [];

// 1. Ambil foto dari tabel gallery
try {
    $stmt = $pdo->query(
        "SELECT g.id, g.title, g.description, g.image as main_image, g.uploaded_by, g.created_at, g.updated_at, u.username, 'gallery' as source_type
         FROM gallery g
         LEFT JOIN users u ON g.uploaded_by = u.id
         ORDER BY g.created_at DESC"
    );
    $gallery_photos = $stmt->fetchAll();
    $gallery_items = array_merge($gallery_items, $gallery_photos);
} catch (Exception $e) {
    $gallery_photos = [];
}

// 2. Ambil foto dari post_images (foto kegiatan)
try {
    $stmt = $pdo->query(
        "SELECT pi.id, CONCAT('Foto Kegiatan - ', p.title) as title, p.content as description, pi.image_path as main_image, p.user_id as uploaded_by, pi.created_at, pi.created_at as updated_at, u.username, 'post_image' as source_type
         FROM post_images pi
         JOIN posts p ON pi.post_id = p.id
         LEFT JOIN users u ON p.user_id = u.id
         WHERE p.status = 'approved'
         ORDER BY pi.created_at DESC"
    );
    $post_images = $stmt->fetchAll();
    $gallery_items = array_merge($gallery_items, $post_images);
} catch (Exception $e) {
    $post_images = [];
}

// 3. Ambil thumbnail images dari posts
try {
    $stmt = $pdo->query(
        "SELECT p.id, p.title, p.content as description,
         CASE
           WHEN p.thumbnail_image IS NOT NULL AND p.thumbnail_image != '' THEN p.thumbnail_image
           WHEN p.image IS NOT NULL AND p.image != '' THEN p.image
           ELSE NULL
         END as main_image,
         p.user_id as uploaded_by, p.created_at, p.updated_at, u.username, 'post_thumbnail' as source_type
         FROM posts p
         LEFT JOIN users u ON p.user_id = u.id
         WHERE p.status = 'approved'
         AND (p.thumbnail_image IS NOT NULL AND p.thumbnail_image != '' OR p.image IS NOT NULL AND p.image != '')
         ORDER BY p.created_at DESC"
    );
    $post_thumbnails = $stmt->fetchAll();
    $gallery_items = array_merge($gallery_items, $post_thumbnails);
} catch (Exception $e) {
    $post_thumbnails = [];
}

// Sort combined results by created_at DESC
usort($gallery_items, function($a, $b) {
    return strtotime($b['created_at']) - strtotime($a['created_at']);
});

// Limit to first 50 items for performance
$gallery = array_slice($gallery_items, 0, 50);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Kelola Galeri Foto - Admin Informatics A</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="<?= asset('tailwind.css') ?>" rel="stylesheet">
    <style>
        /* Enhanced card styles for admin manage gallery */
        .line-clamp-2 {
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }
    </style>
</head>
<body class="bg-gradient-to-br from-gray-50 to-blue-50 min-h-screen">
    <?php include __DIR__ . "/../includes/navbar.php"; ?>

    <?php include __DIR__ . "/sidebar.php"; ?>

    <!-- Header -->
    <header class="lg:ml-64 bg-gradient-to-r from-blue-900 via-blue-800 to-indigo-900 text-white py-10 px-6">
        <div class="max-w-7xl mx-auto">
            <div class="flex items-center gap-4">
                <div class="bg-white/10 backdrop-blur-sm p-3 rounded-xl">
                    <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                    </svg>
                </div>
                <div>
                    <h1 class="text-3xl font-bold mb-1">Kelola Galeri Foto</h1>
                    <p class="text-blue-100">Approve dan kelola foto galeri kelas</p>
                </div>
            </div>
        </div>
    </header>

    <main class="lg:ml-64 max-w-7xl mx-auto px-6 py-10">
        <?php if (empty($gallery)): ?>
            <div class="bg-white rounded-2xl shadow-lg p-12 text-center">
                <svg class="w-20 h-20 mx-auto mb-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                </svg>
                <h3 class="text-xl font-bold text-gray-700 mb-2">Belum Ada Foto</h3>
                <p class="text-gray-500">Belum ada foto di galeri.</p>
            </div>
        <?php else: ?>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6 auto-rows-fr">
                <?php foreach ($gallery as $item): ?>
                    <div class="bg-white rounded-2xl shadow-lg overflow-hidden hover:shadow-xl transition-all duration-300 group hover:-translate-y-1">
                        <!-- Image -->
                        <div class="relative h-64 overflow-hidden bg-gradient-to-br from-blue-100 to-blue-200">
                            <?php
                            $imagePath = !empty($item['main_image']) ? $item['main_image'] : null;
                            $fullImagePath = $imagePath ? __DIR__ . '/../public/uploads/' . $imagePath : null;
                            $imageExists = $imagePath && file_exists($fullImagePath);
                            ?>

                            <?php if ($imagePath && $imageExists): ?>
                                <img src=" <?= BASE_URL ?>/public/uploads/<?= htmlspecialchars($imagePath) ?>"
                                     alt="Foto Galeri"
                                     class="w-full h-full object-cover transition-transform duration-300 group-hover:scale-110"
                                     onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                                <div class="w-full h-full bg-gradient-to-br from-blue-100 to-blue-200 flex items-center justify-center hidden">
                                    <svg class="w-20 h-20 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                    </svg>
                                </div>
                            <?php else: ?>
                                <div class="w-full h-full bg-gradient-to-br from-blue-100 to-blue-200 flex items-center justify-center">
                                    <svg class="w-20 h-20 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                    </svg>
                                </div>
                            <?php endif; ?>
                            <!-- Status Badge -->
                            <div class="absolute top-3 right-3">
                                <?php
                                $isApproved = false;
                                if ($item['source_type'] === 'gallery') {
                                    // Gallery items - check if they have their own status or default to approved
                                    $isApproved = !isset($item['status']) || $item['status'] === 'approved';
                                } elseif (in_array($item['source_type'], ['post_image', 'post_thumbnail'])) {
                                    // Post images and thumbnails come from approved posts
                                    $isApproved = true;
                                }
                                ?>

                                <?php if ($isApproved): ?>
                                    <span class="inline-flex items-center gap-1 bg-green-500 text-white px-3 py-1 rounded-full text-xs font-semibold shadow-lg">
                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                        </svg>
                                        Approved
                                    </span>
                                <?php else: ?>
                                    <span class="inline-flex items-center gap-1 bg-yellow-500 text-white px-3 py-1 rounded-full text-xs font-semibold shadow-lg">
                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                        </svg>
                                        Pending
                                    </span>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Content -->
                        <div class="p-5">
                            <h3 class="text-gray-900 font-semibold mb-3 line-clamp-2 text-sm leading-tight">
                                <?= htmlspecialchars($item["title"]) ?>
                            </h3>
                            <div class="space-y-2 mb-4 text-xs text-gray-600">
                                <div class="flex items-center gap-2">
                                    <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                                    </svg>
                                    <span class="truncate">Oleh: <?= htmlspecialchars($item["username"] ?? "Admin") ?></span>
                                </div>
                                <div class="flex items-center gap-2">
                                    <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3a2 2 0 012-2h4a2 2 0 012 2v4m-6 9l6-6m0 0v6m0-6h-6"/>
                                    </svg>
                                    <span><?= htmlspecialchars(date("d M Y H:i", strtotime($item["created_at"]))) ?></span>
                                </div>
                            </div>

                            <!-- Actions -->
                            <form action="" method="POST" class="space-y-2">
                                <input type="hidden" name="gallery_id" value="<?= $item["id"] ?>">
                                <input type="hidden" name="source_type" value="<?= $item["source_type"] ?>">

                                <?php if ($item['source_type'] === 'gallery' && (!$isApproved)): ?>
                                    <button type="submit" name="action" value="approve" class="w-full inline-flex items-center justify-center gap-2 bg-green-600 text-white px-3 py-2 rounded-lg hover:bg-green-700 transition-colors font-medium text-sm">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                        </svg>
                                        Approve
                                    </button>
                                <?php endif; ?>

                                <button type="submit" name="action" value="delete" class="w-full inline-flex items-center justify-center gap-2 bg-red-600 text-white px-3 py-2 rounded-lg hover:bg-red-700 transition-colors font-medium text-sm" onclick="return confirm('Hapus foto ini?')">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                    </svg>
                                    Hapus
                                </button>
                            </form>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
        <div class="mt-8 text-center">
            <a href="dashboard.php" class="inline-flex items-center gap-2 text-blue-900 font-semibold hover:text-blue-700 transition">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                </svg>
                Kembali ke Dashboard
            </a>
        </div>
    </main>

    <footer class="lg:ml-64 bg-white border-t border-gray-200 py-6 mt-12">
        <div class="max-w-7xl mx-auto px-6 text-center text-gray-600">
            &copy; <?= date("Y") ?> Informatics A. All rights reserved.
        </div>
    </footer>

    <script>
        // Enhanced functionality for admin manage gallery
        document.addEventListener('DOMContentLoaded', function() {
            // Add smooth scrolling for better UX
            document.querySelectorAll('a[href^="#"]').forEach(anchor => {
                anchor.addEventListener('click', function (e) {
                    e.preventDefault();
                    const target = document.querySelector(this.getAttribute('href'));
                    if (target) {
                        target.scrollIntoView({
                            behavior: 'smooth'
                        });
                    }
                });
            });
        });
    </script>
</body>
</html>
