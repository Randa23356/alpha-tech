<?php
// gallery_detail.php - Detail Foto Galeri
session_start();
require_once __DIR__ . "/src/config/db.php";

// Get photo ID
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if (!$id) {
    header("Location: " . url('gallery'));
    exit();
}

// Get photo detail from posts
try {
    $stmt = $pdo->prepare("
        SELECT posts.*, users.username 
        FROM posts 
        JOIN users ON posts.user_id = users.id 
        WHERE posts.id = ? AND posts.status = 'approved' AND posts.image IS NOT NULL
    ");
    $stmt->execute([$id]);
    $photo = $stmt->fetch();
    
    if (!$photo) {
        header("Location: " . url('gallery'));
        exit();
    }
} catch (Exception $e) {
    header("Location: " . url('gallery'));
    exit();
}

// Get other photos (related)
try {
    $stmt = $pdo->prepare("
        SELECT posts.*, users.username 
        FROM posts 
        JOIN users ON posts.user_id = users.id 
        WHERE posts.status = 'approved' AND posts.image IS NOT NULL AND posts.id != ?
        ORDER BY posts.date DESC 
        LIMIT 6
    ");
    $stmt->execute([$id]);
    $related_photos = $stmt->fetchAll();
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
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($photo['title']) ?> - Galeri Informatics A</title>
    <style>
        /* Dynamic theme variables for gallery detail page */
        :root {
            --primary-color: <?= $primary_color ?>;
            --secondary-color: <?= $secondary_color ?>;
            --accent-color: <?= $accent_color ?>;
            --success-color: <?= $success_color ?>;
            --warning-color: <?= $warning_color ?>;
            --danger-color: <?= $danger_color ?>;
        }

        /* Custom gradient backgrounds using theme colors */
        .avatar-gradient {
            background: linear-gradient(135deg, <?= $primary_color ?> 0%, <?= $secondary_color ?> 100%);
        }

        /* Theme hover effects */
        .theme-hover:hover {
            color: <?= $primary_color ?>;
        }

        /* Theme text colors */
        .theme-text {
            color: <?= $primary_color ?>;
        }

        /* Theme background colors */
        .theme-bg-light {
            background-color: <?= $primary_color ?>10;
        }
    </style>
    <link href=" <?= asset('tailwind.css') ?>" rel="stylesheet">
    <?php require_once __DIR__ . '/includes/favicon.php'; ?>
</head>
<body class="bg-gradient-to-br from-gray-50 to-blue-50 min-h-screen">
    <?php include __DIR__ . '/includes/navbar.php'; ?>

    <!-- Main Content -->
    <main class="max-w-7xl mx-auto px-6 py-8 mt-16">
        <!-- Back Button -->
        <a href="<?= url('gallery') ?>" class="inline-flex items-center gap-2 theme-text hover:theme-hover font-medium mb-6 transition">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
            </svg>
            Kembali ke Galeri
        </a>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <!-- Main Photo -->
            <div class="lg:col-span-2">
                <div class="bg-white rounded-2xl shadow-2xl overflow-hidden">
                    <!-- Large Image -->
                    <div class="relative">
                        <img src="<?= asset('uploads/' . htmlspecialchars($photo['image'])) ?>" 
                             alt="<?= htmlspecialchars($photo['title']) ?>" 
                             class="w-full h-auto max-h-[600px] object-contain bg-gray-900">
                        
                        <!-- Download Button -->
                        <a href="<?= asset('uploads/' . htmlspecialchars($photo['image'])) ?>" 
                           download 
                           class="absolute top-4 right-4 bg-white/90 backdrop-blur-sm text-gray-900 px-4 py-2 rounded-lg hover:bg-white transition shadow-lg flex items-center gap-2">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                            </svg>
                            Download
                        </a>
                    </div>

                    <!-- Photo Info -->
                    <div class="p-8">
                        <h1 class="text-3xl font-bold theme-text mb-4"><?= htmlspecialchars($photo['title']) ?></h1>
                        
                        <div class="flex flex-wrap items-center gap-6 text-gray-600 mb-6 pb-6 border-b border-gray-200">
                            <div class="flex items-center gap-2">
                                <div class="w-10 h-10 avatar-gradient rounded-full flex items-center justify-center text-white font-bold">
                                    <?= strtoupper(substr($photo['username'], 0, 1)) ?>
                                </div>
                                <div>
                                    <p class="text-sm text-gray-500">Diposting oleh</p>
                                    <p class="font-semibold text-gray-900"><?= htmlspecialchars($photo['username']) ?></p>
                                </div>
                            </div>
                            <div class="flex items-center gap-2">
                                <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                </svg>
                                <span><?= date('d F Y', strtotime($photo['date'])) ?></span>
                            </div>
                        </div>

                        <div class="text-gray-700 leading-relaxed whitespace-pre-line">
                            <?= nl2br(htmlspecialchars($photo['content'])) ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Sidebar -->
            <div class="lg:col-span-1">
                <!-- Related Photos -->
                <?php if (!empty($related_photos)): ?>
                    <div class="bg-white rounded-2xl shadow-xl p-6">
                        <h2 class="text-xl font-bold text-blue-900 mb-4">Foto Lainnya</h2>
                        <div class="space-y-4">
                            <?php foreach ($related_photos as $related): ?>
                                        <a href="<?= url('gallery_detail?id=' . $related['id']) ?>" 
                                   class="block group">
                                    <div class="flex gap-3 p-2 rounded-lg hover:bg-gray-50 transition">
                                        <div class="w-20 h-20 flex-shrink-0 rounded-lg overflow-hidden">
                                            <img src="<?= asset('uploads/' . htmlspecialchars($related['image'])) ?>" 
                                                 alt="<?= htmlspecialchars($related['title']) ?>" 
                                                 class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-300">
                                        </div>
                                        <div class="flex-1 min-w-0">
                                            <h3 class="font-semibold text-gray-900 group-hover:text-blue-900 transition line-clamp-2 mb-1">
                                                <?= htmlspecialchars($related['title']) ?>
                                            </h3>
                                            <p class="text-xs text-gray-500">
                                                <?= date('d M Y', strtotime($related['date'])) ?>
                                            </p>
                                        </div>
                                    </div>
                                </a>
                            <?php endforeach; ?>
                        </div>
                        
                        <a href="<?= url('gallery') ?>" 
                           class="mt-4 block text-center text-blue-600 hover:text-blue-800 font-medium text-sm">
                            Lihat Semua Foto →
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </main>

    <?php include __DIR__ . '/includes/footer.php'; ?>
</body>
</html>