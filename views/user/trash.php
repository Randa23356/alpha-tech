<?php
// views/user/trash.php

// Start output buffering at the very beginning
ob_start();

// Error reporting
ini_set("display_errors", 1);
ini_set("display_startup_errors", 1);
error_reporting(E_ALL);

// Session is already started in session.php, no need to start again
require_once __DIR__ . "/../../src/helpers/session.php";
require_once __DIR__ . "/../../src/config/db.php";
require_once __DIR__ . "/../../src/config/urls.php";

// Function to send JSON response and exit
function sendJsonResponse($data, $statusCode = 200) {
    // Clear any previous output
    if (ob_get_level() > 0) {
        ob_clean();
    }
    
    http_response_code($statusCode);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}

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

// Ambil postingan yang di-trash
try {
    // Query untuk menampilkan postingan yang di-trash (hanya berdasarkan deleted_at)
    $stmt = $pdo->prepare("SELECT p.*, COALESCE(pi.image_path, p.image) as thumbnail_image FROM posts p LEFT JOIN post_images pi ON p.id = pi.post_id AND pi.image_order = 0 WHERE p.user_id = ? AND p.deleted_at IS NOT NULL ORDER BY p.deleted_at DESC, p.updated_at DESC");
    $stmt->execute([$user['id']]);
    $trashed_posts = $stmt->fetchAll();

    // Get all images for each post
    foreach ($trashed_posts as &$post) {
        $stmt = $pdo->prepare("SELECT image_path, image_order FROM post_images WHERE post_id = ? ORDER BY image_order");
        $stmt->execute([$post['id']]);
        $post['images'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Hitung jumlah postingan di trash
    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM posts WHERE user_id = ? AND deleted_at IS NOT NULL");
    $stmt->execute([$user['id']]);
    $trash_count = $stmt->fetch()['total'] ?? 0;

} catch (Exception $e) {
    $trashed_posts = [];
    $trash_count = 0;
    error_log('Database error in user trash: ' . $e->getMessage());
}

// Handle post restoration and permanent deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isLoggedIn()) {
    $postId = intval($_POST['post_id'] ?? 0);
    $action = $_POST['action'] ?? '';
    
    // Handle empty trash action
    if ($action === 'empty_trash') {
        // Check if this is an AJAX request
        $isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
                 strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';
        
        if (!$isAjax) {
            sendJsonResponse(['success' => false, 'message' => 'Invalid request'], 400);
        }
        
        // Pastikan user sudah login
        if (!isLoggedIn() || !isset($_SESSION['user']['id'])) {
            error_log('User tidak login atau session tidak valid. Session data: ' . print_r($_SESSION, true));
            sendJsonResponse(['success' => false, 'message' => 'Anda harus login terlebih dahulu'], 401);
        }
        
        $user_id = $_SESSION['user']['id'];
        error_log('User ID dari session: ' . $user_id);
        error_log("Mengosongkan trash untuk user_id: " . $user_id);
        
        try {
            // Get all trashed posts by current user
            $sql = "
                SELECT p.id, p.title, p.deleted_at 
                FROM posts p
                WHERE p.user_id = :user_id 
                AND p.deleted_at IS NOT NULL
                LIMIT 100  -- Batasi jumlah data untuk keamanan
            ";
            
            $stmt = $pdo->prepare($sql);
            $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
            
            error_log("Mencari postingan di trash untuk user_id: " . $user_id);
            error_log("Query: " . $sql);
            
            if (!$stmt->execute()) {
                $errorInfo = $stmt->errorInfo();
                throw new Exception("Gagal mengambil data postingan: " . ($errorInfo[2] ?? 'Unknown error'));
            }
            
            $posts = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $trashedPosts = array_column($posts, 'id');
            
            error_log("Ditemukan " . count($trashedPosts) . " postingan di trash");
            
            if (empty($trashedPosts)) {
                error_log("Tidak ada postingan di trash untuk user_id: " . $user_id);
                sendJsonResponse(['success' => true, 'message' => 'Tidak ada postingan di trash']);
            } else {
                $postDetails = [];
                foreach ($posts as $post) {
                    $postDetails[] = $post['id'] . ' - ' . $post['title'];
                }
                error_log("Postingan yang akan dihapus: " . implode(', ', $postDetails));
            }
            
            // Start transaction
            try {
                $pdo->beginTransaction();
                error_log("Transaction dimulai");
            } catch (Exception $e) {
                error_log("Gagal memulai transaction: " . $e->getMessage());
                throw $e;
            }
            
            // Delete post images
            $placeholders = rtrim(str_repeat('?,', count($trashedPosts)), ',');
            
            // Get image paths before deleting
            $stmt = $pdo->prepare("
                SELECT image_path FROM post_images 
                WHERE post_id IN ($placeholders)
            
            ");
            $stmt->execute($trashedPosts);
            $images = $stmt->fetchAll(PDO::FETCH_COLUMN);
            
            // Debug: Tampilkan data yang akan dihapus
            error_log('Menghapus post dengan ID: ' . implode(', ', $trashedPosts));
            
            // Matikan sementara foreign key check
            $pdo->exec('SET FOREIGN_KEY_CHECKS=0');
            error_log("Foreign key checks disabled");
            
            try {
                // Hapus dari post_images
                $deleteImagesStmt = $pdo->prepare(
                    "DELETE FROM post_images WHERE post_id IN (" . 
                    implode(',', array_fill(0, count($trashedPosts), '?')) . 
                    ")"
                );
                
                error_log("Menghapus gambar untuk post_id: " . implode(', ', $trashedPosts));
                
                if (!$deleteImagesStmt->execute($trashedPosts)) {
                    $errorInfo = $deleteImagesStmt->errorInfo();
                    throw new Exception("Gagal menghapus gambar: " . ($errorInfo[2] ?? 'Unknown error'));
                }
                
                error_log("Berhasil menghapus " . $deleteImagesStmt->rowCount() . " gambar");
                
            } catch (Exception $e) {
                $pdo->exec('SET FOREIGN_KEY_CHECKS=1');
                throw $e;
            }
            
            // Hapus dari posts
            $placeholders = rtrim(str_repeat('?,', count($trashedPosts)), ',');
            $sql = "DELETE FROM posts WHERE id IN ($placeholders) AND user_id = ?";
            $deletePostsStmt = $pdo->prepare($sql);
            
            // Gabungkan parameter: pertama ID postingan, terakhir user_id
            $params = array_merge($trashedPosts, [$user_id]);
            
            error_log("Menghapus postingan dengan query: " . $sql);
            error_log("Parameter: " . print_r($params, true));
            
            if (!$deletePostsStmt->execute($params)) {
                $errorInfo = $deletePostsStmt->errorInfo();
                throw new Exception("Gagal menghapus postingan: " . ($errorInfo[2] ?? 'Unknown error'));
            }
            
            $deletedCount = $deletePostsStmt->rowCount();
            error_log("Jumlah postingan yang berhasil dihapus: " . $deletedCount);
            
            if ($deletedCount === 0) {
                error_log("Tidak ada postingan yang terhapus. Mungkin user_id tidak sesuai atau postingan sudah dihapus.");
            }
            
            $deletedRows = $deletePostsStmt->rowCount();
            error_log("Jumlah postingan yang dihapus: " . $deletedRows);
            
            // Commit transaction
            $pdo->commit();
            error_log("Transaction berhasil di-commit");
            
            // Aktifkan kembali foreign key check
            $pdo->exec('SET FOREIGN_KEY_CHECKS=1');
            error_log("Foreign key checks diaktifkan kembali");
            
            // Delete image files
            foreach ($images as $image) {
                if (file_exists($_SERVER['DOCUMENT_ROOT'] . $image)) {
                    unlink($_SERVER['DOCUMENT_ROOT'] . $image);
                }
            }
            
            sendJsonResponse(['success' => true, 'message' => 'Semua postingan berhasil dihapus permanen']);
            
        } catch (Exception $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $errorMessage = 'Gagal mengosongkan trash: ' . $e->getMessage();
            error_log($errorMessage);
            sendJsonResponse(['success' => false, 'message' => $errorMessage], 500);
        }
    }
    
    // Handle other actions (restore, permanent_delete)

    if ($postId > 0 && in_array($action, ['restore', 'permanent_delete'])) {
        try {
            // Verify the post belongs to the current user and is in trash
            $stmt = $pdo->prepare("SELECT user_id FROM posts WHERE id = ? AND deleted_at IS NOT NULL");
            $stmt->execute([$postId]);
            $post = $stmt->fetch();

            if (!$post || $post['user_id'] !== $user['id']) {
                echo json_encode(['success' => false, 'message' => 'Postingan tidak ditemukan atau bukan milik Anda']);
                exit();
            }

            if ($action === 'restore') {
                // Restore post from trash - only clear deleted_at, keep status unchanged
                $stmt = $pdo->prepare("UPDATE posts SET deleted_at = NULL WHERE id = ? AND user_id = ?");
                if ($stmt->execute([$postId, $user['id']])) {
                    echo json_encode(['success' => true, 'message' => 'Postingan berhasil dikembalikan']);
                } else {
                    echo json_encode(['success' => false, 'message' => 'Gagal mengembalikan postingan']);
                }
            } elseif ($action === 'permanent_delete') {
                // Permanently delete post - delete all associated images first
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
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Trash - Dashboard User - Informatics A</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="<?= url('public/tailwind.css') ?>" rel="stylesheet">
    <style>
        /* Responsive Design Improvements */
        @media (max-width: 768px) {
            .trash-card {
                flex-direction: column !important;
            }
            .trash-card-image {
                width: 100% !important;
                height: 200px !important;
            }
            .trash-card-content {
                padding: 1rem !important;
            }
            .trash-actions {
                flex-direction: column;
                gap: 0.5rem;
                margin-top: 1rem;
            }
            .trash-actions button {
                width: 100%;
                justify-content: center;
            }
            .post-meta {
                flex-wrap: wrap;
                gap: 0.5rem;
            }
            .post-meta span {
                font-size: 0.8rem;
            }
        }

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

        /* Enhanced modal animations */
        .modal-overlay {
            backdrop-filter: blur(4px);
            background: rgba(0, 0, 0, 0.5);
        }

        /* Smooth hover effects */
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
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-4xl font-bold mb-2" style="background: linear-gradient(135deg, <?= $primary_color ?> 0%, <?= $secondary_color ?> 100%); -webkit-background-clip: text; background-clip: text; color: transparent;">Trash</h1>
                    <p class="text-gray-600 text-lg">Postingan yang telah dihapus sementara</p>
                </div>
                <a href="<?= url('dashboard') ?>" class="nav-link inline-flex items-center gap-2 text-white px-4 py-2 rounded-lg font-medium transition" style="background: linear-gradient(135deg, <?= $primary_color ?> 0%, <?= $secondary_color ?> 100%);" onmouseover="this.style.background='linear-gradient(135deg, <?= $secondary_color ?> 0%, <?= $primary_color ?> 100%)'"" onmouseout="this.style.background='linear-gradient(135deg, <?= $primary_color ?> 0%, <?= $secondary_color ?> 100%)'">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                    </svg>
                    Kembali ke Dashboard
                </a>
            </div>
        </div>

        <!-- Statistics -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-10">
            <div class="bg-white rounded-2xl shadow-lg p-6 border-l-4 hover:shadow-xl transition" style="border-color: <?= $primary_color ?>;">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-600 text-sm font-medium mb-1">Postingan di Trash</p>
                        <p class="text-3xl font-bold text-gray-900"><?= $trash_count ?></p>
                    </div>
                    <div class="bg-gray-100 p-4 rounded-xl">
                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="color: <?= $primary_color ?>;">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                        </svg>
                    </div>
                </div>
            </div>
        </div>

        <!-- Trash Content -->
        <div class="bg-white rounded-2xl shadow-lg p-8">
            <h2 class="text-2xl font-bold text-gray-900 mb-6">Postingan yang Dihapus</h2>

            <?php if (empty($trashed_posts)): ?>
                <div class="text-center py-12">
                    <div class="text-6xl mb-4">🗑️</div>
                    <p class="text-gray-600 text-lg mb-4">Trash kosong.</p>
                    <p class="text-gray-500 mb-6">Belum ada postingan yang dihapus.</p>
                    <a href="<?= url('dashboard') ?>" class="inline-flex items-center gap-2 text-white px-6 py-3 rounded-lg font-semibold transition" style="background: linear-gradient(135deg, <?= $primary_color ?> 0%, <?= $secondary_color ?> 100%);" onmouseover="this.style.background='linear-gradient(135deg, <?= $secondary_color ?> 0%, <?= $primary_color ?> 100%)'"" onmouseout="this.style.background='linear-gradient(135deg, <?= $primary_color ?> 0%, <?= $secondary_color ?> 100%)'">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                        </svg>
                        Buat Postingan Baru
                    </a>
                </div>
            <?php else: ?>
                <div class="space-y-4 md:space-y-6">
                    <?php foreach ($trashed_posts as $post): ?>
                        <div class="bg-white border-2 rounded-xl overflow-hidden opacity-75 hover:opacity-100 transition-all duration-300 trash-card" style="border-color: <?= $primary_color ?>30;">
                            <div class="flex flex-col md:flex-row">
                                <!-- Post Images -->
                                <?php if (!empty($post['images'])): ?>
                                    <div class="md:w-48 flex-shrink-0 relative trash-card-image" data-post="<?= $post['id'] ?>">
                                        <?php if (count($post['images']) == 1): ?>
                                            <!-- Single image -->
                                            <img src="<?= upload_url(htmlspecialchars($post['images'][0]['image_path'])) ?>"
                                                 alt="<?= htmlspecialchars($post['title']) ?>"
                                                 class="w-full h-48 md:h-full object-cover grayscale">
                                        <?php else: ?>
                                            <!-- Multiple images carousel -->
                                            <div class="relative h-48 md:h-full overflow-hidden" data-post="<?= $post['id'] ?>">
                                                <div class="flex h-full">
                                                    <?php foreach ($post['images'] as $index => $image): ?>
                                                        <div class="carousel-slide w-full h-full flex-shrink-0 <?= $index === 0 ? 'active' : '' ?>">
                                                            <img src="<?= upload_url(htmlspecialchars($image['image_path'])) ?>"
                                                                 alt="<?= htmlspecialchars($post['title']) ?>"
                                                                 class="w-full h-full object-cover grayscale">
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
                                                            onclick="nextSlide(<?= $post['id'] ?>)">
                                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                                                        </svg>
                                                    </button>
                                                    <button class="absolute right-2 top-1/2 transform -translate-y-1/2 bg-black bg-opacity-50 text-white p-1 rounded-full hover:bg-opacity-75 transition-opacity"
                                                            onclick="nextSlide(<?= $post['id'] ?>)">
                                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
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
                                    <div class="flex flex-col md:flex-row md:justify-between md:items-start gap-4">
                                        <div class="flex-1">
                                            <div class="flex flex-col sm:flex-row sm:items-center gap-2 mb-2">
                                                <h3 class="text-lg md:text-xl font-bold text-gray-800 line-clamp-2">
                                                    <?= htmlspecialchars($post["title"]) ?>
                                                </h3>
                                                <span class="text-xs px-2 py-1 rounded-full w-fit <?= $post['status'] === 'draft' ? 'bg-yellow-100 text-yellow-800' : ($post['status'] === 'published' ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800') ?>">
                                                    <?= ucfirst(htmlspecialchars($post['status'])) ?>
                                                </span>
                                            </div>

                                            <div class="flex flex-wrap items-center gap-2 md:gap-3 text-xs md:text-sm text-gray-500 mb-3 post-meta">
                                                <span class="flex items-center">
                                                    <svg class="w-3.5 h-3.5 md:w-4 md:h-4 mr-1 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                                    </svg>
                                                    <?= date('d M Y', strtotime($post['created_at'])) ?>
                                                </span>
                                                <span class="flex items-center">
                                                    <svg class="w-3.5 h-3.5 md:w-4 md:h-4 mr-1 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                    </svg>
                                                    <?= date('H:i', strtotime($post['created_at'])) ?>
                                                </span>
                                                <span class="flex items-center">
                                                    <svg class="w-3.5 h-3.5 md:w-4 md:h-4 mr-1 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4" />
                                                    </svg>
                                                    <span class="hidden sm:inline">Dihapus: </span>
                                                    <?= date('d M Y', strtotime($post['deleted_at'])) ?>
                                                </span>
                                            </div>

                                            <p class="text-gray-600 text-sm md:text-base mb-3 md:mb-4 line-clamp-2">
                                                <?= htmlspecialchars(substr($post['content'], 0, 150)) ?><?= strlen($post['content']) > 150 ? '...' : '' ?>
                                            </p>
                                        </div>

                                        <div class="flex flex-col sm:flex-row md:flex-col gap-2 trash-actions">
                                            <button onclick="restorePost(<?= $post['id'] ?>, '<?= htmlspecialchars($post['title']) ?>')"
                                                    class="px-4 py-2 text-white rounded-lg hover:bg-green-700 transition font-medium text-sm" style="background: linear-gradient(135deg, <?= $success_color ?> 0%, <?= $primary_color ?> 100%);" onmouseover="this.style.background='linear-gradient(135deg, <?= $primary_color ?> 0%, <?= $success_color ?> 100%)'" onmouseout="this.style.background='linear-gradient(135deg, <?= $success_color ?> 0%, <?= $primary_color ?> 100%)'">
                                                <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                                                </svg>
                                                Pulihkan
                                            </button>
                                            <button onclick="permanentDeletePost(<?= $post['id'] ?>, '<?= htmlspecialchars(addslashes($post['title'])) ?>', this)"
                                                    class="px-3 py-2 text-white rounded-lg hover:bg-red-700 transition text-sm font-medium" style="background: linear-gradient(135deg, <?= $accent_color ?> 0%, #dc2626 100%);" onmouseover="this.style.background='linear-gradient(135deg, #dc2626 0%, <?= $accent_color ?> 100%)'" onmouseout="this.style.background='linear-gradient(135deg, <?= $accent_color ?> 0%, #dc2626 100%)'">
                                                <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                                </svg>
                                                Hapus Permanen
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <!-- Bulk Actions (if needed in the future) -->
                <?php if ($trash_count > 0): ?>
                    <div class="mt-8 pt-6 border-t border-gray-200">
                        <div class="flex justify-between items-center">
                            <p class="text-sm text-gray-600">
                                Total: <?= $trash_count ?> postingan di trash
                            </p>
                            <button onclick="emptyTrash()"
                                    class="px-4 py-2 bg-red-100 text-red-600 rounded-lg hover:bg-red-200 transition text-sm font-medium">
                                Kosongkan Trash
                            </button>
                        </div>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>

        <!-- Empty Trash Modal -->
        <div id="emptyTrashModal" class="fixed inset-0 bg-black bg-opacity-0 z-50 hidden transition-opacity duration-300">
            <div class="flex items-center justify-center min-h-screen p-4">
                <div class="bg-white rounded-2xl shadow-2xl max-w-md w-full mx-4 transform scale-95 opacity-0 transition-all duration-300" id="emptyTrashModalContent">
                    <!-- Modal Header -->
                    <div class="px-4 py-3 sm:px-6 sm:py-4 border-b border-gray-200">
                        <h3 class="text-lg sm:text-xl font-semibold text-gray-800">Bersihkan Sampah</h3>
                    </div>

                    <!-- Modal Body -->
                    <div class="p-4 sm:p-6">
                        <div class="flex items-center justify-center mb-4">
                            <div class="bg-red-100 p-3 rounded-full">
                                <svg class="w-6 h-6 sm:w-8 sm:h-8 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                </svg>
                            </div>
                        </div>
                        <p class="text-gray-700 text-sm sm:text-base text-center mb-4 sm:mb-6">
                            Apakah Anda yakin ingin menghapus semua postingan di sampah secara permanen? Tindakan ini tidak dapat dibatalkan.
                        </p>
                    </div>

                    <!-- Modal Footer -->
                    <div class="p-4 sm:p-6 pt-0">
                        <div class="flex flex-col sm:flex-row-reverse gap-3 mt-6">
                            <button type="button" id="confirmEmptyTrash" 
                                onclick="confirmEmptyTrash()" 
                                class="w-full px-4 py-2.5 text-sm font-medium text-white bg-red-600 rounded-lg hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 transition-colors flex items-center justify-center">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                </svg>
                                Ya, Hapus Semua
                            </button>
                            <button type="button" id="cancelEmptyTrash" 
                                class="w-full px-4 py-2.5 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors">
                                Batal
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Restore Confirmation Modal -->
        <div id="restoreModal" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden">
            <div class="flex items-center justify-center min-h-screen p-4">
                <div class="bg-white rounded-2xl shadow-2xl max-w-md w-full transform scale-95 opacity-0 transition-all duration-300">
                    <!-- Modal Header -->
                    <div class="px-6 py-4 border-b border-gray-200">
                        <div class="flex items-center gap-3">
                            <div class="bg-green-100 p-2 rounded-full">
                                <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                                </svg>
                            </div>
                            <h3 class="text-lg font-semibold text-gray-900">Pulihkan Postingan</h3>
                        </div>
                    </div>

                    <!-- Modal Body -->
                    <div class="px-6 py-4">
                        <p class="text-gray-600 mb-4">Apakah Anda yakin ingin memulihkan postingan ini?</p>
                        <p class="text-sm text-gray-500 mb-6" id="restorePostTitle"></p>

                        <div class="bg-green-50 border border-green-200 rounded-lg p-3 mb-4">
                            <div class="flex items-center gap-2">
                                <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                                </svg>
                                <span class="font-medium text-green-800">Postingan akan dikembalikan ke dashboard utama</span>
                            </div>
                        </div>
                    </div>

                    <!-- Modal Footer -->
                    <div class="px-6 py-4 border-t border-gray-200 flex gap-3">
                        <button id="cancelRestore" class="flex-1 px-4 py-2 text-gray-700 bg-gray-100 rounded-lg hover:bg-gray-200 transition font-medium">
                            Batal
                        </button>
                        <button id="confirmRestore" class="flex-1 px-4 py-2 text-white bg-green-600 rounded-lg hover:bg-green-700 transition font-medium disabled:opacity-50 disabled:cursor-not-allowed">
                            Pulihkan
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Permanent Delete Confirmation Modal -->
        <div id="permanentDeleteModal" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden" tabindex="-1" role="dialog" aria-modal="true">
            <div class="flex items-center justify-center min-h-screen p-4">
                <div class="bg-white rounded-2xl shadow-2xl max-w-md w-full mx-4 transform scale-95 opacity-0 transition-all duration-300">
                    <!-- Modal Header -->
                    <div class="px-4 py-3 sm:px-6 sm:py-4 border-b border-gray-200">
                        <h3 class="text-lg sm:text-xl font-semibold text-gray-800">Hapus Permanen</h3>
                    </div>

                    <!-- Modal Body -->
                    <div class="p-4 sm:p-6">
                        <p class="text-gray-600 mb-6">
                            Apakah Anda yakin ingin menghapus postingan <span id="deletePostTitle" class="font-semibold"></span> secara permanen?
                            Tindakan ini tidak dapat dibatalkan dan semua data yang terkait akan dihapus.
                        </p>

                        <div class="flex flex-col sm:flex-row-reverse gap-3 mt-6">
                            <button type="button" id="confirmPermanentDelete" class="w-full px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 transition-colors">
                                Ya, Hapus Permanen
                            </button>
                            <button type="button" id="cancelPermanentDelete" class="w-full px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors">
                                Batal
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <?php include __DIR__ . '/../../includes/footer.php'; ?>

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

        // Enhanced modal animations

        // Modal show/hide functions
        function showRestoreModal() {
            const modal = document.getElementById('restoreModal');
            const postTitleElement = document.getElementById('restorePostTitle');

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
            document.getElementById('cancelRestore').focus();
        }

        function hideRestoreModal() {
            const modal = document.getElementById('restoreModal');
            const modalContent = modal.querySelector('.bg-white');

            // Hide modal with animation
            modalContent.classList.remove('scale-100', 'opacity-100');
            modalContent.classList.add('scale-95', 'opacity-0');

            setTimeout(() => {
                modal.classList.add('hidden');
            }, 300);
        }

        function showPermanentDeleteModal() {
            const modal = document.getElementById('permanentDeleteModal');
            const postTitleElement = document.getElementById('deletePostTitle');

            if (!modal) {
                console.error('Modal element not found');
                return;
            }

            // Set post title in modal if element exists
            if (postTitleElement) {
                postTitleElement.textContent = '"' + currentPostTitle + '"';
            }

            // Show modal with animation
            modal.classList.remove('hidden');
            setTimeout(() => {
                const modalContent = modal.querySelector('.bg-white');
                if (modalContent) {
                    modalContent.classList.remove('scale-95', 'opacity-0');
                    modalContent.classList.add('scale-100', 'opacity-100');
                }
            }, 10);

            // Focus on cancel button for accessibility
            const cancelBtn = document.getElementById('cancelPermanentDelete');
            if (cancelBtn) {
                cancelBtn.focus();
            } else {
                console.error('Cancel button not found');
            }
        }

        function hidePermanentDeleteModal() {
            const modal = document.getElementById('permanentDeleteModal');
            const modalContent = modal.querySelector('.bg-white');

            // Hide modal with animation
            modalContent.classList.remove('scale-100', 'opacity-100');
            modalContent.classList.add('scale-95', 'opacity-0');

            setTimeout(() => {
                modal.classList.add('hidden');
            }, 300);
        }

        // Global variables for modals
        let currentPostId = null;
        let currentPostTitle = '';
        let currentButton = null;
        let originalButtonText = '';

        function restorePost(postId, postTitle) {
            currentPostId = postId;
            currentPostTitle = postTitle;
            showRestoreModal();
        }

        function permanentDeletePost(postId, postTitle, button) {
            currentPostId = postId;
            currentPostTitle = postTitle;
            currentButton = button;
            originalButtonText = button.innerHTML;
            showPermanentDeleteModal();
        }

        function performRestore() {
            // Show loading state
            const confirmBtn = document.getElementById('confirmRestore');
            const cancelBtn = document.getElementById('cancelRestore');

            confirmBtn.disabled = true;
            confirmBtn.innerHTML = '<svg class="w-4 h-4 inline animate-spin mr-2" fill="none" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" class="opacity-25"></circle><path fill="currentColor" class="opacity-75" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg> Memulihkan...';

            cancelBtn.disabled = true;

            // Send restore request
            fetch(window.location.href, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams({
                    'post_id': currentPostId,
                    'action': 'restore'
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Remove the post from DOM with animation
                    if (currentButton) {
                        const postElement = currentButton.closest('.bg-white');
                        postElement.style.transition = 'all 0.3s ease-out';
                        postElement.style.opacity = '0';
                        postElement.style.transform = 'translateX(100%)';

                        setTimeout(() => {
                            postElement.remove();
                            // Update trash count
                            updateTrashCount();
                        }, 300);
                    }

                showNotification('Postingan berhasil dikembalikan!', 'success');
                hideRestoreModal();
            } else {
                throw new Error(data.message || 'Gagal memulihkan postingan');
            }
            })
            .catch(error => {
                console.error('Error:', error);
                showNotification('Gagal memulihkan postingan: ' + error.message, 'error');
            })
            .finally(() => {
                // Restore button states
                confirmBtn.disabled = false;
                cancelBtn.disabled = false;
                confirmBtn.innerHTML = 'Pulihkan';

                // Restore original button if exists
                if (currentButton) {
                    currentButton.innerHTML = originalButtonText;
                    currentButton.disabled = false;
                }
            });
        }

        function performPermanentDelete() {
            if (!currentPostId) {
                console.error('No post ID specified for permanent deletion');
                showNotification('Gagal: ID postingan tidak valid', 'error');
                return;
            }

            // Show loading state
            const confirmBtn = document.getElementById('confirmPermanentDelete');
            const cancelBtn = document.getElementById('cancelPermanentDelete');

            confirmBtn.disabled = true;
            confirmBtn.innerHTML = '<svg class="w-4 h-4 inline animate-spin mr-2" fill="none" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" class="opacity-25"></circle><path fill="currentColor" class="opacity-75" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg> Menghapus...';

            cancelBtn.disabled = true;

            // Send permanent delete request
            fetch(window.location.href, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams({
                    'post_id': currentPostId,
                    'action': 'permanent_delete'
                })
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    // Remove the post from DOM with animation
                    if (currentButton) {
                        const postElement = currentButton.closest('.trash-card');
                        if (postElement) {
                            postElement.style.transition = 'all 0.3s ease-out';
                            postElement.style.opacity = '0';
                            postElement.style.transform = 'translateX(100%)';

                            setTimeout(() => {
                                postElement.remove();
                                // Update trash count
                                updateTrashCount();
                            }, 300);
                        }
                    }
                    showNotification('Postingan berhasil dihapus permanen!', 'success');
                    hidePermanentDeleteModal();
                } else {
                    throw new Error(data.message || 'Gagal menghapus postingan');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showNotification('Gagal menghapus postingan: ' + error.message, 'error');
            })
            .finally(() => {
                // Restore button states
                confirmBtn.disabled = false;
                cancelBtn.disabled = false;
                confirmBtn.innerHTML = 'Hapus Permanen';
            });
        }

    function updateTrashCount() {
        const trashCountElements = document.querySelectorAll('.trash-count');
        const currentCount = document.querySelectorAll('.trash-card').length;
        
        trashCountElements.forEach(element => {
            element.textContent = currentCount;
        });

        if (currentCount === 0) {
            window.location.reload();
        }
    }

    // Notification function
function showSuccessNotification() {
    showNotification('Postingan berhasil dikembalikan!', 'success');
    hideRestoreModal();
}

function showEmptyTrashModal() {
    const modal = document.getElementById('emptyTrashModal');
    const modalContent = document.getElementById('emptyTrashModalContent');

    if (!modal || !modalContent) return;

    // Show modal overlay and content with animation
    modal.classList.remove('hidden');
    setTimeout(() => {
        modal.classList.add('bg-black', 'bg-opacity-50');
        modalContent.classList.remove('scale-95', 'opacity-0');
        modalContent.classList.add('scale-100', 'opacity-100');
    }, 10);

    // Focus the cancel button for accessibility
    const cancelBtn = document.getElementById('cancelEmptyTrash');
    if (cancelBtn) cancelBtn.focus();

    // Close modal when clicking outside
    modal.onclick = function(e) {
        if (e.target === modal) {
            hideEmptyTrashModal();
        }
    };

    // Close on ESC
    document.addEventListener('keydown', handleEscKey);
}

function hideEmptyTrashModal() {
    const modal = document.getElementById('emptyTrashModal');
    const modalContent = document.getElementById('emptyTrashModalContent');

    if (!modal || !modalContent) return;

    // Hide modal content with animation
    modalContent.classList.remove('scale-100', 'opacity-100');
    modalContent.classList.add('scale-95', 'opacity-0');

    setTimeout(() => {
        modal.classList.add('hidden');
        modal.classList.remove('bg-black', 'bg-opacity-50');
        modal.onclick = null;
        document.removeEventListener('keydown', handleEscKey);
    }, 300);
}

function handleEscKey(event) {
    if (event.key === 'Escape') {
        const emptyModal = document.getElementById('emptyTrashModal');
        if (emptyModal && !emptyModal.classList.contains('hidden')) {
            hideEmptyTrashModal();
        }
    }
}

        function emptyTrash() {
            showEmptyTrashModal();
        }

        function confirmEmptyTrash() {
            const confirmBtn = document.getElementById('confirmEmptyTrash');
            const cancelBtn = document.getElementById('cancelEmptyTrash');
            
            // Show loading state
            confirmBtn.disabled = true;
            cancelBtn.disabled = true;
            confirmBtn.innerHTML = '<div class="inline-block w-5 h-5 border-2 border-white border-t-transparent rounded-full animate-spin"></div> Menghapus...';
            
            // Send AJAX request to empty trash
            const formData = new URLSearchParams();
            formData.append('action', 'empty_trash');
            
            fetch(window.location.href, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                },
                body: formData,
                credentials: 'same-origin'
            })
            .then(async response => {
                const contentType = response.headers.get('content-type');
                if (!contentType || !contentType.includes('application/json')) {
                    const text = await response.text();
                    console.error('Response is not JSON:', text);
                    throw new Error('Response is not in JSON format');
                }
                return response.json().catch(error => {
                    console.error('Error parsing JSON:', error);
                    throw new Error('Invalid JSON response from server');
                });
            })
            .then(data => {
                if (data.success) {
                    // Show success message
                    showNotification('Semua postingan berhasil dihapus permanen', 'success');
                    
                    // Hide modal
                    hideEmptyTrashModal();
                    
                    // Reload the page after a short delay
                    setTimeout(() => {
                        window.location.reload();
                    }, 1000);
                } else {
                    throw new Error(data.message || 'Gagal mengosongkan trash');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showNotification('Gagal mengosongkan trash: ' + (error.message || 'Terjadi kesalahan'), 'error');
                
                // Reset buttons
                if (confirmBtn) confirmBtn.disabled = false;
                if (cancelBtn) cancelBtn.disabled = false;
                if (confirmBtn) confirmBtn.innerHTML = 'Ya, Kosongkan';
            });
        }

        // Event listeners for modals
        document.addEventListener('DOMContentLoaded', function() {
            // Permanent delete modal events
            const confirmDeleteBtn = document.getElementById('confirmPermanentDelete');
            const cancelDeleteBtn = document.getElementById('cancelPermanentDelete');
            
            if (confirmDeleteBtn) {
                confirmDeleteBtn.addEventListener('click', performPermanentDelete);
            }
            
            if (cancelDeleteBtn) {
                cancelDeleteBtn.addEventListener('click', hidePermanentDeleteModal);
            }
            
            // Empty trash modal events
            document.getElementById('cancelEmptyTrash').addEventListener('click', function() {
                hideEmptyTrashModal();
            });
            
            // Restore modal events
            document.getElementById('cancelRestore').addEventListener('click', function() {
                hideRestoreModal();
                // Restore button state if needed
                if (currentButton) {
                    currentButton.innerHTML = originalButtonText;
                    currentButton.disabled = false;
                }
            });

            document.getElementById('confirmRestore').addEventListener('click', performRestore);

            // Permanent delete modal events
            document.getElementById('cancelPermanentDelete').addEventListener('click', function() {
                hidePermanentDeleteModal();
                // Restore button state if needed
                if (currentButton) {
                    currentButton.innerHTML = originalButtonText;
                    currentButton.disabled = false;
                }
            });

            document.getElementById('confirmPermanentDelete').addEventListener('click', performPermanentDelete);

            // Empty trash modal events
            document.getElementById('cancelEmptyTrash').addEventListener('click', hideEmptyTrashModal);

            // Close modals when clicking outside
            document.getElementById('restoreModal').addEventListener('click', function(e) {
                if (e.target === this) {
                    hideRestoreModal();
                }
            });

            document.getElementById('permanentDeleteModal').addEventListener('click', function(e) {
                if (e.target === this) {
                    hidePermanentDeleteModal();
                }
            });

            document.getElementById('emptyTrashModal').addEventListener('click', function(e) {
                if (e.target === this) {
                    hideEmptyTrashModal();
                }
            });

            // Keyboard navigation
            document.addEventListener('keydown', function(e) {
                if (e.key === 'Escape') {
                    const restoreModal = document.getElementById('restoreModal');
                    const deleteModal = document.getElementById('permanentDeleteModal');
                    const emptyModal = document.getElementById('emptyTrashModal');

                    if (!restoreModal.classList.contains('hidden')) {
                        hideRestoreModal();
                    } else if (!deleteModal.classList.contains('hidden')) {
                        hidePermanentDeleteModal();
                    } else if (!emptyModal.classList.contains('hidden')) {
                        hideEmptyTrashModal();
                    }
                }
            });
        });

        function showNotification(message, type = 'info') {
            // Remove existing notifications
            const existingNotifications = document.querySelectorAll('.notification');
            existingNotifications.forEach(notif => notif.remove());

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

            setTimeout(() => {
                notification.style.transform = 'translateX(0)';
            }, 100);

            setTimeout(() => {
                notification.style.transform = 'translateX(full)';
                setTimeout(() => {
                    notification.remove();
                }, 300);
            }, 4000);
        }
    </script>
</body>
</html>
