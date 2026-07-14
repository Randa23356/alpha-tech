<?php
ini_set("display_errors", 1);
ini_set("display_startup_errors", 1);
error_reporting(E_ALL);

// informatics_a/public/activities.php - Halaman Kegiatan
session_start();
require_once __DIR__ . "/../src/config/db.php";
require_once __DIR__ . "/../src/config/urls.php";

// Load theme colors from database
$primary_color = '#1e3a8a'; // default
$secondary_color = '#1e40af'; // default

try {
    $stmt = $pdo->query("SELECT setting_key, setting_value FROM site_settings WHERE setting_key IN ('primary_color', 'secondary_color')");
    $settings = [];
    foreach ($stmt->fetchAll() as $row) {
        $settings[$row['setting_key']] = $row['setting_value'];
    }
    $primary_color = $settings['primary_color'] ?? $primary_color;
    $secondary_color = $settings['secondary_color'] ?? $secondary_color;
} catch (Exception $e) {
    // Use default colors if database fails
}

// Ambil semua kegiatan approved
try {
    // Check if deleted_at column exists
    $deletedAtCheck = $pdo->query("SHOW COLUMNS FROM posts LIKE 'deleted_at'")->fetch();
    $deletedAtCondition = $deletedAtCheck ? 'AND posts.deleted_at IS NULL' : '';

    $stmt = $pdo->query("SELECT posts.*, users.username, users.profile_pic, COALESCE(posts.thumbnail_image, posts.image) as display_image FROM posts JOIN users ON posts.user_id = users.id WHERE posts.status = 'approved' {$deletedAtCondition} ORDER BY posts.date DESC");
    $kegiatan = $stmt->fetchAll();

    // Get all images for each activity
    foreach ($kegiatan as $index => $item) {
        $stmt = $pdo->prepare("SELECT image_path, image_order FROM post_images WHERE post_id = ? ORDER BY image_order");
        $stmt->execute([$item['id']]);
        $kegiatan[$index]['images'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

} catch (Exception $e) {
    $kegiatan = [];
}

// URL helper functions are included from src/config/urls.php
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kegiatan - <?= SITE_NAME ?></title>
    <link rel="stylesheet" href="<?= url('public/tailwind.css') ?>">
    <style>
        /* Dynamic theme variables for activities page */
        :root {
            --primary-color: <?= $primary_color ?>;
            --secondary-color: <?= $secondary_color ?>;
        }

        /* Custom gradient backgrounds using theme colors */
        .hero-gradient {
            background: linear-gradient(135deg, <?= $primary_color ?> 0%, <?= $secondary_color ?> 100%);
        }

        /* Animations */
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .animate-fade-in-up {
            animation: fadeInUp 0.6s ease-out forwards;
        }

        /* Card improvements - FIXED */
        .activity-card {
            background: white;
            border-radius: 1rem;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            transition: all 0.3s ease;
            opacity: 0;
            overflow: hidden;
            display: flex;
            flex-direction: column;
            height: 100%;
        }

        .activity-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
        }

        /* Image container improvements - FIXED */
        .image-container {
            position: relative;
            height: 200px; /* Fixed height instead of aspect ratio */
            background: #f8fafc;
            overflow: hidden;
            flex-shrink: 0; /* Prevent image container from shrinking */
        }
        
        /* Image count badge styling */
        .image-count-badge {
            position: absolute;
            top: 1rem;
            right: 1rem;
            z-index: 20; /* Higher than image container */
            background-color: rgba(0, 0, 0, 0.6);
            color: white;
            font-size: 0.75rem;
            line-height: 1;
            padding: 0.25rem 0.5rem;
            border-radius: 9999px;
            display: flex;
            align-items: center;
            gap: 0.25rem;
            backdrop-filter: blur(4px);
            pointer-events: none; /* Allow clicking through the badge */
        }

        .image-container img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.7s ease;
        }

        .activity-card:hover .image-container img {
            transform: scale(1.05);
        }

        /* Content container - FIXED */
        .content-container {
            padding: 1.5rem;
            flex: 1; /* Take remaining space */
            display: flex;
            flex-direction: column;
        }

        /* Carousel improvements */
        .carousel-slide {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            opacity: 0;
            transition: opacity 0.5s ease-in-out;
        }

        .carousel-slide.active {
            opacity: 1;
            z-index: 1;
        }

        /* Line clamp utilities */
        .line-clamp-2 {
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        .line-clamp-3 {
            display: -webkit-box;
            -webkit-line-clamp: 3;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        /* Gradient overlay */
        .gradient-overlay {
            background: linear-gradient(to top, rgba(0, 0, 0, 0.4) 0%, transparent 50%);
        }

        /* Fallback image styling */
        .fallback-image {
            background: linear-gradient(135deg, <?= $primary_color ?> 0%, <?= $secondary_color ?> 100%);
        }

        /* Ensure proper card link coverage */
        .card-link {
            display: flex;
            flex-direction: column;
            height: 100%;
            text-decoration: none;
            color: inherit;
        }
    </style>
</head>
<body class="bg-slate-50">
    <?php include __DIR__ . '/../includes/navbar.php'; ?>

    <!-- Modern Hero Section -->
    <header class="relative py-24 hero-gradient text-white overflow-hidden">
        <!-- Animated background elements -->
        <div class="absolute inset-0">
            <div class="absolute top-20 left-20 w-72 h-72 bg-white/10 rounded-full blur-3xl animate-pulse"></div>
            <div class="absolute bottom-20 right-20 w-96 h-96 bg-purple-400/20 rounded-full blur-3xl animate-pulse delay-1000"></div>
            <div class="absolute top-1/2 left-1/2 transform -translate-x-1/2 -translate-y-1/2 w-[800px] h-[800px] bg-gradient-to-r from-blue-400/20 to-purple-600/20 rounded-full blur-3xl"></div>
        </div>

        <div class="relative max-w-7xl mx-auto px-6 text-center">
            <div class="animate-fade-in-up">
                <!-- Icon -->
                <div class="mb-8 flex justify-center">
                    <div class="relative">
                        <div class="absolute inset-0 bg-white/20 rounded-full blur-xl"></div>
                        <div class="relative w-20 h-20 bg-white/10 backdrop-blur-sm rounded-2xl flex items-center justify-center">
                            <svg class="w-10 h-10 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
                            </svg>
                        </div>
                    </div>
                </div>

                <!-- Main heading -->
                <h1 class="text-5xl md:text-6xl font-bold mb-6 leading-tight">
                    Kegiatan Kelas
                </h1>

                <!-- Subtitle -->
                <p class="text-xl md:text-2xl text-gray-200 mb-8 max-w-3xl mx-auto leading-relaxed">
                    Dokumentasi lengkap semua kegiatan seru dan aktivitas akademik kelas Informatics <?= htmlspecialchars($site_name) ?>.
                </p>

                <!-- Stats -->
                <div class="inline-flex items-center gap-8 bg-white/10 backdrop-blur-sm rounded-2xl px-8 py-4">
                    <div class="text-center">
                        <div class="text-2xl font-bold text-white">
                            <?= count($kegiatan) ?>
                        </div>
                        <div class="text-sm text-gray-300">Kegiatan</div>
                    </div>
                    <div class="w-px h-8 bg-white/20"></div>
                    <div class="text-center">
                        <div class="text-2xl font-bold text-white">
                            <?= array_sum(array_map(function($item) { return count($item['images'] ?? []); }, $kegiatan)) ?>
                        </div>
                        <div class="text-sm text-gray-300">Foto</div>
                    </div>
                </div>
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <main class="max-w-7xl mx-auto px-6 py-24">
        <?php if (empty($kegiatan)): ?>
            <!-- Empty State -->
            <div class="bg-white p-16 rounded-3xl shadow-lg text-center animate-fade-in-up">
                <div class="w-24 h-24 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-6">
                    <svg class="w-12 h-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                </div>
                <h3 class="text-2xl font-bold text-gray-700 mb-3">Belum Ada Kegiatan</h3>
                <p class="text-gray-500 text-lg">Belum ada kegiatan yang dipublikasikan saat ini.</p>
            </div>
        <?php else: ?>
            <!-- Kegiatan Grid - FIXED LAYOUT -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                <?php foreach ($kegiatan as $index => $item): 
                    $cardId = 'activity-card-' . $index . '-' . uniqid();
                    $animationDelay = ($index % 3) * 100;
                ?>
                    <article 
                        id="<?= $cardId ?>"
                        class="activity-card group"
                        style="animation: fadeInUp 0.5s ease-out <?= $animationDelay ?>ms forwards;"
                    >
                        <a href="<?= url('activity_detail') ?>?id=<?= $item['id'] ?>" class="card-link">
                            <!-- Image Container - FIXED -->
                            <div class="image-container">
                                <?php if (!empty($item['images'])): ?>
                                    <?php if (count($item['images']) == 1): ?>
                                        <!-- Single image -->
                                        <div class="relative w-full h-full">
                                            <img 
                                                src="<?= upload_url(htmlspecialchars($item['images'][0]['image_path'])) ?>"
                                                alt="<?= htmlspecialchars($item['title']) ?>"
                                                class="w-full h-full object-cover"
                                                loading="lazy"
                                                onerror="this.onerror=null; this.src='data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHdpZHRoPSI0MDAiIGhlaWdodD0iMzAwIiB2aWV3Qm94PSIwIDAgNDAwIDMwMCI+PHJlY3Qgd2lkdGg9IjQwMCIgaGVpZ2h0PSIzMDAiIGZpbGw9IiNmMWY1ZjkiLz48Y2lyY2xlIGN4PSIyMDAiIGN5PSIxNTAiIHI9IjQwIiBmaWxsPSIjZGRlMWU2Ii8+PHBhdGggZD0iTTIwMCAzMDBjLTgyLjggMC0xNTAtNjcuMi0xNTAtMTUwUzExNy4yIDAgMjAwIDBzMTUwIDY3LjIgMTUwIDE1MC02Ny4yIDE1MC0xNTAgMTUweiIgZmlsbD0ibm9uZSIgc3Ryb2tlPSIjZGRlMWU2IiBzdHJva2Utd2lkdGg9IjIiLz48L3N2Zz4=';"
                                            >
                                            <?php if (count($item['images']) > 1): ?>
                                                <div class="image-count-badge">
                                                    <svg class="w-3 h-3 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                                    </svg>
                                                    <span>1 foto</span>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    <?php else: ?>
                                        <!-- Multiple images carousel -->
                                        <div class="carousel-container relative w-full h-full">
                                            <?php foreach ($item['images'] as $imgIndex => $image): ?>
                                                <div class="carousel-slide <?= $imgIndex === 0 ? 'active' : '' ?>">
                                                    <img 
                                                        src="<?= upload_url(htmlspecialchars($image['image_path'])) ?>"
                                                        alt="<?= htmlspecialchars($item['title'] . ' ' . ($imgIndex + 1)) ?>"
                                                        loading="lazy"
                                                        onerror="this.onerror=null; this.src='data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHdpZHRoPSI0MDAiIGhlaWdodD0iMzAwIiB2aWV3Qm94PSIwIDAgNDAwIDMwMCI+PHJlY3Qgd2lkdGg9IjQwMCIgaGVpZ2h0PSIzMDAiIGZpbGw9IiNmMWY1ZjkiLz48Y2lyY2xlIGN4PSIyMDAiIGN5PSIxNTAiIHI9IjQwIiBmaWxsPSIjZGRlMWU2Ii8+PHBhdGggZD0iTTIwMCAzMDBjLTgyLjggMC0xNTAtNjcuMi0xNTAtMTUwUzExNy4yIDAgMjAwIDBzMTUwIDY3LjIgMTUwIDE1MC06Ny4yIDE1MC0xNTAgMTUweiIgZmlsbD0ibm9uZSIgc3Ryb2tlPSIjZGRlMWU2IiBzdHJva2Utd2lkdGg9IjIiLz48L3N2Zz4=';"
                                                    >
                                                </div>
                                            <?php endforeach; ?>
                                            
                                            <div class="absolute inset-0 bg-gradient-to-t from-black/40 via-black/10 to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-300"></div>
                                            
                                            <div class="absolute top-3 right-3 bg-black/60 text-white text-xs px-2 py-1 rounded-full flex items-center">
                                                <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                                </svg>
                                                <span><?= count($item['images']) ?> foto</span>
                                            </div>
                                        </div>
                                        <!-- Image count badge for carousel -->
                                        <div class="image-count-badge">
                                            <svg class="w-3 h-3 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                            </svg>
                                            <span><?= count($item['images']) ?> foto</span>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <!-- Gradient overlay -->
                                    <div class="absolute inset-0 gradient-overlay opacity-0 group-hover:opacity-100 transition-opacity duration-300"></div>
                                    
                                <?php else: ?>
                                    <!-- Fallback image with pattern -->
                                    <div class="fallback-image w-full h-full flex items-center justify-center relative overflow-hidden">
                                        <div class="absolute inset-0 opacity-20">
                                            <div class="absolute top-0 left-0 w-32 h-32 bg-white rounded-full -translate-x-1/2 -translate-y-1/2"></div>
                                            <div class="absolute bottom-0 right-0 w-64 h-64 bg-white rounded-full translate-x-1/4 translate-y-1/4"></div>
                                        </div>
                                        <div class="relative text-center text-white">
                                            <svg class="w-12 h-12 mx-auto mb-2 opacity-80" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                            </svg>
                                            <p class="text-sm opacity-80">Tidak ada gambar</p>
                                        </div>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <!-- Content Container - FIXED -->
                            <div class="content-container">
                                <!-- Category & Date -->
                                <div class="flex items-center justify-between mb-4">
                                    <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium" 
                                          style="background-color: <?= $primary_color ?>10; color: <?= $primary_color ?>">
                                        <svg class="w-3 h-3 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
                                        </svg>
                                        Kegiatan
                                    </span>
                                    <span class="text-xs text-gray-500 flex items-center">
                                        <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                        </svg>
                                        <?= date('d M Y', strtotime($item['date'])) ?>
                                    </span>
                                </div>

                                <!-- Title & Excerpt -->
                                <div class="mb-4 flex-1">
                                    <h3 class="text-lg font-bold text-gray-900 mb-3 group-hover:text-blue-600 transition-colors duration-200 line-clamp-2 leading-tight">
                                        <?= htmlspecialchars($item['title']) ?>
                                    </h3>
                                    <p class="text-gray-600 text-sm leading-relaxed line-clamp-3">
                                        <?= htmlspecialchars(trim(substr($item['content'], 0, 120))) ?><?= strlen($item['content']) > 120 ? '...' : '' ?>
                                    </p>
                                </div>

                                <!-- Author & Read More -->
                                <div class="flex items-center justify-between pt-4 border-t border-gray-100">
                                    <div class="flex items-center space-x-2 sm:space-x-3">
                                        <?php if (isset($item['profile_pic']) && !empty($item['profile_pic'])): ?>
                                            <?php if (strpos($item['profile_pic'], 'http') === 0): ?>
                                                <img src="<?= htmlspecialchars($item['profile_pic']) ?>"
                                                     alt="Author" class="w-8 h-8 rounded-full object-cover border-2 border-white shadow-sm">
                                            <?php else: ?>
                                                <img src="<?= url('public/uploads/' . basename($item['profile_pic'])) ?>"
                                                     alt="Author" class="w-8 h-8 rounded-full object-cover border-2 border-white shadow-sm">
                                            <?php endif; ?>
                                        <?php else: ?>
                                            <div class="w-8 h-8 rounded-full flex items-center justify-center text-white font-medium text-sm shadow-inner"
                                                 style="background-color: <?= $primary_color ?>;">
                                                <?= strtoupper(substr($item["username"] ?? 'U', 0, 1)) ?>
                                            </div>
                                        <?php endif; ?>
                                        <div class="min-w-0">
                                            <p class="text-sm font-medium text-gray-900 truncate max-w-[120px]">
                                                <?= htmlspecialchars($item["username"] ?? 'User') ?>
                                            </p>
                                            <p class="text-xs text-gray-500 truncate max-w-[120px]">
                                                <?= isset($item['role']) ? htmlspecialchars($item['role']) : 'Anggota' ?>
                                            </p>
                                        </div>
                                    </div>

                                    <span class="inline-flex items-center text-sm font-medium text-blue-600 group-hover:text-blue-700 transition-colors">
                                        Lihat
                                        <svg class="w-4 h-4 ml-1 transform group-hover:translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                        </svg>
                                    </span>
                                </div>
                            </div>
                        </a>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
        
        <!-- Back Button -->
        <div class="flex justify-center mt-12">
            <a href="<?= url('') ?>" 
               class="inline-flex items-center gap-2 bg-white text-gray-700 px-6 py-3 rounded-xl font-medium hover:bg-gray-50 transition-all duration-300 shadow-md hover:shadow-lg transform hover:-translate-y-0.5 border border-gray-200">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                </svg>
                Kembali ke Beranda
            </a>
        </div>
    </main>

    <?php include __DIR__ . '/../includes/footer.php'; ?>

    <script>
        // Carousel functionality
        function changeSlide(slideIndex, cardId) {
            const card = document.getElementById(cardId);
            if (!card) return;
            
            const container = card.querySelector('.carousel-container');
            const slides = container.querySelectorAll('.carousel-slide');

            // Update slides
            slides.forEach((slide, index) => {
                if (index === slideIndex) {
                    slide.classList.add('active');
                    slide.style.opacity = '1';
                    slide.style.zIndex = '1';
                } else {
                    slide.classList.remove('active');
                    slide.style.opacity = '0';
                    slide.style.zIndex = '0';
                }
            });
        }

        // Auto-advance carousel
        function setupCarouselAutoAdvance() {
            document.querySelectorAll('.carousel-container').forEach(container => {
                const slides = container.querySelectorAll('.carousel-slide');
                
                if (slides.length > 1) {
                    let currentIndex = 0;
                    
                    setInterval(() => {
                        currentIndex = (currentIndex + 1) % slides.length;
                        const dot = dots[currentIndex];
                        if (dot) {
                            const card = container.closest('[id^="activity-card-"]');
                            if (card) {
                                changeSlide(dot, card.id);
                            }
                        }
                    }, 5000);
                }
            });
        }

        // Initialize when DOM is loaded
        document.addEventListener('DOMContentLoaded', function() {
            setupCarouselAutoAdvance();
        });

        // Fallback initialization
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', setupCarouselAutoAdvance);
        } else {
            setupCarouselAutoAdvance();
        }
    </script>
</body>
</html>