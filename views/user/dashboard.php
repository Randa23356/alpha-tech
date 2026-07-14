<?php
ini_set("display_errors", 1);
ini_set("display_startup_errors", 1);
error_reporting(E_ALL);
// views/user/dashboard.php
session_start();
require_once __DIR__ . "/../../src/helpers/session.php";
require_once __DIR__ . "/../../src/config/db.php";
require_once __DIR__ . "/../../src/config/urls.php";

// Load theme colors from database
$primary_color = "#1e3a8a"; // default
$secondary_color = "#1e40af"; // default
$accent_color = "#ec4899"; // default
$success_color = "#10b981"; // default
$warning_color = "#f59e0b"; // default

try {
    $stmt = $pdo->query(
        "SELECT setting_key, setting_value FROM site_settings WHERE setting_key IN ('primary_color', 'secondary_color', 'accent_color', 'success_color', 'warning_color')",
    );
    $settings = [];
    foreach ($stmt->fetchAll() as $row) {
        $settings[$row["setting_key"]] = $row["setting_value"];
    }
    $primary_color = $settings["primary_color"] ?? $primary_color;
    $secondary_color = $settings["secondary_color"] ?? $secondary_color;
    $accent_color = $settings["accent_color"] ?? $accent_color;
    $success_color = $settings["success_color"] ?? $success_color;
    $warning_color = $settings["warning_color"] ?? $warning_color;
} catch (Exception $e) {
    // Use default colors if database fails
}

// Proteksi: hanya user login yang bisa akses
if (!isLoggedIn() || !isUser()) {
    header("Location: " . url('login'));
    exit();
}

$user = getCurrentUser();

// Ambil postingan user dan statistik
try {
    // Query with deleted_at check - status tetap approved ketika di trash
    $stmt = $pdo->prepare("SELECT p.*, COALESCE(pi.image_path, p.image) as thumbnail_image FROM posts p LEFT JOIN post_images pi ON p.id = pi.post_id AND pi.image_order = 0 WHERE p.user_id = ? AND (p.deleted_at IS NULL OR p.deleted_at = '0000-00-00 00:00:00') ORDER BY p.created_at DESC");
    $stmt->execute([$user['id']]);
    $posts = $stmt->fetchAll();

    // Get all images for each post
    foreach ($posts as $index => $post_item) {
        $stmt = $pdo->prepare("SELECT image_path, image_order FROM post_images WHERE post_id = ? ORDER BY image_order");
        $stmt->execute([$post_item['id']]);
        $posts[$index]['images'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Hitung statistik
    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM posts WHERE user_id = ? AND status = 'pending' AND (deleted_at IS NULL OR deleted_at = '0000-00-00 00:00:00')");
    $stmt->execute([$user['id']]);
    $pending_count = $stmt->fetch()['total'] ?? 0;

    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM posts WHERE user_id = ? AND status = 'approved' AND (deleted_at IS NULL OR deleted_at = '0000-00-00 00:00:00')");
    $stmt->execute([$user['id']]);
    $approved_count = $stmt->fetch()['total'] ?? 0;

    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM posts WHERE user_id = ? AND (deleted_at IS NULL OR deleted_at = '0000-00-00 00:00:00')");
    $stmt->execute([$user['id']]);
    $total_posts = $stmt->fetch()['total'] ?? 0;

} catch (Exception $e) {
    // Handle database errors gracefully
    $posts = [];
    $pending_count = 0;
    $approved_count = 0;
    $total_posts = 0;
    // You can log the error or show a user-friendly message
    error_log('Database error in user dashboard: ' . $e->getMessage());
}
    
    // Most liked activities will be implemented later
    // Handle post deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_post']) && isLoggedIn()) {
    $postId = intval($_POST['post_id']);
    $deleteType = $_POST['delete_type'] ?? 'permanent'; // Default to permanent if not specified
    $user = getCurrentUser();

    try {
        // Verify the post belongs to the current user (include trashed posts for deletion)
        $stmt = $pdo->prepare("SELECT user_id FROM posts WHERE id = ?");
        $stmt->execute([$postId]);
        $post = $stmt->fetch();

        if (!$post) {
            echo json_encode(['success' => false, 'message' => 'Postingan tidak ditemukan']);
            exit();
        }

        if ($post['user_id'] !== $user['id']) {
            echo json_encode(['success' => false, 'message' => 'Postingan bukan milik Anda']);
            exit();
        }

        if ($deleteType === 'trash') {
            // Move to trash (soft delete) - only set deleted_at, keep status unchanged
            try {
                // Check if deleted_at column exists first
                $deletedAtCheck = $pdo->query("SHOW COLUMNS FROM posts LIKE 'deleted_at'")->fetch();

                if ($deletedAtCheck) {
                    // Use deleted_at column (preferred method)
                    $stmt = $pdo->prepare("UPDATE posts SET deleted_at = NOW() WHERE id = ? AND user_id = ?");
                    if ($stmt->execute([$postId, $user['id']])) {
                        echo json_encode(['success' => true, 'message' => 'Postingan dipindahkan ke trash']);
                    } else {
                        echo json_encode(['success' => false, 'message' => 'Gagal memindahkan postingan ke trash']);
                    }
                } else {
                    // Fallback: if no deleted_at column, we can't implement proper soft delete
                    echo json_encode(['success' => false, 'message' => 'Sistem trash belum tersedia. Jalankan migration script terlebih dahulu.']);
                }
            } catch (Exception $e) {
                echo json_encode(['success' => false, 'message' => 'Gagal memindahkan postingan ke trash']);
            }
        } else {
            // Permanent delete - delete all associated images first
            try {
                // Get all image paths for this post (including thumbnail)
                $stmt = $pdo->prepare("
                    SELECT DISTINCT image_path FROM (
                        SELECT COALESCE(thumbnail_image, image) as image_path FROM posts WHERE id = ?
                        UNION
                        SELECT image_path FROM post_images WHERE post_id = ?
                    ) as all_images
                    WHERE image_path IS NOT NULL
                ");
                $stmt->execute([$postId, $postId]);
                $images = $stmt->fetchAll(PDO::FETCH_ASSOC);

                // Delete image files
                $upload_dir = __DIR__ . "/../../public/uploads/";
                foreach ($images as $image) {
                    $image_path = $upload_dir . $image['image_path'];
                    if (file_exists($image_path)) {
                        unlink($image_path);
                    }
                }

                // Delete from post_images table
                $stmt = $pdo->prepare("DELETE FROM post_images WHERE post_id = ?");
                $stmt->execute([$postId]);

                // Delete comments
                $stmt = $pdo->prepare("DELETE FROM comments WHERE post_id = ?");
                $stmt->execute([$postId]);

                // Delete likes
                $stmt = $pdo->prepare("DELETE FROM likes WHERE post_id = ? AND type IN ('post', 'comment')");
                $stmt->execute([$postId]);

                // Delete the post
                $stmt = $pdo->prepare("DELETE FROM posts WHERE id = ? AND user_id = ?");
                if ($stmt->execute([$postId, $user['id']])) {
                    echo json_encode(['success' => true, 'message' => 'Postingan berhasil dihapus permanen']);
                } else {
                    echo json_encode(['success' => false, 'message' => 'Gagal menghapus postingan']);
                }
            } catch (Exception $e) {
                echo json_encode(['success' => false, 'message' => 'Terjadi kesalahan: ' . $e->getMessage()]);
            }
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Terjadi kesalahan: ' . $e->getMessage()]);
    }
    exit();
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Dashboard User - Informatics A</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="<?= url('public/tailwind.css') ?>" rel="stylesheet">
    <?php require_once __DIR__ . '/../../includes/favicon.php'; ?>
    <style>
        /* Page transition animations */
        .page-transition-enter {
            opacity: 0;
            transform: translateY(20px);
        }

        .page-transition-enter-active {
            opacity: 1;
            transform: translateY(0);
            transition: opacity 0.6s ease-out, transform 0.6s ease-out;
        }

        .page-transition-exit {
            opacity: 1;
            transform: translateY(0);
        }

        .page-transition-exit-active {
            opacity: 0;
            transform: translateY(-20px);
            transition: opacity 0.4s ease-in, transform 0.4s ease-in;
        }

        /* Loading overlay */
        .page-loading-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(255, 255, 255, 0.9);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 9999;
            opacity: 0;
            visibility: hidden;
            transition: opacity 0.3s ease, visibility 0.3s ease;
        }

        .page-loading-overlay.show {
            opacity: 1;
            visibility: visible;
        }

        .loading-spinner {
            width: 40px;
            height: 40px;
            border: 4px solid #e5e7eb;
            border-top: 4px solid #3b82f6;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        /* Smooth hover effects for navigation */
        .nav-link {
            position: relative;
            overflow: hidden;
        }

        .nav-link::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
            transition: left 0.5s;
        }

        .nav-link:hover::before {
            left: 100%;
        }

        /* Enhanced delete button animations */
        .delete-btn {
            transform-origin: center;
            backface-visibility: hidden;
        }

        .delete-btn:hover {
            transform: scale(1.05) rotate(-1deg);
            box-shadow: 0 4px 12px rgba(239, 68, 68, 0.3);
        }

        .delete-btn:active {
            transform: scale(0.98);
        }

        /* Carousel styles */
        .carousel-container {
            position: relative;
            width: 100%;
            height: 100%;
        }

        .carousel-slide {
            opacity: 0;
            transition: opacity 0.3s ease-in-out;
        }

        .carousel-slide.active {
            opacity: 1;
        }

        .carousel-dot {
            transition: all 0.3s ease;
        }

        .carousel-dot:hover {
            transform: scale(1.2);
        }

        /* Enhanced hover effects for carousel */
        .carousel-container:hover .carousel-dot {
            opacity: 0.8;
        }

        .trash-shake {
            animation: trashShake 0.5s ease-in-out;
        }

        .trash-overlay {
            animation: trashOverlayPulse 0.8s ease-out;
        }

        @keyframes trashHighlight {
            0% {
                border-color: #e5e7eb;
                background: white;
                transform: scale(1);
            }
            50% {
                border-color: #f97316;
                background: linear-gradient(135deg, #fff7ed 0%, #fed7aa 100%);
                transform: scale(1.02);
            }
            100% {
                border-color: #f97316;
                background: linear-gradient(135deg, #fff7ed 0%, #fed7aa 100%);
                transform: scale(1.02);
            }
        }

        @keyframes trashShake {
            0%, 100% { transform: translateX(0) scale(1.02); }
            10% { transform: translateX(-2px) scale(1.02); }
            20% { transform: translateX(2px) scale(1.02); }
            30% { transform: translateX(-2px) scale(1.02); }
            40% { transform: translateX(2px) scale(1.02); }
            50% { transform: translateX(-1px) scale(1.02); }
            60% { transform: translateX(1px) scale(1.02); }
        }

        @keyframes trashOverlayPulse {
            0% {
                opacity: 0;
                transform: scale(0.8);
            }
            50% {
                opacity: 1;
                transform: scale(1.1);
            }
            100% {
                opacity: 0.1;
                transform: scale(1);
            }
        }
    </style>
</head>
<body class="min-h-screen" style="background: linear-gradient(135deg, <?= $primary_color ?>10 0%, <?= $secondary_color ?>10 100%);">
    <!-- Loading Overlay -->
    <div id="pageLoadingOverlay" class="page-loading-overlay">
        <div class="loading-spinner"></div>
    </div>
    
    <?php include __DIR__ . '/../../includes/navbar.php'; ?>
    
    <main class="max-w-7xl mx-auto px-6 py-10 page-transition-enter" id="mainContent">
        <!-- Header -->
        <div class="mb-8">
            <h1 class="text-2xl md:text-4xl font-bold mb-2" style="background: linear-gradient(135deg, <?= $primary_color ?> 0%, <?= $secondary_color ?> 100%); -webkit-background-clip: text; background-clip: text; color: transparent;">Dashboard User</h1>
            <p class="text-gray-600 text-base md:text-lg">Selamat datang, <?= htmlspecialchars($user['username']) ?>! 👋</p>
        </div>

        <!-- Statistics Cards -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 md:gap-6 mb-10">
            <!-- Total Posts -->
            <div class="bg-white rounded-2xl shadow-lg p-4 md:p-6 border-l-4 hover:shadow-xl transition" style="border-color: <?= $primary_color ?>;">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-600 text-xs md:text-sm font-medium mb-1">Total Postingan</p>
                        <p class="text-2xl md:text-3xl font-bold text-gray-900 leading-tight"><?= $total_posts ?></p>
                    </div>
                    <div class="bg-blue-100 p-3 md:p-4 rounded-xl flex-shrink-0">
                        <svg class="w-6 h-6 md:w-8 md:h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="color: <?= $primary_color ?>;">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                    </div>
                </div>
            </div>

            <!-- Pending Posts -->
            <div class="bg-white rounded-2xl shadow-lg p-4 md:p-6 border-l-4 hover:shadow-xl transition" style="border-color: <?= $warning_color ?>;">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-600 text-xs md:text-sm font-medium mb-1">Menunggu Approval</p>
                        <p class="text-2xl md:text-3xl font-bold text-gray-900 leading-tight"><?= $pending_count ?></p>
                    </div>
                    <div class="bg-yellow-100 p-3 md:p-4 rounded-xl flex-shrink-0">
                        <svg class="w-6 h-6 md:w-8 md:h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="color: <?= $warning_color ?>;">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                </div>
            </div>

            <!-- Approved Posts -->
            <div class="bg-white rounded-2xl shadow-lg p-4 md:p-6 border-l-4 hover:shadow-xl transition" style="border-color: <?= $success_color ?>;">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-600 text-xs md:text-sm font-medium mb-1">Disetujui</p>
                        <p class="text-2xl md:text-3xl font-bold text-gray-900 leading-tight"><?= $approved_count ?></p>
                    </div>
                    <div class="bg-green-100 p-3 md:p-4 rounded-xl flex-shrink-0">
                        <svg class="w-6 h-6 md:w-8 md:h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="color: <?= $success_color ?>;">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                </div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="bg-white rounded-2xl shadow-lg p-6 md:p-8 mb-10">
            <h2 class="text-xl md:text-2xl font-bold text-gray-900 mb-6 flex items-center gap-2">
                <svg class="w-6 h-6 md:w-7 md:h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="color: <?= $primary_color ?>;">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                </svg>
                Quick Actions
            </h2>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 md:gap-6">
                <a href=" <?= url('post') ?>" class="group block text-white p-4 md:p-6 rounded-xl hover:shadow-2xl hover:scale-105 transition-all duration-300" style="background: linear-gradient(135deg, <?= $primary_color ?> 0%, <?= $secondary_color ?> 100%);">
                    <div class="flex items-center gap-3 md:gap-4 mb-3">
                        <div class="bg-white/20 p-2 md:p-3 rounded-lg group-hover:bg-white/30 transition">
                            <svg class="w-6 h-6 md:w-8 md:h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                            </svg>
                        </div>
                        <h3 class="text-lg md:text-xl font-bold">Buat Postingan</h3>
                    </div>
                    <p class="text-blue-100 text-xs md:text-sm">Posting kegiatan kelas baru</p>
                </a>

                <a href=" <?= url('announcement') ?>" class="group block bg-white border-2 border-gray-200 p-4 md:p-6 rounded-xl hover:border-blue-500 hover:shadow-xl hover:scale-105 transition-all duration-300">
                    <div class="flex items-center gap-3 md:gap-4 mb-3">
                        <div class="bg-blue-100 p-2 md:p-3 rounded-lg group-hover:bg-blue-200 transition">
                            <svg class="w-6 h-6 md:w-8 md:h-8 text-blue-900" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z"/>
                            </svg>
                        </div>
                        <h3 class="text-lg md:text-xl font-bold text-gray-900">Pengumuman</h3>
                    </div>
                    <p class="text-gray-600 text-xs md:text-sm">Lihat pengumuman kelas</p>
                </a>

                <a href=" <?= url('profile') ?>" class="group block bg-white border-2 border-gray-200 p-4 md:p-6 rounded-xl hover:border-blue-500 hover:shadow-xl hover:scale-105 transition-all duration-300">
                    <div class="flex items-center gap-3 md:gap-4 mb-3">
                        <div class="bg-blue-100 p-2 md:p-3 rounded-lg group-hover:bg-blue-200 transition">
                            <svg class="w-6 h-6 md:w-8 md:h-8 text-blue-900" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                            </svg>
                        </div>
                        <h3 class="text-lg md:text-xl font-bold text-gray-900">Profil Saya</h3>
                    </div>
                    <p class="text-gray-600 text-xs md:text-sm">Edit profil dan pengaturan</p>
                </a>

                <a href=" <?= url('trash') ?>" class="nav-link group block bg-white border-2 border-gray-200 p-4 md:p-6 rounded-xl hover:border-orange-500 hover:shadow-xl hover:scale-105 transition-all duration-300">
                    <div class="flex items-center gap-3 md:gap-4 mb-3">
                        <div class="bg-orange-100 p-2 md:p-3 rounded-lg group-hover:bg-orange-200 transition">
                            <svg class="w-6 h-6 md:w-8 md:h-8 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                            </svg>
                        </div>
                        <h3 class="text-lg md:text-xl font-bold text-gray-900">Trash</h3>
                    </div>
                    <p class="text-gray-600 text-xs md:text-sm">Lihat postingan yang dihapus</p>
                </a>
            </div>
        </div>

        <!-- Posts List -->
        <div class="bg-white rounded-2xl shadow-lg p-6 md:p-8">
            <h2 class="text-xl md:text-2xl font-bold text-gray-900 mb-6">Postingan Kegiatan Anda</h2>
            
            <?php if (empty($posts)): ?>
                <div class="text-center py-12">
                    <div class="text-4xl md:text-6xl mb-4">📝</div>
                    <p class="text-gray-600 text-base md:text-lg mb-4">Belum ada postingan.</p>
                    <p class="text-gray-500 mb-6">Yuk mulai posting kegiatan kelas!</p>
                    <a href=" <?= url('post') ?>" class="inline-flex items-center gap-2 bg-blue-900 text-white px-4 md:px-6 py-2 md:py-3 rounded-lg font-semibold hover:bg-blue-800 transition">
                        <svg class="w-4 h-4 md:w-5 md:h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                        </svg>
                        Buat Postingan Pertama
                    </a>
                </div>
            <?php else: ?>
                <div class="space-y-4 md:space-y-6">
                    <?php foreach ($posts as $post): ?>
                        <div class="bg-white border-2 border-gray-200 rounded-xl overflow-hidden hover:border-blue-300 hover:shadow-lg transition-all duration-300">
                            <div class="flex flex-col lg:flex-row">
                                <!-- Post Images -->
                                <?php if (!empty($post['images'])): ?>
                                    <div class="lg:w-48 flex-shrink-0 relative" data-post="<?= $post['id'] ?>">
                                        <?php if (count($post['images']) == 1): ?>
                                            <!-- Single image -->
                                            <img src="<?= upload_url(htmlspecialchars($post['images'][0]['image_path'])) ?>"
                                                 alt="<?= htmlspecialchars($post['title']) ?>"
                                                 class="w-full h-48 lg:h-full object-cover">
                                        <?php else: ?>
                                            <!-- Multiple images carousel -->
                                            <div class="relative h-48 lg:h-full overflow-hidden" data-post="<?= $post['id'] ?>">
                                                <div class="flex h-full">
                                                    <?php foreach ($post['images'] as $index => $image): ?>
                                                        <div class="carousel-slide w-full h-full flex-shrink-0 <?= $index === 0 ? 'active' : '' ?>">
                                                            <img src="<?= upload_url(htmlspecialchars($image['image_path'])) ?>"
                                                                 alt="<?= htmlspecialchars($post['title']) ?>"
                                                                 class="w-full h-full object-cover">
                                                        </div>
                                                    <?php endforeach; ?>
                                                </div>
                                                <?php if (count($post['images']) > 1): ?>
                                                    <div class="absolute bottom-2 left-1/2 transform -translate-x-1/2 flex space-x-1">
                                                        <?php foreach ($post['images'] as $index => $image): ?>
                                                            <button class="carousel-dot w-2 h-2 rounded-full bg-white opacity-50 hover:opacity-100 transition-opacity <?= $index === 0 ? 'opacity-100' : '' ?>"
                                                                    data-slide="<?= $index ?>" onclick="changeSlide(this, <?= $post['id'] ?>)"></button>
                                                        <?php endforeach; ?>
                                                    </div>
                                                    <button class="absolute left-2 top-1/2 transform -translate-y-1/2 bg-black bg-opacity-50 text-white p-1 rounded-full hover:bg-opacity-75 transition-opacity"
                                                            onclick="prevSlide(<?= $post['id'] ?>)">
                                                        <svg class="w-3 h-3 md:w-4 md:h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                                                        </svg>
                                                    </button>
                                                    <button class="absolute right-2 top-1/2 transform -translate-y-1/2 bg-black bg-opacity-50 text-white p-1 rounded-full hover:bg-opacity-75 transition-opacity"
                                                            onclick="nextSlide(<?= $post['id'] ?>)">
                                                        <svg class="w-3 h-3 md:w-4 md:h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                                                        </svg>
                                                    </button>
                                                <?php endif; ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                <?php endif; ?>

                                <!-- Post Content -->
                                <div class="flex-1 p-4 md:p-6">
                                    <div class="flex flex-col sm:flex-row sm:justify-between sm:items-start gap-4">
                                        <div class="flex-1">
                                            <h3 class="text-lg md:text-xl font-bold text-gray-900 mb-2 hover:text-blue-600 transition-colors cursor-pointer"
                                                onclick="window.location.href='<?= url('activity_detail?id=' . $post['id']) ?>'">
                                                <?= htmlspecialchars($post["title"]) ?>
                                            </h3>
                                            <p class="text-gray-600 mb-4 line-clamp-2 leading-relaxed text-sm md:text-base">
                                                <?= htmlspecialchars(substr($post["content"] ?? $post["description"] ?? "No description available", 0, 150)) ?>
                                                <?= (strlen($post["content"] ?? $post["description"] ?? "") > 150) ? '...' : '' ?>
                                            </p>

                                            <!-- Post Meta -->
                                            <div class="flex flex-wrap items-center gap-3 md:gap-4 text-xs md:text-sm">
                                                <span class="flex items-center gap-1 text-gray-500">
                                                    <svg class="w-3 h-3 md:w-4 md:h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                                    </svg>
                                                    <?= date("d M Y", strtotime($post["date"])) ?>
                                                </span>
                                                <span class="px-2 md:px-3 py-1 rounded-full text-xs font-semibold
                                                    <?php if ($post['status'] == 'approved'): ?>
                                                        bg-green-100 text-green-800
                                                    <?php elseif ($post['status'] == 'rejected'): ?>
                                                        bg-red-100 text-red-800
                                                    <?php else: ?>
                                                        bg-yellow-100 text-yellow-800
                                                    <?php endif; ?>">
                                                    <?= ucfirst($post["status"]) ?>
                                                </span>
                                            </div>
                                        </div>

                                        <!-- Action Buttons -->
                                        <div class="flex flex-col sm:flex-row gap-2 sm:gap-3">
                                            <a href=" <?= url('activity_detail?id=' . $post['id']) ?>"
                                               class="px-3 md:px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition font-medium text-xs md:text-sm text-center">
                                                Lihat Detail
                                            </a>
                                            <button onclick="deletePost(<?= $post['id'] ?>, '<?= htmlspecialchars($post['title']) ?>')"
                                                    class="delete-btn px-3 md:px-4 py-2 bg-red-100 text-red-600 rounded-lg hover:bg-red-200 hover:scale-105 transition-all duration-200 text-xs md:text-sm font-medium group relative overflow-hidden">
                                                <span class="relative z-10 flex items-center justify-center gap-1">
                                                    <svg class="w-3 h-3 md:w-4 md:h-4 transition-transform group-hover:rotate-12" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                                    </svg>
                                                    <span class="hidden sm:inline">Hapus</span>
                                                </span>
                                                <div class="absolute inset-0 bg-red-200 transform scale-x-0 group-hover:scale-x-100 transition-transform duration-200 origin-left"></div>
                                            </button>
                                            <?php if ($post['status'] == 'pending'): ?>
                                                <button onclick="alert('Postingan sedang menunggu approval admin')"
                                                        class="px-3 md:px-4 py-2 bg-gray-100 text-gray-600 rounded-lg hover:bg-gray-200 transition text-xs md:text-sm text-center">
                                                    Pending
                                                </button>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </main>

    <?php include __DIR__ . '/../../includes/footer.php'; ?>

    <!-- Delete Confirmation Modal -->
    <div id="deleteModal" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden">
        <div class="flex items-center justify-center min-h-screen p-4">
            <div class="bg-white rounded-2xl shadow-2xl max-w-md w-full transform scale-95 opacity-0 transition-all duration-300">
                <!-- Modal Header -->
                <div class="px-6 py-4 border-b border-gray-200">
                    <div class="flex items-center gap-3">
                        <div class="bg-red-100 p-2 rounded-full">
                            <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                            </svg>
                        </div>
                        <h3 class="text-lg font-semibold text-gray-900">Hapus Postingan</h3>
                    </div>
                </div>

                <!-- Modal Body -->
                <div class="px-6 py-4">
                    <p class="text-gray-600 mb-4">Apa yang ingin Anda lakukan dengan postingan ini?</p>
                    <p class="text-sm text-gray-500 mb-6" id="postTitle"></p>

                    <!-- Options -->
                    <div class="space-y-3">
                        <label class="flex items-center p-3 border-2 border-gray-200 rounded-lg cursor-pointer hover:border-yellow-300 hover:bg-yellow-50 transition-all">
                            <input type="radio" name="deleteOption" value="trash" class="text-yellow-600 focus:ring-yellow-500" checked>
                            <div class="ml-3 flex-1">
                                <div class="flex items-center gap-2">
                                    <svg class="w-5 h-5 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 5a2 2 0 012-2h10a2 2 0 012 2v16l-7-3-7 3V5z"/>
                                    </svg>
                                    <span class="font-medium text-gray-900">Pindahkan ke Trash</span>
                                </div>
                                <p class="text-sm text-gray-500 mt-1">Postingan dapat dikembalikan nanti</p>
                            </div>
                        </label>

                        <label class="flex items-center p-3 border-2 border-gray-200 rounded-lg cursor-pointer hover:border-red-300 hover:bg-red-50 transition-all">
                            <input type="radio" name="deleteOption" value="permanent" class="text-red-600 focus:ring-red-500">
                            <div class="ml-3 flex-1">
                                <div class="flex items-center gap-2">
                                    <svg class="w-5 h-5 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                    </svg>
                                    <span class="font-medium text-gray-900">Hapus Permanen</span>
                                </div>
                                <p class="text-sm text-gray-500 mt-1">Postingan akan hilang selamanya</p>
                            </div>
                        </label>
                    </div>
                </div>

                <!-- Modal Footer -->
                <div class="px-6 py-4 border-t border-gray-200 flex gap-3">
                    <button id="cancelDelete" class="flex-1 px-4 py-2 text-gray-700 bg-gray-100 rounded-lg hover:bg-gray-200 transition font-medium">
                        Batal
                    </button>
                    <button id="confirmDelete" class="flex-1 px-4 py-2 text-white bg-red-600 rounded-lg hover:bg-red-700 transition font-medium disabled:opacity-50 disabled:cursor-not-allowed">
                        Hapus
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Global variables for delete functionality
        let currentButton = null;
        let originalButtonText = null;
        let currentPostId = null;
        let currentPostTitle = null;

        function changeSlide(dot, postId) {
            const container = dot.closest('.carousel-container');
            const slides = container.querySelectorAll('.carousel-slide');
            const dots = container.querySelectorAll('.carousel-dot');

            const slideIndex = parseInt(dot.getAttribute('data-slide'));

            // Update slides
            slides.forEach((slide, index) => {
                if (index === slideIndex) {
                    slide.classList.add('active');
                } else {
                    slide.classList.remove('active');
                }
            });

            // Update dots
            dots.forEach((d, index) => {
                if (index === slideIndex) {
                    d.classList.remove('opacity-50');
                    d.classList.add('opacity-100');
                } else {
                    d.classList.remove('opacity-100');
                    d.classList.add('opacity-50');
                }
            });
        }

        function nextSlide(postId) {
            const container = document.querySelector(`[data-post="${postId}"] .carousel-container`);
            if (!container) return;

            const slides = container.querySelectorAll('.carousel-slide');
            const dots = container.querySelectorAll('.carousel-dot');

            let currentIndex = 0;
            slides.forEach((slide, index) => {
                if (slide.classList.contains('active')) {
                    currentIndex = index;
                }
            });

            const nextIndex = (currentIndex + 1) % slides.length;

            // Update slides
            slides[currentIndex].classList.remove('active');
            slides[nextIndex].classList.add('active');

            // Update dots
            dots[currentIndex].classList.remove('opacity-100');
            dots[currentIndex].classList.add('opacity-50');
            dots[nextIndex].classList.remove('opacity-50');
            dots[nextIndex].classList.add('opacity-100');
        }

        function prevSlide(postId) {
            const container = document.querySelector(`[data-post="${postId}"] .carousel-container`);
            if (!container) return;

            const slides = container.querySelectorAll('.carousel-slide');
            const dots = container.querySelectorAll('.carousel-dot');

            let currentIndex = 0;
            slides.forEach((slide, index) => {
                if (slide.classList.contains('active')) {
                    currentIndex = index;
                }
            });

            const prevIndex = currentIndex === 0 ? slides.length - 1 : currentIndex - 1;

            // Update slides
            slides[currentIndex].classList.remove('active');
            slides[prevIndex].classList.add('active');

            // Update dots
            dots[currentIndex].classList.remove('opacity-100');
            dots[currentIndex].classList.add('opacity-50');
            dots[prevIndex].classList.remove('opacity-50');
            dots[prevIndex].classList.add('opacity-100');
        }

        function deletePost(postId, postTitle) {
            const button = event.target.closest('button');
            currentButton = button;
            originalButtonText = button.innerHTML;
            currentPostId = postId;
            currentPostTitle = postTitle;
            showDeleteModal();
        }

        function showDeleteModal() {
            const modal = document.getElementById('deleteModal');
            const postTitleElement = document.getElementById('postTitle');

            // Set post title in modal
            postTitleElement.textContent = '"' + currentPostTitle + '"';

            // Show modal with animation
            modal.classList.remove('hidden');
            setTimeout(() => {
                const modalContent = modal.querySelector('.bg-white');
                modalContent.classList.remove('scale-95', 'opacity-0');
                modalContent.classList.add('scale-100', 'opacity-100');
            }, 10);

            // Focus on cancel button for accessibility
            document.getElementById('cancelDelete').focus();
        }

        function hideDeleteModal() {
            const modal = document.getElementById('deleteModal');
            const modalContent = modal.querySelector('.bg-white');

            // Hide modal with animation
            modalContent.classList.remove('scale-100', 'opacity-100');
            modalContent.classList.add('scale-95', 'opacity-0');

            setTimeout(() => {
                modal.classList.add('hidden');
                // Reset form
                document.querySelector('input[name="deleteOption"]:checked').checked = true;
            }, 300);
        }

        // Event listeners for modal
        document.addEventListener('DOMContentLoaded', function() {
            const cancelBtn = document.getElementById('cancelDelete');
            const confirmBtn = document.getElementById('confirmDelete');

            cancelBtn.addEventListener('click', function() {
                hideDeleteModal();
                // Restore button state if needed
                if (currentButton) {
                    currentButton.innerHTML = originalButtonText;
                    currentButton.disabled = false;
                }
            });

            confirmBtn.addEventListener('click', function() {
                const selectedOption = document.querySelector('input[name="deleteOption"]:checked').value;
                performDelete(selectedOption);
            });

            // Close modal when clicking outside
            document.getElementById('deleteModal').addEventListener('click', function(e) {
                if (e.target === this) {
                    hideDeleteModal();
                }
            });

            // Keyboard navigation
            document.addEventListener('keydown', function(e) {
                const modal = document.getElementById('deleteModal');
                if (!modal.classList.contains('hidden') && e.key === 'Escape') {
                    hideDeleteModal();
                }
            });
        });

        function performDelete(deleteType) {
            // Show loading state
            const confirmBtn = document.getElementById('confirmDelete');
            const cancelBtn = document.getElementById('cancelDelete');

            confirmBtn.disabled = true;
            confirmBtn.innerHTML = '<svg class="w-4 h-4 inline animate-spin mr-2" fill="none" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" class="opacity-25"></circle><path fill="currentColor" class="opacity-75" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg> ' + (deleteType === 'trash' ? 'Memindahkan...' : 'Menghapus...');

            cancelBtn.disabled = true;

            // Send delete request
            fetch(window.location.href, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams({
                    'delete_post': '1',
                    'post_id': currentPostId,
                    'delete_type': deleteType
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Enhanced animation for post removal
                    if (currentButton) {
                        const postElement = currentButton.closest('.bg-white');

                        if (deleteType === 'trash') {
                            // Special animation for trash - make it look like it's being "thrown away"
                            animateTrashAction(postElement);
                        } else {
                            // Normal delete animation
                            animateNormalDelete(postElement);
                        }
                    }

                    // Show success message with better description
                    const message = deleteType === 'trash'
                        ? '🎯 Postingan berhasil dipindahkan ke trash!'
                        : '🗑️ Postingan berhasil dihapus permanen!';
                    showNotification(message, 'success');

                    // Hide modal
                    hideDeleteModal();
                } else {
                    throw new Error(data.message || 'Gagal menghapus postingan');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showNotification('❌ Gagal menghapus postingan: ' + error.message, 'error');
            })
            .finally(() => {
                // Restore button states
                confirmBtn.disabled = false;
                cancelBtn.disabled = false;
                confirmBtn.innerHTML = 'Hapus';

                // Restore original button if exists
                if (currentButton) {
                    currentButton.innerHTML = originalButtonText;
                    currentButton.disabled = false;
                }
            });
        }

        function animateTrashAction(postElement) {
            // Multi-stage animation for trash action
            const stages = [
                // Stage 1: Highlight with orange border and shake
                () => {
                    postElement.style.border = '3px solid #f97316';
                    postElement.style.background = 'linear-gradient(135deg, #fff7ed 0%, #fed7aa 100%)';
                    postElement.style.transform = 'scale(1.02)';

                    // Add shake animation
                    let shakeCount = 0;
                    const shakeInterval = setInterval(() => {
                        postElement.style.transform = shakeCount % 2 === 0
                            ? 'scale(1.02) translateX(-3px)'
                            : 'scale(1.02) translateX(3px)';
                        shakeCount++;

                        if (shakeCount >= 6) {
                            clearInterval(shakeInterval);
                            nextStage();
                        }
                    }, 80);
                },

                // Stage 2: Add trash icon overlay and fade
                () => {
                    // Create trash overlay
                    const trashOverlay = document.createElement('div');
                    trashOverlay.innerHTML = `
                        <div style="
                            position: absolute;
                            top: 50%;
                            left: 50%;
                            transform: translate(-50%, -50%);
                            background: rgba(251, 146, 60, 0.9);
                            border-radius: 50%;
                            padding: 12px;
                            z-index: 10;
                        ">
                            <svg style="width: 24px; height: 24px; color: white;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                            </svg>
                        </div>
                    `;
                    trashOverlay.style.position = 'absolute';
                    trashOverlay.style.top = '0';
                    trashOverlay.style.left = '0';
                    trashOverlay.style.width = '100%';
                    trashOverlay.style.height = '100%';
                    trashOverlay.style.background = 'rgba(251, 146, 60, 0.1)';
                    trashOverlay.style.borderRadius = '12px';
                    trashOverlay.style.display = 'flex';
                    trashOverlay.style.alignItems = 'center';
                    trashOverlay.style.justifyContent = 'center';
                    trashOverlay.style.zIndex = '5';

                    postElement.style.position = 'relative';
                    postElement.appendChild(trashOverlay);

                    setTimeout(() => nextStage(), 800);
                },

                // Stage 3: Animate out with slide and fade
                () => {
                    postElement.style.transition = 'all 0.6s cubic-bezier(0.25, 0.46, 0.45, 0.94)';
                    postElement.style.opacity = '0';
                    postElement.style.transform = 'translateX(150%) scale(0.8) rotate(5deg)';

                    setTimeout(() => {
                        postElement.remove();
                    }, 600);
                }
            ];

            let currentStage = 0;

            function nextStage() {
                currentStage++;
                if (stages[currentStage]) {
                    stages[currentStage]();
                }
            }

            // Start animation
            stages[0]();
        }

        function animateNormalDelete(postElement) {
            // Simple fade and slide out for permanent delete
            postElement.style.transition = 'all 0.4s ease-out';
            postElement.style.opacity = '0';
            postElement.style.transform = 'translateX(100%)';

            setTimeout(() => {
                postElement.remove();
            }, 400);
        }

        function showNotification(message, type = 'info') {
            // Remove existing notifications
            const existingNotifications = document.querySelectorAll('.notification');
            existingNotifications.forEach(notif => notif.remove());

            // Create notification element
            const notification = document.createElement('div');
            notification.className = `notification fixed top-4 right-4 px-6 py-3 rounded-lg shadow-lg z-50 transition-all duration-300 transform translate-x-full
                ${type === 'success' ? 'bg-green-500 text-white' :
                  type === 'error' ? 'bg-red-500 text-white' :
                  'bg-blue-500 text-white'}`;

            notification.innerHTML = `
                <div class="flex items-center gap-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        ${type === 'success' ?
                            '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>' :
                            type === 'error' ?
                            '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>' :
                            '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>'}
                    </svg>
                    ${message}
                </div>
            `;

            document.body.appendChild(notification);

            // Animate in
            setTimeout(() => {
                notification.style.transform = 'translateX(0)';
            }, 100);

            // Auto remove after 4 seconds
            setTimeout(() => {
                notification.style.transform = 'translateX(full)';
                setTimeout(() => {
                    notification.remove();
                }, 300);
            }, 4000);
        }
    </script>

    <!-- Page Transition Script -->
    <script>
        // Page transition and loading management
        document.addEventListener('DOMContentLoaded', function() {
            // Detect if this is browser back/forward navigation
            const navigationType = window.performance.getEntriesByType('navigation')[0]?.type;

            if (navigationType && navigationType.includes('back_forward')) {
                // For browser back/forward - disable all animations and overlays
                disablePageAnimations();
            } else {
                // For normal page loads - enable animations
                initializePageTransitions();
            }

            // Setup navigation links with loading animation (only for normal clicks)
            setupNavigationAnimations();
        });

        // Listen for browser back/forward navigation
        window.addEventListener('pageshow', function(event) {
            // If this is from browser cache (back/forward)
            if (event.persisted) {
                disablePageAnimations();
            }
        });

        // Also listen for popstate events (browser back/forward)
        window.addEventListener('popstate', function(event) {
            disablePageAnimations();
        });

        function disablePageAnimations() {
            // Hide loading overlay immediately and completely
            const loadingOverlay = document.getElementById('pageLoadingOverlay');
            if (loadingOverlay) {
                loadingOverlay.style.display = 'none';
                loadingOverlay.classList.remove('opacity-100', 'visible');
                loadingOverlay.classList.add('opacity-0', 'invisible');
            }

            // Remove animation classes from main content and ensure visibility
            const mainContent = document.getElementById('mainContent');
            if (mainContent) {
                mainContent.classList.remove('page-transition-enter');
                mainContent.style.opacity = '1';
                mainContent.style.transform = 'none';
                mainContent.style.transition = 'none'; // Disable transitions for browser nav
            }

            // Force page to be fully visible
            document.body.style.opacity = '1';
            document.body.style.visibility = 'visible';
        }

        function initializePageTransitions() {
            const mainContent = document.getElementById('mainContent');
            const loadingOverlay = document.getElementById('pageLoadingOverlay');

            // Animate page entrance only if not from browser back/forward
            if (mainContent && !window.performance.getEntriesByType('navigation')[0]?.type?.includes('back_forward')) {
                setTimeout(() => {
                    mainContent.classList.remove('page-transition-enter');
                    mainContent.classList.add('page-transition-enter-active');
                }, 100);
            }

            // Hide loading overlay if visible
            if (loadingOverlay) {
                // Check if we're coming from browser back/forward
                const navigationType = window.performance.getEntriesByType('navigation')[0]?.type;
                if (navigationType && navigationType.includes('back_forward')) {
                    // Hide loading overlay immediately for browser navigation
                    loadingOverlay.classList.remove('opacity-100', 'visible');
                    loadingOverlay.classList.add('opacity-0', 'invisible');
                } else {
                    // Normal page load - hide after animation
                    setTimeout(() => {
                        loadingOverlay.classList.remove('opacity-100', 'visible');
                        loadingOverlay.classList.add('opacity-0', 'invisible');
                    }, 500);
                }
            }
        }

        function setupNavigationAnimations() {
            // Add loading animation to navigation links
            const navLinks = document.querySelectorAll('.nav-link, a[href*="dashboard"], a[href*="trash"], a[href*="announcement"]');

            navLinks.forEach(link => {
                link.addEventListener('click', function(e) {
                    const href = this.getAttribute('href');
                    if (href && !href.startsWith('#') && !href.startsWith('http')) {
                        e.preventDefault();
                        showLoadingAndNavigate(href);
                    }
                });
            });
        }

        function showLoadingAndNavigate(targetUrl) {
            const loadingOverlay = document.getElementById('pageLoadingOverlay');
            const mainContent = document.getElementById('mainContent');

            if (loadingOverlay && mainContent) {
                // Show loading overlay
                loadingOverlay.classList.add('opacity-100', 'visible');
                loadingOverlay.classList.remove('opacity-0', 'invisible');

                // Animate content out
                mainContent.classList.remove('page-transition-enter-active');
                mainContent.classList.add('opacity-0', 'translate-y-4');

                // Navigate after animation
                setTimeout(() => {
                    window.location.href = targetUrl;
                }, 400);
            } else {
                // Fallback if elements not found
                window.location.href = targetUrl;
            }
        }
    </script>
</body>
</html>
