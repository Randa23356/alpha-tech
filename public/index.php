<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
// index.php - Halaman Utama
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
    $stmt = $pdo->query("SELECT setting_key, setting_value FROM site_settings WHERE setting_key IN ('primary_color', 'secondary_color', 'accent_color', 'success_color', 'warning_color', 'danger_color', 'site_name')");
    $settings = [];
    foreach ($stmt->fetchAll() as $row) {
        $settings[$row['setting_key']] = $row['setting_value'];
    }
    
    // Apply theme colors if they exist in the database
    $primary_color = $settings['primary_color'] ?? $primary_color;
    $secondary_color = $settings['secondary_color'] ?? $secondary_color;
    $accent_color = $settings['accent_color'] ?? $accent_color;
    $success_color = $settings['success_color'] ?? $success_color;
    $warning_color = $settings['warning_color'] ?? $warning_color;
    $danger_color = $settings['danger_color'] ?? $danger_color;
    $site_name = $settings['site_name'] ?? 'Informatics A';
} catch (Exception $e) {
    // Use default colors if database fails
    $site_name = 'Informatics A';
}
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

    // Ambil kegiatan approved - FIXED VARIABLE NAME TO AVOID OVERWRITE
    // First, let's check what's in the posts table
    $stmt = $pdo->query("SELECT id, title, status, user_id FROM posts WHERE status = 'approved' {$deletedAtCondition} ORDER BY date DESC LIMIT 3");
    $raw_posts = $stmt->fetchAll();
    error_log('=== ACTIVITIES DEBUG START ===');
    error_log('DEBUG: Raw posts count: ' . count($raw_posts));
    foreach ($raw_posts as $index => $post) {
        error_log("DEBUG: Raw post {$index}: ID={$post['id']}, Title={$post['title']}, UserID={$post['user_id']}, Status={$post['status']}");
    }

    // Now do the JOIN query
    $stmt = $pdo->query("SELECT posts.*, users.username, users.profile_pic, COALESCE(posts.thumbnail_image, posts.image) as display_image FROM posts JOIN users ON posts.user_id = users.id WHERE posts.status = 'approved' {$deletedAtCondition} ORDER BY posts.date DESC LIMIT 3");
    $activities_list = $stmt->fetchAll();

    error_log('DEBUG: Joined query result count: ' . count($activities_list));
    if (!empty($activities_list)) {
        foreach ($activities_list as $index => $activity) {
            error_log("DEBUG: Joined Activity {$index}: ID={$activity['id']}, Title={$activity['title']}, UserID={$activity['user_id']}, Username={$activity['username']}");
        }
    } else {
        error_log('DEBUG: Joined query returned NO RESULTS!');
    }

    // ADDITIONAL DEBUG: Check for duplicates in the array
    $unique_ids = [];
    $duplicates = [];
    foreach ($activities_list as $activity) {
        if (in_array($activity['id'], $unique_ids)) {
            $duplicates[] = $activity['id'];
        }
        $unique_ids[] = $activity['id'];
    }
    error_log('DEBUG: Unique activity IDs: ' . implode(', ', $unique_ids));
    error_log('DEBUG: Duplicate IDs found: ' . (empty($duplicates) ? 'NONE' : implode(', ', $duplicates)));

    // Get all images for each activity
    foreach ($activities_list as $index => $item) {
        $stmt = $pdo->prepare("SELECT image_path, image_order FROM post_images WHERE post_id = ? ORDER BY image_order");
        $stmt->execute([$item['id']]);
        $activities_list[$index]['images'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Debug: Log activities count and data
    error_log('Activities count: ' . count($activities_list));
    if (!empty($activities_list)) {
        error_log('Activities data: ' . json_encode($activities_list));
        // Add unique identifiers to help debug
        foreach ($activities_list as $index => $item) {
            $activities_list[$index]['debug_index'] = $index;
            error_log("Activity {$index}: ID={$item['id']}, Title={$item['title']}");
        }
    }
    
// Ambil konten dari database
    $stmt = $pdo->query("SELECT setting_key, setting_value FROM site_settings");
    $settings = [];
    foreach ($stmt->fetchAll() as $row) {
        $settings[$row['setting_key']] = $row['setting_value'];
    }

    // Ambil hero slides dari database
    require_once __DIR__ . '/../src/helpers/hero_slider.php';
    $hero_slides = get_hero_slides($pdo);

    // Ambil about features (Kenapa Memilih Platform Ini?)
    $stmt = $pdo->query("SELECT * FROM about_features WHERE is_active = 1 ORDER BY display_order ASC, id ASC");
    $about_features_list = $stmt->fetchAll();

    // Ambil platform features untuk features section
    $stmt = $pdo->query("SELECT * FROM platform_features WHERE is_active = 1 ORDER BY display_order ASC, id ASC");
    $platform_features_list = $stmt->fetchAll();

    // Debug: Log platform features count
    error_log('Platform features count: ' . count($platform_features_list));
    if (!empty($platform_features_list)) {
        error_log('Platform features data: ' . json_encode($platform_features_list));
    }
} catch (Exception $e) {
    $total_kegiatan = 0;
    $total_users = 0;
    $total_photos = 0;
    $activities_list = [];
    $settings = [];
    $hero_slides = [];
    $about_features_list = [];
    $platform_features_list = [];

    // Show detailed error for debugging in development
    echo "<!-- Database Error: " . htmlspecialchars($e->getMessage()) . " -->\n";
    error_log('Public index database error: ' . $e->getMessage());
}



// Load theme colors from database
$primary_color = $settings['primary_color'] ?? '#8b5cf6';
$secondary_color = $settings['secondary_color'] ?? '#7c3aed';
$accent_color = $settings['accent_color'] ?? '#ec4899';

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

// Site title dari database
$site_title = $settings['site_name'] ?? 'Informatics A';

    // Get current settings for form defaults
    $current_slider_settings = [];
    try {
        $stmt = $pdo->query("SELECT setting_key, setting_value FROM site_settings WHERE setting_key IN ('hero_autoplay', 'hero_transition', 'hero_show_arrows', 'hero_show_dots', 'hero_show_arrows_mobile', 'hero_show_arrows_tablet', 'hero_show_arrows_desktop')");
        while ($row = $stmt->fetch()) {
            $current_slider_settings[$row['setting_key']] = $row['setting_value'];
        }
    } catch (Exception $e) {
        // Use default values if query fails
        error_log('Slider settings query failed: ' . $e->getMessage());
    }

$hero_autoplay = $current_slider_settings['hero_autoplay'] ?? 'true';
$hero_transition = $current_slider_settings['hero_transition'] ?? 'fade';
$hero_show_arrows = $current_slider_settings['hero_show_arrows'] ?? 'true';
$hero_show_dots = $current_slider_settings['hero_show_dots'] ?? 'true';
$hero_show_arrows_mobile = $current_slider_settings['hero_show_arrows_mobile'] ?? 'false';
$hero_show_arrows_tablet = $current_slider_settings['hero_show_arrows_tablet'] ?? 'true';
$hero_show_arrows_desktop = $current_slider_settings['hero_show_arrows_desktop'] ?? 'true';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($site_title); ?> - Platform Kolaborasi Informatika</title>
    <link href="<?= asset('tailwind.css') ?>" rel="stylesheet">
    <link href="<?= asset('css/dynamic-theme.php') ?>" rel="stylesheet">
    <style>
        /* Card Animations */
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Card hover effects */
        .card-hover {
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .card-hover:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1), 0 8px 10px -6px rgba(0, 0, 0, 0.1);
        }

        /* Image zoom effect */
        .group:hover .group-hover\:scale-105 {
            transform: scale(1.05);
        }

        /* Truncate text with ellipsis */
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

        /* Carousel styles */
        .carousel-container {
            scroll-snap-type: x mandatory;
            scroll-behavior: smooth;
            -webkit-overflow-scrolling: touch;
        }

        .carousel-slide {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            opacity: 0;
            transition: opacity 0.5s ease-in-out;
            pointer-events: none;
        }
        
        .carousel-slide.active {
            opacity: 1;
            position: relative;
            pointer-events: auto;
        }

        /* Responsive adjustments */
        @media (max-width: 640px) {
            .carousel-dot {
                width: 6px;
                height: 6px;
            }
            .carousel-dot.active {
                width: 16px;
                border-radius: 3px;
            }
        }

        /* Modern Hero Slider Styles */
        .hero-slider {
            position: relative;
            width: 100%;
            height: 100%;
            overflow: hidden;
        }

        .slide {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            opacity: 0;
            transition: opacity 0.6s ease-in-out;
            pointer-events: none;
        }

        .slide.active {
            opacity: 1;
            pointer-events: auto;
        }

        .slide-image {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            object-fit: cover;
            z-index: 1;
        }

        .overlay {
            position: absolute;
            inset: 0;
            background: linear-gradient(to bottom right, <?= $primary_color ?>66, transparent, <?= $secondary_color ?>66);
            z-index: 2;
        }

        .overlay::after {
            content: '';
            position: absolute;
            inset: 0;
            background: linear-gradient(to top, rgba(0, 0, 0, 0.8), transparent, transparent);
            z-index: 2;
        }

        .slide-content {
            position: relative;
            height: 100%;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            text-align: center;
            padding: 0 1rem;
            z-index: 3;
            max-width: 64rem;
            margin: 0 auto;
        }

        .active .slide-content {
            animation: slideUp 0.8s ease-out;
        }

        /* Modern Badge */
        .badge {
            display: inline-block;
            padding: 0.5rem 1rem;
            border-radius: 9999px;
            background: <?= $primary_color ?>1a;
            backdrop-filter: blur(12px);
            border: 1px solid <?= $primary_color ?>33;
            color: #fff;
            font-size: 0.875rem;
            font-weight: 500;
            margin-bottom: 1.5rem;
            animation: badgeSlideIn 0.6s ease-out 0.2s both;
        }

        /* Modern Typography */
        .slide-title {
            font-size: clamp(2.5rem, 8vw, 4.5rem);
            font-weight: bold;
            color: #fff;
            text-shadow: 0 4px 12px rgba(0, 0, 0, 0.3);
            margin-bottom: 1.5rem;
            line-height: 1.1;
        }

        .title-line-1 {
            animation: titleSlideIn 0.8s ease-out 0.3s both;
        }

        .title-line-2 {
            animation: titleSlideIn 0.8s ease-out 0.4s both;
        }

        .slide-description {
            font-size: clamp(1.125rem, 3vw, 1.5rem);
            color: rgba(255, 255, 255, 0.9);
            max-width: 42rem;
            margin: 0 auto 2rem;
            line-height: 1.6;
            animation: descriptionSlideIn 0.7s ease-out 0.5s both;
        }

        /* Modern Buttons */
        .slide-buttons {
            display: flex;
            gap: 1rem;
            justify-content: center;
            flex-wrap: wrap;
            animation: buttonsSlideIn 0.8s ease-out 0.6s both;
        }

        .btn {
            padding: 0.875rem 2rem;
            font-size: 1rem;
            font-weight: 600;
            border: none;
            border-radius: 0.5rem;
            cursor: pointer;
            transition: all 0.3s ease;
            font-family: inherit;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            text-decoration: none;
        }

        .btn-primary {
            background: linear-gradient(135deg, <?= $primary_color ?>, <?= $secondary_color ?>);
            color: #fff;
            box-shadow: 0 10px 30px -10px <?= $primary_color ?>80;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 0 40px <?= $secondary_color ?>99;
        }

        .btn-outline {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(12px);
            color: #fff;
            border: 2px solid <?= $primary_color ?>4d;
        }

        .btn-outline:hover {
            background: <?= $primary_color ?>33;
            transform: translateY(-2px);
        }

        /* Modern Navigation - Combined with responsive settings */
        .nav-arrow {
            position: absolute;
            top: 50%;
            transform: translateY(-50%);
            z-index: 10;
            padding: 0.75rem;
            border-radius: 9999px;
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(12px);
            border: 1px solid <?= $primary_color ?>33;
            color: #fff;
            cursor: pointer;
            transition: all 0.3s ease;
            /* Hidden by default, shown based on responsive settings */
            display: none !important;
        }

        .nav-arrow:hover {
            background: <?= $primary_color ?>4d;
            transform: translateY(-50%) scale(1.1);
        }

        .nav-prev {
            left: 2rem;
        }

        .nav-next {
            right: 2rem;
        }

        /* Responsive Navigation Arrows - Independent control per breakpoint */
        .nav-arrow {
            display: none !important;
        }

        /* Mobile arrows (default < 641px) */
        .hero-slider[data-show-arrows-mobile="true"] .nav-arrow {
            display: block !important;
        }

        /* Tablet arrows (641px - 1024px) */
        @media (min-width: 641px) and (max-width: 1024px) {
            .hero-slider[data-show-arrows-tablet="true"] .nav-arrow {
                display: block !important;
            }
        }

        /* Desktop arrows (1025px+) */
        @media (min-width: 1025px) {
            .hero-slider[data-show-arrows-desktop="true"] .nav-arrow {
                display: block !important;
            }
        }

        /* Always hide arrows on very small screens for better UX */
        @media (max-width: 480px) {
            .nav-arrow {
                display: none !important;
            }
        }

        /* Modern Dots Navigation */
        .dots-nav {
            position: absolute;
            bottom: 2rem;
            left: 50%;
            transform: translateX(-50%);
            z-index: 10;
            display: flex;
            gap: 0.75rem;
        }

        .dot {
            width: 0.75rem;
            height: 0.75rem;
            border-radius: 9999px;
            background: rgba(255, 255, 255, 0.3);
            backdrop-filter: blur(12px);
            border: none;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .dot:hover {
            background: <?= $primary_color ?>99;
        }

        .dot.active {
            width: 3rem;
            background: <?= $primary_color ?>;
            box-shadow: 0 0 40px <?= $secondary_color ?>99;
        }

        /* Mobile dots - smaller size */
        @media (max-width: 640px) {
            .dot {
                width: 0.5rem;
                height: 0.5rem;
            }

            .dot.active {
                width: 2rem;
            }

            .dots-nav {
                gap: 0.5rem;
                bottom: 1rem;
            }
        }

        /* Extra small mobile dots - even smaller */
        @media (max-width: 480px) {
            .dot {
                width: 0.4rem;
                height: 0.4rem;
            }

            .dot.active {
                width: 1.5rem;
            }

            .dots-nav {
                gap: 0.4rem;
                bottom: 0.75rem;
            }
        }

        /* Floating Particles */
        .floating-particles {
            position: absolute;
            width: 100%;
            height: 100%;
            pointer-events: none;
            overflow: hidden;
            z-index: 1;
        }

        .particle {
            position: absolute;
            background: <?= $primary_color ?>1a;
            border-radius: 50%;
            animation: float 6s ease-in-out infinite;
        }

        /* Modern Animations */
        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes badgeSlideIn {
            from {
                opacity: 0;
                transform: translateY(20px) scale(0.9);
            }
            to {
                opacity: 1;
                transform: translateY(0) scale(1);
            }
        }

        @keyframes titleSlideIn {
            from {
                opacity: 0;
                transform: translateY(30px) scale(0.95);
                filter: blur(1px);
            }
            to {
                opacity: 1;
                transform: translateY(0) scale(1);
                filter: blur(0px);
            }
        }

        @keyframes descriptionSlideIn {
            from {
                opacity: 0;
                transform: translateX(30px);
            }
            to {
                opacity: 1;
                transform: translateX(0);
            }
        }

        @keyframes buttonsSlideIn {
            from {
                opacity: 0;
                transform: translateY(30px) scale(0.9);
            }
            to {
                opacity: 1;
                transform: translateY(0) scale(1);
            }
        }

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

        @keyframes slideInRight {
            from {
                opacity: 0;
                transform: translateX(50px);
            }
            to {
                opacity: 1;
                transform: translateX(0);
            }
        }

        @keyframes slideInLeft {
            from {
                opacity: 0;
                transform: translateX(-50px);
            }
            to {
                opacity: 1;
                transform: translateX(0);
            }
        }

        .animate-fade-in-up {
            animation: fadeInUp 0.6s ease-out forwards;
            opacity: 0;
        }

        .animate-slide-in-right {
            animation: slideInRight 0.6s ease-out forwards;
            opacity: 0;
        }

        .animate-slide-in-left {
            animation: slideInLeft 0.6s ease-out forwards;
            opacity: 0;
        }

        /* Override animations for mobile - ensure content is always visible */
        @media (max-width: 640px) {
            .animate-fade-in-up,
            .animate-slide-in-left,
            .animate-slide-in-right {
                opacity: 1 !important;
                transform: translateY(0) translateX(0) !important;
                animation: none !important;
            }

            /* Force show all content in cards - be more specific */
        }
    .bg-white .grid .group.bg-white {
        opacity: 1 !important;
        transform: none !important;
        animation: none !important;
        display: block !important;
        visibility: visible !important;
    }

    section.bg-white .grid .group {
        opacity: 1 !important;
        transform: translateY(0) !important;
        display: block !important;
        visibility: visible !important;
    }

    /* Target cards with animation classes specifically */
    .animate-slide-in-left,
    .animate-fade-in-up,
    .animate-slide-in-right {
        opacity: 1 !important;
        transform: translateY(0) translateX(0) !important;
        animation: none !important;
    }

    /* Force all grid items to be visible */
    .grid > * {
        opacity: 1 !important;
        visibility: visible !important;
        display: block !important;
    }

    /* Platform Features Section - Mobile Specific Rules */
    @media (max-width: 640px) {
        /* Target the specific features section grid */
        section.py-12.bg-white .grid.grid-cols-1 > .group {
            min-height: 220px !important;
            opacity: 1 !important;
            visibility: visible !important;
            display: block !important;
            position: relative !important;
            z-index: 1 !important;
            margin-bottom: 2rem !important;
            padding: 1.5rem !important;
            width: 100% !important;
            height: auto !important;
            background: white !important;
            border-radius: 1.5rem !important;
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1) !important;
        }

        /* Ensure cards are properly sized and visible */
        section.py-12.bg-white .grid.grid-cols-1 > .group.bg-white {
            opacity: 1 !important;
            transform: none !important;
            animation: none !important;
            display: block !important;
            visibility: visible !important;
            width: 100% !important;
            height: auto !important;
            min-height: 220px !important;
            position: relative !important;
            z-index: 10 !important;
        }

        /* Force all content in cards to be visible */
        section.py-12.bg-white .grid.grid-cols-1 > .group * {
            opacity: 1 !important;
            visibility: visible !important;
        }

        /* Ensure SVG icons are visible and properly sized on mobile */
        section.py-12.bg-white .grid.grid-cols-1 > .group svg {
            opacity: 1 !important;
            visibility: visible !important;
            display: block !important;
            width: 2rem !important;
            height: 2rem !important;
            color: white !important;
            fill: none !important;
            stroke: currentColor !important;
        }

        /* Ensure icon containers are visible and properly sized on mobile */
        section.py-12.bg-white .grid.grid-cols-1 > .group .bg-gradient-to-br {
            opacity: 1 !important;
            visibility: visible !important;
            display: flex !important;
            width: 3rem !important;
            height: 3rem !important;
            background: inherit !important;
        }

        /* Ensure SVG containers maintain proper dimensions */
        section.py-12.bg-white .grid.grid-cols-1 > .group div[class*="w-12"][class*="h-12"] {
            width: 3rem !important;
            height: 3rem !important;
            opacity: 1 !important;
            visibility: visible !important;
            display: flex !important;
        }

        /* Targeted fix for platform features SVG visibility - mobile only */
        @media (max-width: 640px) {
            /* Target only the platform features section specifically */
            section.py-12.bg-white .grid .group .bg-gradient-to-br svg {
                display: block !important;
                opacity: 1 !important;
                visibility: visible !important;
                width: 2rem !important;
                height: 2rem !important;
                color: white !important;
            }

            /* Target the icon containers in platform features */
            section.py-12.bg-white .grid .group .bg-gradient-to-br {
                display: flex !important;
                opacity: 1 !important;
                visibility: visible !important;
                width: 3rem !important;
                height: 3rem !important;
                align-items: center !important;
                justify-content: center !important;
            }

            /* Target the container divs specifically */
            section.py-12.bg-white .grid .group div[class*="w-12"][class*="h-12"],
            section.py-12.bg-white .grid .group div[class*="w-16"][class*="h-16"] {
                width: 3rem !important;
                height: 3rem !important;
                opacity: 1 !important;
                visibility: visible !important;
                display: flex !important;
            }
        }

        /* Debug numbers should be visible on mobile */
        section.py-12.bg-white .grid.grid-cols-1 > .group .bg-red-500 {
            display: block !important;
            opacity: 1 !important;
        }

        /* Force the grid container itself */
        section.py-12.bg-white .grid.grid-cols-1 {
            display: block !important;
            width: 100% !important;
        }
    }

        @media (max-width: 480px) {
            .hero-slider {
                height: 45vh !important;
            }

            .slide-content {
                padding: 0.75rem 0.25rem;
            }

            .slide-title {
                font-size: clamp(1.5rem, 7vw, 2rem);
                margin-bottom: 0.75rem;
            }

            .slide-description {
                font-size: clamp(0.8rem, 3.5vw, 0.9rem);
                margin-bottom: 1rem;
            }

            .btn {
                padding: 0.5rem 0.875rem;
                font-size: 0.75rem;
            }

            .badge {
                font-size: 0.7rem;
                padding: 0.25rem 0.5rem;
            }

            /* Extra small screen fixes for platform features SVGs */
            section.py-12.bg-white .grid .group .bg-gradient-to-br svg {
                width: 1.5rem !important;
                height: 1.5rem !important;
            }

            section.py-12.bg-white .grid .group .bg-gradient-to-br {
                width: 2.5rem !important;
                height: 2.5rem !important;
            }

            section.py-12.bg-white .grid .group div[class*="w-12"][class*="h-12"],
            section.py-12.bg-white .grid .group div[class*="w-16"][class*="h-16"] {
                width: 2.5rem !important;
                height: 2.5rem !important;
            }
        }

        @media (min-width: 1280px) {
            .scroll-indicator {
                top: 95vh;
            }
        }

        @media (min-width: 1280px) {
            .scroll-indicator {
                top: 95vh;
            }
        }

        /* Custom gradient backgrounds using theme colors */
        .hero-gradient {
            background: linear-gradient(135deg, <?= $primary_color ?> 0%, <?= $secondary_color ?> 100%);
        }

        .about-gradient {
            background: linear-gradient(135deg, <?= $primary_color ?> 0%, <?= $accent_color ?> 50%, <?= $secondary_color ?> 100%);
        }

        /* Theme-based icon gradients */
        .icon-gradient-primary {
            background: linear-gradient(135deg, <?= $primary_color ?> 0%, <?= $secondary_color ?> 100%);
        }

        .icon-gradient-secondary {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
        }

        .icon-gradient-accent {
            background: linear-gradient(135deg, <?= $accent_color ?> 0%, #db2777 100%);
        }

        /* Modern button hover effects */
        .slide-buttons.animate-in a {
            position: relative;
            overflow: hidden;
        }

        .slide-buttons.animate-in a::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, <?= $primary_color ?>33, transparent);
            transition: left 0.5s ease;
        }

        .slide-buttons.animate-in a:hover::before {
            left: 100%;
        }

        /* Enhanced icon glow effect */
        .slide-icon.animate-in {
            position: relative;
        }

        .slide-icon.animate-in::after {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            width: 120%;
            height: 120%;
            background: radial-gradient(circle, <?= $primary_color ?>4d 0%, transparent 70%);
            transform: translate(-50%, -50%);
            border-radius: 50%;
            animation: iconGlow 2s ease-in-out infinite alternate;
            z-index: -1;
        }

        @keyframes iconGlow {
            0% {
                opacity: 0;
                transform: translate(-50%, -50%) scale(0.8);
            }
            100% {
                opacity: 0.6;
                transform: translate(-50%, -50%) scale(1.2);
            }
        }

        /* Typewriter effect for titles */
        .slide-title.animate-in {
            overflow: hidden;
            white-space: nowrap;
        }

        .slide-title.animate-in span {
            display: inline-block;
            animation: typewriter 1s steps(40, end);
        }

        @keyframes typewriter {
            from {
                width: 0;
            }
            to {
                width: 100%;
            }
        }

        .hero-slide.prev-out {
            transform: translateX(-100%) scale(0.9);
            opacity: 0;
        }

        .hero-slide.next-out {
            transform: translateX(100%) scale(0.9);
            opacity: 0;
        }

        .hero-slide.fade-out {
            opacity: 0;
            transform: scale(0.95);
        }

        /* Scale transition effects */
        .hero-slide.prev-out-scale {
            transform: translateX(-50%) scale(0.8) rotateY(-10deg);
            opacity: 0;
        }

        .hero-slide.next-out-scale {
            transform: translateX(50%) scale(0.8) rotateY(10deg);
            opacity: 0;
        }

        /* Flip transition effects */
        .hero-slide.prev-out-flip {
            transform: translateX(-100%) rotateY(-90deg) scale(0.8);
            opacity: 0;
        }

        .hero-slide.next-out-flip {
            transform: translateX(100%) rotateY(90deg) scale(0.8);
            opacity: 0;
        }

        .hero-gradient {
            background: linear-gradient(135deg, <?= $primary_color ?> 0%, <?= $secondary_color ?> 100%);
        }

        .about-gradient {
            background: linear-gradient(135deg, <?= $primary_color ?> 0%, <?= $accent_color ?> 50%, <?= $secondary_color ?> 100%);
        }

        /* Theme-based icon gradients */
        .icon-gradient-primary {
            background: linear-gradient(135deg, <?= $primary_color ?> 0%, <?= $secondary_color ?> 100%);
        }

        .icon-gradient-secondary {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
        }

        .icon-gradient-accent {
            background: linear-gradient(135deg, <?= $accent_color ?> 0%, #db2777 100%);
        }

        /* Mobile Card Improvements */
        @media (max-width: 640px) {
            /* Activities cards mobile optimization */
            .grid-cols-1 > .bg-white {
                margin-bottom: 1.5rem !important;
                border-radius: 1rem !important;
            }

            /* Ensure proper card sizing on mobile */
            .grid-cols-1 .rounded-3xl {
                min-height: auto !important;
                max-width: 100% !important;
            }

            /* Better text hierarchy on mobile */
            .text-lg {
                font-size: 1.125rem !important;
                line-height: 1.4 !important;
            }

            /* Improved spacing for mobile cards */
            .p-5 {
                padding: 1.25rem !important;
            }

            /* Better meta info layout on small screens */
            .flex.items-center.gap-2 {
                gap: 0.5rem !important;
            }

            /* Ensure profile pictures don't get too small */
            .w-8.h-8 {
                width: 2rem !important;
                height: 2rem !important;
            }

            /* Better text truncation */
            .truncate {
                overflow: hidden !important;
                text-overflow: ellipsis !important;
                white-space: nowrap !important;
            }

            /* Improved line clamping for mobile */
            .line-clamp-2 {
                display: -webkit-box !important;
                -webkit-line-clamp: 2 !important;
                -webkit-box-orient: vertical !important;
                overflow: hidden !important;
            }

            .line-clamp-3 {
                display: -webkit-box !important;
                -webkit-line-clamp: 3 !important;
                -webkit-box-orient: vertical !important;
                overflow: hidden !important;
            }
        }

        /* Mobile Card Improvements */
        @media (max-width: 640px) {
            /* Activities cards mobile optimization */
            .grid-cols-1 > .bg-white {
                margin-bottom: 1.5rem !important;
                border-radius: 1rem !important;
            }

            /* Ensure proper card sizing on mobile */
            .grid-cols-1 .rounded-3xl {
                min-height: auto !important;
                max-width: 100% !important;
            }

            /* Better text hierarchy on mobile */
            .text-lg {
                font-size: 1.125rem !important;
                line-height: 1.4 !important;
            }

            /* Improved spacing for mobile cards */
            .p-5 {
                padding: 1.25rem !important;
            }

            /* Better meta info layout on small screens */
            .flex.items-center.gap-2 {
                gap: 0.5rem !important;
            }

            /* Ensure profile pictures don't get too small */
            .w-8.h-8 {
                width: 2rem !important;
                height: 2rem !important;
            }

            /* Modern Card Styles for Activities */
            .grid .animate-fade-in-up {
                animation-duration: 0.4s !important;
            }

            /* Card hover effects */
            .group:hover {
                transform: translateY(-2px) !important;
            }

            /* Image hover effects */
            .group:hover .group-hover\:scale-105 {
                transform: scale(1.05) !important;
            }

            .group:hover .group-hover\:opacity-100 {
                opacity: 1 !important;
            }

            /* Line clamping for consistent heights */
            .line-clamp-2 {
                display: -webkit-box !important;
                -webkit-line-clamp: 2 !important;
                -webkit-box-orient: vertical !important;
                overflow: hidden !important;
            }

            /* Meta info layout improvements */
            .pt-3.border-t .flex {
                align-items: center !important;
            }

            /* Profile picture sizing */
            .w-6.h-6 {
                width: 1.5rem !important;
                height: 1.5rem !important;
            }

            /* Text truncation */
            .truncate {
                overflow: hidden !important;
                text-overflow: ellipsis !important;
                white-space: nowrap !important;
            }

            /* Mobile responsiveness for activities cards */
            @media (max-width: 640px) {
                .grid.grid-cols-1.sm\\:grid-cols-2.lg\\:grid-cols-3 {
                    grid-template-columns: 1fr !important;
                    gap: 1rem !important;
                }

                .p-4.sm\\:p-5 {
                    padding: 0.75rem !important;
                }

                .text-lg {
                    font-size: 1rem !important;
                }

                .h-48 {
                    height: 10rem !important;
                }
            }

            .hero-gradient {
                min-height: 60vh !important;
            }

            .animate-fade-in-up .mb-4,
            .animate-fade-in-up .mb-6,
            .animate-fade-in-up .mb-8,
            .animate-fade-in-up .mb-10 {
                margin-bottom: 1rem !important;
            }

            .animate-fade-in-up h1 {
                font-size: clamp(1.75rem, 8vw, 2.5rem) !important;
                margin-bottom: 0.75rem !important;
            }

            .animate-fade-in-up p {
                font-size: clamp(0.875rem, 4vw, 1rem) !important;
                margin-bottom: 1.5rem !important;
                padding: 0 0.5rem;
            }

            .animate-fade-in-up .flex {
                flex-direction: column !important;
                gap: 0.75rem !important;
                margin-bottom: 1.5rem !important;
            }

            .animate-fade-in-up .group {
                padding: 0.5rem 1rem !important;
                font-size: 0.75rem !important;
            }

            /* Hide complex background elements on mobile */
            .animate-fade-in-up + div {
                display: none;
            }

            /* Mobile About Section optimizations */
            .about-gradient .grid {
                gap: 2rem !important;
            }

            .about-gradient .animate-slide-in-left,
            .about-gradient .animate-slide-in-right {
                animation-duration: 0.4s !important;
            }

            .about-gradient .animate-fade-in-up {
                animation-duration: 0.4s !important;
            }

            /* Mobile Features Section optimizations */
            .bg-white .animate-fade-in-up,
            .bg-white .animate-slide-in-left,
            .bg-white .animate-slide-in-right {
                animation-duration: 0.4s !important;
            }

            /* Ensure all feature cards are visible on mobile */
            .bg-white .grid .group {
                opacity: 1 !important;
                transform: translateY(0) !important;
                display: block !important;
                visibility: visible !important;
            }

            /* Force show all feature card content */
            .bg-white .grid .group h3,
            .bg-white .grid .group p {
                opacity: 1 !important;
                color: rgb(17 24 39) !important;
            }

            .bg-white .grid .group .bg-gradient-to-br {
                opacity: 1 !important;
                background: inherit !important;
            }
        }

        @media (max-width: 480px) {
            .hero-gradient {
                min-height: 55vh !important;
            }

            .animate-fade-in-up h1 {
                font-size: clamp(1.5rem, 7vw, 2rem) !important;
                margin-bottom: 0.5rem !important;
            }

            .animate-fade-in-up p {
                font-size: clamp(0.8rem, 3.5vw, 0.9rem) !important;
                margin-bottom: 1rem !important;
            }

            .animate-fade-in-up .flex {
                gap: 0.5rem !important;
                margin-bottom: 1rem !important;
            }

            .animate-fade-in-up .group {
                padding: 0.4rem 0.875rem !important;
                font-size: 0.7rem !important;
            }

            /* Extra small mobile About Section */
            .about-gradient .bg-white\/10 {
                padding: 1rem !important;
            }

            .about-gradient h3 {
                font-size: 1.25rem !important;
                margin-bottom: 1rem !important;
            }

            /* Ensure all feature items are visible on extra small screens */
            .about-gradient .space-y-6 .flex {
                opacity: 1 !important;
                transform: translateY(0) !important;
                display: flex !important;
                visibility: visible !important;
            }

            /* Force show all feature content on extra small screens */
            .about-gradient .space-y-6 .flex p {
                opacity: 1 !important;
                color: rgb(229 231 235) !important;
            }

            .about-gradient .space-y-6 .flex .bg-gradient-to-br {
                opacity: 1 !important;
                background: linear-gradient(to bottom right, rgb(52 211 153), rgb(34 197 94)) !important;
            }

            /* Features Section mobile fixes for extra small screens */
            .bg-white .animate-fade-in-up,
            .bg-white .animate-slide-in-left,
            .bg-white .animate-slide-in-right {
                animation-duration: 0.3s !important;
            }

            .bg-white .grid .group {
                opacity: 1 !important;
                transform: translateY(0) !important;
                display: block !important;
                visibility: visible !important;
            }

            /* Extra specific rules for extra small screens */
            .bg-white .grid .group.bg-white {
                opacity: 1 !important;
                transform: none !important;
                animation: none !important;
                display: block !important;
                visibility: visible !important;
            }

            section.bg-white .grid .group {
                opacity: 1 !important;
                transform: translateY(0) !important;
                display: block !important;
                visibility: visible !important;
            }

            /* Ensure SVG icons are visible on extra small mobile screens */
            section.py-12.bg-white .grid.grid-cols-1 > .group svg {
                opacity: 1 !important;
                visibility: visible !important;
                display: block !important;
                width: 1.5rem !important;
                height: 1.5rem !important;
            }

            /* Ensure icon containers are visible on extra small screens */
            section.py-12.bg-white .grid.grid-cols-1 > .group .bg-gradient-to-br {
                width: 2.5rem !important;
                height: 2.5rem !important;
            }

            /* Ensure SVG containers maintain dimensions on extra small screens */
            section.py-12.bg-white .grid.grid-cols-1 > .group div[class*="w-12"][class*="h-12"] {
                width: 2.5rem !important;
                height: 2.5rem !important;
            }

            /* FORCE FIX - Nuclear option for extra small screens */
            .py-12.bg-white .grid-cols-1 .group .bg-gradient-to-br svg,
            .py-12.bg-white .grid .group .bg-gradient-to-br svg {
                width: 1.5rem !important;
                height: 1.5rem !important;
            }

            .py-12.bg-white .grid-cols-1 .group .bg-gradient-to-br,
            .py-12.bg-white .grid .group .bg-gradient-to-br {
                width: 2.5rem !important;
                height: 2.5rem !important;
            }
        }

                /* Ensure text is black in features cards */
                .bg-white .grid .group h3 {
                    color: rgb(17 24 39) !important;
                }
                .bg-white .grid .group p {
                    color: rgb(55 65 81) !important;
                }

        /* Force text color for features section on mobile - more specific */
        @media (max-width: 640px) {
            .bg-white\/10 .space-y-6 .flex p {
                color: rgb(229 231 235) !important;
                font-size: 0.875rem !important;
            }

            /* Override any inline styles that might be in database content */
            .bg-white\/10 .space-y-6 .flex p * {
                color: rgb(229 231 235) !important;
            }
        }

        @media (max-width: 480px) {
            .bg-white\/10 .space-y-6 .flex p {
                color: rgb(229 231 235) !important;
                font-size: 0.8rem !important;
            }

            /* Override any inline styles that might be in database content */
            .bg-white\/10 .space-y-6 .flex p * {
                color: rgb(229 231 235) !important;
            }
        }
        /* Mobile Card Improvements - Ultra Specific and Aggressive */
        @media (max-width: 767px) {
            /* Target ALL grid containers that might contain cards */
            .grid {
                display: grid !important;
                grid-template-columns: 1fr !important;
                gap: 1.5rem !important;
            }

            /* Target activities section grid specifically */
            #activities .grid,
            section#activities .grid,
            .bg-slate-50 .grid {
                display: grid !important;
                grid-template-columns: 1fr !important;
                gap: 1.5rem !important;
            }

            /* Ensure ALL cards are full width */
            .grid > *,
            .grid article,
            .grid .bg-white,
            .grid .rounded-3xl {
                width: 100% !important;
                max-width: 100% !important;
                margin-bottom: 1.5rem !important;
                margin-left: 0 !important;
                margin-right: 0 !important;
            }

            /* Last card should not have bottom margin */
            .grid > *:last-child,
            .grid article:last-child,
            .grid .bg-white:last-child {
                margin-bottom: 0 !important;
            }

            /* Card styling improvements */
            .rounded-3xl {
                border-radius: 1.5rem !important;
            }

            /* Typography for mobile */
            .text-xl {
                font-size: 1.125rem !important;
                line-height: 1.4 !important;
            }
        }

        /* Mobile Meta Info Layout Fix */
        @media (max-width: 767px) {
            /* Force meta info container layout */
            .pt-4.border-t.border-gray-100 {
                display: flex !important;
                flex-direction: row !important;
                align-items: center !important;
                justify-content: space-between !important;
                flex-wrap: nowrap !important;
                gap: 0.75rem !important;
                padding-top: 1rem !important;
                width: 100% !important;
                min-width: 0 !important;
            }

            /* Left container (avatar + text) */
            .pt-4.border-t.border-gray-100 > div:first-child {
                display: flex !important;
                flex-direction: row !important;
                align-items: center !important;
                gap: 0.75rem !important;
                flex: 1 1 auto !important;
                min-width: 0 !important;
                flex-wrap: nowrap !important;
            }

            /* Text container (username + date) */
            .pt-4.border-t.border-gray-100 > div:first-child > div:last-child {
                display: flex !important;
                flex-direction: column !important;
                gap: 0.125rem !important;
                flex: 1 1 auto !important;
                min-width: 0 !important;
                align-self: flex-start !important;
            }

            /* Username styling */
            .pt-4.border-t.border-gray-100 .text-sm.font-medium {
                font-size: 0.875rem !important;
                font-weight: 500 !important;
                color: rgb(17 24 39) !important;
                overflow: hidden !important;
                text-overflow: ellipsis !important;
                white-space: nowrap !important;
                line-height: 1.25 !important;
                margin: 0 !important;
                padding: 0 !important;
            }

            /* Date styling */
            .pt-4.border-t.border-gray-100 .text-xs.text-gray-500 {
                font-size: 0.75rem !important;
                color: rgb(107 114 128) !important;
                line-height: 1 !important;
                margin: 0 !important;
                padding: 0 !important;
            }

            /* Right container (eye icon) */
            .pt-4.border-t.border-gray-100 > div:last-child {
                display: flex !important;
                flex-direction: row !important;
                align-items: center !important;
                gap: 0.25rem !important;
                flex-shrink: 0 !important;
                color: rgb(156 163 175) !important;
            }

            /* CAROUSEL DOTS MOBILE FIX - NUCLEAR OPTION */
            @media (max-width: 767px) {
                /* Carousel dots container */
                .carousel-container + div[class*="absolute"][class*="bottom-3"] {
                    display: flex !important;
                    position: absolute !important;
                    bottom: 0.75rem !important;
                    left: 50% !important;
                    transform: translateX(-50%) !important;
                    background-color: rgba(0, 0, 0, 0.3) !important;
                    backdrop-filter: blur(4px) !important;
                    padding: 0.25rem 0.5rem !important;
                    border-radius: 9999px !important;
                    z-index: 30 !important;
                    width: auto !important;
                    height: auto !important;
                    gap: 0.25rem !important;
                }

                /* Carousel dots */
                .carousel-dot {
                    display: inline-block !important;
                    width: 6px !important;
                    height: 6px !important;
                    margin: 0 2px !important;
                    padding: 0 !important;
                    border: none !important;
                    border-radius: 50% !important;
                    background-color: rgba(255, 255, 255, 0.6) !important;
                    cursor: pointer !important;
                    transition: all 0.3s ease !important;
                    flex-shrink: 0 !important;
                }

                /* Active dot */
                .carousel-dot.bg-white,
                .carousel-dot[class*="!bg-white"] {
                    width: 16px !important;
                    border-radius: 8px !important;
                    background-color: #ffffff !important;
                }

                /* Ensure proper spacing between dots */
                .carousel-container + div[class*="absolute"][class*="bottom-3"] {
                    gap: 0.25rem !important;
                }

                /* Make sure the dot container stays within the card */
                .relative.overflow-hidden.bg-gray-100 {
                    position: relative;
                    overflow: hidden;
                }

                /* Active state */
                .carousel-dot.bg-white,
                .carousel-dot[class*="bg-white"] {
                    background-color: rgba(255, 255, 255, 1) !important;
                }

                /* Hover state */
                .carousel-dot:hover,
                .carousel-dot[class*="hover"] {
                    background-color: rgba(255, 255, 255, 0.8) !important;
                    transform: scale(1.1) !important;
                }
            }
        }

        /* Fix View All Button Positioning */
.w-full.mt-12.flex.justify-center {
    width: 100% !important;
    display: flex !important;
    justify-content: center !important;
    margin-top: 3rem !important;
    grid-column: 1 / -1 !important;
}

/* Ensure button stays centered on all screen sizes */
.col-span-full {
    grid-column: 1 / -1 !important;
}

/* Mobile specific fixes for button positioning */
@media (max-width: 767px) {
    .w-full.mt-12.flex.justify-center {
        margin-top: 2rem !important;
        padding: 0 1rem !important;
    }
    
    .grid.grid-cols-1.md\:grid-cols-2.lg\:grid-cols-3.gap-6 {
        margin-bottom: 0 !important;
    }
}

/* Extra small screens */
@media (max-width: 480px) {
    .w-full.mt-12.flex.justify-center {
        margin-top: 1.5rem !important;
    }
    
    /* Ensure button text is properly sized on small screens */
    .w-full.mt-12.flex.justify-center a {
        font-size: 0.875rem !important;
        padding: 0.75rem 1.5rem !important;
    }
}
        </style>
</head>
<body class="bg-slate-50">
    
    <?php include __DIR__ . '/../includes/navbar.php'; ?>

    <!-- Dynamic Hero Slider Section -->
    <?php if (!empty($hero_slides)): ?>
        <section class="relative h-[45vh] sm:h-[50vh] md:h-[60vh] lg:h-[70vh] xl:h-screen flex items-center justify-center overflow-hidden lg:pt-0 sm:mt-0 bg-gradient-to-br from-blue-900 to-blue-800">
            <!-- Dark overlay for text readability -->
            <div class="absolute inset-0 bg-black bg-opacity-20 z-0"></div>
            <!-- Hero Slider Container -->
            <div class="hero-slider relative w-full h-full z-10" data-autoplay="<?= $hero_autoplay ?>" data-transition="<?= $hero_transition ?>" data-duration="5000"
                 data-show-arrows-mobile="<?= $hero_show_arrows_mobile ?>" data-show-arrows-tablet="<?= $hero_show_arrows_tablet ?>" data-show-arrows-desktop="<?= $hero_show_arrows_desktop ?>">
                <?php foreach ($hero_slides as $index => $slide): ?>
                    <div class="slide <?= $index === 0 ? 'active' : '' ?>">
                        <!-- Background Image -->
                        <img src="<?= !empty($slide['background_image']) ? upload_url('hero/' . $slide['background_image']) : '' ?>"
                             alt="Slide background"
                             class="slide-image"
                             style="background: <?= empty($slide['background_image']) ? 'linear-gradient(135deg, #1e3a8a 0%, #1e40af 100%)' : '' ?>;">

                        <!-- Modern Overlay Effects -->
                        <div class="overlay"></div>

                        <!-- Slide Content -->
                        <div class="slide-content">
                            <!-- Animated Badge -->
                            <div class="badge">Transform Your Vision</div>

                            <!-- Main Title with Modern Typography -->
                            <h1 class="slide-title">
                                <span class="inline-block" style="color: <?= htmlspecialchars($slide['title_color'] ?? '#ffffff') ?>;">
                                    <?= htmlspecialchars($slide['title']) ?>
                                </span>
                            </h1>

                            <!-- Subtitle -->
                            <?php if (!empty($slide['subtitle'])): ?>
                                <p class="slide-description" style="color: <?= htmlspecialchars($slide['subtitle_color'] ?? '#f3f4f6') ?>;">
                                    <?= htmlspecialchars($slide['subtitle']) ?>
                                </p>
                            <?php endif; ?>

                            <!-- Modern Button Group -->
                            <div class="slide-buttons">
                                <?php if (!empty($slide['button_text']) && !empty($slide['button_url'])): ?>
                                    <a href="<?= htmlspecialchars($slide['button_url']) ?>" class="btn btn-primary">
                                        <svg class="w-5 h-5 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                                        </svg>
                                        <?= htmlspecialchars($slide['button_text']) ?>
                                    </a>
                                <?php endif; ?>

                                <a href="#activities" class="btn btn-outline">
                                    <svg class="w-5 h-5 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.828 14.828a4 4 0 01-5.656 0M9 10h1m4 0h1m-7 4h12a1 1 0 011 1v7a1 1 0 01-1 1H5a1 1 0 01-1-1v-7a1 1 0 011-1z"/>
                                    </svg>
                                    Jelajahi Lebih Lanjut
                                </a>
                            </div>
                        </div>

                        <!-- Floating Particles -->
                        <div class="floating-particles hidden sm:block">
                            <div class="particle" style="left: 15%; top: 20%; animation-delay: 0s;"></div>
                            <div class="particle" style="left: 25%; top: 80%; animation-delay: 1s;"></div>
                            <div class="particle" style="left: 35%; top: 40%; animation-delay: 2s;"></div>
                            <div class="particle" style="left: 45%; top: 70%; animation-delay: 3s;"></div>
                            <div class="particle" style="left: 55%; top: 30%; animation-delay: 4s;"></div>
                            <div class="particle" style="left: 65%; top: 60%; animation-delay: 5s;"></div>
                            <div class="particle" style="left: 75%; top: 10%; animation-delay: 1.5s;"></div>
                            <div class="particle" style="left: 85%; top: 50%; animation-delay: 2.5s;"></div>
                        </div>
                    </div>
                <?php endforeach; ?>

                <!-- Modern Navigation -->
                <?php if ($hero_show_arrows === 'true' || $hero_show_arrows_mobile === 'true' || $hero_show_arrows_tablet === 'true' || $hero_show_arrows_desktop === 'true'): ?>
                    <button class="nav-arrow nav-prev" aria-label="Previous slide">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <polyline points="15 18 9 12 15 6"></polyline>
                        </svg>
                    </button>
                    <button class="nav-arrow nav-next" aria-label="Next slide">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <polyline points="9 18 15 12 9 6"></polyline>
                        </svg>
                    </button>
                <?php endif; ?>

                <!-- Modern Dots Navigation -->
                <?php if ($hero_show_dots === 'true' && count($hero_slides) > 1): ?>
                    <div class="dots-nav">
                        <?php foreach ($hero_slides as $index => $slide): ?>
                            <button class="dot <?= $index === 0 ? 'active' : '' ?>" data-slide="<?= $index ?>" aria-label="Go to slide <?= $index + 1 ?>"></button>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </section>
    <?php else: ?>
        <!-- Fallback Static Hero Section -->
        <section class="relative min-h-[60vh] sm:min-h-[70vh] md:min-h-[75vh] lg:min-h-screen flex items-center justify-center overflow-hidden hero-gradient pt-3 sm:pt-4 md:pt-6 lg:pt-0 sm:mt-0">
            <!-- Animated background elements -->
            <div class="absolute inset-0">
                <div class="absolute top-12 sm:top-16 md:top-20 left-6 sm:left-8 md:left-10 lg:left-20 w-20 h-20 sm:w-24 sm:h-24 md:w-32 md:h-32 lg:w-72 lg:h-72 bg-white/10 rounded-full blur-3xl animate-pulse"></div>
                <div class="absolute bottom-12 sm:bottom-16 md:bottom-20 right-6 sm:right-8 md:right-10 lg:right-20 w-24 h-24 sm:w-32 sm:h-32 md:w-40 md:h-40 lg:w-96 lg:h-96 bg-purple-400/20 rounded-full blur-3xl animate-pulse delay-1000"></div>
                <div class="absolute top-1/2 left-1/2 transform -translate-x-1/2 -translate-y-1/2 w-[200px] h-[200px] sm:w-[300px] sm:h-[300px] md:w-[400px] md:h-[400px] lg:w-[800px] lg:h-[800px] bg-gradient-to-r from-blue-400/20 to-purple-600/20 rounded-full blur-3xl"></div>
            </div>

            <!-- Floating elements -->
            <div class="absolute inset-0 overflow-hidden hidden sm:block">
                <div class="absolute top-1/4 left-1/4 w-1 h-1 sm:w-1.5 sm:h-1.5 md:w-2 md:h-2 bg-white/20 rounded-full animate-bounce" style="animation-delay: 0s;"></div>
                <div class="absolute top-1/3 right-1/4 w-1 h-1 sm:w-1.5 sm:h-1.5 md:w-2 md:h-2 bg-white/30 rounded-full animate-bounce" style="animation-delay: 1s;"></div>
                <div class="absolute bottom-1/4 left-1/3 w-1 h-1 sm:w-1.5 sm:h-1.5 md:w-2 md:h-2 bg-white/25 rounded-full animate-bounce" style="animation-delay: 2s;"></div>
            </div>

            <div class="relative z-10 max-w-7xl mx-auto px-4 sm:px-6 text-center">
                <div class="animate-fade-in-up">
                    <!-- Icon -->
                    <div id="hero-icon-container" class="mb-16 sm:mb-10 md:mb-10 lg:mb-10 xl:mb-10" style="display: flex !important; justify-content: center !important; margin: 0 auto !important; width: fit-content !important;">
                        <div class="relative">
                            <div class="absolute inset-0 bg-white/20 rounded-full blur-xl"></div>
                            <div class="relative w-14 h-14 sm:w-16 sm:h-16 md:w-20 md:h-20 rounded-2xl flex items-center justify-center"
                                 style="background: rgba(255,255,255,0.1); backdrop-filter: blur(8px);">
                                <svg class="w-7 h-7 sm:w-8 sm:h-8 md:w-10 md:h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                                     style="color: #ffffff;">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
                                </svg>
                            </div>
                        </div>
                    </div>

                    <!-- Main heading -->
                    <h1 class="text-3xl sm:text-4xl md:text-5xl lg:text-6xl xl:text-7xl font-bold mb-3 sm:mb-4 md:mb-6 leading-tight mt-4 sm:mt-4 md:mt-4 lg:mt-4 xl:mt-4">
                        <span class="block" style="color: #ffffff;">Selamat Datang di</span>
                        <span class="block" style="color: #ffffff;"><?php echo htmlspecialchars($site_title); ?></span>
                    </h1>

                    <!-- Subtitle -->
                    <p class="text-base sm:text-lg md:text-xl lg:text-2xl mb-6 sm:mb-8 md:mb-10 lg:mb-12 max-w-4xl mx-auto leading-relaxed px-2 sm:px-4"
                       style="color: #f3f4f6;">
                        Platform kolaborasi dan dokumentasi kelas Informatics <?php echo htmlspecialchars($site_title); ?> terbaik untuk berbagi kegiatan, pengumuman, dan galeri foto.
                    </p>

                    <!-- CTA Buttons -->
                    <div class="flex flex-col sm:flex-row gap-2 sm:gap-3 md:gap-4 justify-center items-center mb-8 sm:mb-10 md:mb-12 lg:mb-16">
                        <a href="<?= url('register') ?>" class="group relative bg-white text-gray-900 px-4 sm:px-6 md:px-8 py-2 sm:py-3 md:py-4 lg:py-4 rounded-lg sm:rounded-xl md:rounded-2xl font-bold text-sm sm:text-base md:text-lg lg:text-lg hover:bg-gray-50 transition-all duration-300 shadow-lg hover:shadow-xl transform hover:-translate-y-1">
                            <svg class="w-4 h-4 sm:w-5 sm:h-5 md:w-6 md:h-6 inline mr-1 sm:mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/>
                            </svg>
                            Mulai Sekarang
                        </a>
                        <a href="#activities" class="group relative bg-transparent border-2 border-white text-white px-4 sm:px-6 md:px-8 py-2 sm:py-3 md:py-4 lg:py-4 rounded-lg sm:rounded-xl md:rounded-2xl font-bold text-sm sm:text-base md:text-lg lg:text-lg hover:bg-white hover:text-gray-900 transition-all duration-300">
                            <svg class="w-4 h-4 sm:w-5 sm:h-5 md:w-6 md:h-6 inline mr-1 sm:mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3"/>
                            </svg>
                            Lihat Kegiatan
                        </a>
                    </div>

                    <!-- Scroll indicator -->
                    <div class="absolute mt-8 left-1/2 transform -translate-x-1/2 animate-bounce">
                        <svg class="w-4 h-4 sm:w-5 sm:h-5 md:w-6 md:h-6 text-white/80" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3"/>
                        </svg>
                    </div>
                </div>
            </div>
        </section>
    <?php endif; ?>

    <!-- Modern Stats Section -->
    <section class="py-12 sm:py-16 lg:py-24 bg-white relative overflow-hidden">
        <!-- Background decoration -->
        <div class="absolute inset-0 bg-gradient-to-br from-slate-50 via-blue-50/30 to-indigo-50/50"></div>
        <div class="absolute top-0 left-0 w-full h-full">
            <div class="absolute top-10 sm:top-20 left-5 sm:left-10 w-20 h-20 sm:w-32 sm:h-32 bg-blue-100/40 rounded-full blur-2xl"></div>
            <div class="absolute bottom-10 sm:bottom-20 right-5 sm:right-10 w-24 h-24 sm:w-40 sm:h-40 bg-indigo-100/40 rounded-full blur-2xl"></div>
        </div>

        <div class="relative max-w-7xl mx-auto px-6">
            <div class="text-center mb-16">
                <h2 class="text-4xl md:text-5xl font-bold text-gray-900 mb-4">
                    Platform dalam Angka
                </h2>
                <p class="text-xl text-gray-600 max-w-3xl mx-auto">
                    Bergabunglah dengan komunitas yang terus berkembang dan aktif
                </p>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-4 sm:gap-6 lg:gap-8">
                <!-- Kegiatan Card -->
                <div class="group relative bg-white p-4 sm:p-6 lg:p-8 rounded-3xl shadow-lg hover:shadow-2xl transition-all duration-500 card-hover border border-gray-100 overflow-hidden">
                    <div class="absolute top-0 right-0 w-24 h-24 bg-gradient-to-br from-blue-500/10 to-indigo-500/10 rounded-full -translate-y-8 translate-x-8"></div>
                    <div class="relative">
                        <div class="w-12 h-12 sm:w-16 sm:h-16 icon-gradient-primary rounded-2xl flex items-center justify-center mb-4 sm:mb-6 mx-auto group-hover:scale-110 transition-transform duration-300">
                            <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                            </svg>
                        </div>
                        <div class="text-center">
                            <div class="text-5xl font-bold text-gray-900 mb-2 stats-counter">
                                <?= number_format($total_kegiatan) ?>
                            </div>
                            <div class="text-lg font-semibold text-gray-700 mb-1">Kegiatan</div>
                            <div class="text-sm text-gray-500">Terdokumentasi</div>
                        </div>
                    </div>
                </div>

                <!-- Users Card -->
                <div class="group relative bg-white p-4 sm:p-6 lg:p-8 rounded-3xl shadow-lg hover:shadow-2xl transition-all duration-500 card-hover border border-gray-100 overflow-hidden">
                    <div class="absolute top-0 right-0 w-24 h-24 bg-gradient-to-br from-purple-500/10 to-pink-500/10 rounded-full -translate-y-8 translate-x-8"></div>
                    <div class="relative">
                        <div class="w-12 h-12 sm:w-16 sm:h-16 icon-gradient-secondary rounded-2xl flex items-center justify-center mb-4 sm:mb-6 mx-auto group-hover:scale-110 transition-transform duration-300">
                            <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z"/>
                            </svg>
                        </div>
                        <div class="text-center">
                            <div class="text-5xl font-bold text-gray-900 mb-2 stats-counter">
                                <?= number_format($total_users) ?>
                            </div>
                            <div class="text-lg font-semibold text-gray-700 mb-1">Anggota</div>
                            <div class="text-sm text-gray-500">Aktif</div>
                        </div>
                    </div>
                </div>

                <!-- Photos Card -->
                <div class="group relative bg-white p-4 sm:p-6 lg:p-8 rounded-3xl shadow-lg hover:shadow-2xl transition-all duration-500 card-hover border border-gray-100 overflow-hidden">
                    <div class="absolute top-0 right-0 w-24 h-24 bg-gradient-to-br from-emerald-500/10 to-teal-500/10 rounded-full -translate-y-8 translate-x-8"></div>
                    <div class="relative">
                        <div class="w-12 h-12 sm:w-16 sm:h-16 icon-gradient-accent rounded-2xl flex items-center justify-center mb-4 sm:mb-6 mx-auto group-hover:scale-110 transition-transform duration-300">
                            <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                            </svg>
                        </div>
                        <div class="text-center">
                            <div class="text-5xl font-bold text-gray-900 mb-2 stats-counter">
                                <?= number_format($total_photos) ?>
                            </div>
                            <div class="text-lg font-semibold text-gray-700 mb-1">Foto</div>
                            <div class="text-sm text-gray-500">Dokumentasi</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Modern Activities Section -->
<section id="activities" class="py-12 sm:py-16 lg:py-24 bg-slate-50 relative overflow-hidden">
    <!-- Background decoration -->
    <div class="absolute inset-0">
        <div class="absolute top-5 sm:top-10 right-5 sm:right-10 w-32 h-32 sm:w-64 sm:h-64 bg-blue-100/30 rounded-full blur-3xl"></div>
        <div class="absolute bottom-5 sm:bottom-10 left-5 sm:left-10 w-24 h-24 sm:w-48 sm:h-48 bg-indigo-100/30 rounded-full blur-3xl"></div>
    </div>
    
    <div class="relative max-w-7xl mx-auto px-4 sm:px-6">
        <div class="text-center mb-12 md:mb-16">
            <div class="inline-flex items-center gap-2 bg-blue-100 text-blue-800 px-4 py-2 rounded-full text-sm font-semibold mb-4">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
                </svg>
                Kegiatan Terbaru
            </div>
            <h2 class="text-3xl md:text-4xl lg:text-5xl font-bold text-gray-900 mb-4">
                Eksplorasi Kegiatan Kami
            </h2>
            <p class="text-lg text-gray-600 max-w-3xl mx-auto">
                Temukan berbagai kegiatan menarik yang telah kami selenggarakan untuk mengembangkan potensi di bidang informatika.
            </p>
        </div>

        <?php if (empty($activities_list)): ?>
            <div class="text-center py-12">
                <p class="text-gray-500">Tidak ada kegiatan tersedia saat ini.</p>
                <p class="text-gray-500 text-sm sm:text-base max-w-md mx-auto">Belum ada kegiatan yang dipublikasikan saat ini. Silakan periksa kembali nanti untuk update terbaru dari admin.</p>
            </div>
        <?php else: ?>
            <!-- Grid Container -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <?php foreach ($activities_list as $index => $item): 
                // Generate a unique ID for each card for animations
                $cardId = 'card-' . $index . '-' . uniqid();
                // Calculate the delay for staggered animations
                $animationDelay = ($index % 3) * 100;
            ?>
                <article 
                    id="<?= $cardId ?>" 
                    class="bg-white rounded-2xl shadow-md hover:shadow-xl transition-all duration-300 overflow-hidden group card-hover h-full flex flex-col transform hover:-translate-y-1"
                    style="opacity: 0; animation: fadeInUp 0.5s ease-out forwards; animation-delay: <?= $animationDelay ?>ms;"
                >
                    <a href="<?= url('activity_detail.php?id=' . $item['id']) ?>" class="block h-full flex flex-col">
                        <!-- Image Container -->
                        <div class="relative overflow-hidden bg-gray-100">
                            <?php if (!empty($item['images'])): ?>
                                <?php if (count($item['images']) == 1): ?>
                                    <!-- Single image with hover zoom -->
                                    <div class="aspect-w-16 aspect-h-9 relative">
                                        <img 
                                            src="<?= upload_url(htmlspecialchars($item['images'][0]['image_path'])) ?>"
                                            alt="<?= htmlspecialchars($item['title']) ?>"
                                            class="w-full h-full object-cover transition-transform duration-700 group-hover:scale-105"
                                            loading="lazy"
                                        >
                                        <div class="absolute inset-0 bg-gradient-to-t from-black/40 via-black/10 to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-300"></div>
                                    </div>
                                <?php else: ?>
                                    <!-- Multiple images carousel -->
                                    <div class="aspect-w-16 aspect-h-9 relative">
                                        <div class="carousel-container flex h-full w-full overflow-hidden">
                                            <?php foreach ($item['images'] as $imgIndex => $image): ?>
                                                <div class="carousel-slide w-full h-full flex-shrink-0 <?= $imgIndex === 0 ? 'active' : '' ?>">
                                                    <img 
                                                        src="<?= upload_url(htmlspecialchars($image['image_path'])) ?>"
                                                        alt="<?= htmlspecialchars($item['title']) . ' ' . ($imgIndex + 1) ?>"
                                                        class="w-full h-full object-cover"
                                                        loading="lazy"
                                                    >
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                        
                                        <div class="absolute inset-0 bg-gradient-to-t from-black/40 via-black/10 to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-300"></div>
                                        
                                        <!-- Image count badge -->
                                        <?php if (count($item['images']) > 1): ?>
                                            <div class="absolute top-3 right-3 bg-black/60 text-white text-xs px-2 py-1 rounded-full flex items-center">
                                                <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                                </svg>
                                                <span><?= count($item['images']) ?> foto</span>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                <?php endif; ?>
                            <?php else: ?>
                                <!-- Fallback image with pattern -->
                                <div class="aspect-w-16 aspect-h-9 bg-gradient-to-br from-blue-500 to-indigo-600 flex items-center justify-center relative overflow-hidden">
                                    <div class="absolute inset-0 opacity-20">
                                        <div class="absolute top-0 left-0 w-32 h-32 bg-white rounded-full -translate-x-1/2 -translate-y-1/2"></div>
                                        <div class="absolute bottom-0 right-0 w-40 h-40 bg-white rounded-full translate-x-1/3 translate-y-1/3"></div>
                                    </div>
                                    <svg class="w-12 h-12 text-white relative z-10" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                    </svg>
                                </div>
                            <?php endif; ?>
                        </div>

                        <!-- Card Content -->
                        <div class="p-5 flex-1 flex flex-col">
                            <!-- Category & Date -->
                            <div class="flex items-center justify-between mb-3">
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                    <?= isset($item['category']) && !empty($item['category']) ? htmlspecialchars($item['category']) : 'Kegiatan' ?>
                                </span>
                                <span class="text-xs text-gray-500 flex items-center">
                                    <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                    </svg>
                                    <?= date('d M Y', strtotime($item['date'])) ?>
                                </span>
                            </div>

                            <!-- Title & Excerpt -->
                            <div class="mb-4">
                                <h3 class="text-lg font-bold text-gray-900 mb-2 group-hover:text-blue-600 transition-colors duration-200 line-clamp-2 leading-tight">
                                    <?= htmlspecialchars($item['title']) ?>
                                </h3>
                                <p class="text-gray-600 text-sm leading-relaxed line-clamp-3">
                                    <?= htmlspecialchars(trim(substr($item['content'], 0, 120))) ?><?= strlen($item['content']) > 120 ? '...' : '' ?>
                                </p>
                            </div>

                            <!-- Author & Action -->
                            <div class="mt-auto pt-4 border-t border-gray-100">
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center space-x-2 sm:space-x-3">
                                        <?php if (isset($item['profile_pic']) && !empty($item['profile_pic'])): ?>
                                            <?php if (strpos($item['profile_pic'], 'http') === 0): ?>
                                                <img src="<?= htmlspecialchars($item['profile_pic']) ?>"
                                                     alt="<?= htmlspecialchars($item['username']) ?>" 
                                                     class="w-8 h-8 rounded-full object-cover border-2 border-white shadow-sm">
                                            <?php else: ?>
                                                <img src="<?= url('public/uploads/' . basename($item['profile_pic'])) ?>"
                                                     alt="<?= htmlspecialchars($item['username']) ?>"
                                                     class="w-8 h-8 rounded-full object-cover border-2 border-white shadow-sm">
                                            <?php endif; ?>
                                        <?php else: ?>
                                            <div class="w-8 h-8 flex-shrink-0 bg-gradient-to-br from-blue-500 to-purple-600 rounded-full flex items-center justify-center text-white font-medium text-sm shadow-sm">
                                                <?= strtoupper(substr($item['username'], 0, 1)) ?>
                                            </div>
                                        <?php endif; ?>
                                        <div class="min-w-0">
                                            <p class="text-sm font-medium text-gray-900 truncate">
                                                <?= htmlspecialchars($item['username']) ?>
                                            </p>
                                            <p class="text-xs text-gray-500">
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
                    </div>
                </a>
            </article>
            <?php endforeach; ?>
            </div>
            <!-- End Grid Container -->
            
            <!-- View All Button - FIXED POSITIONING -->
            <div class="w-full mt-12 flex justify-center col-span-full">
                <a 
                    href="<?= url('activities') ?>" 
                    class="inline-flex items-center justify-center gap-2 text-white px-6 py-3 rounded-xl font-medium transition-all duration-300 shadow-md hover:shadow-lg hover:-translate-y-0.5 focus:outline-none focus:ring-2 focus:ring-offset-2"
                    style="
                        opacity: 0; 
                        animation: fadeInUp 0.5s ease-out forwards; 
                        animation-delay: 300ms;
                        background: linear-gradient(135deg, <?= $primary_color ?> 0%, <?= $secondary_color ?> 100%);
                        --tw-ring-color: <?= $primary_color ?>80;
                    "
                    onmouseover="this.style.background='linear-gradient(135deg, <?= $secondary_color ?> 0%, <?= $primary_color ?> 100%)'"
                    onmouseout="this.style.background='linear-gradient(135deg, <?= $primary_color ?> 0%, <?= $secondary_color ?> 100%)'"
                >
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                    </svg>
                    Lihat Semua Kegiatan
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                    </svg>
                </a>
            </div>
        <?php endif; ?>
    </div>
</section>

    <!-- Modern Features Section -->
    <section class="py-12 sm:py-16 lg:py-24 bg-white relative overflow-hidden">
        <!-- Background decoration -->
        <div class="absolute inset-0">
            <div class="absolute top-0 left-0 w-full h-full bg-gradient-to-br from-gray-50/50 to-blue-50/30"></div>
            <div class="absolute top-10 sm:top-20 left-10 sm:left-20 w-16 h-16 sm:w-32 sm:h-32 bg-blue-100/40 rounded-full blur-2xl"></div>
            <div class="absolute bottom-10 sm:bottom-20 right-10 sm:right-20 w-20 h-20 sm:w-40 sm:h-40 bg-indigo-100/40 rounded-full blur-2xl"></div>
        </div>

        <div class="relative max-w-7xl mx-auto px-6">
            <div class="text-center mb-16 animate-fade-in-up">
                <div class="inline-flex items-center gap-2 bg-indigo-100 text-indigo-800 px-4 py-2 rounded-full text-sm font-semibold mb-4">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                    </svg>
                    Fitur Unggulan
                </div>
                <h2 class="text-4xl md:text-5xl font-bold text-gray-900 mb-6">
                    Semua yang Kamu Butuhkan
                </h2>
                <p class="text-xl text-gray-600 max-w-3xl mx-auto">
                    Platform lengkap untuk kolaborasi dan dokumentasi kelas dengan fitur modern
                </p>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-4 sm:gap-6 lg:gap-8">
                <?php if (!empty($platform_features_list)): ?>
                    <?php foreach ($platform_features_list as $index => $feature): ?>
                        <?php
                        // Map icon names to SVG paths and colors
                        $iconMap = [
                            'document' => [
                                'path' => 'M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z',
                                'gradient' => 'from-blue-500 to-indigo-600',
                                'bg' => 'from-blue-500/10 to-indigo-500/10',
                                'hover' => 'group-hover:text-blue-900'
                            ],
                            'photo' => [
                                'path' => 'M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z',
                                'gradient' => 'from-emerald-500 to-teal-600',
                                'bg' => 'from-emerald-500/10 to-teal-500/10',
                                'hover' => 'group-hover:text-emerald-900'
                            ],
                            'chat' => [
                                'path' => 'M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z',
                                'gradient' => 'from-purple-500 to-pink-600',
                                'bg' => 'from-purple-500/10 to-pink-500/10',
                                'hover' => 'group-hover:text-purple-900'
                            ],
                            'announcement' => [
                                'path' => 'M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.5-1.985 8.414-4.396M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.5-1.985 8.414-4.396',
                                'gradient' => 'from-orange-500 to-red-600',
                                'bg' => 'from-orange-500/10 to-red-500/10',
                                'hover' => 'group-hover:text-orange-900'
                            ]
                        ];

                        $iconData = $iconMap[$feature['icon_name']] ?? $iconMap['document'];
                        $animationClass = $index === 0 ? 'animate-slide-in-left' : ($index === 1 ? 'animate-fade-in-up' : 'animate-slide-in-right');
                        ?>
                        <div class="group bg-white p-4 sm:p-6 lg:p-8 rounded-3xl shadow-lg hover:shadow-2xl transition-all duration-500 card-hover border border-gray-100 relative overflow-hidden <?= $animationClass ?>">
                            <div class="absolute top-0 right-0 w-20 h-20 bg-gradient-to-br <?= $iconData['bg'] ?> rounded-full -translate-y-8 translate-x-8"></div>
                            <div class="relative">
                                <div class="w-12 h-12 sm:w-16 sm:h-16 bg-gradient-to-br <?= $iconData['gradient'] ?> rounded-2xl flex items-center justify-center mb-4 sm:mb-6 group-hover:scale-110 transition-transform duration-300">
                                    <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="<?= $iconData['path'] ?>"/>
                                    </svg>
                                </div>
                                <h3 class="text-xl font-bold text-gray-900 mb-4 <?= $iconData['hover'] ?> transition-colors">
                                    <?= htmlspecialchars($feature['title']) ?>
                                </h3>
                                <p class="text-gray-600 leading-relaxed">
                                    <?= htmlspecialchars($feature['description']) ?>
                                </p>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <!-- Jika tidak ada platform features, jangan tampilkan apa-apa -->
                <?php endif; ?>
            </div>
        </div>
    </section>

    <!-- Modern About Section -->
    <section class="py-12 sm:py-16 lg:py-24 about-gradient text-white relative overflow-hidden">
        <!-- Animated background -->
        <div class="absolute inset-0">
            <div class="absolute top-0 left-0 w-full h-full bg-[url('data:image/svg+xml,%3Csvg width=\"60\" height=\"60\" viewBox=\"0 0 60 60\" xmlns=\"http://www.w3.org/2000/svg\"%3E%3Cg fill=\"none\" fill-rule=\"evenodd\"%3E%3Cg fill=\"%23ffffff\" fill-opacity=\"0.03\"%3E%3Ccircle cx=\"7\" cy=\"7\" r=\"1\"/%3E%3C/g%3E%3C/g%3E%3C/svg%3E')] opacity-40"></div>
            <div class="absolute top-5 sm:top-10 left-5 sm:left-10 w-36 h-36 sm:w-72 sm:h-72 bg-white/5 rounded-full blur-3xl animate-pulse"></div>
            <div class="absolute bottom-5 sm:bottom-10 right-5 sm:right-10 w-48 h-48 sm:w-96 sm:h-96 bg-purple-400/10 rounded-full blur-3xl animate-pulse delay-1000"></div>
        </div>

        <div class="relative max-w-7xl mx-auto px-6">
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-16 items-center">
                <!-- Left Content -->
                <div class="animate-slide-in-left">
                    <div class="inline-flex items-center gap-2 bg-white/10 backdrop-blur-sm text-white px-4 py-2 rounded-full text-sm font-semibold mb-6">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        Tentang Platform
                    </div>

                    <h2 class="text-4xl md:text-5xl font-bold mb-6 leading-tight">
                        <?= htmlspecialchars($about_title) ?>
                    </h2>

                    <p class="text-xl text-gray-200 mb-8 leading-relaxed">
                        <?= nl2br(htmlspecialchars($about_description)) ?>
                    </p>

                    <a href="<?= url('about') ?>" class="inline-flex items-center gap-3 bg-white text-gray-900 px-8 py-4 rounded-2xl font-bold hover:bg-gray-50 transition-all duration-300 shadow-lg hover:shadow-xl transform hover:-translate-y-1">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        Pelajari Lebih Lanjut
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                        </svg>
                    </a>
                </div>

                <!-- Right Content - Features -->
                <div class="animate-slide-in-right">
                    <div class="bg-white/10 backdrop-blur-sm rounded-3xl p-8 border border-white/20">
                        <h3 class="text-2xl font-bold mb-8 text-center">Kelebihan <?= htmlspecialchars($site_name) ?>?</h3>

                        <?php if (!empty($about_features_list)): ?>
                            <div class="space-y-6">
                                <?php foreach ($about_features_list as $index => $feature): ?>
                                    <div class="flex items-start gap-4 animate-fade-in-up" style="animation-delay: <?= $index * 0.1 ?>s;">
                                        <div class="flex-shrink-0 w-8 h-8 bg-gradient-to-br from-emerald-400 to-green-500 rounded-full flex items-center justify-center mt-1">
                                            <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                            </svg>
                                        </div>
                                        <p class="text-gray-200 leading-relaxed">
                                            <?= htmlspecialchars($feature['feature_text']) ?>
                                        </p>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <div class="text-center py-8">
                                <div class="w-16 h-16 bg-white/10 rounded-full flex items-center justify-center mx-auto mb-4">
                                    <svg class="w-8 h-8 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                    </svg>
                                </div>
                                <p class="text-gray-300">Fitur akan segera ditambahkan</p>
                                <!-- Debug info for mobile -->
                                <div class="mt-4 p-2 bg-red-500/20 rounded text-xs text-red-200">
                                    Debug: about_features_list has <?= count($about_features_list) ?> items
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </section>

   

    <?php include __DIR__ . '/../includes/footer.php'; ?>

    <script>
        // Modern Hero Slider with Premium Animations
        class HeroSlider {
            constructor() {
                this.slides = document.querySelectorAll('.slide');
                this.dots = document.querySelectorAll('.dot');
                this.prevBtn = document.querySelector('.nav-prev');
                this.nextBtn = document.querySelector('.nav-next');
                this.currentSlide = 0;
                this.isAnimating = false;
                this.autoPlayInterval = null;

                console.log('HeroSlider constructor - slides:', this.slides.length, 'dots:', this.dots.length, 'arrows:', !!(this.prevBtn && this.nextBtn));

                this.init();
            }

            init() {
                if (this.slides.length <= 1) {
                    console.log('Not enough slides for slider');
                    return;
                }

                // Add click events to navigation arrows (only if they exist)
                if (this.prevBtn) this.prevBtn.addEventListener('click', () => this.prevSlide());
                if (this.nextBtn) this.nextBtn.addEventListener('click', () => this.nextSlide());

                // Add click events to dots
                this.dots.forEach((dot, index) => {
                    dot.addEventListener('click', () => this.goToSlide(index));
                });

                // Start autoplay
                this.startAutoPlay();

                // Pause autoplay on hover (only if slider exists)
                const slider = document.querySelector('.hero-slider');
                if (slider) {
                    slider.addEventListener('mouseenter', () => this.stopAutoPlay());
                    slider.addEventListener('mouseleave', () => this.startAutoPlay());
                }

                console.log('Premium Hero Slider initialized with', this.slides.length, 'slides');
            }

            async showSlide(index, direction = 'next') {
                if (this.isAnimating || index === this.currentSlide || this.slides.length === 0) {
                    console.log('Skipping transition - transitioning:', this.isAnimating, 'same slide:', index === this.currentSlide, 'no slides:', this.slides.length === 0);
                    return;
                }

                this.isAnimating = true;
                const transitionType = this.slider?.dataset.transition || 'slide';
                console.log('Using transition type:', transitionType);

                // Animate out current slide elements first
                const currentSlideElement = this.slides[this.currentSlide];
                if (currentSlideElement) {
                    const currentElements = currentSlideElement.querySelectorAll('.slide-icon, .slide-title, .slide-subtitle, .slide-description, .slide-buttons');
                    currentElements.forEach((el, index) => {
                        setTimeout(() => {
                            el.classList.add('exiting');
                        }, index * 50);
                    });
                }

                // Wait for exit animations to start
                await this.delay(200);

                // Remove all animation classes from all slides first
                this.slides.forEach(slide => {
                    slide.classList.remove('active', 'prev-out', 'next-out', 'prev-out-scale', 'next-out-scale', 'prev-out-flip', 'next-out-flip');
                });

                // Set new current slide
                this.currentSlide = index;

                // Animate in new slide
                const newSlideElement = this.slides[this.currentSlide];
                if (newSlideElement) {
                    console.log('Activating slide', this.currentSlide);
                    newSlideElement.classList.add('active');
                    this.animateSlideContent(newSlideElement);
                } else {
                    console.log('New slide element not found for index', this.currentSlide);
                }

                // Update dots
                this.updateDots();

                // Reset transition flag after animation completes
                setTimeout(() => {
                    this.isTransitioning = false;
                    console.log('Transition completed');
                }, 1000);
            }

            animateSlideContent(slideElement) {
                // Animate content elements with staggered timing
                const elements = slideElement.querySelectorAll('.slide-icon, .slide-title, .slide-subtitle, .slide-description, .slide-buttons');

                elements.forEach((el, index) => {
                    // Remove all animation classes first
                    el.classList.remove('animate-in', 'exiting');

                    // Add staggered animation delays
                    setTimeout(() => {
                        el.classList.add('animate-in');
                    }, index * 100 + 200);
                });

                // Animate background elements with parallax
                const bgElements = slideElement.querySelectorAll('.parallax-bg');
                bgElements.forEach((el, index) => {
                    el.style.transform = `translateY(${index * 15}px) scale(${1 + index * 0.1})`;
                });

                // Animate floating particles
                const particles = slideElement.querySelectorAll('.particle');
                particles.forEach((particle, index) => {
                    particle.style.animationDelay = `${index * 0.5}s`;
                    particle.style.animationDuration = `${4 + index * 2}s`;
                });
            }

            goToSlide(index) {
                if (this.isAnimating || index === this.currentSlide) return;

                this.isAnimating = true;

                // Remove active class from current slide and dot
                this.slides[this.currentSlide]?.classList.remove('active');
                this.dots[this.currentSlide]?.classList.remove('active');

                // Update current slide
                this.currentSlide = index;

                // Add active class to new slide and dot
                this.slides[this.currentSlide]?.classList.add('active');
                this.dots[this.currentSlide]?.classList.add('active');

                // Reset animation lock
                setTimeout(() => {
                    this.isAnimating = false;
                }, 600);
            }

            nextSlide() {
                console.log('Next slide clicked, current:', this.currentSlide, 'total:', this.slides.length);
                const nextIndex = (this.currentSlide + 1) % this.slides.length;
                this.goToSlide(nextIndex);
            }

            prevSlide() {
                console.log('Prev slide clicked, current:', this.currentSlide, 'total:', this.slides.length);
                const prevIndex = (this.currentSlide - 1 + this.slides.length) % this.slides.length;
                this.goToSlide(prevIndex);
            }

            startAutoPlay() {
                this.autoPlayInterval = setInterval(() => {
                    if (!this.isAnimating) {
                        this.nextSlide();
                    }
                }, 5000);
            }

            stopAutoPlay() {
                if (this.autoPlayInterval) {
                    clearInterval(this.autoPlayInterval);
                    this.autoPlayInterval = null;
                }
            }
        }

        // Initialize slider when DOM is ready
        document.addEventListener('DOMContentLoaded', function() {
            new HeroSlider();

            // Debug: Log about features count
            const featureItems = document.querySelectorAll('.about-gradient .space-y-6 .flex');
            console.log('About features found:', featureItems.length);

            // Debug: Log features section cards
            const featureCards = document.querySelectorAll('.bg-white .grid .group');
            console.log('Features section cards found:', featureCards.length);

            // Debug: Log the PHP array length if available
            <?php if (!empty($about_features_list)): ?>
                console.log('PHP about_features_list count:', <?= count($about_features_list) ?>);
            <?php else: ?>
                console.log('PHP about_features_list is empty');
            <?php endif; ?>

            // Debug: Log platform features count if available
            <?php if (!empty($platform_features_list)): ?>
                console.log('PHP platform_features_list count:', <?= count($platform_features_list) ?>);
            <?php else: ?>
                console.log('PHP platform_features_list is empty');
            <?php endif; ?>

            // Mobile debugging for platform features cards
            function debugPlatformFeatures() {
                const cards = document.querySelectorAll('section.py-12.bg-white .grid.grid-cols-1 > .group');
                console.log('Platform features cards found:', cards.length);

                if (cards.length > 0 && typeof cards.forEach === 'function') {
                    cards.forEach((card, index) => {
                        const styles = window.getComputedStyle(card);
                        console.log(`Card ${index + 1}:`, {
                            display: styles.display,
                            visibility: styles.visibility,
                            opacity: styles.opacity,
                            width: styles.width,
                            height: styles.height,
                            position: styles.position,
                            zIndex: styles.zIndex,
                            transform: styles.transform,
                            top: styles.top,
                            left: styles.left,
                            marginBottom: styles.marginBottom,
                            padding: styles.padding,
                            backgroundColor: styles.backgroundColor,
                            borderRadius: styles.borderRadius,
                            boxShadow: styles.boxShadow
                        });

                        // Check SVG elements specifically
                        const svgs = card.querySelectorAll('svg');
                        console.log(`Card ${index + 1} SVGs found:`, svgs.length);
                        svgs.forEach((svg, svgIndex) => {
                            const svgStyles = window.getComputedStyle(svg);
                            console.log(`Card ${index + 1} SVG ${svgIndex + 1}:`, {
                                display: svgStyles.display,
                                visibility: svgStyles.visibility,
                                opacity: svgStyles.opacity,
                                width: svgStyles.width,
                                height: svgStyles.height,
                                color: svgStyles.color
                            });
                        });

                        // Check icon containers
                        const iconContainers = card.querySelectorAll('.bg-gradient-to-br');
                        console.log(`Card ${index + 1} icon containers found:`, iconContainers.length);
                        iconContainers.forEach((container, containerIndex) => {
                            const containerStyles = window.getComputedStyle(container);
                            console.log(`Card ${index + 1} icon container ${containerIndex + 1}:`, {
                                display: containerStyles.display,
                                visibility: containerStyles.visibility,
                                opacity: containerStyles.opacity,
                                width: containerStyles.width,
                                height: containerStyles.height,
                                background: containerStyles.background
                            });
                        });
                    });

                    // Check if cards are actually visible in viewport
                    const rects = Array.from(cards).map(card => card.getBoundingClientRect());
                    rects.forEach((rect, index) => {
                        console.log(`Card ${index + 1} bounding rect:`, {
                            top: rect.top,
                            left: rect.left,
                            width: rect.width,
                            height: rect.height,
                            bottom: rect.bottom,
                            right: rect.right
                        });
                    });
                } else {
                    console.log('No cards found or cards is not iterable');
                }
            }

            // Run debug after page load
            setTimeout(debugPlatformFeatures, 1000);

            // Also run on resize/orientation change
            window.addEventListener('resize', debugPlatformFeatures);
            window.addEventListener('orientationchange', debugPlatformFeatures);
        });
    </script>
    
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
                            const card = container.closest('[id^="card-"]');
                            if (card) {
                                changeSlide(dot, card.id);
                            }
                        }
                    }, 5000); // Change slide every 5 seconds
                }
            });
        }

        // Initialize carousels when DOM is loaded
        document.addEventListener('DOMContentLoaded', function() {
            // Start auto-advance for all carousels
            setupCarouselAutoAdvance();
            
            // Add click handlers for dots
            document.querySelectorAll('.carousel-dot').forEach(dot => {
                dot.addEventListener('click', function(e) {
                    e.preventDefault();
                    const card = this.closest('[id^="card-"]');
                    if (card) {
                        changeSlide(this, card.id);
                    }
                });
            });
        });
    </script>
</body>
</html>