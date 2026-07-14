<?php
// informatics_a/admin/site_settings.php
session_start();
require_once __DIR__ . "/../src/config/db.php";
require_once __DIR__ . "/../src/config/urls.php";
require_once __DIR__ . "/../src/helpers/session.php";

// Hero Slider Management Functions
require_once __DIR__ . '/../src/helpers/hero_slider.php';

// Handle hero slider actions FIRST (before main form processing)
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['slide_action'])) {
    $slide_action = $_POST['slide_action'];

    if ($slide_action === 'add' || $slide_action === 'edit') {
        $slide_data = [
            'title' => trim($_POST['slide_title'] ?? ''),
            'subtitle' => trim($_POST['slide_subtitle'] ?? ''),
            'description' => trim($_POST['slide_description'] ?? ''),
            'button_text' => trim($_POST['slide_button_text'] ?? 'Learn More'),
            'button_url' => trim($_POST['slide_button_url'] ?? '#'),
            'slide_order' => intval($_POST['slide_order'] ?? 0),
            'is_active' => isset($_POST['slide_is_active']) ? 1 : 0,
            'autoplay_duration' => intval($_POST['slide_autoplay_duration'] ?? 5000)
        ];

        if ($slide_action === 'add') {
            if (isset($_FILES['slide_background']) && $_FILES['slide_background']['error'] === UPLOAD_ERR_OK) {
                $upload_result = upload_hero_background($_FILES['slide_background']);
                if ($upload_result['success']) {
                    $slide_data['background_image'] = $upload_result['filename'];
                    if (add_hero_slide($pdo, $slide_data)) {
                        $success = "Slide berhasil ditambahkan!";
                    } else {
                        $error = "Gagal menambahkan slide ke database";
                    }
                } else {
                    $error = $upload_result['error'];
                }
            } else {
                $error = "Background image is required for new slides";
            }
        } else {
            // Edit slide
            $slide_id = intval($_POST['slide_id']);
            if (isset($_FILES['slide_background']) && $_FILES['slide_background']['error'] === UPLOAD_ERR_OK) {
                $upload_result = upload_hero_background($_FILES['slide_background']);
                if ($upload_result['success']) {
                    $slide_data['background_image'] = $upload_result['filename'];
                } else {
                    $error = $upload_result['error'];
                }
            } else {
                $current_slide = get_hero_slide_by_id($pdo, $slide_id);
                $slide_data['background_image'] = $current_slide['background_image'];
            }

            if (!isset($error) && update_hero_slide($pdo, $slide_id, $slide_data)) {
                $success = "Slide berhasil diperbarui!";
            } else {
                $error = $error ?? "Gagal memperbarui slide";
            }
        }
    } elseif ($slide_action === 'delete') {
        $slide_id = intval($_POST['slide_id']);
        if (delete_hero_slide($pdo, $slide_id)) {
            $success = "Slide berhasil dihapus!";
        } else {
            $error = "Gagal menghapus slide";
        }
    }

    // Redirect to avoid form resubmission
    header("Location: " . $_SERVER['PHP_SELF'] . "?success=" . urlencode($success ?? '') . "&error=" . urlencode($error ?? ''));
    exit();
}

// Handle hero slider settings
$hero_autoplay = $current_settings['hero_autoplay'] ?? 'true';
$hero_transition = $current_settings['hero_transition'] ?? 'fade';
$hero_show_arrows = $current_settings['hero_show_arrows'] ?? 'true';
$hero_show_dots = $current_settings['hero_show_dots'] ?? 'true';

// Update hero slider settings
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['hero_slider_settings'])) {
    $hero_settings = [
        'hero_autoplay' => $_POST['hero_autoplay'] ?? 'true',
        'hero_transition' => $_POST['hero_transition'] ?? 'fade',
        'hero_show_arrows' => $_POST['hero_show_arrows'] ?? 'true',
        'hero_show_dots' => $_POST['hero_show_dots'] ?? 'true'
    ];

    foreach ($hero_settings as $key => $value) {
        $stmt = $pdo->prepare(
            "INSERT INTO site_settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = ?",
        );
        $stmt->execute([$key, $value, $value]);
    }

    $success = "Pengaturan hero slider berhasil disimpan!";
}

// Handle AJAX request for slide data
if (isset($_GET['get_slide_data']) && isset($_GET['slide_id'])) {
    $slide_id = intval($_GET['slide_id']);
    $slide = get_hero_slide_by_id($pdo, $slide_id);

    if ($slide) {
        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'slide' => $slide]);
    } else {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'error' => 'Slide not found']);
    }
    exit();
}

// Proteksi: hanya admin yang bisa akses
if (!isLoggedIn() || !isAdmin()) {
    header("Location: " . url('login'));
    exit();
}

// Get success/error messages from redirect
$success = $_GET['success'] ?? null;
$error = $_GET['error'] ?? null;

// Get all hero slides (refresh after potential changes)
$hero_slides = get_hero_slides($pdo);

// Handle update settings
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $site_name = trim($_POST["site_name"] ?? "Informatics A");
    $site_tagline = trim($_POST["site_tagline"] ?? "");
    $hero_title = trim($_POST["hero_title"] ?? "");
    $hero_subtitle = trim($_POST["hero_subtitle"] ?? "");
    $about_title = trim($_POST["about_title"] ?? "");
    $about_description = trim($_POST["about_description"] ?? "");
    $about_vision = trim($_POST["about_vision"] ?? "");
    $about_mission = trim($_POST["about_mission"] ?? "");
    $about_feature_1 = trim($_POST["about_feature_1"] ?? "");
    $about_feature_2 = trim($_POST["about_feature_2"] ?? "");
    $about_feature_3 = trim($_POST["about_feature_3"] ?? "");
    $about_feature_4 = trim($_POST["about_feature_4"] ?? "");
    $platform_feature_1_title = trim($_POST["platform_feature_1_title"] ?? "");
    $platform_feature_1_desc = trim($_POST["platform_feature_1_desc"] ?? "");
    $platform_feature_2_title = trim($_POST["platform_feature_2_title"] ?? "");
    $platform_feature_2_desc = trim($_POST["platform_feature_2_desc"] ?? "");
    $platform_feature_3_title = trim($_POST["platform_feature_3_title"] ?? "");
    $platform_feature_3_desc = trim($_POST["platform_feature_3_desc"] ?? "");
    $platform_feature_4_title = trim($_POST["platform_feature_4_title"] ?? "");
    $platform_feature_4_desc = trim($_POST["platform_feature_4_desc"] ?? "");
    $primary_color = trim($_POST["primary_color"] ?? "#1e3a8a");
    $secondary_color = trim($_POST["secondary_color"] ?? "#1e40af");
    $accent_color = trim($_POST["accent_color"] ?? "#ec4899");
    $success_color = trim($_POST["success_color"] ?? "#10b981");
    $warning_color = trim($_POST["warning_color"] ?? "#f59e0b");
    $danger_color = trim($_POST["danger_color"] ?? "#ef4444");
    $navbar_bg_color = trim($_POST["navbar_bg_color"] ?? "#1e3a8a");
    $navbar_font_color = trim($_POST["navbar_font_color"] ?? "#ffffff");

    // Initialize success message
    $success = "";

    // Handle navbar icon upload
    $navbar_icon = trim($_POST["navbar_icon"] ?? "public/images/logo.png");
    $navbar_icon_id = null;

    // Check if navbar_icon_id is selected from dropdown
    if (isset($_POST['navbar_icon_id']) && !empty($_POST['navbar_icon_id'])) {
        $selected_icon_id = intval($_POST['navbar_icon_id']);
        $stmt = $pdo->prepare("SELECT id, file_path FROM navbar_icons WHERE id = ? AND is_active = 1");
        $stmt->execute([$selected_icon_id]);
        $selected_icon = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($selected_icon) {
            $navbar_icon_id = $selected_icon['id'];
            $navbar_icon = $selected_icon['file_path'];
            $success .= " Logo navbar berhasil dipilih!";
        }
    }

    $footer_text = trim($_POST["footer_text"] ?? "All rights reserved. Built with ❤️ by Informatics A Team");
    $contact_email = trim($_POST["contact_email"] ?? "info@informaticsa.edu");
    $contact_instagram = trim($_POST["contact_instagram"] ?? "@informaticsa");
    $contact_phone = trim($_POST["contact_phone"] ?? "+62 812-3456-7890");
    $contact_address = trim($_POST["contact_address"] ?? "Jl. Pendidikan No. 123, Jakarta, Indonesia");
    $google_maps_embed = trim($_POST["google_maps_embed"] ?? "");

    // Simpan ke database
    $settings = [
        'site_name' => $site_name,
        'site_tagline' => $site_tagline,
        'hero_title' => $hero_title,
        'hero_subtitle' => $hero_subtitle,
        'about_title' => $about_title,
        'about_description' => $about_description,
        'about_vision' => $about_vision,
        'about_mission' => $about_mission,
        'about_feature_1' => $about_feature_1,
        'about_feature_2' => $about_feature_2,
        'about_feature_3' => $about_feature_3,
        'about_feature_4' => $about_feature_4,
        'platform_feature_1_title' => $platform_feature_1_title,
        'platform_feature_1_desc' => $platform_feature_1_desc,
        'platform_feature_2_title' => $platform_feature_2_title,
        'platform_feature_2_desc' => $platform_feature_2_desc,
        'platform_feature_3_title' => $platform_feature_3_title,
        'platform_feature_3_desc' => $platform_feature_3_desc,
        'platform_feature_4_title' => $platform_feature_4_title,
        'platform_feature_4_desc' => $platform_feature_4_desc,
        'primary_color' => $primary_color,
        'secondary_color' => $secondary_color,
        'accent_color' => $accent_color,
        'success_color' => $success_color,
        'warning_color' => $warning_color,
        'danger_color' => $danger_color,
        'navbar_bg_color' => $navbar_bg_color,
        'navbar_font_color' => $navbar_font_color,
        'navbar_icon' => $navbar_icon,
        'navbar_icon_id' => $navbar_icon_id ?: null, // Ensure null if empty
        'footer_text' => $footer_text,
        'contact_email' => $contact_email,
        'contact_instagram' => $contact_instagram,
        'contact_phone' => $contact_phone,
        'contact_address' => $contact_address,
        'google_maps_embed' => $google_maps_embed
    ];

    foreach ($settings as $key => $value) {
        $stmt = $pdo->prepare(
            "INSERT INTO site_settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = ?",
        );
        $stmt->execute([$key, $value, $value]);
    }

    // Add general success message if no specific error
    if (empty($error)) {
        if (!empty($success)) {
            $success .= " Pengaturan website berhasil disimpan!";
        } else {
            $success = "Pengaturan website berhasil disimpan!";
        }
    }
}

// Ambil settings saat ini
$stmt = $pdo->query("SELECT setting_key, setting_value FROM site_settings");
$current_settings = [];
foreach ($stmt->fetchAll() as $row) {
    $current_settings[$row['setting_key']] = $row['setting_value'];
}

// Default values
$site_name = $current_settings['site_name'] ?? 'Informatics A';
$site_tagline =
    $current_settings['site_tagline'] ??
    'Platform kolaborasi dan dokumentasi kelas Informatika terbaik';
$hero_title =
    $current_settings['hero_title'] ?? 'Selamat Datang di Informatics A';
$hero_subtitle =
    $current_settings['hero_subtitle'] ??
    'Platform kolaborasi dan dokumentasi kelas Informatika terbaik untuk berbagi kegiatan, pengumuman, dan galeri foto.';
$about_title = $current_settings['about_title'] ?? 'Tentang Informatics A';
$about_description =
    $current_settings["about_description"] ??
    "Platform digital yang dirancang khusus untuk memfasilitasi dokumentasi, berbagi informasi, dan kolaborasi antar anggota kelas Informatika A.";
$about_vision =
    $current_settings["about_vision"] ??
    "Menjadi platform terdepan dalam mendokumentasikan dan berbagi kegiatan kelas.";
$about_mission =
    $current_settings["about_mission"] ??
    "Memfasilitasi kolaborasi antar mahasiswa melalui teknologi.";
$about_feature_1 =
    $current_settings["about_feature_1"] ??
    "Interface modern dan mudah digunakan";
$about_feature_2 =
    $current_settings["about_feature_2"] ??
    "Sistem approval untuk menjaga kualitas konten";
$about_feature_3 =
    $current_settings["about_feature_3"] ??
    "Galeri foto untuk dokumentasi visual";
$about_feature_4 =
    $current_settings["about_feature_4"] ??
    "Responsive design untuk semua perangkat";
$platform_feature_1_title =
    $current_settings["platform_feature_1_title"] ?? "Posting Kegiatan";
$platform_feature_1_desc =
    $current_settings["platform_feature_1_desc"] ??
    "Bagikan kegiatan kelas dengan mudah dan cepat. Setiap postingan akan di-review oleh admin sebelum dipublikasikan.";
$platform_feature_2_title =
    $current_settings["platform_feature_2_title"] ?? "Galeri Foto";
$platform_feature_2_desc =
    $current_settings["platform_feature_2_desc"] ??
    "Dokumentasi visual kegiatan dalam satu tempat. Foto otomatis diambil dari kegiatan yang sudah di-approve.";
$platform_feature_3_title =
    $current_settings["platform_feature_3_title"] ?? "Komentar";
$platform_feature_3_desc =
    $current_settings["platform_feature_3_desc"] ??
    "Diskusi dan berikan feedback pada setiap kegiatan. Interaksi antar anggota kelas lebih mudah.";
$platform_feature_4_title =
    $current_settings["platform_feature_4_title"] ?? "Pengumuman";
$platform_feature_4_desc =
    $current_settings["platform_feature_4_desc"] ??
    "Informasi penting dari koordinator kelas dan admin. Pastikan tidak ketinggalan update terbaru.";
$primary_color = $current_settings['primary_color'] ?? '#1e3a8a';
$secondary_color = $current_settings['secondary_color'] ?? '#1e40af';
$accent_color = $current_settings['accent_color'] ?? '#ec4899';
$success_color = $current_settings['success_color'] ?? '#10b981';
$warning_color = $current_settings['warning_color'] ?? '#f59e0b';
$danger_color = $current_settings['danger_color'] ?? '#ef4444';
$navbar_bg_color = $current_settings['navbar_bg_color'] ?? '#1e3a8a';
$navbar_font_color = $current_settings['navbar_font_color'] ?? '#ffffff';

// Load available navbar icons for dropdown
$available_icons = [];
try {
    $stmt = $pdo->query("SELECT id, filename, original_filename, file_path, created_at FROM navbar_icons WHERE is_active = 1 ORDER BY sort_order, created_at DESC");
    $available_icons = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    // If navbar_icons table doesn't exist yet, use empty array
    $available_icons = [];
}
$footer_text = 
    $current_settings['footer_text'] ??
    'All rights reserved. Built with ❤️ by Informatics A Team';
$contact_email = $current_settings['contact_email'] ?? 'info@informaticsa.edu';
$contact_instagram = $current_settings['contact_instagram'] ?? '@informaticsa';
$contact_phone = $current_settings['contact_phone'] ?? '+62 812-3456-7890';
$contact_address =
    $current_settings['contact_address'] ??
    'Jl. Pendidikan No. 123, Jakarta, Indonesia';
$google_maps_embed = $current_settings['google_maps_embed'] ?? '';
$show_facebook_icon = $current_settings['show_facebook_icon'] ?? 1;
$show_twitter_icon = $current_settings['show_twitter_icon'] ?? 1;
$show_github_icon = $current_settings['show_github_icon'] ?? 1;
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Pengaturan Website - Admin Informatics A</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="<?= asset('tailwind.css') ?>" rel="stylesheet">
</head>
<body class="bg-gradient-to-br from-gray-50 to-blue-50 min-h-screen">
    <?php include __DIR__ . '/../includes/navbar.php'; ?>

    <?php include __DIR__ . '/sidebar.php'; ?>

    <!-- Header -->
    <header class="lg:ml-64 bg-gradient-to-r from-blue-900 via-blue-800 to-indigo-900 text-white py-10 px-6">
        <div class="max-w-7xl mx-auto">
            <div class="flex items-center gap-4">
                <div class="bg-white/10 backdrop-blur-sm p-3 rounded-xl">
                    <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                    </svg>
                </div>
                <div>
                    <h1 class="text-3xl font-bold mb-1">Pengaturan Website</h1>
                    <p class="text-blue-100">Customize tampilan dan konten website</p>
                </div>
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <main class="lg:ml-64 max-w-4xl mx-auto px-6 py-10">
        <?php if ($error): ?>
            <div class="mb-6 p-4 bg-red-100 border-l-4 border-red-500 text-red-700 rounded">
                <div class="flex items-center gap-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <?= htmlspecialchars($error) ?>
                </div>
            </div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="mb-6 p-4 bg-green-100 border-l-4 border-green-500 text-green-700 rounded">
                <div class="flex items-center gap-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <?= htmlspecialchars($success) ?>
                </div>
            </div>
        <?php endif; ?>

        <form action="" method="POST" class="bg-white rounded-xl shadow-md p-8 space-y-6">
            <!-- Site Identity -->
            <div class="border-b pb-6">
                <h2 class="text-2xl font-bold text-blue-900 mb-4 flex items-center gap-2">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21a4 4 0 01-4-4V5a2 2 0 012-2h4a2 2 0 012 2v12a4 4 0 01-4 4zm0 0h12a2 2 0 002-2v-4a2 2 0 00-2-2h-2.343M11 7.343l1.657-1.657a2 2 0 012.828 0l2.829 2.829a2 2 0 010 2.828l-8.486 8.485M7 17h.01"/>
                    </svg>
                    Identitas Website
                </h2>
                <div class="space-y-4">
                    <div>
                        <label for="site_name" class="block text-gray-700 font-semibold mb-2">Nama Website</label>
                        <input type="text" id="site_name" name="site_name" value="<?= htmlspecialchars(
                            $site_name,
                        ) ?>" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                    <div>
                        <label for="site_tagline" class="block text-gray-700 font-semibold mb-2">Tagline</label>
                        <textarea id="site_tagline" name="site_tagline" rows="2" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"><?= htmlspecialchars(
                            $site_tagline,
                        ) ?></textarea>
                    </div>
                </div>
            </div>

            <!-- Hero Section -->
            <div class="border-b pb-6">
                <h2 class="text-2xl font-bold text-blue-900 mb-4 flex items-center gap-2">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21a4 4 0 01-4-4V5a2 2 0 012-2h4a2 2 0 012 2v12a4 4 0 01-4 4zm0 0h12a2 2 0 002-2v-4a2 2 0 00-2-2h-2.343M11 7.343l1.657-1.657a2 2 0 012.828 0l2.829 2.829a2 2 0 010 2.828l-8.486 8.485M7 17h.01"/>
                    </svg>
                    Hero Section (Homepage)
                </h2>
                <div class="space-y-4">
                    <div>
                        <label for="hero_title" class="block text-gray-700 font-semibold mb-2">Judul Hero</label>
                        <input type="text" id="hero_title" name="hero_title" value="<?= htmlspecialchars(
                            $hero_title,
                        ) ?>" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                    <div>
                        <label for="hero_subtitle" class="block text-gray-700 font-semibold mb-2">Subtitle Hero</label>
                        <textarea id="hero_subtitle" name="hero_subtitle" rows="3" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"><?= htmlspecialchars(
                            $hero_subtitle,
                        ) ?></textarea>
                    </div>
                </div>
            </div>

            <!-- Color Theme -->
            <div class="border-b pb-6">
                <h2 class="text-2xl font-bold text-blue-900 mb-4 flex items-center gap-2">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    Halaman Tentang
                </h2>
                <div class="space-y-4">
                    <div>
                        <label for="about_title" class="block text-gray-700 font-semibold mb-2">Judul Tentang</label>
                        <input type="text" id="about_title" name="about_title" value="<?= htmlspecialchars(
                            $about_title,
                        ) ?>" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                    <div>
                        <label for="about_description" class="block text-gray-700 font-semibold mb-2">Deskripsi Tentang</label>
                        <textarea id="about_description" name="about_description" rows="4" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"><?= htmlspecialchars(
                            $about_description,
                        ) ?></textarea>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label for="about_vision" class="block text-gray-700 font-semibold mb-2">Visi</label>
                            <textarea id="about_vision" name="about_vision" rows="3" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"><?= htmlspecialchars(
                                $about_vision,
                            ) ?></textarea>
                        </div>
                        <div>
                            <label for="about_mission" class="block text-gray-700 font-semibold mb-2">Misi</label>
                            <textarea id="about_mission" name="about_mission" rows="3" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"><?= htmlspecialchars(
                                $about_mission,
                            ) ?></textarea>
                        </div>
                    </div>
                    <div>
                        <label class="block text-gray-700 font-semibold mb-3">Fitur Platform (Kenapa Memilih Platform Ini?)</label>
                        <div class="space-y-3">
                            <div>
                                <label for="about_feature_1" class="block text-gray-600 text-sm mb-1">Fitur 1</label>
                                <input type="text" id="about_feature_1" name="about_feature_1" value="<?= htmlspecialchars(
                                    $about_feature_1,
                                ) ?>" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                            </div>
                            <div>
                                <label for="about_feature_2" class="block text-gray-600 text-sm mb-1">Fitur 2</label>
                                <input type="text" id="about_feature_2" name="about_feature_2" value="<?= htmlspecialchars(
                                    $about_feature_2,
                                ) ?>" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                            </div>
                            <div>
                                <label for="about_feature_3" class="block text-gray-600 text-sm mb-1">Fitur 3</label>
                                <input type="text" id="about_feature_3" name="about_feature_3" value="<?= htmlspecialchars(
                                    $about_feature_3,
                                ) ?>" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                            </div>
                            <div>
                                <label for="about_feature_4" class="block text-gray-600 text-sm mb-1">Fitur 4</label>
                                <input type="text" id="about_feature_4" name="about_feature_4" value="<?= htmlspecialchars(
                                    $about_feature_4,
                                ) ?>" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                            </div>
                        </div>
                    </div>
                    <div>
                        <label class="block text-gray-700 font-semibold mb-3">Fitur Platform Detail (Halaman Tentang)</label>
                        <div class="space-y-4">
                            <div class="border-l-4 border-blue-500 pl-4">
                                <label for="platform_feature_1_title" class="block text-gray-600 text-sm mb-1">Fitur 1 - Judul</label>
                                <input type="text" id="platform_feature_1_title" name="platform_feature_1_title" value="<?= htmlspecialchars(
                                    $platform_feature_1_title,
                                ) ?>" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 mb-2">
                                <label for="platform_feature_1_desc" class="block text-gray-600 text-sm mb-1">Fitur 1 - Deskripsi</label>
                                <textarea id="platform_feature_1_desc" name="platform_feature_1_desc" rows="2" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"><?= htmlspecialchars(
                                    $platform_feature_1_desc,
                                ) ?></textarea>
                            </div>
                            <div class="border-l-4 border-blue-500 pl-4">
                                <label for="platform_feature_2_title" class="block text-gray-600 text-sm mb-1">Fitur 2 - Judul</label>
                                <input type="text" id="platform_feature_2_title" name="platform_feature_2_title" value="<?= htmlspecialchars(
                                    $platform_feature_2_title,
                                ) ?>" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 mb-2">
                                <label for="platform_feature_2_desc" class="block text-gray-600 text-sm mb-1">Fitur 2 - Deskripsi</label>
                                <textarea id="platform_feature_2_desc" name="platform_feature_2_desc" rows="2" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"><?= htmlspecialchars(
                                    $platform_feature_2_desc,
                                ) ?></textarea>
                            </div>
                            <div class="border-l-4 border-blue-500 pl-4">
                                <label for="platform_feature_3_title" class="block text-gray-600 text-sm mb-1">Fitur 3 - Judul</label>
                                <input type="text" id="platform_feature_3_title" name="platform_feature_3_title" value="<?= htmlspecialchars(
                                    $platform_feature_3_title,
                                ) ?>" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 mb-2">
                                <label for="platform_feature_3_desc" class="block text-gray-600 text-sm mb-1">Fitur 3 - Deskripsi</label>
                                <textarea id="platform_feature_3_desc" name="platform_feature_3_desc" rows="2" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"><?= htmlspecialchars(
                                    $platform_feature_3_desc,
                                ) ?></textarea>
                            </div>
                            <div class="border-l-4 border-blue-500 pl-4">
                                <label for="platform_feature_4_title" class="block text-gray-600 text-sm mb-1">Fitur 4 - Judul</label>
                                <input type="text" id="platform_feature_4_title" name="platform_feature_4_title" value="<?= htmlspecialchars(
                                    $platform_feature_4_title,
                                ) ?>" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 mb-2">
                                <label for="platform_feature_4_desc" class="block text-gray-600 text-sm mb-1">Fitur 4 - Deskripsi</label>
                                <textarea id="platform_feature_4_desc" name="platform_feature_4_desc" rows="2" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"><?= htmlspecialchars(
                                    $platform_feature_4_desc,
                                ) ?></textarea>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Color Theme -->
            <div class="border-b pb-6">
                <h2 class="text-2xl font-bold text-blue-900 mb-4 flex items-center gap-2">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21a4 4 0 01-4-4V5a2 2 0 012-2h4a2 2 0 012 2v12a4 4 0 01-4 4zm0 0h12a2 2 0 002-2v-4a2 2 0 00-2-2h-2.343M11 7.343l1.657-1.657a2 2 0 012.828 0l2.829 2.829a2 2 0 010 2.828l-8.486 8.485M7 17h.01"/>
                    </svg>
                    Tema Warna Lengkap
                </h2>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    <div>
                        <label for="primary_color" class="block text-gray-700 font-semibold mb-2">Warna Primer</label>
                        <div class="flex gap-2">
                            <input type="color" id="primary_color" name="primary_color" value="<?= htmlspecialchars(
                                $primary_color,
                            ) ?>" class="w-16 h-12 border border-gray-300 rounded cursor-pointer">
                            <input type="text" value="<?= htmlspecialchars(
                                $primary_color,
                            ) ?>" readonly class="flex-1 px-4 py-3 border border-gray-300 rounded-lg bg-gray-50">
                        </div>
                    </div>
                    <div>
                        <label for="secondary_color" class="block text-gray-700 font-semibold mb-2">Warna Sekunder</label>
                        <div class="flex gap-2">
                            <input type="color" id="secondary_color" name="secondary_color" value="<?= htmlspecialchars(
                                $secondary_color,
                            ) ?>" class="w-16 h-12 border border-gray-300 rounded cursor-pointer">
                            <input type="text" value="<?= htmlspecialchars(
                                $secondary_color,
                            ) ?>" readonly class="flex-1 px-4 py-3 border border-gray-300 rounded-lg bg-gray-50">
                        </div>
                    </div>
                    <div>
                        <label for="accent_color" class="block text-gray-700 font-semibold mb-2">Warna Aksen</label>
                        <div class="flex gap-2">
                            <input type="color" id="accent_color" name="accent_color" value="<?= htmlspecialchars(
                                $accent_color ?? '#ec4899',
                            ) ?>" class="w-16 h-12 border border-gray-300 rounded cursor-pointer">
                            <input type="text" value="<?= htmlspecialchars(
                                $accent_color ?? '#ec4899',
                            ) ?>" readonly class="flex-1 px-4 py-3 border border-gray-300 rounded-lg bg-gray-50">
                        </div>
                    </div>
                    <div>
                        <label for="success_color" class="block text-gray-700 font-semibold mb-2">Warna Sukses</label>
                        <div class="flex gap-2">
                            <input type="color" id="success_color" name="success_color" value="<?= htmlspecialchars(
                                $success_color ?? '#10b981',
                            ) ?>" class="w-16 h-12 border border-gray-300 rounded cursor-pointer">
                            <input type="text" value="<?= htmlspecialchars(
                                $success_color ?? '#10b981',
                            ) ?>" readonly class="flex-1 px-4 py-3 border border-gray-300 rounded-lg bg-gray-50">
                        </div>
                    </div>
                    <div>
                        <label for="warning_color" class="block text-gray-700 font-semibold mb-2">Warna Peringatan</label>
                        <div class="flex gap-2">
                            <input type="color" id="warning_color" name="warning_color" value="<?= htmlspecialchars(
                                $warning_color ?? '#f59e0b',
                            ) ?>" class="w-16 h-12 border border-gray-300 rounded cursor-pointer">
                            <input type="text" value="<?= htmlspecialchars(
                                $warning_color ?? '#f59e0b',
                            ) ?>" readonly class="flex-1 px-4 py-3 border border-gray-300 rounded-lg bg-gray-50">
                        </div>
                    </div>
                    <div>
                        <label for="danger_color" class="block text-gray-700 font-semibold mb-2">Warna Bahaya</label>
                        <div class="flex gap-2">
                            <input type="color" id="danger_color" name="danger_color" value="<?= htmlspecialchars(
                                $danger_color ?? '#ef4444',
                            ) ?>" class="w-16 h-12 border border-gray-300 rounded cursor-pointer">
                            <input type="text" value="<?= htmlspecialchars(
                                $danger_color ?? '#ef4444',
                            ) ?>" readonly class="flex-1 px-4 py-3 border border-gray-300 rounded-lg bg-gray-50">
                        </div>
                    </div>
                </div>
                <p class="text-sm text-gray-500 mt-2">⚠️ Perubahan warna akan langsung diterapkan. Jika website tidak update otomatis, Anda dapat rebuild CSS dengan menjalankan script: <code>./rebuild-css.sh</code></p>
            </div>

            <!-- Navbar Settings -->
            <div class="border-b pb-6">
                <h2 class="text-2xl font-bold text-blue-900 mb-4 flex items-center gap-2">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                    </svg>
                    Pengaturan Navbar
                </h2>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label for="navbar_bg_color" class="block text-gray-700 font-semibold mb-2">Warna Background Navbar</label>
                        <div class="flex gap-2">
                            <input type="color" id="navbar_bg_color" name="navbar_bg_color" value="<?= htmlspecialchars(
                                $navbar_bg_color,
                            ) ?>" class="w-16 h-12 border border-gray-300 rounded cursor-pointer">
                            <input type="text" value="<?= htmlspecialchars(
                                $navbar_bg_color,
                            ) ?>" readonly class="flex-1 px-4 py-3 border border-gray-300 rounded-lg bg-gray-50">
                        </div>
                    </div>
                    <div>
                        <label for="navbar_font_color" class="block text-gray-700 font-semibold mb-2">Warna Font Navbar</label>
                        <div class="flex gap-2">
                            <input type="color" id="navbar_font_color" name="navbar_font_color" value="<?= htmlspecialchars(
                                $navbar_font_color,
                            ) ?>" class="w-16 h-12 border border-gray-300 rounded cursor-pointer">
                            <input type="text" value="<?= htmlspecialchars(
                                $navbar_font_color,
                            ) ?>" readonly class="flex-1 px-4 py-3 border border-gray-300 rounded-lg bg-gray-50">
                        </div>
                    </div>
                </div>
                <p class="text-sm text-gray-500 mt-2">⚠️ Perubahan warna navbar akan langsung diterapkan ke seluruh website.</p>
            </div>

            <!-- Footer Settings -->
            <div class="border-b pb-6">
                <h2 class="text-2xl font-bold text-blue-900 mb-4 flex items-center gap-2">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 5h16a1 1 0 010 2H4a1 1 0 110-2zm0 8h16a1 1 0 010 2H4a1 1 0 010-2zm0 8h16a1 1 0 010 2H4a1 1 0 010-2z"/>
                    </svg>
                    Pengaturan Footer
                </h2>
                <div class="space-y-4">
                    <div>
                        <label for="footer_text" class="block text-gray-700 font-semibold mb-2">Teks Footer</label>
                        <input type="text" id="footer_text" name="footer_text" value="<?= htmlspecialchars(
                            $footer_text,
                        ) ?>" placeholder="All rights reserved. Built with ❤️ by Informatics A Team" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <p class="text-sm text-gray-500 mt-1">Teks yang akan ditampilkan di bagian bawah website</p>
                    </div>
                </div>
            </div>

            <!-- Navbar Icon Settings -->
            <div class="border-b pb-6">
                <h2 class="text-2xl font-bold text-blue-900 mb-4 flex items-center gap-2">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                    </svg>
                    Pengaturan Logo Navbar
                </h2>
                <div class="space-y-4">
                    <div class="flex items-center justify-between">
                        <div class="flex-1">
                            <label for="navbar_icon_select" class="block text-gray-700 font-semibold mb-2">Pilih Logo Navbar</label>
                            <select id="navbar_icon_select" name="navbar_icon_id" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                                <option value="">-- Pilih Logo --</option>
                                <?php foreach ($available_icons as $icon): ?>
                                    <option value="<?= $icon['id'] ?>" <?= ($navbar_icon_id == $icon['id']) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($icon['original_filename']) ?> (<?= date('d/m/Y', strtotime($icon['created_at'])) ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <p class="text-sm text-gray-500 mt-1">Pilih logo yang akan digunakan di navbar</p>
                        </div>
                        <div class="ml-4">
                            <a href="<?= url('admin/navbar_icons') ?>" class="inline-flex items-center gap-2 px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                                </svg>
                                Kelola Icon
                            </a>
                        </div>
                    </div>

                    <div>
                        <label class="block text-gray-700 font-semibold mb-2">Preview Logo Saat Ini</label>
                        <div class="flex items-center gap-3 p-4 border border-gray-300 rounded-lg">
                            <img src="<?= url(htmlspecialchars($navbar_icon)) ?>?v=<?= time() ?>" alt="Current Navbar Logo" class="w-12 h-12 object-cover rounded-lg border" id="navbar-logo-preview">
                            <div>
                                <span class="text-sm text-gray-600">Logo saat ini: <?= htmlspecialchars(basename($navbar_icon)) ?></span>
                                <?php if ($navbar_icon_id): ?>
                                    <br><span class="text-xs text-blue-600">ID: <?= $navbar_icon_id ?></span>
                                <?php endif; ?>
                            </div>
                        </div>
                        <p class="text-xs text-gray-500 mt-2">💡 Jika logo tidak berubah setelah disimpan, refresh halaman ini</p>
                    </div>
                </div>
                <p class="text-sm text-gray-500 mt-2">⚠️ Logo navbar akan langsung diterapkan ke seluruh website setelah disimpan.</p>
            </div>

            <!-- Contact Info -->
            <div class="pb-6">
                <h2 class="text-2xl font-bold text-blue-900 mb-4 flex items-center gap-2">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                    </svg>
                    Informasi Kontak
                </h2>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label for="contact_email" class="block text-gray-700 font-semibold mb-2">Email</label>
                        <input type="email" id="contact_email" name="contact_email" value="<?= htmlspecialchars(
                            $contact_email,
                        ) ?>" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                    <div>
                        <label for="contact_instagram" class="block text-gray-700 font-semibold mb-2">Instagram</label>
                        <input type="text" id="contact_instagram" name="contact_instagram" value="<?= htmlspecialchars(
                            $contact_instagram,
                        ) ?>" placeholder="@username" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                </div>
                <div class="mt-4">
    <label for="contact_phone" class="block text-gray-700 font-semibold mb-2">Nomor Telepon/WhatsApp</label>
    <input type="text" id="contact_phone" name="contact_phone" value="<?= htmlspecialchars(
        $contact_phone,
    ) ?>" placeholder="+62 812-3456-7890" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
</div>
<div class="mt-4">
    <label for="contact_address" class="block text-gray-700 font-semibold mb-2">Alamat/Lokasi</label>
    <textarea id="contact_address" name="contact_address" rows="3"
        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
        placeholder="Jl. Pendidikan No. 123, Jakarta, Indonesia"><?= htmlspecialchars(
            $contact_address,
        ) ?></textarea>
</div>
<div class="mt-4">
    <label for="google_maps_embed" class="block text-gray-700 font-semibold mb-2">Google Maps Embed Code</label>
    <textarea id="google_maps_embed" name="google_maps_embed" rows="4"
        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 font-mono text-sm"
        placeholder='&lt;iframe src="https://www.google.com/maps/embed?pb=..."&gt;&lt;/iframe&gt;'><?= htmlspecialchars(
            $google_maps_embed,
        ) ?></textarea>
    <p class="text-xs text-gray-500 mt-1">
        Cara dapatkan: Buka Google Maps → Share → Embed a map → Copy HTML code
    </p>
</div>
            </div>

            <!-- Submit Button -->
            <div class="flex gap-4">
                <button type="submit" class="flex-1 bg-blue-900 text-white px-6 py-3 rounded-lg font-bold hover:bg-blue-800 transition flex items-center justify-center gap-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                    </svg>
                    Simpan Pengaturan
                </button>
                <a href="/admin/dashboard.php" class="px-6 py-3 border-2 border-gray-300 rounded-lg font-bold hover:bg-gray-50 transition flex items-center justify-center gap-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                    Batal
                </a>
            </div>
        </form>
    </main>

    <!-- Footer -->
    <footer class="lg:ml-64 bg-white border-t border-gray-200 py-6 mt-12">
        <div class="max-w-7xl mx-auto px-6 text-center text-gray-600">
            &copy; <?= date("Y") ?> Informatics A. All rights reserved.
        </div>
    </footer>

    <script>
        // Update text input when color picker changes
        document.getElementById('primary_color')?.addEventListener('input', function(e) {
            e.target.nextElementSibling.value = e.target.value;
        });
        document.getElementById('secondary_color')?.addEventListener('input', function(e) {
            e.target.nextElementSibling.value = e.target.value;
        });
        document.getElementById('accent_color')?.addEventListener('input', function(e) {
            e.target.nextElementSibling.value = e.target.value;
        });
        document.getElementById('success_color')?.addEventListener('input', function(e) {
            e.target.nextElementSibling.value = e.target.value;
        });
        document.getElementById('warning_color')?.addEventListener('input', function(e) {
            e.target.nextElementSibling.value = e.target.value;
        });
        document.getElementById('danger_color')?.addEventListener('input', function(e) {
            e.target.nextElementSibling.value = e.target.value;
        });
        document.getElementById('navbar_bg_color')?.addEventListener('input', function(e) {
            e.target.nextElementSibling.value = e.target.value;
        });
        document.getElementById('navbar_font_color')?.addEventListener('input', function(e) {
            e.target.nextElementSibling.value = e.target.value;
        });

        // Handle navbar icon file upload preview
        document.getElementById('navbar_icon_file')?.addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    document.getElementById('navbar-logo-preview').src = e.target.result;
                };
                reader.readAsDataURL(file);
            }
        });
    </script>
</body>
</html>
