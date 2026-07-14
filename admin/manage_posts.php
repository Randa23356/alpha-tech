<?php
// informatics_a/admin/manage_posts.php
require_once __DIR__ . "/../src/helpers/session.php";
require_once __DIR__ . "/../src/helpers/helpers.php";
require_once __DIR__ . "/../src/config/db.php";
require_once __DIR__ . "/../src/config/urls.php";

// Proteksi: admin dan korti yang bisa akses
if (!isLoggedIn() || (!isAdmin() && !isKorti())) {
    header("Location: " . url('login'));
    exit();
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
        // First, get all associated data for cleanup
        $stmt = $pdo->prepare("SELECT image FROM posts WHERE id = ?");
        $stmt->execute([$post_id]);
        $post = $stmt->fetch();

        // Delete associated images from post_images table and files
        $stmt = $pdo->prepare("SELECT image_path FROM post_images WHERE post_id = ?");
        $stmt->execute([$post_id]);
        $images = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $upload_dir = __DIR__ . "/../public/uploads/";
        foreach ($images as $image) {
            $image_path = $upload_dir . $image['image_path'];
            if (file_exists($image_path)) {
                unlink($image_path);
            }
        }

        // Delete from post_images table
        $stmt = $pdo->prepare("DELETE FROM post_images WHERE post_id = ?");
        $stmt->execute([$post_id]);

        // Delete comments
        $stmt = $pdo->prepare("DELETE FROM comments WHERE post_id = ?");
        $stmt->execute([$post_id]);

        // Delete likes
        $stmt = $pdo->prepare("DELETE FROM likes WHERE post_id = ? AND type IN ('post', 'comment')");
        $stmt->execute([$post_id]);

        // Delete the post
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
    <title>Kelola Postingan Kegiatan - Admin Informatics A</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="<?= asset('tailwind.css') ?>" rel="stylesheet">
    <style>
        /* Carousel styles for admin manage posts */
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
<body class="bg-gradient-to-br from-gray-50 to-blue-50 min-h-screen">
    <?php include __DIR__ . "/../includes/navbar.php"; ?>
    <?php include __DIR__ . "/sidebar.php"; ?>

    <!-- Header -->
    <header class="lg:ml-64 bg-gradient-to-r from-blue-900 via-blue-800 to-indigo-900 text-white py-10 px-6">
        <div class="max-w-7xl">
            <div class="flex items-center gap-4">
                <div class="bg-white/10 backdrop-blur-sm p-3 rounded-xl">
                    <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                </div>
                <div>
                    <h1 class="text-3xl font-bold mb-1">Kelola Postingan Kegiatan</h1>
                    <p class="text-blue-100">Approve, edit, atau hapus postingan dari user</p>
                </div>
            </div>
        </div>
    </header>

    <main class="lg:ml-64 max-w-7xl mx-auto px-6 py-10">
        <?php if (empty($posts)): ?>
            <div class="bg-white rounded-2xl shadow-lg p-12 text-center">
                <svg class="w-20 h-20 mx-auto mb-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
                <h3 class="text-xl font-bold text-gray-700 mb-2">Belum Ada Postingan</h3>
                <p class="text-gray-500">Belum ada postingan kegiatan dari user.</p>
            </div>
        <?php else: ?>
            <div class="space-y-6">
                <?php foreach ($posts as $post): ?>
                    <div class="bg-white rounded-2xl shadow-lg overflow-hidden hover:shadow-xl transition">
                        <div class="md:flex">
                            <!-- Image Section -->
                            <div class="md:w-1/3 relative">
                                <?php if (!empty($post['images'])): ?>
                                    <?php if (count($post['images']) == 1): ?>
                                        <!-- Single image -->
                                        <img src=" <?= BASE_URL ?>/public/uploads/<?= htmlspecialchars($post['images'][0]['image_path']) ?>"
                                             alt="Foto Kegiatan"
                                             class="w-full h-64 md:h-full object-cover">
                                    <?php else: ?>
                                        <!-- Multiple images carousel -->
                                        <div class="relative h-64 md:h-full overflow-hidden">
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
                                <?php else: ?>
                                    <div class="w-full h-64 md:h-full bg-gradient-to-br from-blue-100 to-blue-200 flex items-center justify-center">
                                        <svg class="w-20 h-20 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                        </svg>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <!-- Content Section -->
                            <div class="md:w-2/3 p-6">
                                <div class="flex items-start justify-between mb-4">
                                    <div class="flex-1">
                                        <h3 class="text-2xl font-bold text-gray-900 mb-2"><?= htmlspecialchars(
                                            $post["title"],
                                        ) ?></h3>
                                        <div class="flex flex-wrap items-center gap-3 text-sm text-gray-600 mb-3">
                                            <div class="flex items-center gap-1">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                                                </svg>
                                                <span class="font-medium"><?= htmlspecialchars(
                                                    $post["username"],
                                                ) ?></span>
                                            </div>
                                            <div class="flex items-center gap-1">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                                </svg>
                                                <span><?= htmlspecialchars(
                                                    $post["date"],
                                                ) ?></span>
                                            </div>
                                        </div>
                                    </div>
                                    <div>
                                        <?php if (
                                            $post["status"] === "pending"
                                        ): ?>
                                            <span class="inline-flex items-center gap-1 bg-yellow-100 text-yellow-800 px-3 py-1 rounded-full text-sm font-semibold">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                                </svg>
                                                Pending
                                            </span>
                                        <?php elseif (
                                            $post["status"] === "approved"
                                        ): ?>
                                            <span class="inline-flex items-center gap-1 bg-green-100 text-green-800 px-3 py-1 rounded-full text-sm font-semibold">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                                </svg>
                                                Approved
                                            </span>
                                        <?php else: ?>
                                            <span class="inline-flex items-center gap-1 bg-red-100 text-red-800 px-3 py-1 rounded-full text-sm font-semibold">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                                </svg>
                                                Rejected
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                </div>

                                <div class="mb-4">
                                    <p class="text-gray-700 leading-relaxed"><?= nl2br(
                                        htmlspecialchars($post["description"]),
                                    ) ?></p>
                                </div>

                                <!-- Action Buttons -->
                                <form action="" method="POST" class="flex flex-wrap gap-2">
                                    <input type="hidden" name="post_id" value="<?= $post[
                                        "id"
                                    ] ?>">
                                    <?php if ($post["status"] === "pending"): ?>
                                        <button type="submit" name="action" value="approve" class="inline-flex items-center gap-2 bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700 transition font-medium">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                            </svg>
                                            Approve
                                        </button>
                                        <button type="submit" name="action" value="reject" class="inline-flex items-center gap-2 bg-yellow-600 text-white px-4 py-2 rounded-lg hover:bg-yellow-700 transition font-medium">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                            </svg>
                                            Reject
                                        </button>
                                    <?php endif; ?>
                                    <button type="submit" name="action" value="delete" class="inline-flex items-center gap-2 bg-red-600 text-white px-4 py-2 rounded-lg hover:bg-red-700 transition font-medium" onclick="return confirm('Hapus postingan ini?')">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
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
        // Carousel functions for admin manage posts
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
    </script>
</body>
</html>
