<?php
// informatics_a/public/gallery.php
session_start();
require_once __DIR__ . "/../src/config/db.php";
require_once __DIR__ . "/../src/config/urls.php";

// Load theme colors from database
$primary_color = '#1e3a8a'; // default
$secondary_color = '#1e40af'; // default
$accent_color = '#ec4899'; // default
$success_color = '#10b981'; // default
$warning_color = '#f59e0b'; // default
$danger_color = '#ef4444'; // default

try {
    $stmt = $pdo->query("SELECT setting_key, setting_value FROM site_settings WHERE setting_key IN ('primary_color', 'secondary_color', 'accent_color', 'success_color', 'warning_color', 'danger_color')");
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
} catch (Exception $e) {
    // Use default colors if database fails
}

// Ambil semua foto dari database
try {
    // Check if deleted_at column exists
    $deletedAtCheck = $pdo->query("SHOW COLUMNS FROM posts LIKE 'deleted_at'")->fetch();
    $deletedAtCondition = $deletedAtCheck ? 'AND posts.deleted_at IS NULL' : '';

    // Ambil semua foto dengan informasi post
    $stmt = $pdo->query("
        SELECT pi.image_path, pi.image_order, p.title, p.content, p.date, u.username, u.id as user_id
        FROM post_images pi
        JOIN posts p ON pi.post_id = p.id
        JOIN users u ON p.user_id = u.id
        WHERE p.status = 'approved' {$deletedAtCondition}
        ORDER BY p.date DESC, pi.image_order ASC
    ");

    $all_photos = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Group photos by post for better organization
    $photos_by_post = [];
    foreach ($all_photos as $photo) {
        $post_id = $photo['post_id'] ?? 'unknown';
        if (!isset($photos_by_post[$post_id])) {
            $photos_by_post[$post_id] = [
                'title' => $photo['title'],
                'content' => $photo['content'],
                'date' => $photo['date'],
                'username' => $photo['username'],
                'user_id' => $photo['user_id'],
                'photos' => []
            ];
        }
        $photos_by_post[$post_id]['photos'][] = [
            'image_path' => $photo['image_path'],
            'image_order' => $photo['image_order']
        ];
    }

    // Count total photos
    $total_photos = count($all_photos);

} catch (Exception $e) {
    $all_photos = [];
    $photos_by_post = [];
    $total_photos = 0;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Galeri - <?= htmlspecialchars($site_name) ?></title>
    <link href="<?= asset('tailwind.css') ?>" rel="stylesheet">
    <link href="<?= asset('css/dynamic-theme.php') ?>" rel="stylesheet">
    <style>
        /* Dynamic theme variables for gallery page */
        :root {
            --primary-color: <?= $primary_color ?>;
            --secondary-color: <?= $secondary_color ?>;
            --accent-color: <?= $accent_color ?>;
            --success-color: <?= $success_color ?>;
            --warning-color: <?= $warning_color ?>;
            --danger-color: <?= $danger_color ?>;
        }

        /* Custom gradient backgrounds using theme colors */
        .hero-gradient {
            background: linear-gradient(135deg, <?= $primary_color ?> 0%, <?= $accent_color ?> 50%, <?= $secondary_color ?> 100%);
        }

        .filter-gradient {
            background: linear-gradient(135deg, <?= $primary_color ?> 0%, <?= $secondary_color ?> 100%);
        }

        .btn-gradient {
            background: linear-gradient(135deg, <?= $primary_color ?> 0%, <?= $secondary_color ?> 100%);
        }

        .btn-gradient:hover {
            background: linear-gradient(135deg, <?= $secondary_color ?> 0%, <?= $primary_color ?> 100%);
        }

        .avatar-gradient {
            background: linear-gradient(135deg, <?= $primary_color ?> 0%, <?= $secondary_color ?> 100%);
        }

        /* Theme hover effects */
        .theme-hover:hover {
            color: <?= $primary_color ?>;
        }
    </style>
    <style>
        /* Modern animations */
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

        @keyframes scaleIn {
            from {
                opacity: 0;
                transform: scale(0.8);
            }
            to {
                opacity: 1;
                transform: scale(1);
            }
        }

        .animate-fade-in-up {
            animation: fadeInUp 0.8s ease-out;
        }

        .animate-scale-in {
            animation: scaleIn 0.6s ease-out;
        }

        /* Premium gallery styles */
        .gallery-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 2rem;
        }

        .gallery-item {
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            cursor: pointer;
            position: relative;
            overflow: hidden;
        }

        .gallery-item:hover {
            transform: translateY(-12px) scale(1.02);
            box-shadow: 0 25px 50px rgba(0, 0, 0, 0.25);
        }

        .gallery-image {
            transition: transform 0.6s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .gallery-item:hover .gallery-image {
            transform: scale(1.1);
        }

        .gallery-overlay {
            background: linear-gradient(
                to bottom,
                rgba(0, 0, 0, 0) 0%,
                rgba(0, 0, 0, 0.3) 50%,
                rgba(0, 0, 0, 0.8) 100%
            );
        }

        /* Premium filter buttons */
        .filter-btn {
            position: relative;
            overflow: hidden;
            transition: all 0.3s ease;
        }

        .filter-btn::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
            transition: left 0.5s ease;
        }

        .filter-btn:hover::before {
            left: 100%;
        }

        .filter-btn.active {
            background: linear-gradient(135deg, <?= $primary_color ?> 0%, <?= $accent_color ?> 100%);
            box-shadow: 0 8px 25px rgba(<?= hexdec(substr($primary_color, 1, 2)) ?>, <?= hexdec(substr($primary_color, 3, 2)) ?>, <?= hexdec(substr($primary_color, 5, 2)) ?>, 0.4);
        }

        /* Enhanced modal */
        .modal-backdrop {
            backdrop-filter: blur(12px);
            background: rgba(0, 0, 0, 0.9);
        }

        /* Masonry layout support */
        .masonry {
            column-count: 1;
            column-gap: 1rem;
        }

        @media (min-width: 640px) {
            .masonry {
                column-count: 2;
            }
        }

        @media (min-width: 768px) {
            .masonry {
                column-count: 3;
            }
        }

        @media (min-width: 1024px) {
            .masonry {
                column-count: 4;
            }
        }
    </style>
</head>
<body class="bg-slate-50">
    <?php include __DIR__ . '/../includes/navbar.php'; ?>

    <!-- Modern Hero Section -->
    <section class="relative py-24 hero-gradient text-white overflow-hidden">
        <!-- Animated background elements -->
        <div class="absolute inset-0">
            <div class="absolute top-20 left-20 w-72 h-72 bg-white/10 rounded-full blur-3xl animate-pulse"></div>
            <div class="absolute bottom-20 right-20 w-96 h-96 rounded-full blur-3xl animate-pulse delay-1000" style="background-color: <?= $accent_color ?>20;"></div>
            <div class="absolute top-1/2 left-1/2 transform -translate-x-1/2 -translate-y-1/2 w-[800px] h-[800px] rounded-full blur-3xl" style="background: linear-gradient(135deg, <?= $primary_color ?>20 0%, <?= $accent_color ?>20 100%);"></div>
        </div>

        <div class="relative max-w-7xl mx-auto px-6 text-center">
            <div class="animate-fade-in-up">
                <!-- Icon -->
                <div class="mb-8 flex justify-center">
                    <div class="relative">
                        <div class="absolute inset-0 bg-white/20 rounded-full blur-xl"></div>
                        <div class="relative w-20 h-20 bg-white/10 backdrop-blur-sm rounded-2xl flex items-center justify-center">
                            <svg class="w-10 h-10 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                            </svg>
                        </div>
                    </div>
                </div>

                <!-- Main heading -->
                <h1 class="text-5xl md:text-6xl font-bold mb-6 leading-tight">
                    Galeri Foto
                </h1>

                <!-- Subtitle -->
                <p class="text-xl md:text-2xl text-gray-200 mb-8 max-w-3xl mx-auto leading-relaxed">
                    Koleksi dokumentasi visual dari semua kegiatan seru kelas Informatics <?= htmlspecialchars($site_name) ?>.
                </p>

                <!-- Stats -->
                <div class="inline-flex items-center gap-8 bg-white/10 backdrop-blur-sm rounded-2xl px-8 py-4">
                    <div class="text-center">
                        <div class="text-2xl font-bold text-white">
                            <?= number_format($total_photos) ?>
                        </div>
                        <div class="text-sm text-gray-300">Foto</div>
                    </div>
                    <div class="w-px h-8 bg-white/20"></div>
                    <div class="text-center">
                        <div class="text-2xl font-bold text-white">
                            <?= count($photos_by_post) ?>
                        </div>
                        <div class="text-sm text-gray-300">Kegiatan</div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Gallery Section -->
    <section class="py-24 bg-white">
        <div class="max-w-7xl mx-auto px-6">

            <!-- Filter Buttons -->
            <div class="flex flex-wrap justify-center gap-4 mb-16">
                <button class="filter-btn active px-8 py-3 rounded-2xl font-bold text-white filter-gradient shadow-lg hover:shadow-xl transition-all duration-300">
                    Semua Foto
                </button>
                <button class="filter-btn px-8 py-3 rounded-2xl font-bold text-gray-700 bg-white border-2 border-gray-200 hover:border-indigo-300 hover:bg-indigo-50 transition-all duration-300 theme-hover">
                    Kegiatan Terbaru
                </button>
                <button class="filter-btn px-8 py-3 rounded-2xl font-bold text-gray-700 bg-white border-2 border-gray-200 hover:border-indigo-300 hover:bg-indigo-50 transition-all duration-300 theme-hover">
                    Foto Populer
                </button>
            </div>

            <?php if (empty($all_photos)): ?>
                <!-- Empty State -->
                <div class="text-center py-24 animate-fade-in-up">
                    <div class="w-24 h-24 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-6">
                        <svg class="w-12 h-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                        </svg>
                    </div>
                    <h3 class="text-2xl font-bold text-gray-700 mb-3">Galeri Masih Kosong</h3>
                    <p class="text-gray-500 text-lg mb-8 max-w-2xl mx-auto">Belum ada foto yang diunggah. Tunggu kegiatan pertama untuk melihat dokumentasi visual!</p>
                    <a href="<?= url('activities') ?>" class="inline-flex items-center gap-3 btn-gradient text-white px-8 py-4 rounded-2xl font-bold hover:btn-gradient transition-all duration-300 shadow-lg hover:shadow-xl transform hover:-translate-y-1">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
                        </svg>
                        Lihat Kegiatan
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                        </svg>
                    </a>
                </div>

            <?php else: ?>
                <!-- Gallery Grid -->
                <div class="gallery-grid">
                    <?php foreach ($all_photos as $index => $photo): ?>
                        <div class="gallery-item bg-white rounded-3xl shadow-xl hover:shadow-2xl overflow-hidden group animate-fade-in-up"
                             style="animation-delay: <?= ($index % 12) * 0.1 ?>s;"
                             onclick="openPhotoModal('<?= htmlspecialchars($photo['image_path']) ?>', '<?= htmlspecialchars($photo['title']) ?>', '<?= htmlspecialchars($photo['username']) ?>', '<?= htmlspecialchars($photo['date']) ?>')">

                            <div class="relative overflow-hidden aspect-[4/3]">
                                <img src="<?= upload_url(htmlspecialchars($photo['image_path'])) ?>"
                                     alt="<?= htmlspecialchars($photo['title']) ?>"
                                     class="gallery-image w-full h-full object-cover">

                                <!-- Enhanced overlay on hover -->
                                <div class="gallery-overlay absolute inset-0 opacity-0 group-hover:opacity-100 transition-opacity duration-500 flex items-end p-6">
                                    <div class="text-white transform translate-y-4 group-hover:translate-y-0 transition-transform duration-500">
                                        <p class="font-bold text-lg mb-2 line-clamp-2">
                                            <?= htmlspecialchars($photo['title']) ?>
                                        </p>
                                        <p class="text-sm text-gray-200 mb-3">Oleh <?= htmlspecialchars($photo['username']) ?></p>
                                        <div class="flex items-center gap-2 text-sm text-gray-300">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                            </svg>
                                            <span><?= date('d M Y', strtotime($photo['date'])) ?></span>
                                        </div>
                                    </div>
                                </div>

                                <!-- Premium corner indicator -->
                                <div class="absolute top-4 right-4 w-8 h-8 bg-black/50 backdrop-blur-sm rounded-full flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity duration-300">
                                    <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                                    </svg>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <!-- Load More Button -->
                <div class="text-center mt-16 animate-fade-in-up">
                    <button class="inline-flex items-center gap-3 bg-white text-gray-900 px-8 py-4 rounded-2xl font-bold hover:bg-gray-50 transition-all duration-300 shadow-lg hover:shadow-xl transform hover:-translate-y-1 border border-gray-200">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                        </svg>
                        Muat Lebih Banyak
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                        </svg>
                    </button>
                </div>
            <?php endif; ?>

        </div>
    </section>

    <!-- Enhanced Photo Modal -->
    <div id="photoModal" class="fixed inset-0 z-50 hidden modal-backdrop">
        <div class="flex items-center justify-center min-h-screen p-4">
            <div class="relative max-w-6xl w-full bg-white rounded-3xl shadow-2xl overflow-hidden transform transition-all">

                <!-- Close Button -->
                <button onclick="closePhotoModal()"
                        class="absolute top-6 right-6 z-20 w-12 h-12 bg-white/90 backdrop-blur-sm text-gray-900 rounded-full hover:bg-white transition-all duration-300 shadow-lg flex items-center justify-center group">
                    <svg class="w-6 h-6 group-hover:rotate-90 transition-transform duration-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>

                <!-- Image Container -->
                <div class="relative bg-gradient-to-br from-gray-900 to-black p-8 lg:p-12">
                    <img id="modalImage" src="" alt="" class="w-full h-auto max-h-[70vh] object-contain mx-auto rounded-2xl shadow-2xl">
                </div>

                <!-- Info Section -->
                <div class="p-8 lg:p-12 bg-white">
                    <div class="flex flex-col lg:flex-row items-start justify-between gap-8">
                        <div class="flex-1">
                            <h3 id="modalTitle" class="text-3xl font-bold text-gray-900 mb-4"></h3>
                            <p id="modalDescription" class="text-gray-600 text-lg leading-relaxed mb-6"></p>

                            <div class="flex flex-wrap items-center gap-6 text-sm text-gray-500">
                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 avatar-gradient rounded-full flex items-center justify-center text-white font-bold">
                                        <span id="modalUserInitial" class="text-sm"></span>
                                    </div>
                                    <div>
                                        <p class="font-semibold text-gray-900" id="modalAuthor"></p>
                                        <p class="text-xs text-gray-500">Kontributor</p>
                                    </div>
                                </div>
                                <div class="flex items-center gap-2">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                    </svg>
                                    <span id="modalDate"></span>
                                </div>
                            </div>
                        </div>

                        <!-- Action Buttons -->
                        <div class="flex flex-col sm:flex-row gap-4">
                            <button onclick="downloadPhoto()" class="btn-gradient text-white px-6 py-3 rounded-2xl font-bold hover:btn-gradient transition-all duration-300 shadow-lg hover:shadow-xl transform hover:-translate-y-1 flex items-center gap-2">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                                </svg>
                                Download
                            </button>
                            <button onclick="sharePhoto()" class="bg-white text-gray-700 px-6 py-3 rounded-2xl font-bold hover:bg-gray-50 transition-all duration-300 shadow-lg border border-gray-200 flex items-center gap-2">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.367 2.684 3 3 0 00-5.367-2.684z"/>
                                </svg>
                                Bagikan
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php include __DIR__ . '/../includes/footer.php'; ?>

    <script>
        let currentPhotoData = {};

        function openPhotoModal(imagePath, title, author, date) {
            currentPhotoData = { imagePath, title, author, date };

            const modal = document.getElementById('photoModal');
            const modalImage = document.getElementById('modalImage');
            const modalTitle = document.getElementById('modalTitle');
            const modalAuthor = document.getElementById('modalAuthor');
            const modalDate = document.getElementById('modalDate');

            modalImage.src = `<?= BASE_URL ?>/public/uploads/${imagePath}`;
            modalTitle.textContent = title;
            modalAuthor.textContent = author;
            modalDate.textContent = new Date(date).toLocaleDateString('id-ID', {
                day: 'numeric',
                month: 'long',
                year: 'numeric'
            });

            modal.classList.remove('hidden');
            modal.classList.add('flex');
            document.body.style.overflow = 'hidden';
        }

        function closePhotoModal() {
            const modal = document.getElementById('photoModal');
            modal.classList.add('hidden');
            modal.classList.remove('flex');
            document.body.style.overflow = 'auto';
        }

        function sharePhoto() {
            if (navigator.share && currentPhotoData) {
                navigator.share({
                    title: currentPhotoData.title,
                    text: `Foto dari ${currentPhotoData.author}`,
                    url: window.location.href
                }).catch(console.error);
            } else {
                // Fallback: copy to clipboard
                navigator.clipboard.writeText(window.location.href).then(() => {
                    alert('Link berhasil disalin ke clipboard!');
                }).catch(() => {
                    alert('Gagal membagikan foto');
                });
            }
        }

        // Close modal when clicking outside
        document.getElementById('photoModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closePhotoModal();
            }
        });

        // Close modal with ESC key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closePhotoModal();
            }
        });

        // Filter functionality
        document.querySelectorAll('.filter-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                // Remove active class from all buttons
                document.querySelectorAll('.filter-btn').forEach(b => b.classList.remove('active'));

                // Add active class to clicked button
                this.classList.add('active');

                // Here you would implement actual filtering logic
                console.log('Filter:', this.textContent);
            });
        });
    </script>
</body>
</html>
