<?php
// Error reporting
ini_set("display_errors", 1);
ini_set("display_startup_errors", 1);
error_reporting(E_ALL);

// Start session
session_start();

// Check if user is logged in
$isLoggedIn = !empty($_SESSION['user']['id']);
$username = $isLoggedIn ? ($_SESSION['user']['username'] ?? 'Teman') : '';



// Fungsi untuk mengatur kecerahan warna
if (!function_exists('adjustBrightness')) {
    function adjustBrightness($hex, $steps) {
        // Steps should be between -255 and 255. Negative = darker, positive = lighter
        $steps = max(-255, min(255, $steps));
        
        // Format the hex color string
        $hex = str_replace('#', '', $hex);
        if (strlen($hex) == 3) {
            $hex = str_repeat(substr($hex, 0, 1), 2) . 
                   str_repeat(substr($hex, 1, 1), 2) . 
                   str_repeat(substr($hex, 2, 1), 2);
        }
        
        // Get decimal values
        $r = hexdec(substr($hex, 0, 2));
        $g = hexdec(substr($hex, 2, 2));
        $b = hexdec(substr($hex, 4, 2));
        
        // Adjust brightness
        $r = max(0, min(255, $r + $steps));
        $g = max(0, min(255, $g + $steps));
        $b = max(0, min(255, $b + $steps));
        
        // Convert back to hex
        return '#' . str_pad(dechex($r), 2, '0', STR_PAD_LEFT) 
                    . str_pad(dechex($g), 2, '0', STR_PAD_LEFT) 
                    . str_pad(dechex($b), 2, '0', STR_PAD_LEFT);
    }
}
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
    $stmt = $pdo->query(
        "SELECT setting_key, setting_value FROM site_settings WHERE setting_key IN ('primary_color', 'secondary_color', 'accent_color', 'success_color', 'warning_color', 'danger_color')",
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
    $danger_color = $settings["danger_color"] ?? $danger_color;
} catch (Exception $e) {
    // Use default colors if database fails
}

// Ambil stats
try {
    // Check if deleted_at column exists
    $deletedAtCheck = $pdo
        ->query("SHOW COLUMNS FROM posts LIKE 'deleted_at'")
        ->fetch();
    $deletedAtCondition = $deletedAtCheck ? "AND deleted_at IS NULL" : "";

    $stmt = $pdo->query(
        "SELECT COUNT(*) as total FROM posts WHERE status = 'approved' {$deletedAtCondition}",
    );
    $total_kegiatan = $stmt->fetch()["total"] ?? 0;

    $stmt = $pdo->query(
        "SELECT COUNT(*) as total FROM users WHERE role = 'user'",
    );
    $total_users = $stmt->fetch()["total"] ?? 0;

    // Count total foto (semua foto dari post_images table)
    $stmt = $pdo->query(
        "SELECT COUNT(*) as total FROM post_images pi JOIN posts p ON pi.post_id = p.id WHERE p.status = 'approved' {$deletedAtCondition}",
    );
    $total_photos = $stmt->fetch()["total"] ?? 0;

    // Ambil 4 foto terbaru untuk galeri di about page
    $recent_photos = [];
    if ($total_photos > 0) {
        // Query untuk mengambil 4 foto terbaru berdasarkan tanggal post
        $stmt = $pdo->query(
            "SELECT pi.image_path, p.id, p.title as caption, p.content as description, p.date as uploaded_at, 
                    users.username, users.id as user_id
             FROM post_images pi 
             JOIN posts p ON pi.post_id = p.id 
             JOIN users ON p.user_id = users.id 
             WHERE p.status = 'approved' {$deletedAtCondition}
             ORDER BY p.date DESC, pi.image_order ASC 
             LIMIT 4"
        );
        $recent_photos = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Ambil about features (Kenapa Memilih Platform Ini?)
    $stmt = $pdo->query(
        "SELECT * FROM about_features WHERE is_active = 1 ORDER BY display_order ASC, id ASC",
    );
    $about_features_list = $stmt->fetchAll();

    // Ambil platform features (Fitur Platform Detail)
    $stmt = $pdo->query(
        "SELECT * FROM platform_features WHERE is_active = 1 ORDER BY display_order ASC, id ASC",
    );
    $platform_features_list = $stmt->fetchAll();
} catch (Exception $e) {
    $total_kegiatan = 0;
    $total_users = 0;
    $total_photos = 0;
    $settings = [];
    $recent_photos = [];
    $about_features_list = [];
    $platform_features_list = [];
}

$about_title = $settings["about_title"] ?? "Tentang Informatics A";
$about_description =
    $settings["about_description"] ??
    "Platform kolaborasi dan dokumentasi kelas Informatika terbaik untuk berbagi kegiatan, pengumuman, dan galeri foto.";
$about_vision =
    $settings["about_vision"] ??
    "Menjadi platform terdepan dalam mendokumentasikan dan berbagi kegiatan kelas.";
$about_mission =
    $settings["about_mission"] ??
    "Memfasilitasi kolaborasi antar mahasiswa melalui teknologi.";
$about_feature_1 =
    $settings["about_feature_1"] ?? "Interface modern dan mudah digunakan";
$about_feature_2 =
    $settings["about_feature_2"] ??
    "Sistem approval untuk menjaga kualitas konten";
$about_feature_3 =
    $settings["about_feature_3"] ?? "Galeri foto untuk dokumentasi visual";
$about_feature_4 =
    $settings["about_feature_4"] ?? "Responsive design untuk semua perangkat";
$platform_feature_1_title =
    $settings["platform_feature_1_title"] ?? "Posting Kegiatan";
$platform_feature_1_desc =
    $settings["platform_feature_1_desc"] ??
    "Bagikan kegiatan kelas dengan mudah dan cepat. Setiap postingan akan di-review oleh admin sebelum dipublikasikan.";
$platform_feature_2_title =
    $settings["platform_feature_2_title"] ?? "Galeri Foto";
$platform_feature_2_desc =
    $settings["platform_feature_2_desc"] ??
    "Dokumentasi visual kegiatan dalam satu tempat. Foto otomatis diambil dari kegiatan yang sudah di-approve.";
$platform_feature_3_title = $settings["platform_feature_3_title"] ?? "Komentar";
$platform_feature_3_desc =
    $settings["platform_feature_3_desc"] ??
    "Diskusi dan berikan feedback pada setiap kegiatan. Interaksi antar anggota kelas lebih mudah.";
$platform_feature_4_title =
    $settings["platform_feature_4_title"] ?? "Pengumuman";
$platform_feature_4_desc =
    $settings["platform_feature_4_desc"] ??
    "Informasi penting dari koordinator kelas dan admin. Pastikan tidak ketinggalan update terbaru.";
$site_name = $settings["site_name"] ?? "Informatics A";

// Dalam bagian query recent_photos, modifikasi untuk mengambil profile_pic
$stmt = $pdo->query(
    "SELECT pi.image_path, p.id, p.title as caption, p.content as description, p.date as uploaded_at, 
            users.username, users.id as user_id, users.profile_pic
     FROM post_images pi 
     JOIN posts p ON pi.post_id = p.id 
     JOIN users ON p.user_id = users.id 
     WHERE p.status = 'approved' {$deletedAtCondition}
     ORDER BY p.date DESC, pi.image_order ASC 
     LIMIT 4"
);
$recent_photos = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tentang - <?= htmlspecialchars($site_name) ?></title>
    <link href=" <?= asset("tailwind.css") ?>" rel="stylesheet">
    <?php require_once __DIR__ . '/../includes/favicon.php'; ?>
    <link href=" <?= asset("css/dynamic-theme.php") ?>" rel="stylesheet">
    <style>
        /* Tombol unduh dengan warna primary */
        #modalDownload {
            background-color: var(--primary-color);
            transition: background-color 0.2s ease;
        }
        #modalDownload:hover {
            background-color: <?= adjustBrightness($primary_color, -20) ?> !important;
        }
        /* Dynamic theme variables for about page */
        :root {
            --primary-color: <?= $primary_color ?>;
            --secondary-color: <?= $secondary_color ?>;
            --accent-color: <?= $accent_color ?>;
            --success-color: <?= $success_color ?>;
            --warning-color: <?= $warning_color ?>;
            --danger-color: <?= $danger_color ?>;
        }

        /* Custom gradient backgrounds using theme colors */
        .header-gradient {
            background: linear-gradient(135deg, <?= $primary_color ?> 0%, <?= $accent_color ?> 50%, <?= $secondary_color ?> 100%);
        }

        .stats-gradient-1 {
            background: linear-gradient(135deg, <?= $primary_color ?> 0%, <?= $secondary_color ?> 100%);
        }

        .stats-gradient-2 {
            background: linear-gradient(135deg, <?= $accent_color ?> 0%, <?= $secondary_color ?> 100%);
        }

        .stats-gradient-3 {
            background: linear-gradient(135deg, <?= $success_color ?> 0%, #059669 100%);
        }

        .cta-gradient {
            background: linear-gradient(135deg, <?= $primary_color ?> 0%, <?= $accent_color ?> 50%, <?= $secondary_color ?> 100%);
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

        /* Modal backdrop */
        .modal-backdrop {
            backdrop-filter: blur(12px);
            background: rgba(0, 0, 0, 0.9);
        }

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

        .animate-fade-in-up {
            animation: fadeInUp 0.8s ease-out;
        }

        /* Mobile-only responsive adjustments */
        @media (max-width: 768px) {
            /* Header section - reduce large elements on mobile */
            .mobile-header-svg {
                width: 3rem !important;
                height: 3rem !important;
            }

            .mobile-header-title {
                font-size: 2.5rem !important;
                line-height: 1.2 !important;
            }

            .mobile-header-subtitle {
                font-size: 1.25rem !important;
                line-height: 1.4 !important;
            }

            /* Stats section - reduce icon and number sizes on mobile */
            .mobile-stats-icon {
                width: 2.5rem !important;
                height: 2.5rem !important;
            }

            .mobile-stats-number {
                font-size: 2.5rem !important;
                line-height: 1 !important;
            }

            /* Feature sections - reduce icon sizes on mobile */
            .mobile-feature-icon-container {
                width: 3.5rem !important;
                height: 3.5rem !important;
            }

            .mobile-feature-icon {
                width: 1.5rem !important;
                height: 1.5rem !important;
            }

            .mobile-feature-title {
                font-size: 1.5rem !important;
                line-height: 1.3 !important;
            }

            .mobile-feature-text {
                font-size: 1rem !important;
                line-height: 1.5 !important;
            }

            /* CTA section - reduce title size on mobile */
            .mobile-cta-title {
                font-size: 2.25rem !important;
                line-height: 1.2 !important;
            }

            .mobile-cta-subtitle {
                font-size: 1.25rem !important;
                line-height: 1.4 !important;
            }

            /* Vision & Mission section - adjust text sizes */
            .mobile-vision-mission-title {
                font-size: 1.5rem !important;
            }

            .mobile-vision-mission-text {
                font-size: 1.125rem !important;
            }

            /* About section title - reduce size on mobile */
            .mobile-about-title {
                font-size: 2rem !important;
            }

            /* Platform features title - reduce size on mobile */
            .mobile-platform-title {
                font-size: 2rem !important;
            }

            /* Modal adjustments for mobile */
            #photoModal .p-8 {
                padding: 1rem !important;
            }

            #photoModal .text-3xl {
                font-size: 1.5rem !important;
            }

            #photoModal .flex-col.lg\:flex-row {
                flex-direction: column !important;
            }
        }
    </style>
</head>
<body class="bg-slate-50 min-h-screen">
    <?php include __DIR__ . "/../includes/navbar.php"; ?>

    <!-- Header -->
    <header class="relative header-gradient text-white py-12 sm:py-16 lg:py-20 px-6 overflow-hidden pt-6 sm:mt-0">
        <!-- Background Pattern -->
        <div class="absolute inset-0 bg-[url('data:image/svg+xml,%3Csvg width="60" height="60" viewBox="0 0 60 60" xmlns="http://www.w3.org/2000/svg"%3E%3Cg fill="none" fill-rule="evenodd"%3E%3Cg fill="%23ffffff" fill-opacity="0.05"%3E%3Ccircle cx="7" cy="7" r="1"/%3E%3C/g%3E%3C/g%3E%3C/svg%3E')] opacity-20"></div>

        <!-- Animated Background Shapes -->
        <div class="absolute top-5 sm:top-10 left-5 sm:left-10 w-36 h-36 sm:w-72 sm:h-72 bg-white/5 rounded-full blur-3xl animate-pulse"></div>
        <div class="absolute bottom-5 sm:bottom-10 right-5 sm:right-10 w-48 h-48 sm:w-96 sm:h-96 bg-indigo-400/10 rounded-full blur-3xl animate-pulse delay-1000"></div>

        <div class="relative max-w-6xl mx-auto text-center">
            <div class="flex justify-center mb-6">
                <div class="relative">
                    <div class="absolute inset-0 bg-white/20 rounded-full blur-xl animate-pulse"></div>
                    <svg class="relative w-24 h-24 animate-bounce mobile-header-svg" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
            </div>
            <h1 class="text-6xl font-bold mb-6 bg-gradient-to-r from-white via-blue-100 to-white bg-clip-text text-transparent animate-fade-in mobile-header-title">
                Tentang <?= htmlspecialchars($site_name) ?>
            </h1>
            <p class="text-2xl text-blue-100 max-w-4xl mx-auto leading-relaxed animate-fade-in-delay mobile-header-subtitle">
                Platform digital untuk kolaborasi dan dokumentasi kelas yang menghubungkan mahasiswa dalam satu ekosistem terintegrasi
            </p>

            <!-- Scroll indicator -->
            <div class="absolute mt-16 left-1/2 transform -translate-x-1/2 animate-bounce">
                <svg class="w-6 h-6 text-blue-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3"/>
                </svg>
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <main class="max-w-6xl mx-auto px-6 py-16">
        <!-- About Section -->
        <div class="relative bg-white rounded-3xl shadow-xl p-12 mb-16 overflow-hidden">
            <!-- Decorative elements -->
            <div class="absolute top-0 right-0 w-64 h-64 rounded-full -translate-y-32 translate-x-32" style="background: linear-gradient(135deg, <?= $primary_color ?>15 0%, transparent 100%);"></div>
            <div class="absolute bottom-0 left-0 w-48 h-48 rounded-full translate-y-24 -translate-x-24" style="background: linear-gradient(135deg, <?= $secondary_color ?>15 0%, transparent 100%);"></div>

            <div class="relative">
                <div class="text-center mb-12">
                    <div class="inline-flex items-center gap-2 bg-blue-100 text-blue-800 px-4 py-2 rounded-full text-sm font-semibold mb-4" style="background-color: <?= $primary_color ?>20; color: <?= $primary_color ?>;">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        Tentang Platform
                    </div>
                    <h2 class="text-4xl font-bold text-gray-900 mb-6 bg-clip-text text-transparent mobile-about-title" style="background: linear-gradient(135deg, <?= $primary_color ?> 0%, <?= $secondary_color ?> 100%); -webkit-background-clip: text; background-clip: text;">
                    Tentang <?= htmlspecialchars($site_name) ?>
                    </h2>
                </div>

                <div class="prose prose-xl max-w-none text-gray-700 leading-relaxed mb-12">
                    <p class="text-xl text-gray-600 leading-relaxed">
                        <?= nl2br(htmlspecialchars($about_description)) ?>
                    </p>
                </div>

                <!-- Vision & Mission -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-6 sm:gap-8">
                    <div class="group relative bg-gradient-to-br from-blue-50 via-indigo-50 to-blue-100 p-8 rounded-2xl border border-blue-100 hover:shadow-lg transition-all duration-300">
                        <div class="absolute top-4 right-4 w-12 h-12 bg-blue-500/10 rounded-full flex items-center justify-center">
                            <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                            </svg>
                        </div>
                        <h3 class="text-2xl font-bold text-blue-900 mb-4 flex items-center gap-3 mobile-vision-mission-title">
                            <div class="w-3 h-3 bg-blue-500 rounded-full"></div>
                            Visi
                        </h3>
                        <p class="text-gray-700 leading-relaxed text-lg mobile-vision-mission-text">
                            <?= nl2br(htmlspecialchars($about_vision)) ?>
                        </p>
                    </div>

                    <div class="group relative bg-gradient-to-br from-indigo-50 via-purple-50 to-indigo-100 p-8 rounded-2xl border border-indigo-100 hover:shadow-lg transition-all duration-300">
                        <div class="absolute top-4 right-4 w-12 h-12 bg-indigo-500/10 rounded-full flex items-center justify-center">
                            <svg class="w-6 h-6 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z"/>
                            </svg>
                        </div>
                        <h3 class="text-2xl font-bold text-indigo-900 mb-4 flex items-center gap-3 mobile-vision-mission-title">
                            <div class="w-3 h-3 bg-indigo-500 rounded-full"></div>
                            Misi
                        </h3>
                        <p class="text-gray-700 leading-relaxed text-lg mobile-vision-mission-text">
                            <?= nl2br(htmlspecialchars($about_mission)) ?>
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Stats -->
        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-4 sm:gap-6 lg:gap-8 mb-16">
            <div class="group relative stats-gradient-1 text-white p-8 rounded-2xl shadow-xl hover:shadow-2xl transform hover:-translate-y-2 transition-all duration-300 overflow-hidden">
                <!-- Background decoration -->
                <div class="absolute top-0 right-0 w-32 h-32 bg-white/5 rounded-full -translate-y-16 translate-x-16"></div>
                <div class="absolute bottom-0 left-0 w-24 h-24 bg-indigo-400/20 rounded-full translate-y-12 -translate-x-12"></div>

                <div class="relative text-center">
                    <div class="mb-4">
                        <svg class="w-12 h-12 mx-auto text-blue-200 group-hover:scale-110 transition-transform duration-300 mobile-stats-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                    </div>
                    <div class="text-5xl font-bold mb-3 stats-counter group-hover:scale-110 transition-transform duration-300 mobile-stats-number">
                        <?= number_format($total_kegiatan) ?>
                    </div>
                    <div class="text-blue-100 text-lg font-medium">Kegiatan Terdokumentasi</div>
                    <div class="absolute inset-x-0 bottom-0 h-1 bg-gradient-to-r from-transparent via-white/20 to-transparent transform scale-x-0 group-hover:scale-x-100 transition-transform duration-500"></div>
                </div>
            </div>

            <div class="group relative stats-gradient-2 text-white p-8 rounded-2xl shadow-xl hover:shadow-2xl transform hover:-translate-y-2 transition-all duration-300 overflow-hidden">
                <!-- Background decoration -->
                <div class="absolute top-0 right-0 w-32 h-32 bg-white/5 rounded-full -translate-y-16 translate-x-16"></div>
                <div class="absolute bottom-0 left-0 w-24 h-24 bg-purple-400/20 rounded-full translate-y-12 -translate-x-12"></div>

                <div class="relative text-center">
                    <div class="mb-4">
                        <svg class="w-12 h-12 mx-auto text-indigo-200 group-hover:scale-110 transition-transform duration-300 mobile-stats-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z"/>
                        </svg>
                    </div>
                    <div class="text-5xl font-bold mb-3 stats-counter group-hover:scale-110 transition-transform duration-300 mobile-stats-number">
                        <?= number_format($total_users) ?>
                    </div>
                    <div class="text-indigo-100 text-lg font-medium">Anggota Aktif</div>
                    <div class="absolute inset-x-0 bottom-0 h-1 bg-gradient-to-r from-transparent via-white/20 to-transparent transform scale-x-0 group-hover:scale-x-100 transition-transform duration-500"></div>
                </div>
            </div>

            <div class="group relative stats-gradient-3 text-white p-8 rounded-2xl shadow-xl hover:shadow-2xl transform hover:-translate-y-2 transition-all duration-300 overflow-hidden">
                <!-- Background decoration -->
                <div class="absolute top-0 right-0 w-32 h-32 bg-white/5 rounded-full -translate-y-16 translate-x-16"></div>
                <div class="absolute bottom-0 left-0 w-24 h-24 bg-pink-400/20 rounded-full translate-y-12 -translate-x-12"></div>

                <div class="relative text-center">
                    <div class="mb-4">
                        <svg class="w-12 h-12 mx-auto text-purple-200 group-hover:scale-110 transition-transform duration-300 mobile-stats-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                        </svg>
                    </div>
                    <div class="text-5xl font-bold mb-3 stats-counter group-hover:scale-110 transition-transform duration-300 mobile-stats-number">
                        <?= number_format($total_photos) ?>
                    </div>
                    <div class="text-purple-100 text-lg font-medium">Foto & Dokumentasi</div>
                    <div class="absolute inset-x-0 bottom-0 h-1 bg-gradient-to-r from-transparent via-white/20 to-transparent transform scale-x-0 group-hover:scale-x-100 transition-transform duration-500"></div>
                </div>
            </div>
        </div>

        <!-- Foto & Dokumentasi Section - Hanya 4 Foto Terbaru -->
        <?php if (!empty($recent_photos)): ?>
        <div class="relative bg-white rounded-3xl shadow-xl p-12 mb-16 overflow-hidden">
            <!-- Decorative elements -->
            <div class="absolute top-0 right-0 w-40 h-40 bg-gradient-to-bl from-blue-50 to-transparent rounded-full -translate-y-20 translate-x-20"></div>
            <div class="absolute bottom-0 left-0 w-32 h-32 bg-gradient-to-tr from-indigo-50 to-transparent rounded-full translate-y-16 -translate-x-16"></div>

            <div class="relative">
                <div class="text-center mb-12">
                    <div class="inline-flex items-center gap-2 bg-gradient-to-r from-blue-500 to-indigo-600 text-white px-4 py-2 rounded-full text-sm font-semibold mb-4">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                        </svg>
                        Foto & Dokumentasi
                    </div>
                    <h2 class="text-4xl font-bold text-gray-900 mb-6 bg-clip-text text-transparent" style="background: linear-gradient(135deg, <?= $primary_color ?> 0%, <?= $secondary_color ?> 100%); -webkit-background-clip: text; background-clip: text;">
                        Foto & Dokumentasi
                    </h2>
                    <p class="text-gray-600 text-xl max-w-3xl mx-auto leading-relaxed">
                        Dokumentasi visual dari semua kegiatan kelas <?= htmlspecialchars(
                            $site_name,
                        ) ?> yang telah terselenggara dengan koleksi foto berkualitas tinggi
                    </p>
                </div>

                <!-- Grid untuk 4 foto terbaru -->
                <div class="grid grid-cols-2 sm:grid-cols-2 md:grid-cols-4 gap-6">
                    <?php foreach ($recent_photos as $photo): ?>
                        <div class="relative aspect-square overflow-hidden rounded-2xl shadow-lg hover:shadow-2xl transition-all duration-500 group cursor-pointer transform hover:-translate-y-2"
                             onclick='openPhotoModal(<?= json_encode([
                                 "id" => $photo["id"],
                                 "image" => $photo["image_path"],
                                 "title" => $photo["caption"],
                                 "description" => $photo["description"],
                                 "username" => $photo["username"],
                                 "user_id" => $photo["user_id"],
                                 "date" => $photo["uploaded_at"],
                             ]) ?>)'>
                            <img src=" <?= BASE_URL ?>/public/uploads/<?= htmlspecialchars(
    $photo["image_path"],
) ?>"
                                 alt="<?= htmlspecialchars($photo["caption"]) ?>"
                                 class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-700">

                            <!-- Enhanced overlay on hover -->
                            <div class="absolute inset-0 bg-gradient-to-t from-black/80 via-black/30 to-transparent opacity-0 group-hover:opacity-100 transition-all duration-300 flex items-end p-4">
                                <div class="text-white">
                                    <p class="font-bold text-sm line-clamp-2 leading-tight mb-1 group-hover:text-blue-200 transition-colors">
                                        <?= htmlspecialchars($photo["caption"]) ?>
                                    </p>
                                    <p class="text-xs text-gray-300">Oleh <?= htmlspecialchars(
                                        $photo["username"],
                                    ) ?></p>
                                    <div class="flex items-center gap-1 mt-2">
                                        <svg class="w-3 h-3 text-blue-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                        </svg>
                                        <span class="text-xs text-gray-300">
                                            <?= date(
                                                "d M Y",
                                                strtotime($photo["uploaded_at"]),
                                            ) ?>
                                        </span>
                                    </div>
                                </div>
                            </div>

                            <!-- Photo count indicator -->
                            <div class="absolute top-2 right-2 w-6 h-6 bg-black/50 rounded-full flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity duration-300">
                                <svg class="w-3 h-3 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                </svg>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <!-- Info bahwa hanya menampilkan 4 foto terbaru -->
                <div class="text-center mt-8">
                    <p class="text-gray-500 text-sm">
                        Menampilkan 4 foto terbaru dari total <?= number_format($total_photos) ?> foto dokumentasi
                    </p>
                </div>

                <div class="text-center mt-12">
                    <a href="gallery.php" class="inline-flex items-center gap-3 btn-gradient text-white px-8 py-4 rounded-2xl font-bold hover:btn-gradient transition-all duration-300 shadow-lg hover:shadow-xl transform hover:-translate-y-1">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                        </svg>
                                                Lihat Semua Foto
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                        </svg>
                    </a>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Keunggulan Platform Section -->
        <div class="relative bg-gradient-to-br from-gray-50 to-blue-50 rounded-3xl p-12 mb-16 overflow-hidden">
            <!-- Decorative elements -->
            <div class="absolute top-0 right-0 w-48 h-48 bg-gradient-to-bl from-blue-100 to-transparent rounded-full -translate-y-24 translate-x-24"></div>
            <div class="absolute bottom-0 left-0 w-40 h-40 bg-gradient-to-tr from-indigo-100 to-transparent rounded-full translate-y-20 -translate-x-20"></div>

            <div class="relative">
                <div class="text-center mb-12">
                    <div class="inline-flex items-center gap-2 text-white px-4 py-2 rounded-full text-sm font-semibold mb-4" style="background: linear-gradient(135deg, <?= $success_color ?> 0%, <?= $primary_color ?> 100%);">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z"/>
                        </svg>
                        Keunggulan Platform
                    </div>
                    <h2 class="text-4xl font-bold text-gray-900 mb-6 bg-clip-text text-transparent" style="background: linear-gradient(135deg, <?= $primary_color ?> 0%, <?= $secondary_color ?> 100%); -webkit-background-clip: text; background-clip: text;">
                        Kenapa Memilih Platform Ini?
                    </h2>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-2 gap-4 sm:gap-6 lg:gap-8">
                    <?php
                    // Use database features if available, otherwise use fallback features
                    $features_to_show = $about_features_list;

                    // If no database features, use fallback features from settings
                    if (empty($about_features_list)) {
                        $features_to_show = [
                            ['feature_text' => $about_feature_1],
                            ['feature_text' => $about_feature_2],
                            ['feature_text' => $about_feature_3],
                            ['feature_text' => $about_feature_4],
                        ];
                    }
                    ?>

                    <?php if (!empty($features_to_show)): ?>
                    <?php foreach ($features_to_show as $index => $feature): ?>
                    <div class="group relative bg-white p-8 rounded-2xl shadow-lg hover:shadow-xl transition-all duration-300 border border-gray-100 hover:border-gray-200" style="border-color: <?= $primary_color ?>30;">
                        <div class="absolute inset-0 rounded-2xl opacity-0 group-hover:opacity-100" style="background: linear-gradient(135deg, <?= $primary_color ?>08 0%, <?= $secondary_color ?>08 100%);"></div>
                        <div class="relative flex gap-6 items-start">
                            <div class="flex-shrink-0">
                                <div class="w-16 h-16 rounded-2xl flex items-center justify-center shadow-lg group-hover:scale-110 transition-transform duration-300 mobile-feature-icon-container" style="background: linear-gradient(135deg, <?= $primary_color ?> 0%, <?= $secondary_color ?> 100%);">
                                    <svg class="w-8 h-8 text-white mobile-feature-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                    </svg>
                                </div>
                            </div>
                            <div class="flex-1">
                                <div class="w-12 h-1 rounded-full mb-4" style="background: linear-gradient(135deg, <?= $primary_color ?> 0%, <?= $secondary_color ?> 100%);"></div>
                                <p class="text-gray-700 text-lg leading-relaxed group-hover:text-gray-800 transition-colors mobile-feature-text">
                                    <?= htmlspecialchars($feature["feature_text"]) ?>
                                </p>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Platform Features Detail -->
        <?php if (!empty($platform_features_list)): ?>
        <div class="relative bg-white rounded-3xl shadow-xl p-12 mb-16 overflow-hidden">
            <!-- Decorative elements -->
            <div class="absolute top-0 left-0 w-56 h-56 bg-gradient-to-br from-indigo-50 to-transparent rounded-full -translate-y-28 -translate-x-28"></div>
            <div class="absolute bottom-0 right-0 w-48 h-48 bg-gradient-to-tl from-purple-50 to-transparent rounded-full translate-y-24 translate-x-24"></div>

            <div class="relative">
                <div class="text-center mb-12">
                    <div class="inline-flex items-center gap-2 bg-gradient-to-r from-purple-500 to-pink-600 text-white px-4 py-2 rounded-full text-sm font-semibold mb-4">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
                        </svg>
                        Fitur Unggulan
                    </div>
                    <h2 class="text-4xl font-bold text-gray-900 mb-6 bg-clip-text text-transparent mobile-platform-title" style="background: linear-gradient(135deg, <?= $primary_color ?> 0%, <?= $accent_color ?> 100%); -webkit-background-clip: text; background-clip: text;">
                        Fitur Platform Lengkap
                    </h2>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-2 gap-4 sm:gap-6 lg:gap-8">
                    <?php
                    $icon_paths = [
                        "document" =>
                            "M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z",
                        "photo" =>
                            "M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z",
                        "chat" =>
                            "M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z",
                        "announcement" =>
                            "M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z",
                        "default" => "M5 13l4 4L19 7",
                    ];

                    $color_schemes = [
                        "document" => "from-blue-500 to-indigo-600",
                        "photo" => "from-green-500 to-teal-600",
                        "chat" => "from-purple-500 to-pink-600",
                        "announcement" => "from-orange-500 to-red-600",
                        "default" => "from-gray-500 to-gray-600",
                    ];
                    ?>

                    <?php foreach (
                        $platform_features_list
                        as $index => $feature
                    ):

                        $icon_path =
                            $icon_paths[$feature["icon_name"]] ??
                            $icon_paths["default"];
                        $color_scheme =
                            $color_schemes[$feature["icon_name"]] ??
                            $color_schemes["default"];
                        ?>
                    <div class="group relative bg-white p-6 sm:p-8 rounded-2xl shadow-lg hover:shadow-xl transition-all duration-300 border border-gray-100 hover:border-purple-200 overflow-hidden">
                        <div class="flex flex-col sm:flex-row gap-4 sm:gap-6">
                            <div class="flex-shrink-0">
                                <div class="w-14 h-14 sm:w-16 sm:h-16 bg-gradient-to-br <?= $color_scheme ?> rounded-2xl flex items-center justify-center shadow-lg group-hover:scale-110 transition-all duration-300">
                                    <svg class="w-6 h-6 sm:w-8 sm:h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="<?= $icon_path ?>"/>
                                    </svg>
                                </div>
                            </div>
                            <div class="flex-1 min-w-0">
                                <div class="w-10 sm:w-12 h-1 bg-gradient-to-r <?= $color_scheme ?> rounded-full mb-3 sm:mb-4"></div>
                                <h3 class="text-xl sm:text-2xl font-bold text-gray-900 mb-2 sm:mb-3 group-hover:text-purple-900 transition-colors break-words">
                                    <?= htmlspecialchars($feature["title"]) ?>
                                </h3>
                                <p class="text-gray-600 leading-relaxed text-base sm:text-lg group-hover:text-gray-700 transition-colors break-words">
                                    <?= htmlspecialchars($feature["description"]) ?>
                                </p>
                            </div>
                        </div>
                        <div class="absolute inset-0 bg-gradient-to-br from-purple-50/50 to-pink-50/50 rounded-2xl opacity-0 group-hover:opacity-100 transition-opacity duration-300 -z-10"></div>
                    </div>
                    <?php
                    endforeach; ?>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- CTA Section -->
        <div class="relative cta-gradient rounded-3xl p-8 sm:p-12 lg:p-16 text-center text-white overflow-hidden shadow-2xl">
            <!-- Animated background elements -->
            <div class="absolute inset-0 bg-[url('data:image/svg+xml,%3Csvg width=\"60\" height=\"60\" viewBox=\"0 0 60 60\" xmlns=\"http://www.w3.org/2000/svg\"%3E%3Cg fill=\"none\" fill-rule=\"evenodd\"%3E%3Cg fill=\"%23ffffff\" fill-opacity=\"0.03\"%3E%3Ccircle cx=\"7\" cy=\"7\" r=\"1\"/%3E%3C/g%3E%3C/g%3E%3C/svg%3E')] opacity-40"></div>
            <div class="absolute top-5 sm:top-10 left-5 sm:left-10 w-36 h-36 sm:w-72 sm:h-72 bg-white/5 rounded-full blur-3xl animate-pulse"></div>
            <div class="absolute bottom-5 sm:bottom-10 right-5 sm:right-10 w-48 h-48 sm:w-96 sm:h-96 bg-purple-400/10 rounded-full blur-3xl animate-pulse delay-1000"></div>

            <div class="relative">
                <?php 
                // Debug: Tampilkan status login langsung di halaman
                // echo '<!-- Debug: isLoggedIn = ' . ($isLoggedIn ? 'true' : 'false') . ' -->';
                // echo '<!-- Debug: SESSION = ' . print_r($_SESSION, true) . ' -->';
                
                if ($isLoggedIn): ?>
                    <!-- Tampilan untuk pengguna yang sudah login -->
                    <div class="mb-6">
                        <div class="inline-flex items-center gap-2 bg-white/10 backdrop-blur-sm text-white px-6 py-3 rounded-full text-sm font-semibold mb-6">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z" />
                            </svg>
                            Halo, <?= htmlspecialchars($username) ?>!
                        </div>
                    </div>

                    <h2 class="text-4xl md:text-5xl font-bold mb-6 bg-gradient-to-r from-white via-blue-100 to-white bg-clip-text text-transparent">
                        Selamat Datang Kembali di <?= htmlspecialchars($site_name) ?>
                    </h2>
                    <p class="text-xl md:text-2xl text-blue-100 mb-12 max-w-4xl mx-auto leading-relaxed">
                        Ayo mulai eksplorasi dan berkontribusi untuk kelas Anda hari ini!
                    </p>

                    <div class="flex flex-col sm:flex-row gap-6 justify-center items-center">
                        <a href="<?= BASE_URL ?>/korti/" class="group relative bg-white text-gray-900 px-8 md:px-10 py-4 md:py-5 rounded-2xl font-bold text-lg md:text-xl hover:bg-gray-50 transition-all duration-300 shadow-lg hover:shadow-xl transform hover:-translate-y-1 w-full sm:w-auto text-center"
                           onmouseover="this.querySelector('.gradient-overlay').style.background='linear-gradient(135deg, <?= $primary_color ?> 0%, <?= $secondary_color ?> 100%)'"
                           onmouseout="this.querySelector('.gradient-overlay').style.background='linear-gradient(135deg, <?= $primary_color ?> 0%, <?= $secondary_color ?> 100%)'">
                            <span class="relative z-10">Ke Dashboard</span>
                            <div class="gradient-overlay absolute inset-0 rounded-2xl opacity-0 group-hover:opacity-100 transition-opacity duration-300" style="background: linear-gradient(135deg, <?= $primary_color ?> 0%, <?= $secondary_color ?> 100%);"></div>
                        </a>
                        <a href="<?= BASE_URL ?>/korti/create_post.php" class="bg-blue-800 hover:bg-blue-900 text-white px-8 md:px-10 py-4 md:py-5 rounded-2xl font-bold text-lg md:text-xl transition-all duration-300 w-full sm:w-auto text-center shadow-lg hover:shadow-xl transform hover:-translate-y-1">
                            Buat Postingan Baru
                        </a>
                    </div>
                <?php else: ?>
                    <!-- Tampilan untuk pengunjung yang belum login -->
                    <div class="mb-6">
                        <div class="inline-flex items-center gap-2 bg-white/10 backdrop-blur-sm text-white px-6 py-3 rounded-full text-sm font-semibold mb-6">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                            </svg>
                            Bergabung Sekarang
                        </div>
                    </div>

                    <h2 class="text-4xl md:text-5xl font-bold mb-6 bg-gradient-to-r from-white via-blue-100 to-white bg-clip-text text-transparent">
                        Siap Bergabung dengan <?= htmlspecialchars($site_name) ?>?
                    </h2>
                    <p class="text-xl md:text-2xl text-blue-100 mb-12 max-w-4xl mx-auto leading-relaxed">
                        Jadilah bagian dari komunitas <?= htmlspecialchars($site_name) ?> yang aktif dan mulai berkontribusi dalam dokumentasi kegiatan kelas kamu
                    </p>

                    <div class="flex flex-col sm:flex-row gap-4 justify-center items-center">
                        <a href="<?= BASE_URL ?>/register.php" class="bg-gradient-to-r from-slate-700 to-slate-800 hover:from-slate-800 hover:to-slate-900 text-white px-8 md:px-10 py-3 md:py-4 rounded-lg font-semibold text-base md:text-lg transition-all duration-200 w-full sm:w-auto text-center shadow-md hover:shadow-lg transform hover:-translate-y-0.5 border border-slate-600/30">
                            Daftar Sekarang
                        </a>
                        <a href="<?= BASE_URL ?>/login.php" class="bg-white/5 hover:bg-white/10 text-white/90 hover:text-white px-8 md:px-10 py-3 md:py-4 rounded-lg font-semibold text-base md:text-lg transition-all duration-200 w-full sm:w-auto text-center border border-white/10 hover:border-white/20 backdrop-blur-sm">
                            Login
                        </a>
                    </div>
                <?php endif; ?>
                
                <!-- Fitur Unggulan -->
                <div class="mt-12 flex flex-wrap items-center justify-center gap-4 sm:gap-6 text-blue-200 text-sm">
                    <div class="flex items-center gap-2 bg-white/5 backdrop-blur-sm px-4 py-2 rounded-lg">
                        <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        <span>Gratis Selamanya</span>
                    </div>
                    <div class="flex items-center gap-2 bg-white/5 backdrop-blur-sm px-4 py-2 rounded-lg">
                        <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                        </svg>
                        <span>Keamanan Terjamin</span>
                    </div>
                    <div class="flex items-center gap-2 bg-white/5 backdrop-blur-sm px-4 py-2 rounded-lg">
                        <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                        </svg>
                        <span>Performa Tinggi</span>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <?php include __DIR__ . "/../includes/footer.php"; ?>

            <!-- Enhanced Photo Modal -->
    <div id="photoModal" class="fixed inset-0 z-50 hidden overflow-y-auto items-center justify-center">
        <div class="flex items-center justify-center min-h-screen p-4">
            <!-- Backdrop -->
            <div class="fixed inset-0 bg-black/80 backdrop-blur-sm" onclick="closePhotoModal()"></div>
            
            <!-- Modal Content -->
            <div class="relative w-full max-w-4xl bg-white rounded-2xl shadow-2xl overflow-hidden transform transition-all">
                <!-- Close Button -->
                <button onclick="closePhotoModal()" class="absolute top-4 right-4 z-20 w-10 h-10 bg-white/80 hover:bg-white rounded-full flex items-center justify-center transition-all duration-300 shadow-md hover:shadow-lg">
                    <svg class="w-5 h-5 text-gray-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>

                <!-- Image Container -->
                <div class="relative bg-gray-100 p-4 sm:p-8 flex items-center justify-center">
                    <img id="modalImage" src="" alt="" class="max-w-full max-h-[70vh] object-contain rounded-lg shadow-md">
                </div>

                <!-- Info Section -->
                <div class="p-6 sm:p-8 bg-white border-t border-gray-100">
                    <div class="flex flex-col sm:flex-row items-start justify-between gap-6">
                        <div class="flex-1 min-w-0">
                            <h3 id="modalTitle" class="text-xl font-bold text-gray-900 mb-2 truncate"></h3>
                            <p id="modalDescription" class="text-gray-600 text-sm sm:text-base mb-4 line-clamp-2"></p>
                            
                            <!-- User Info -->
                            <div class="flex items-center gap-3 mt-4">
                                <div class="relative">
                                    <img id="modalUserProfilePic" src="" alt="" class="w-10 h-10 rounded-full object-cover border-2 border-white shadow-sm hidden">
                                    <div id="modalUserFallback" class="w-10 h-10 rounded-full flex items-center justify-center text-white font-bold avatar-gradient">
                                        <span id="modalUserInitial" class="text-sm"></span>
                                    </div>
                                </div>
                                <div class="min-w-0">
                                    <p id="modalAuthor" class="font-medium text-gray-900 text-sm sm:text-base truncate"></p>
                                    <div class="flex items-center text-xs text-gray-500">
                                        <svg class="w-3.5 h-3.5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                        </svg>
                                        <span id="modalDate"></span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Action Buttons -->
                        <div class="flex flex-col sm:flex-row gap-3 w-full sm:w-auto mt-4 sm:mt-0">
                            <a id="modalDownload" href="#" 
                               class="flex items-center justify-center px-4 py-2 text-white rounded-lg transition-colors text-sm font-medium shadow-sm">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                                          d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                                </svg>
                                Unduh
                            </a>
                           <button onclick="sharePhoto()" 
        class="group relative inline-flex items-center justify-center px-6 py-3 bg-white border-2 border-gray-200 text-gray-700 hover:text-gray-900 hover:border-primary-color hover:bg-primary-color/5 text-base font-semibold rounded-xl transition-all duration-300 shadow-lg hover:shadow-xl hover:scale-105 transform overflow-hidden">
    <!-- Animated background -->
    <div class="absolute inset-0 bg-gradient-to-r from-primary-color/0 via-primary-color/5 to-primary-color/0 transform -skew-x-12 -translate-x-full group-hover:translate-x-full transition-transform duration-700" 
         style="background: linear-gradient(90deg, transparent, <?= $primary_color ?>15, transparent);"></div>
    
    <!-- Button content -->
    <svg class="w-5 h-5 mr-3 text-gray-600 group-hover:text-primary-color transition-colors duration-300 group-hover:scale-110 transform" 
         fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
              d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.367 2.684 3 3 0 00-5.367-2.684z"/>
    </svg>
    <span class="relative text-gray-700 group-hover:text-primary-color font-medium transition-colors duration-300">
        Bagikan
    </span>
    
    <!-- Ripple effect -->
    <div class="absolute inset-0 overflow-hidden rounded-xl">
        <div class="ripple absolute bg-primary-color/20 rounded-full scale-0 opacity-70 group-active:scale-100 group-active:opacity-0 transition-transform duration-500"></div>
    </div>
</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        let currentPhotoData = {};

        async function openPhotoModal(data) {
            console.log('Data yang diterima oleh openPhotoModal:', JSON.stringify(data, null, 2));
            currentPhotoData = data;
            const modal = document.getElementById('photoModal');
            const modalImage = document.getElementById('modalImage');
            const modalTitle = document.getElementById('modalTitle');
            const modalAuthor = document.getElementById('modalAuthor');
            const modalDate = document.getElementById('modalDate');
            const modalDescription = document.getElementById('modalDescription');
            const modalDownload = document.getElementById('modalDownload');
            const modalUserProfilePic = document.getElementById('modalUserProfilePic');
            const modalUserFallback = document.getElementById('modalUserFallback');
            const modalUserInitial = document.getElementById('modalUserInitial');
            
            // Debug: Cek apakah elemen-elemen yang diperlukan ada
            console.log('Elemen modalUserProfilePic:', modalUserProfilePic);
            console.log('Elemen modalUserFallback:', modalUserFallback);
            console.log('Elemen modalUserInitial:', modalUserInitial);

            try {
                // Set image
                const imagePath = '<?= BASE_URL ?>/public/uploads/' + data.image;
                modalImage.src = imagePath;
                modalImage.alt = data.title || 'Foto Dokumentasi';

                // Set content
                modalTitle.textContent = data.title || 'Foto Dokumentasi';
                modalDescription.textContent = data.description || 'Dokumentasi visual kegiatan kelas.';
                
                // Format tanggal
                let formattedDate = 'Tanggal tidak tersedia';
                try {
                    // Cek format tanggal dan konversi ke objek Date
                    const dateObj = new Date(data.date || data.uploaded_at);
                    if (!isNaN(dateObj.getTime())) {
                        formattedDate = dateObj.toLocaleDateString('id-ID', {
                            day: 'numeric',
                            month: 'long',
                            year: 'numeric'
                        });
                    }
                } catch (e) {
                    console.warn('Error formatting date:', e);
                }
                modalDate.textContent = formattedDate;

                // Set download link
                modalDownload.href = imagePath;
                modalDownload.setAttribute('download', data.caption ? data.caption.replace(/\s+/g, '_').toLowerCase() + '.jpg' : 'dokumentasi.jpg');

                // Set user data
                console.log('Menampilkan data pengguna:', {
                    username: data.username,
                    user_id: data.user_id,
                    profile_pic: data.profile_pic
                });
                
                modalAuthor.textContent = data.username || 'Pengguna';
                
                // Tampilkan fallback avatar dulu
                showFallbackAvatar();
                
                // Cek apakah ada user_id dan profile_pic
                if (data.user_id) {
                    // Buat path ke foto profil
                    const profilePicPath = '<?= BASE_URL ?>/public/uploads/' + (data.profile_pic || '');
                    console.log('Mencoba memuat foto profil dari:', profilePicPath);
                    
                    // Cek apakah path tidak kosong dan bukan default.jpg
                    if (data.profile_pic && data.profile_pic !== '' && data.profile_pic !== 'default.jpg') {
                        // Buat elemen gambar baru untuk memeriksa apakah gambar ada
                        const img = new Image();
                        img.onload = function() {
                            console.log('Foto profil berhasil dimuat:', profilePicPath);
                            modalUserProfilePic.src = profilePicPath;
                            modalUserProfilePic.alt = data.username + ' profile';
                            modalUserProfilePic.classList.remove('hidden');
                            modalUserFallback.classList.add('hidden');
                            
                            // Force reflow
                            void modalUserProfilePic.offsetHeight;
                        };
                        img.onerror = function() {
                            console.warn('Gagal memuat foto profil:', profilePicPath);
                            // Tetap tampilkan fallback avatar
                        };
                        img.src = profilePicPath;
                    }
                }
                
                // Fungsi untuk menampilkan fallback avatar
                function showFallbackAvatar() {
                    console.log('Memanggil showFallbackAvatar');
                    // Pastikan elemen ada sebelum memanipulasi
                    if (!modalUserProfilePic || !modalUserFallback || !modalUserInitial) {
                        console.error('Elemen untuk menampilkan avatar tidak ditemukan:', {
                            modalUserProfilePic: !!modalUserProfilePic,
                            modalUserFallback: !!modalUserFallback,
                            modalUserInitial: !!modalUserInitial
                        });
                        return;
                    }
                    
                    try {
                        modalUserProfilePic.classList.add('hidden');
                        modalUserFallback.classList.remove('hidden');
                        const initial = (data.username || 'P').charAt(0).toUpperCase();
                        console.log('Menampilkan fallback avatar dengan inisial:', initial);
                        modalUserInitial.textContent = initial;
                        
                        // Force reflow
                        void modalUserFallback.offsetHeight;
                    } catch (error) {
                        console.error('Error dalam showFallbackAvatar:', error);
                    }
                }

                // Tampilkan modal setelah semua konten dimuat
                modal.classList.remove('hidden');
                modal.classList.add('flex');
                document.body.style.overflow = 'hidden';
                
                // Fokus ke tombol close untuk aksesibilitas
                setTimeout(() => {
                    const closeBtn = modal.querySelector('button');
                    if (closeBtn) closeBtn.focus();
                }, 100);

            } catch (error) {
                console.error('Error in openPhotoModal:', error);
                alert('Terjadi kesalahan saat memuat foto. Silakan coba lagi.');
            }
        }

        function closePhotoModal() {
            const modal = document.getElementById('photoModal');
            modal.classList.add('hidden');
            modal.classList.remove('flex');
            document.body.style.overflow = 'auto';
        }

        async function sharePhoto() {
            if (!currentPhotoData) return;
            
            const shareData = {
                title: currentPhotoData.caption || 'Foto Dokumentasi',
                text: currentPhotoData.description || 'Dokumentasi visual kegiatan kelas.',
                url: window.location.href
            };

            try {
                if (navigator.share) {
                    await navigator.share(shareData);
                } else if (navigator.clipboard) {
                    await navigator.clipboard.writeText(window.location.href);
                    showToast('Link berhasil disalin ke clipboard!');
                } else {
                    throw new Error('Web Share API not supported');
                }
            } catch (err) {
                console.error('Error sharing:', err);
                showToast('Gagal membagikan foto', 'error');
            }
        }

        function showToast(message, type = 'success') {
            // Implementasi toast notification sederhana
            const toast = document.createElement('div');
            toast.className = `fixed bottom-6 left-1/2 transform -translate-x-1/2 px-6 py-3 rounded-lg shadow-lg text-white font-medium ${
                type === 'error' ? 'bg-red-500' : 'bg-green-500'
            } z-50 animate-fade-in-up`;
            toast.textContent = message;
            document.body.appendChild(toast);
            
            // Hapus toast setelah 3 detik
            setTimeout(() => {
                toast.classList.add('opacity-0', 'transition-opacity', 'duration-300');
                setTimeout(() => toast.remove(), 300);
            }, 3000);
        }

        // Event listeners
        document.addEventListener('DOMContentLoaded', () => {
            // Close modal when clicking outside
            const modal = document.getElementById('photoModal');
            if (modal) {
                modal.addEventListener('click', function(e) {
                    if (e.target === this) closePhotoModal();
                });
            }

            // Close modal with ESC key
            document.addEventListener('keydown', function(e) {
                if (e.key === 'Escape') closePhotoModal();
            });
        });
    </script>
</body>
</html>