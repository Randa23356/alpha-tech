<?php
// informatics_a/public/index.php
session_start();
require_once __DIR__ . "/../src/config/db.php";
require_once __DIR__ . '/../src/helpers/helpers.php'; 

// Ambil REAL DATA dari database
try {
    // Check if deleted_at column exists
    $deletedAtCheck = $pdo->query("SHOW COLUMNS FROM posts LIKE 'deleted_at'")->fetch();
    $deletedAtCondition = $deletedAtCheck ? 'AND deleted_at IS NULL' : '';

    // Count kegiatan approved
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM posts WHERE status = 'approved' {$deletedAtCondition}");
    $total_kegiatan = $stmt->fetch()['total'] ?? 0;
    
    // Count total users
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM users WHERE role = 'user'");
    $total_users = $stmt->fetch()['total'] ?? 0;
    
    // Count total foto (posts dengan image)
    $stmt = $pdo->query("SELECT COUNT(DISTINCT posts.id) as total FROM posts WHERE status = 'approved' AND (thumbnail_image IS NOT NULL OR image IS NOT NULL) {$deletedAtCondition}");
    $total_photos = $stmt->fetch()['total'] ?? 0;

    // Ambil kegiatan approved
    $stmt = $pdo->query("SELECT DISTINCT posts.*, users.username, COALESCE(posts.thumbnail_image, posts.image) as display_image FROM posts JOIN users ON posts.user_id = users.id WHERE posts.status = 'approved' {$deletedAtCondition} ORDER BY posts.date DESC LIMIT 6");
    $kegiatan = $stmt->fetchAll();

    // Get all images for each activity
    foreach ($kegiatan as &$item) {
        $stmt = $pdo->prepare("SELECT image_path, image_order FROM post_images WHERE post_id = ? ORDER BY image_order");
        $stmt->execute([$item['id']]);
        $item['images'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // Ambil konten dari database
    $stmt = $pdo->query("SELECT setting_key, setting_value FROM site_settings");
    $settings = [];
    foreach ($stmt->fetchAll() as $row) {
        $settings[$row['setting_key']] = $row['setting_value'];
    }
    
    // Ambil about features (Kenapa Memilih Platform Ini?)
    $stmt = $pdo->query("SELECT * FROM about_features WHERE is_active = 1 ORDER BY display_order ASC, id ASC");
    $about_features_list = $stmt->fetchAll();
} catch (Exception $e) {
    $total_kegiatan = 0;
    $total_users = 0;
    $total_photos = 0;
    $kegiatan = [];
    $settings = [];
    $about_features_list = [];
}

$hero_title = $settings['hero_title'] ?? 'Selamat Datang di Informatics A';
$hero_subtitle = $settings['hero_subtitle'] ?? 'Platform kolaborasi dan dokumentasi kelas Informatika terbaik untuk berbagi kegiatan, pengumuman, dan galeri foto.';
$about_title = $settings['about_title'] ?? 'Tentang Informatics A';
$about_description = $settings['about_description'] ?? 'Platform digital yang dirancang khusus untuk memfasilitasi dokumentasi, berbagi informasi, dan kolaborasi antar anggota kelas Informatika A.';
$about_feature_1 = $settings['about_feature_1'] ?? 'Interface modern dan mudah digunakan';
$about_feature_2 = $settings['about_feature_2'] ?? 'Sistem approval untuk menjaga kualitas konten';
$about_feature_3 = $settings['about_feature_3'] ?? 'Galeri foto untuk dokumentasi visual';
$about_feature_4 = $settings['about_feature_4'] ?? 'Responsive design untuk semua perangkat';
$contact_email = $settings['contact_email'] ?? 'info@informaticsa.edu';
$contact_instagram = $settings['contact_instagram'] ?? '@informaticsa';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Informatics A - Kegiatan Kelas</title>
    <link href="<?= asset('tailwind.css') ?>" rel="stylesheet">
    <style>
        /* Carousel styles for public index */
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
<body class="bg-gray-50">
    
    <?php include __DIR__ . '/../includes/navbar.php'; ?>

    <!-- Hero Section -->
    <section id="home" class="bg-gradient-to-br from-blue-900 via-blue-800 to-indigo-900 text-white py-20 px-6">
        <div class="max-w-6xl mx-auto text-center">
            <h1 class="text-5xl md:text-6xl font-bold mb-6"><?= htmlspecialchars($hero_title) ?></h1>
            <p class="text-xl md:text-2xl mb-8 text-blue-100 max-w-3xl mx-auto"><?= htmlspecialchars($hero_subtitle) ?></p>
            <div class="flex flex-col sm:flex-row gap-4 justify-center">
                <a href="<?= url('register') ?>" class="bg-white text-blue-900 px-8 py-3 rounded-lg font-bold hover:bg-blue-50 transition shadow-lg inline-flex items-center justify-center gap-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/></svg>
                    Mulai Sekarang
                </a>
                <a href="#activities" class="bg-transparent border-2 border-white text-white px-8 py-3 rounded-lg font-bold hover:bg-white hover:text-blue-900 transition inline-flex items-center justify-center gap-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                    Lihat Kegiatan
                </a>
            </div>
        </div>
    </section>

    <!-- Stats Section - REAL DATA -->
    <section class="py-16 bg-white">
        <div class="max-w-6xl mx-auto px-6">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-8 text-center">
                <div class="p-8 bg-white rounded-2xl shadow-lg hover:shadow-xl transition">
                    <div class="text-6xl font-bold text-blue-900 mb-2"><?= $total_kegiatan ?></div>
                    <div class="text-gray-600 font-semibold text-lg">Kegiatan Terdokumentasi</div>
                </div>
                <div class="p-8 bg-white rounded-2xl shadow-lg hover:shadow-xl transition">
                    <div class="text-6xl font-bold text-blue-900 mb-2"><?= $total_users ?></div>
                    <div class="text-gray-600 font-semibold text-lg">Anggota Aktif</div>
                </div>
                <div class="p-8 bg-white rounded-2xl shadow-lg hover:shadow-xl transition">
                    <div class="text-6xl font-bold text-blue-900 mb-2"><?= $total_photos ?></div>
                    <div class="text-gray-600 font-semibold text-lg">Foto & Dokumentasi</div>
                </div>
            </div>
        </div>
    </section>

    <!-- Activities Section -->
    <section id="activities" class="py-16 bg-gray-50">
        <div class="max-w-6xl mx-auto px-6">
            <div class="text-center mb-12">
                <h2 class="text-4xl font-bold text-blue-900 mb-4">Kegiatan Terbaru</h2>
                <p class="text-gray-600 text-lg">Dokumentasi kegiatan seru kelas Informatics A</p>
            </div>
            <?php if (empty($kegiatan)): ?>
                <div class="bg-white p-12 rounded-xl shadow-md text-center">
                    <div class="text-6xl mb-4">📚</div>
                    <p class="text-gray-600 text-lg">Belum ada kegiatan yang dipublikasikan. Tunggu update dari admin!</p>
                </div>
            <?php else: ?>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                    <?php foreach ($kegiatan as $item): ?>
                        <div class="bg-white rounded-xl shadow-md hover:shadow-xl transition-all duration-300 overflow-hidden group">
                            <a href="<?= url('activity_detail.php?id=' . $item['id']) ?>" class="block">
                            <?php if (!empty($item['images'])): ?>
                                <?php if (count($item['images']) == 1): ?>
                                    <!-- Single image -->
                                    <div class="h-48 overflow-hidden">
                                        <img src="<?= upload_url(htmlspecialchars($item['images'][0]['image_path'])) ?>"
                                             alt="Foto Kegiatan"
                                             class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-300">
                                    </div>
                                <?php else: ?>
                                    <!-- Multiple images carousel -->
                                    <div class="h-48 overflow-hidden relative">
                                        <div class="carousel-container flex h-full">
                                            <?php foreach ($item['images'] as $index => $image): ?>
                                                <div class="carousel-slide w-full h-full flex-shrink-0 <?= $index === 0 ? 'active' : '' ?>">
                                                    <img src="<?= upload_url(htmlspecialchars($image['image_path'])) ?>"
                                                         alt="Foto Kegiatan"
                                                         class="w-full h-full object-cover">
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                        <?php if (count($item['images']) > 1): ?>
                                            <div class="absolute bottom-2 left-1/2 transform -translate-x-1/2 flex space-x-1">
                                                <?php foreach ($item['images'] as $index => $image): ?>
                                                    <button class="carousel-dot w-2 h-2 rounded-full bg-white opacity-50 hover:opacity-100 transition-opacity <?= $index === 0 ? 'opacity-100' : '' ?>"
                                                            data-slide="<?= $index ?>" onclick="changeSlide(this, <?= $item['id'] ?>)"></button>
                                                <?php endforeach; ?>
                                            </div>
                                            <button class="absolute left-2 top-1/2 transform -translate-y-1/2 bg-black bg-opacity-50 text-white p-1 rounded-full hover:bg-opacity-75 transition-opacity"
                                                    onclick="nextSlide(<?= $item['id'] ?>)">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                                                </svg>
                                            </button>
                                            <button class="absolute right-2 top-1/2 transform -translate-y-1/2 bg-black bg-opacity-50 text-white p-1 rounded-full hover:bg-opacity-75 transition-opacity"
                                                    onclick="nextSlide(<?= $item['id'] ?>)">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                                                </svg>
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                <?php endif; ?>
                            <?php else: ?>
                                <div class="h-48 bg-gradient-to-br from-blue-100 to-blue-200 flex items-center justify-center">
                                    <span class="text-6xl">🎓</span>
                                </div>
                            <?php endif; ?>
                            <div class="p-6">
                                <h3 class="text-xl font-bold text-gray-900 mb-2 line-clamp-2 group-hover:text-blue-900 transition"><?= htmlspecialchars($item["title"]) ?></h3>
                                <p class="text-gray-700 mb-4 line-clamp-3 text-sm leading-relaxed"><?= htmlspecialchars(substr($item["content"], 0, 150)) ?><?= strlen($item["content"]) > 150 ? '...' : '' ?></p>
                                <div class="flex justify-between items-center text-sm text-gray-500 border-t pt-4">
                                    <span class="flex items-center gap-1">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                                        <?= date("d M Y", strtotime($item["date"])) ?>
                                    </span>
                                    <span class="flex items-center gap-1">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
                                        <?= htmlspecialchars($item["username"]) ?>
                                    </span>
                                </div>
                            </div>
                            </a>
                        </div>
                    <?php endforeach; ?>
                </div>
                
                <!-- Tombol Lihat Semua -->
                <div class="mt-12 text-center">
                    <a href="<?= url('activities') ?>" class="inline-flex items-center gap-2 bg-blue-900 text-white px-8 py-4 rounded-xl font-bold hover:bg-blue-800 transition shadow-lg hover:shadow-xl transform hover:-translate-y-1">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                        </svg>
                        Lihat Semua Kegiatan
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"/>
                        </svg>
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </section>

    <!-- Features Section -->
    <section class="py-16 bg-white">
        <div class="max-w-6xl mx-auto px-6">
            <div class="text-center mb-12">
                <h2 class="text-4xl font-bold text-blue-900 mb-4">Fitur Platform</h2>
                <p class="text-gray-600 text-lg">Semua yang kamu butuhkan untuk kolaborasi kelas</p>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                <div class="text-center p-8 rounded-xl bg-gradient-to-br from-blue-50 to-blue-100 hover:shadow-lg transition">
                    <div class="text-5xl mb-4">📝</div>
                    <h3 class="text-xl font-bold text-blue-900 mb-3">Posting Kegiatan</h3>
                    <p class="text-gray-600">Bagikan kegiatan kelas dengan mudah dan cepat</p>
                </div>
                <div class="text-center p-8 rounded-xl bg-gradient-to-br from-blue-50 to-blue-100 hover:shadow-lg transition">
                    <div class="text-5xl mb-4">📸</div>
                    <h3 class="text-xl font-bold text-blue-900 mb-3">Galeri Foto</h3>
                    <p class="text-gray-600">Dokumentasi visual kegiatan dalam satu tempat</p>
                </div>
                <div class="text-center p-8 rounded-xl bg-gradient-to-br from-blue-50 to-blue-100 hover:shadow-lg transition">
                    <div class="text-5xl mb-4">💬</div>
                    <h3 class="text-xl font-bold text-blue-900 mb-3">Komentar</h3>
                    <p class="text-gray-600">Diskusi dan berikan feedback pada setiap kegiatan</p>
                </div>
            </div>
        </div>
    </section>

    <!-- About Section -->
    <section id="about" class="py-16 bg-gradient-to-br from-blue-900 to-blue-800 text-white">
        <div class="max-w-6xl mx-auto px-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-12 items-center">
                <div>
                    <h2 class="text-4xl font-bold mb-6"><?= htmlspecialchars($about_title) ?></h2>
                    <p class="text-blue-100 text-lg mb-6"><?= nl2br(htmlspecialchars($about_description)) ?></p>
                    <a href="<?= url('about') ?>" class="inline-block bg-white text-blue-900 px-8 py-3 rounded-lg font-bold hover:bg-blue-50 transition shadow-lg">Selengkapnya</a>
                </div>
                <?php if (!empty($about_features_list)): ?>
                <div class="bg-white/10 backdrop-blur-sm rounded-xl p-8">
                    <h3 class="text-2xl font-bold mb-6">Kenapa Memilih Platform Ini?</h3>
                    <ul class="space-y-4">
                        <?php foreach ($about_features_list as $feature): ?>
                        <li class="flex items-start gap-3">
                            <svg class="w-6 h-6 text-green-400 flex-shrink-0 mt-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                            <span class="text-blue-100"><?= htmlspecialchars($feature['feature_text']) ?></span>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <?php include __DIR__ . '/../includes/footer.php'; ?>

    <script>
        // Carousel functions for public index
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
