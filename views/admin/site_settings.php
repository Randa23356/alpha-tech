<?php
// informatics_a/admin/site_settings.php - Enhanced Theme Management
require_once __DIR__ . "/../src/helpers/session.php";
require_once __DIR__ . "/../src/config/db.php";

// Proteksi: hanya admin yang bisa akses
if (!isLoggedIn() || !isAdmin()) {
    header("Location: /login.php");
    exit();
}

$success = null;
$active_tab = $_GET['tab'] ?? 'basic';

// Handle form submissions
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $action = $_POST['action'] ?? '';

    if ($action === 'update_basic') {
        // Basic settings
        $site_name = trim($_POST["site_name"] ?? "Informatics A");
        $site_tagline = trim($_POST["site_tagline"] ?? "");
        $footer_text = trim($_POST["footer_text"] ?? "");
        $contact_email = trim($_POST["contact_email"] ?? "");
        $contact_instagram = trim($_POST["contact_instagram"] ?? "");
        $contact_phone = trim($_POST["contact_phone"] ?? "");
        $contact_address = trim($_POST["contact_address"] ?? "");

        $settings = [
            'site_name' => $site_name,
            'site_tagline' => $site_tagline,
            'footer_text' => $footer_text,
            'contact_email' => $contact_email,
            'contact_instagram' => $contact_instagram,
            'contact_phone' => $contact_phone,
            'contact_address' => $contact_address
        ];

        foreach ($settings as $key => $value) {
            $stmt = $pdo->prepare("INSERT INTO site_settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = ?");
            $stmt->execute([$key, $value, $value]);
        }

        $success = "Pengaturan dasar berhasil disimpan!";

    } elseif ($action === 'update_theme') {
        // Theme settings
        $primary_500 = trim($_POST["primary_500"] ?? "#6366f1");
        $primary_600 = trim($_POST["primary_600"] ?? "#4f46e5");
        $primary_700 = trim($_POST["primary_700"] ?? "#4338ca");
        $secondary_500 = trim($_POST["secondary_500"] ?? "#a855f7");
        $secondary_600 = trim($_POST["secondary_600"] ?? "#9333ea");
        $secondary_700 = trim($_POST["secondary_700"] ?? "#7c3aed");
        $accent_500 = trim($_POST["accent_500"] ?? "#ec4899");
        $accent_600 = trim($_POST["accent_600"] ?? "#db2777");
        $accent_700 = trim($_POST["accent_700"] ?? "#be185d");
        $hero_gradient_start = trim($_POST["hero_gradient_start"] ?? "#4f46e5");
        $hero_gradient_via = trim($_POST["hero_gradient_via"] ?? "#7c3aed");
        $hero_gradient_end = trim($_POST["hero_gradient_end"] ?? "#ec4899");

        $theme_settings = [
            'primary_500' => $primary_500,
            'primary_600' => $primary_600,
            'primary_700' => $primary_700,
            'secondary_500' => $secondary_500,
            'secondary_600' => $secondary_600,
            'secondary_700' => $secondary_700,
            'accent_500' => $accent_500,
            'accent_600' => $accent_600,
            'accent_700' => $accent_700,
            'hero_gradient_start' => $hero_gradient_start,
            'hero_gradient_via' => $hero_gradient_via,
            'hero_gradient_end' => $hero_gradient_end
        ];

        foreach ($theme_settings as $key => $value) {
            $stmt = $pdo->prepare("INSERT INTO site_settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = ?");
            $stmt->execute([$key, $value, $value]);
        }

        $success = "Pengaturan tema berhasil disimpan!";
    }
}

// Ambil semua settings
$stmt = $pdo->query("SELECT setting_key, setting_value FROM site_settings");
$current_settings = [];
foreach ($stmt->fetchAll() as $row) {
    $current_settings[$row['setting_key']] = $row['setting_value'];
}

// Default values
$site_name = $current_settings['site_name'] ?? 'Informatics A';
$site_tagline = $current_settings['site_tagline'] ?? 'Platform kolaborasi dan dokumentasi kelas Informatika terbaik';
$footer_text = $current_settings['footer_text'] ?? 'All rights reserved. Built with ❤️ by Informatics A Team';
$contact_email = $current_settings['contact_email'] ?? 'info@informaticsa.edu';
$contact_instagram = $current_settings['contact_instagram'] ?? '@informaticsa';
$contact_phone = $current_settings['contact_phone'] ?? '+62 812-3456-7890';
$contact_address = $current_settings['contact_address'] ?? 'Jl. Pendidikan No. 123, Jakarta, Indonesia';

// Theme defaults
$primary_500 = $current_settings['primary_500'] ?? '#6366f1';
$primary_600 = $current_settings['primary_600'] ?? '#4f46e5';
$primary_700 = $current_settings['primary_700'] ?? '#4338ca';
$secondary_500 = $current_settings['secondary_500'] ?? '#a855f7';
$secondary_600 = $current_settings['secondary_600'] ?? '#9333ea';
$secondary_700 = $current_settings['secondary_700'] ?? '#7c3aed';
$accent_500 = $current_settings['accent_500'] ?? '#ec4899';
$accent_600 = $current_settings['accent_600'] ?? '#db2777';
$accent_700 = $current_settings['accent_700'] ?? '#be185d';
$hero_gradient_start = $current_settings['hero_gradient_start'] ?? '#4f46e5';
$hero_gradient_via = $current_settings['hero_gradient_via'] ?? '#7c3aed';
$hero_gradient_end = $current_settings['hero_gradient_end'] ?? '#ec4899';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Pengaturan Website & Tema - Admin Informatics A</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="../public/tailwind.css" rel="stylesheet">
    <style>
        .color-preview {
            transition: all 0.3s ease;
        }
        .color-preview:hover {
            transform: scale(1.05);
        }
        .theme-card {
            transition: all 0.3s ease;
        }
        .theme-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
        }
    </style>
</head>
<body class="bg-gray-50 min-h-screen">
    <!-- Navigation -->
    <nav class="bg-white shadow-md sticky top-0 z-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center h-16">
                <div class="flex items-center gap-2">
                    <svg class="w-8 h-8 text-blue-900" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                    </svg>
                    <span class="text-2xl font-bold text-blue-900">Pengaturan Website</span>
                </div>
                <div class="flex items-center gap-4">
                    <a href="/admin/dashboard.php" class="text-blue-900 hover:text-blue-700 font-medium transition">Dashboard</a>
                    <a href="/logout.php" class="bg-red-600 text-white px-4 py-2 rounded-lg hover:bg-red-700 transition">Logout</a>
                </div>
            </div>
        </div>
    </nav>

    <!-- Header -->
    <header class="bg-gradient-to-r from-blue-900 to-blue-800 text-white py-12 px-6">
        <div class="max-w-4xl mx-auto text-center">
            <div class="flex justify-center mb-4">
                <svg class="w-16 h-16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4"/>
                </svg>
            </div>
            <h1 class="text-4xl font-bold mb-3">Pengaturan Website & Tema</h1>
            <p class="text-xl text-blue-100">Kelola pengaturan dasar dan tema visual website</p>
        </div>
    </header>

    <!-- Main Content -->
    <main class="max-w-6xl mx-auto px-6 py-12">
        <?php if ($success): ?>
            <div class="mb-8 p-4 bg-green-100 border-l-4 border-green-500 text-green-700 rounded-lg">
                <div class="flex items-center gap-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <?= htmlspecialchars($success) ?>
                </div>
            </div>
        <?php endif; ?>

        <!-- Tabs -->
        <div class="mb-8">
            <div class="border-b border-gray-200">
                <nav class="-mb-px flex space-x-8">
                    <a href="?tab=basic" class="py-4 px-1 border-b-2 font-medium text-sm <?= $active_tab === 'basic' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' ?>">
                        Pengaturan Dasar
                    </a>
                    <a href="?tab=theme" class="py-4 px-1 border-b-2 font-medium text-sm <?= $active_tab === 'theme' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' ?>">
                        Tema & Warna
                    </a>
                </nav>
            </div>
        </div>

        <?php if ($active_tab === 'basic'): ?>
        <!-- Basic Settings Tab -->
        <form action="" method="POST" class="bg-white rounded-xl shadow-md p-8 space-y-8">
            <input type="hidden" name="action" value="update_basic">

            <div class="border-b pb-6">
                <h2 class="text-2xl font-bold text-blue-900 mb-4 flex items-center gap-2">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                    </svg>
                    Identitas Website
                </h2>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label for="site_name" class="block text-gray-700 font-semibold mb-2">Nama Website</label>
                        <input type="text" id="site_name" name="site_name" value="<?= htmlspecialchars($site_name) ?>"
                               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                    <div>
                        <label for="site_tagline" class="block text-gray-700 font-semibold mb-2">Tagline</label>
                        <input type="text" id="site_tagline" name="site_tagline" value="<?= htmlspecialchars($site_tagline) ?>"
                               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                </div>
            </div>

            <div class="border-b pb-6">
                <h2 class="text-2xl font-bold text-blue-900 mb-4 flex items-center gap-2">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                    </svg>
                    Footer
                </h2>
                <div>
                    <label for="footer_text" class="block text-gray-700 font-semibold mb-2">Teks Footer</label>
                    <textarea id="footer_text" name="footer_text" rows="3"
                              class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                              placeholder="Teks yang akan muncul di footer website"><?= htmlspecialchars($footer_text) ?></textarea>
                </div>
            </div>

            <div>
                <h2 class="text-2xl font-bold text-blue-900 mb-4 flex items-center gap-2">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                    </svg>
                    Informasi Kontak
                </h2>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label for="contact_email" class="block text-gray-700 font-semibold mb-2">Email</label>
                        <input type="email" id="contact_email" name="contact_email" value="<?= htmlspecialchars($contact_email) ?>"
                               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                    <div>
                        <label for="contact_instagram" class="block text-gray-700 font-semibold mb-2">Instagram</label>
                        <input type="text" id="contact_instagram" name="contact_instagram" value="<?= htmlspecialchars($contact_instagram) ?>"
                               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                               placeholder="@username">
                    </div>
                    <div>
                        <label for="contact_phone" class="block text-gray-700 font-semibold mb-2">Telepon</label>
                        <input type="tel" id="contact_phone" name="contact_phone" value="<?= htmlspecialchars($contact_phone) ?>"
                               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                    <div>
                        <label for="contact_address" class="block text-gray-700 font-semibold mb-2">Alamat</label>
                        <textarea id="contact_address" name="contact_address" rows="2"
                                  class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                                  placeholder="Alamat lengkap institusi"><?= htmlspecialchars($contact_address) ?></textarea>
                    </div>
                </div>
            </div>

            <div class="flex gap-4 pt-6">
                <button type="submit" class="flex-1 bg-blue-900 text-white px-6 py-3 rounded-lg font-bold hover:bg-blue-800 transition flex items-center justify-center gap-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                    </svg>
                    Simpan Pengaturan
                </button>
                <a href="/admin/dashboard.php" class="px-6 py-3 border-2 border-gray-300 rounded-lg font-bold hover:bg-gray-50 transition">
                    Batal
                </a>
            </div>
        </form>

        <?php elseif ($active_tab === 'theme'): ?>
        <!-- Theme Settings Tab -->
        <form action="" method="POST" class="space-y-8">
            <input type="hidden" name="action" value="update_theme">

            <!-- Primary Colors -->
            <div class="bg-white rounded-xl shadow-md p-8">
                <h2 class="text-2xl font-bold text-blue-900 mb-6 flex items-center gap-2">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21a4 4 0 01-4-4V5a2 2 0 012-2h4a2 2 0 012 2v12a4 4 0 01-4 4zm0 0h12a2 2 0 002-2v-4a2 2 0 00-2-2h-2.343M11 7.343l1.657-1.657a2 2 0 012.828 0l2.829 2.829a2 2 0 010 2.828l-8.486 8.485M7 17h.01"/>
                    </svg>
                    Warna Primer (Biru/Indigo)
                </h2>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div>
                        <label class="block text-gray-700 font-semibold mb-2">Warna Dasar</label>
                        <div class="flex gap-2">
                            <input type="color" name="primary_500" value="<?= htmlspecialchars($primary_500) ?>" class="w-16 h-12 border border-gray-300 rounded cursor-pointer">
                            <input type="text" value="<?= htmlspecialchars($primary_500) ?>" readonly class="flex-1 px-4 py-3 border border-gray-300 rounded-lg bg-gray-50">
                        </div>
                    </div>
                    <div>
                        <label class="block text-gray-700 font-semibold mb-2">Warna Gelap</label>
                        <div class="flex gap-2">
                            <input type="color" name="primary_600" value="<?= htmlspecialchars($primary_600) ?>" class="w-16 h-12 border border-gray-300 rounded cursor-pointer">
                            <input type="text" value="<?= htmlspecialchars($primary_600) ?>" readonly class="flex-1 px-4 py-3 border border-gray-300 rounded-lg bg-gray-50">
                        </div>
                    </div>
                    <div>
                        <label class="block text-gray-700 font-semibold mb-2">Warna Sangat Gelap</label>
                        <div class="flex gap-2">
                            <input type="color" name="primary_700" value="<?= htmlspecialchars($primary_700) ?>" class="w-16 h-12 border border-gray-300 rounded cursor-pointer">
                            <input type="text" value="<?= htmlspecialchars($primary_700) ?>" readonly class="flex-1 px-4 py-3 border border-gray-300 rounded-lg bg-gray-50">
                        </div>
                    </div>
                </div>
            </div>

            <!-- Secondary Colors (Purple) -->
            <div class="bg-white rounded-xl shadow-md p-8">
                <h2 class="text-2xl font-bold text-blue-900 mb-6 flex items-center gap-2">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21a4 4 0 01-4-4V5a2 2 0 012-2h4a2 2 0 012 2v12a4 4 0 01-4 4zm0 0h12a2 2 0 002-2v-4a2 2 0 00-2-2h-2.343M11 7.343l1.657-1.657a2 2 0 012.828 0l2.829 2.829a2 2 0 010 2.828l-8.486 8.485M7 17h.01"/>
                    </svg>
                    Warna Sekunder (Ungu)
                </h2>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div>
                        <label class="block text-gray-700 font-semibold mb-2">Warna Dasar</label>
                        <div class="flex gap-2">
                            <input type="color" name="secondary_500" value="<?= htmlspecialchars($secondary_500) ?>" class="w-16 h-12 border border-gray-300 rounded cursor-pointer">
                            <input type="text" value="<?= htmlspecialchars($secondary_500) ?>" readonly class="flex-1 px-4 py-3 border border-gray-300 rounded-lg bg-gray-50">
                        </div>
                    </div>
                    <div>
                        <label class="block text-gray-700 font-semibold mb-2">Warna Gelap</label>
                        <div class="flex gap-2">
                            <input type="color" name="secondary_600" value="<?= htmlspecialchars($secondary_600) ?>" class="w-16 h-12 border border-gray-300 rounded cursor-pointer">
                            <input type="text" value="<?= htmlspecialchars($secondary_600) ?>" readonly class="flex-1 px-4 py-3 border border-gray-300 rounded-lg bg-gray-50">
                        </div>
                    </div>
                    <div>
                        <label class="block text-gray-700 font-semibold mb-2">Warna Sangat Gelap</label>
                        <div class="flex gap-2">
                            <input type="color" name="secondary_700" value="<?= htmlspecialchars($secondary_700) ?>" class="w-16 h-12 border border-gray-300 rounded cursor-pointer">
                            <input type="text" value="<?= htmlspecialchars($secondary_700) ?>" readonly class="flex-1 px-4 py-3 border border-gray-300 rounded-lg bg-gray-50">
                        </div>
                    </div>
                </div>
            </div>

            <!-- Accent Colors (Pink/Magenta) -->
            <div class="bg-white rounded-xl shadow-md p-8">
                <h2 class="text-2xl font-bold text-blue-900 mb-6 flex items-center gap-2">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21a4 4 0 01-4-4V5a2 2 0 012-2h4a2 2 0 012 2v12a4 4 0 01-4 4zm0 0h12a2 2 0 002-2v-4a2 2 0 00-2-2h-2.343M11 7.343l1.657-1.657a2 2 0 012.828 0l2.829 2.829a2 2 0 010 2.828l-8.486 8.485M7 17h.01"/>
                    </svg>
                    Warna Aksen (Pink/Magenta)
                </h2>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div>
                        <label class="block text-gray-700 font-semibold mb-2">Warna Dasar</label>
                        <div class="flex gap-2">
                            <input type="color" name="accent_500" value="<?= htmlspecialchars($accent_500) ?>" class="w-16 h-12 border border-gray-300 rounded cursor-pointer">
                            <input type="text" value="<?= htmlspecialchars($accent_500) ?>" readonly class="flex-1 px-4 py-3 border border-gray-300 rounded-lg bg-gray-50">
                        </div>
                    </div>
                    <div>
                        <label class="block text-gray-700 font-semibold mb-2">Warna Gelap</label>
                        <div class="flex gap-2">
                            <input type="color" name="accent_600" value="<?= htmlspecialchars($accent_600) ?>" class="w-16 h-12 border border-gray-300 rounded cursor-pointer">
                            <input type="text" value="<?= htmlspecialchars($accent_600) ?>" readonly class="flex-1 px-4 py-3 border border-gray-300 rounded-lg bg-gray-50">
                        </div>
                    </div>
                    <div>
                        <label class="block text-gray-700 font-semibold mb-2">Warna Sangat Gelap</label>
                        <div class="flex gap-2">
                            <input type="color" name="accent_700" value="<?= htmlspecialchars($accent_700) ?>" class="w-16 h-12 border border-gray-300 rounded cursor-pointer">
                            <input type="text" value="<?= htmlspecialchars($accent_700) ?>" readonly class="flex-1 px-4 py-3 border border-gray-300 rounded-lg bg-gray-50">
                        </div>
                    </div>
                </div>
            </div>

            <!-- Hero Gradient -->
            <div class="bg-white rounded-xl shadow-md p-8">
                <h2 class="text-2xl font-bold text-blue-900 mb-6 flex items-center gap-2">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21a4 4 0 01-4-4V5a2 2 0 012-2h4a2 2 0 012 2v12a4 4 0 01-4 4zm0 0h12a2 2 0 002-2v-4a2 2 0 00-2-2h-2.343M11 7.343l1.657-1.657a2 2 0 012.828 0l2.829 2.829a2 2 0 010 2.828l-8.486 8.485M7 17h.01"/>
                    </svg>
                    Gradient Hero Section
                </h2>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div>
                        <label class="block text-gray-700 font-semibold mb-2">Warna Awal</label>
                        <div class="flex gap-2">
                            <input type="color" name="hero_gradient_start" value="<?= htmlspecialchars($hero_gradient_start) ?>" class="w-16 h-12 border border-gray-300 rounded cursor-pointer">
                            <input type="text" value="<?= htmlspecialchars($hero_gradient_start) ?>" readonly class="flex-1 px-4 py-3 border border-gray-300 rounded-lg bg-gray-50">
                        </div>
                    </div>
                    <div>
                        <label class="block text-gray-700 font-semibold mb-2">Warna Tengah</label>
                        <div class="flex gap-2">
                            <input type="color" name="hero_gradient_via" value="<?= htmlspecialchars($hero_gradient_via) ?>" class="w-16 h-12 border border-gray-300 rounded cursor-pointer">
                            <input type="text" value="<?= htmlspecialchars($hero_gradient_via) ?>" readonly class="flex-1 px-4 py-3 border border-gray-300 rounded-lg bg-gray-50">
                        </div>
                    </div>
                    <div>
                        <label class="block text-gray-700 font-semibold mb-2">Warna Akhir</label>
                        <div class="flex gap-2">
                            <input type="color" name="hero_gradient_end" value="<?= htmlspecialchars($hero_gradient_end) ?>" class="w-16 h-12 border border-gray-300 rounded cursor-pointer">
                            <input type="text" value="<?= htmlspecialchars($hero_gradient_end) ?>" readonly class="flex-1 px-4 py-3 border border-gray-300 rounded-lg bg-gray-50">
                        </div>
                    </div>
                </div>

                <!-- Live Preview -->
                <div class="mt-6">
                    <label class="block text-gray-700 font-semibold mb-3">Preview Gradient</label>
                    <div class="h-16 rounded-lg" style="background: linear-gradient(135deg, <?= htmlspecialchars($hero_gradient_start) ?> 0%, <?= htmlspecialchars($hero_gradient_via) ?> 50%, <?= htmlspecialchars($hero_gradient_end) ?> 100%);"></div>
                </div>
            </div>

            <div class="flex gap-4">
                <button type="submit" class="flex-1 bg-blue-900 text-white px-6 py-3 rounded-lg font-bold hover:bg-blue-800 transition flex items-center justify-center gap-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                    </svg>
                    Simpan Tema
                </button>
                <a href="/admin/dashboard.php" class="px-6 py-3 border-2 border-gray-300 rounded-lg font-bold hover:bg-gray-50 transition">
                    Batal
                </a>
            </div>
        </form>
        <?php endif; ?>
    </main>

    <!-- Footer -->
    <footer class="bg-blue-900 text-white py-8 mt-16">
        <div class="max-w-6xl mx-auto px-6 text-center">
            <p>&copy; <?= date("Y") ?> Informatics A. All rights reserved.</p>
        </div>
    </footer>

    <script>
        // Update text inputs when color pickers change
        document.querySelectorAll('input[type="color"]').forEach(colorInput => {
            colorInput.addEventListener('input', function(e) {
                e.target.nextElementSibling.value = e.target.value;
            });
        });

        // Real-time gradient preview
        function updateGradientPreview() {
            const start = document.querySelector('input[name="hero_gradient_start"]').value;
            const via = document.querySelector('input[name="hero_gradient_via"]').value;
            const end = document.querySelector('input[name="hero_gradient_end"]').value;
            const preview = document.querySelector('.gradient-preview');
            if (preview) {
                preview.style.background = `linear-gradient(135deg, ${start} 0%, ${via} 50%, ${end} 100%)`;
            }
        }

        document.querySelectorAll('input[name*="hero_gradient"]').forEach(input => {
            input.addEventListener('input', updateGradientPreview);
        });
    </script>
</body>
</html>
            </div>
        </div>
    </nav>

    <!-- Header -->
    <header class="bg-gradient-to-r from-blue-900 to-blue-800 text-white py-12 px-6">
        <div class="max-w-4xl mx-auto text-center">
            <div class="flex justify-center mb-4">
                <svg class="w-16 h-16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4"/>
                </svg>
            </div>
            <h1 class="text-4xl font-bold mb-3">Pengaturan Website</h1>
            <p class="text-xl text-blue-100">Customize tampilan dan konten website</p>
        </div>
    </header>

    <!-- Main Content -->
    <main class="max-w-4xl mx-auto px-6 py-12">
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
                        <input type="text" id="site_name" name="site_name" value="<?= htmlspecialchars($site_name) ?>" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                    <div>
                        <label for="site_tagline" class="block text-gray-700 font-semibold mb-2">Tagline</label>
                        <textarea id="site_tagline" name="site_tagline" rows="2" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"><?= htmlspecialchars($site_tagline) ?></textarea>
                    </div>
                </div>
            </div>

            <!-- Color Theme -->
            <div class="border-b pb-6">
                <h2 class="text-2xl font-bold text-blue-900 mb-4 flex items-center gap-2">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21a4 4 0 01-4-4V5a2 2 0 012-2h4a2 2 0 012 2v12a4 4 0 01-4 4zm0 0h12a2 2 0 002-2v-4a2 2 0 00-2-2h-2.343M11 7.343l1.657-1.657a2 2 0 012.828 0l2.829 2.829a2 2 0 010 2.828l-8.486 8.485M7 17h.01"/>
                    </svg>
                    Tema Warna
                </h2>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label for="primary_color" class="block text-gray-700 font-semibold mb-2">Warna Primer</label>
                        <div class="flex gap-2">
                            <input type="color" id="primary_color" name="primary_color" value="<?= htmlspecialchars($primary_color) ?>" class="w-16 h-12 border border-gray-300 rounded cursor-pointer">
                            <input type="text" value="<?= htmlspecialchars($primary_color) ?>" readonly class="flex-1 px-4 py-3 border border-gray-300 rounded-lg bg-gray-50">
                        </div>
                    </div>
                    <div>
                        <label for="secondary_color" class="block text-gray-700 font-semibold mb-2">Warna Sekunder</label>
                        <div class="flex gap-2">
                            <input type="color" id="secondary_color" name="secondary_color" value="<?= htmlspecialchars($secondary_color) ?>" class="w-16 h-12 border border-gray-300 rounded cursor-pointer">
                            <input type="text" value="<?= htmlspecialchars($secondary_color) ?>" readonly class="flex-1 px-4 py-3 border border-gray-300 rounded-lg bg-gray-50">
                        </div>
                    </div>
                </div>
                <p class="text-sm text-gray-500 mt-2">⚠️ Perubahan warna memerlukan rebuild Tailwind CSS</p>
            </div>

            <!-- Footer Settings -->
            <div class="border-b pb-6">
                <h2 class="text-2xl font-bold text-blue-900 mb-4 flex items-center gap-2">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                    </svg>
                    Footer
                </h2>
                <div class="space-y-4">
                    <div>
                        <label for="footer_text" class="block text-gray-700 font-semibold mb-2">Teks Footer</label>
                        <textarea id="footer_text" name="footer_text" rows="2" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"><?= htmlspecialchars($footer_text) ?></textarea>
                    </div>
                </div>
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
                        <input type="email" id="contact_email" name="contact_email" value="<?= htmlspecialchars($contact_email) ?>" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                    <div>
                        <label for="contact_instagram" class="block text-gray-700 font-semibold mb-2">Instagram</label>
                        <input type="text" id="contact_instagram" name="contact_instagram" value="<?= htmlspecialchars($contact_instagram) ?>" placeholder="@username" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
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
    <footer class="bg-blue-900 text-white py-8 mt-16">
        <div class="max-w-6xl mx-auto px-6 text-center">
            <p>&copy; <?= date("Y") ?> Informatics A. All rights reserved.</p>
        </div>
    </footer>

    <script>
        // Update text input when color picker changes
        document.getElementById('primary_color').addEventListener('input', function(e) {
            e.target.nextElementSibling.value = e.target.value;
        });
        document.getElementById('secondary_color').addEventListener('input', function(e) {
            e.target.nextElementSibling.value = e.target.value;
        });
    </script>
</body>
</html>
