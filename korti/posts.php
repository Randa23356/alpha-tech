<?php
// korti/posts.php
session_start();
require_once __DIR__ . "/../src/helpers/session.php";
require_once __DIR__ . "/../src/config/db.php";
require_once __DIR__ . "/../src/config/urls.php";

// Load theme colors from database
$primary_color = "#1e3a8a"; // default
$secondary_color = "#1e40af"; // default
$accent_color = "#ec4899"; // default
$success_color = "#10b981"; // default
$warning_color = "#f59e0b"; // default
$danger_color = "#ef4444"; // default

try {
    $stmt = $pdo->query("SELECT setting_key, setting_value FROM site_settings WHERE setting_key IN ('primary_color', 'secondary_color', 'accent_color', 'success_color', 'warning_color', 'danger_color', 'site_name')");
    $settings = [];
    foreach ($stmt->fetchAll() as $row) {
        $settings[$row['setting_key']] = $row['setting_value'];
    }
    $primary_color = $settings['primary_color'] ?? $primary_color;
    $secondary_color = $settings['secondary_color'] ?? $secondary_color;
    $accent_color = $settings['accent_color'] ?? $accent_color;
    $success_color = $settings['success_color'] ?? $success_color;
    $warning_color = $settings['warning_color'] ?? $warning_color;
    $danger_color = $settings['danger_color'] ?? $danger_color;
    $site_name = $settings['site_name'] ?? "Informatics A";
} catch (Exception $e) {
    // Use default colors if database fails
    $site_name = "Informatics A";
}

// Handle aksi approve, reject, delete
if (
    $_SERVER["REQUEST_METHOD"] === "POST" &&
    isset($_POST["action"], $_POST["post_id"])
) {
    $post_id = intval($_POST["post_id"]);
    $action = $_POST["action"];

    if ($action === "approve") {
        // Get post details for notification
        $stmt_post = $pdo->prepare("SELECT title FROM posts WHERE id = ?");
        $stmt_post->execute([$post_id]);
        $post_data = $stmt_post->fetch();
        
        $stmt = $pdo->prepare(
            "UPDATE posts SET status = 'approved' WHERE id = ?",
        );
        $stmt->execute([$post_id]);
        
        // Send Firebase push notification
        if ($post_data) {
            require_once __DIR__ . '/../src/helpers/fcm_helper.php';
            notifyNewPost($post_id, $post_data['title']);
        }
    } elseif ($action === "reject") {
        $stmt = $pdo->prepare(
            "UPDATE posts SET status = 'rejected' WHERE id = ?",
        );
        $stmt->execute([$post_id]);
    } elseif ($action === "delete") {
        // Hapus semua file gambar terkait dari server
        $stmt = $pdo->prepare("
            SELECT DISTINCT image_path FROM (
                SELECT COALESCE(thumbnail_image, image) as image_path FROM posts WHERE id = ?
                UNION
                SELECT image_path FROM post_images WHERE post_id = ?
            ) as all_images
            WHERE image_path IS NOT NULL
        ");
        $stmt->execute([$post_id, $post_id]);
        $images = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($images as $image) {
            $image_path = __DIR__ . "/../public/uploads/" . $image['image_path'];
            if (file_exists($image_path)) {
                unlink($image_path);
            }
        }

        // Hapus dari tabel post_images
        $stmt = $pdo->prepare("DELETE FROM post_images WHERE post_id = ?");
        $stmt->execute([$post_id]);

        // Hapus comments terkait
        $stmt = $pdo->prepare("DELETE FROM comments WHERE post_id = ?");
        $stmt->execute([$post_id]);

        // Hapus likes terkait
        $stmt = $pdo->prepare("DELETE FROM likes WHERE post_id = ? AND type IN ('post', 'comment')");
        $stmt->execute([$post_id]);

        // Hapus dari database posts
        $stmt = $pdo->prepare("DELETE FROM posts WHERE id = ?");
        $stmt->execute([$post_id]);
    }
}

// Ambil semua postingan kegiatan
$stmt = $pdo->query(
    "SELECT posts.*, users.username FROM posts JOIN users ON posts.user_id = users.id ORDER BY posts.created_at DESC",
);
$posts = $stmt->fetchAll();

// Get all images for each post
foreach ($posts as $index => $post_item) {
    $stmt = $pdo->prepare("SELECT image_path, image_order FROM post_images WHERE post_id = ? ORDER BY image_order");
    $stmt->execute([$post_item['id']]);
    $posts[$index]['images'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Kelola Postingan Kegiatan - Korti <?= htmlspecialchars($site_name) ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="../public/tailwind.css" rel="stylesheet">
    <?php require_once __DIR__ . '/../includes/favicon.php'; ?>
    <style>
        /* Carousel styles for korti posts */
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
    </style>
</head>
<body class="min-h-screen" style="background: linear-gradient(135deg, <?= $primary_color ?>10 0%, <?= $secondary_color ?>10 100%);">
    <?php include __DIR__ . '/../includes/navbar.php'; ?>
    
    <?php include __DIR__ . '/../includes/korti_sidebar.php'; ?>

    <!-- Header -->
    <header class="lg:ml-64 text-white shadow-xl py-8 md:py-12 px-4 md:px-6 rounded-xl" style="background: linear-gradient(135deg, <?= $primary_color ?> 0%, <?= $secondary_color ?> 100%);">
        <div class="max-w-7xl mx-auto">
            <div class="flex items-center gap-3 md:gap-4">
                <div class="bg-white/10 backdrop-blur-sm p-2 md:p-3 rounded-xl">
                    <svg class="w-8 h-8 md:w-10 md:h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                </div>
                <div>
                    <h1 class="text-xl md:text-3xl font-bold mb-1">Kelola Postingan</h1>
                    <p class="text-white/90 text-sm md:text-base">Approve, edit, atau hapus postingan dari user</p>
                </div>
            </div>
        </div>
    </header>
    
    <!-- Main Content -->
    <main class="lg:ml-64 pt-16">
        <div class="p-4 md:p-8">

    <div class="max-w-7xl mx-auto px-4 md:px-6 py-8 md:py-10">
        <?php if (empty($posts)): ?>
            <div class="bg-white rounded-2xl shadow-lg p-8 md:p-12 text-center">
                <svg class="w-16 h-16 md:w-20 md:h-20 mx-auto mb-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
                <h3 class="text-lg md:text-xl font-bold text-gray-700 mb-2">Belum Ada Postingan</h3>
                <p class="text-gray-500">Belum ada postingan kegiatan dari user.</p>
            </div>
        <?php else: ?>
            <div class="space-y-4 md:space-y-6">
                <?php foreach ($posts as $post): ?>
                    <div class="bg-white rounded-2xl shadow-lg overflow-hidden hover:shadow-xl transition">
                        <div class="flex flex-col lg:flex-row">
                            <!-- Image Section -->
                            <div class="lg:w-2/5 relative">
                                <?php if (!empty($post['images'])): ?>
                                    <?php if (count($post['images']) == 1): ?>
                                        <!-- Single image -->
                                        <img src=" <?= BASE_URL ?>/public/uploads/<?= htmlspecialchars($post['images'][0]['image_path']) ?>"
                                             alt="Foto Kegiatan"
                                             class="w-full h-48 lg:h-full object-cover">
                                    <?php else: ?>
                                        <!-- Multiple images carousel -->
                                        <div class="relative h-48 lg:h-full overflow-hidden">
                                            <div class="carousel-container flex h-full">
                                                <?php foreach ($post['images'] as $index => $image): ?>
                                                    <div class="carousel-slide w-full h-full flex-shrink-0 <?= $index === 0 ? 'active' : '' ?>">
                                                        <img src=" <?= BASE_URL ?>/public/uploads/<?= htmlspecialchars($image['image_path']) ?>"
                                                             alt="Foto Kegiatan"
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
                                <?php else: ?>
                                    <div class="w-full h-48 lg:h-full bg-gradient-to-br from-blue-100 to-blue-200 flex items-center justify-center">
                                        <svg class="w-16 h-16 md:w-20 md:h-20 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                        </svg>
                                    </div>
                                <?php endif; ?>
                            </div>
                            
                            <!-- Content Section -->
                            <div class="lg:w-3/5 p-4 md:p-6">
                                <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-4 mb-4">
                                    <div class="flex-1">
                                        <h3 class="text-lg md:text-2xl font-bold text-gray-900 mb-2 leading-tight line-clamp-2">
                                            <?= htmlspecialchars($post["title"]) ?>
                                        </h3>
                                        <div class="flex flex-wrap items-center gap-2 md:gap-3 text-xs md:text-sm text-gray-600 mb-3">
                                            <div class="flex items-center gap-1">
                                                <svg class="w-3 h-3 md:w-4 md:h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                                                </svg>
                                                <span class="font-medium truncate max-w-24 md:max-w-none">
                                                    <?= htmlspecialchars($post["username"]) ?>
                                                </span>
                                            </div>
                                            <div class="flex items-center gap-1">
                                                <svg class="w-3 h-3 md:w-4 md:h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                                </svg>
                                                <span class="truncate max-w-20 md:max-w-none">
                                                    <?= htmlspecialchars($post["date"]) ?>
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="flex-shrink-0">
                                        <?php if ($post["status"] === "pending"): ?>
                                            <span class="inline-flex items-center gap-1 px-2 md:px-3 py-1 rounded-full text-xs md:text-sm font-semibold" style="background-color: <?= $warning_color ?>20; color: <?= $warning_color ?>;">
                                                <svg class="w-3 h-3 md:w-4 md:h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                                </svg>
                                                Pending
                                            </span>
                                        <?php elseif ($post["status"] === "approved"): ?>
                                            <span class="inline-flex items-center gap-1 px-2 md:px-3 py-1 rounded-full text-xs md:text-sm font-semibold" style="background-color: <?= $success_color ?>20; color: <?= $success_color ?>;">
                                                <svg class="w-3 h-3 md:w-4 md:h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                                </svg>
                                                Approved
                                            </span>
                                        <?php else: ?>
                                            <span class="inline-flex items-center gap-1 px-2 md:px-3 py-1 rounded-full text-xs md:text-sm font-semibold" style="background-color: <?= $danger_color ?>20; color: <?= $danger_color ?>;">
                                                <svg class="w-3 h-3 md:w-4 md:h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                                </svg>
                                                Rejected
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                
                                <div class="mb-4">
                                    <p class="text-gray-700 leading-relaxed text-sm md:text-base line-clamp-3">
                                        <?= nl2br(htmlspecialchars($post["description"])) ?>
                                    </p>
                                </div>
                                
                                <!-- Action Buttons -->
                                <form action="" method="POST" class="flex flex-col sm:flex-row gap-2">
                                    <input type="hidden" name="post_id" value="<?= $post["id"] ?>">
                                    <?php if ($post["status"] === "pending"): ?>
                                        <button type="submit" name="action" value="approve" class="inline-flex items-center justify-center gap-2 px-3 md:px-4 py-2 rounded-lg font-medium transition text-sm" style="background-color: <?= $success_color ?>; color: white;" onmouseover="this.style.backgroundColor='<?= $success_color ?>dd'" onmouseout="this.style.backgroundColor='<?= $success_color ?>'">
                                            <svg class="w-3 h-3 md:w-4 md:h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                            </svg>
                                            Approve
                                        </button>
                                        <button type="submit" name="action" value="reject" class="inline-flex items-center justify-center gap-2 px-3 md:px-4 py-2 rounded-lg font-medium transition text-sm" style="background-color: <?= $warning_color ?>; color: white;" onmouseover="this.style.backgroundColor='<?= $warning_color ?>dd'" onmouseout="this.style.backgroundColor='<?= $warning_color ?>'">
                                            <svg class="w-3 h-3 md:w-4 md:h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                            </svg>
                                            Reject
                                        </button>
                                    <?php endif; ?>
                                    <button type="submit" name="action" value="delete" class="inline-flex items-center justify-center gap-2 px-3 md:px-4 py-2 rounded-lg font-medium transition text-sm" style="background-color: <?= $danger_color ?>; color: white;" onmouseover="this.style.backgroundColor='<?= $danger_color ?>dd'" onmouseout="this.style.backgroundColor='<?= $danger_color ?>'" onclick="showDeleteModal(<?= $post['id'] ?>, '<?= htmlspecialchars(addslashes($post['title'])) ?>'); return false;">
                                        <svg class="w-3 h-3 md:w-4 md:h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                        </svg>
                                        Delete
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
        
        <div class="mt-6 md:mt-8 text-center">
            <a href="dashboard.php" class="inline-flex items-center gap-2 font-semibold transition text-sm md:text-base" style="color: <?= $primary_color ?>;" onmouseover="this.style.color='<?= $secondary_color ?>'" onmouseout="this.style.color='<?= $primary_color ?>'">
                <svg class="w-4 h-4 md:w-5 md:h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                </svg>
                Kembali ke Dashboard
            </a>
        </div>
    </div>
        </div>
    </main>

    <!-- Delete Confirmation Modal -->
    <div id="deleteModal" class="fixed inset-0 bg-black bg-opacity-50 hidden z-50">
        <div class="flex items-center justify-center min-h-screen p-4">
            <div class="bg-white rounded-xl shadow-xl max-w-md w-full">
                <!-- Modal Header -->
                <div class="p-6 border-b">
                    <div class="flex items-center gap-3">
                        <div class="bg-red-100 p-2 rounded-full">
                            <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-lg font-bold text-gray-900">Hapus Postingan</h3>
                            <p class="text-sm text-gray-600">Konfirmasi penghapusan postingan</p>
                        </div>
                    </div>
                </div>

                <!-- Modal Body -->
                <div class="p-6">
                    <p class="text-gray-700 mb-4">
                        Apakah Anda yakin ingin menghapus postingan:
                        <strong id="deletePostTitle" class="text-red-600"></strong>?
                    </p>
                    <p class="text-sm text-gray-500 mb-6">
                        Tindakan ini akan menghapus postingan beserta semua gambar, komentar, dan likes yang terkait. Tidak dapat dibatalkan.
                    </p>

                    <!-- Modal Footer -->
                    <div class="flex gap-3">
                        <button onclick="hideDeleteModal()" class="flex-1 px-4 py-2 border border-gray-300 text-gray-700 rounded-lg font-medium hover:bg-gray-50 transition">
                            Batal
                        </button>
                        <button id="confirmDeleteBtn" onclick="submitDeleteForm()" class="flex-1 px-4 py-2 bg-red-600 text-white rounded-lg font-medium hover:bg-red-700 transition">
                            Hapus
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>

    // Carousel functions for korti posts
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

    // Delete Modal Functions
    let deleteModal = null;
    let deleteId = null;

    function showDeleteModal(id, title) {
        deleteModal = document.getElementById('deleteModal');
        deleteId = id;

        document.getElementById('deletePostTitle').textContent = title;

        deleteModal.classList.remove('hidden');
    }

    function hideDeleteModal() {
        if (deleteModal) {
            deleteModal.classList.add('hidden');
            deleteModal = null;
            deleteId = null;
        }
    }

    function submitDeleteForm() {
        if (deleteId) {
            // Create a form and submit it
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = '';

            const postIdInput = document.createElement('input');
            postIdInput.type = 'hidden';
            postIdInput.name = 'post_id';
            postIdInput.value = deleteId;

            const actionInput = document.createElement('input');
            actionInput.type = 'hidden';
            actionInput.name = 'action';
            actionInput.value = 'delete';

            form.appendChild(postIdInput);
            form.appendChild(actionInput);
            document.body.appendChild(form);
            form.submit();
        }
    }

    // Close modal when clicking outside
    document.getElementById('deleteModal').addEventListener('click', function(e) {
        if (e.target === this) {
            hideDeleteModal();
        }
    });
</script>
</body>
</html>