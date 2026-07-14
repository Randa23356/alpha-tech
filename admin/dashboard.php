<?php

session_start();
require_once __DIR__ . "/../src/config/db.php";
require_once __DIR__ . "/../src/config/urls.php";
require_once __DIR__ . "/../src/helpers/session.php";

// Proteksi: hanya admin yang bisa akses
if (!isLoggedIn() || !isAdmin()) {
    header("Location: " . url('login'));
    exit();
}

$admin = getCurrentUser();

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
try {
    $stmt = $pdo->query(
        "SELECT COUNT(*) as total FROM posts WHERE status = 'pending'",
    );
    $pending_posts = $stmt->fetch()["total"] ?? 0;

    $stmt = $pdo->query(
        "SELECT COUNT(*) as total FROM posts WHERE status = 'approved'",
    );
    $approved_posts = $stmt->fetch()["total"] ?? 0;

    $stmt = $pdo->query(
        "SELECT COUNT(*) as total FROM users WHERE role = 'user'",
    );
    $total_users = $stmt->fetch()["total"] ?? 0;

    $stmt = $pdo->query("SELECT COUNT(*) as total FROM comments");
    $total_comments = $stmt->fetch()["total"] ?? 0;
} catch (Exception $e) {
    $pending_posts = 0;
    $approved_posts = 0;
    $total_users = 0;
    $total_comments = 0;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Dashboard Admin - <?= htmlspecialchars($site_name) ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="<?= asset('tailwind.css') ?>" rel="stylesheet">
</head>
<body class="bg-gradient-to-br from-gray-50 to-blue-50 min-h-screen">
    <?php include __DIR__ . "/../includes/navbar.php"; ?>
    <?php include __DIR__ . "/sidebar.php"; ?>

    <!-- Header -->
    <header class="lg:ml-64 text-white py-12 px-6" style="background: linear-gradient(135deg, <?= $primary_color ?> 0%, <?= $secondary_color ?> 100%);">
        <div class="max-w-7xl">
            <div class="flex items-center gap-4 mb-4">
                <div class="bg-white/10 backdrop-blur-sm p-4 rounded-2xl">
                    <svg class="w-12 h-12" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                    </svg>
                </div>
                <div>
                    <h1 class="text-4xl font-bold mb-2">Dashboard Admin</h1>
                    <p class="text-blue-100 text-lg">Selamat datang kembali, <?= htmlspecialchars($admin['username'] ?? 'Admin') ?>! 👋</p>
                </div>
            </div>
        </div>
    </header>

    <!-- Statistics Cards -->
    <main class="lg:ml-64 px-6 -mt-8 mb-12 max-w-7xl">
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-10">
            <!-- Pending Posts -->
            <div class="bg-white rounded-2xl shadow-lg p-6 hover:shadow-xl transition" style="border-left: 4px solid <?= $warning_color ?>;">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-600 text-sm font-medium mb-1">Postingan Pending</p>
                        <p class="text-3xl font-bold text-gray-900"><?= $pending_posts ?></p>
                    </div>
                    <div class="p-4 rounded-xl" style="background-color: <?= $warning_color ?>20;">
                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="color: <?= $warning_color ?>;">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                </div>
            </div>

            <!-- Approved Posts -->
            <div class="bg-white rounded-2xl shadow-lg p-6 hover:shadow-xl transition" style="border-left: 4px solid <?= $success_color ?>;">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-600 text-sm font-medium mb-1">Postingan Approved</p>
                        <p class="text-3xl font-bold text-gray-900"><?= $approved_posts ?></p>
                    </div>
                    <div class="p-4 rounded-xl" style="background-color: <?= $success_color ?>20;">
                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="color: <?= $success_color ?>;">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                </div>
            </div>

            <!-- Total Users -->
            <div class="bg-white rounded-2xl shadow-lg p-6 hover:shadow-xl transition" style="border-left: 4px solid <?= $primary_color ?>;">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-600 text-sm font-medium mb-1">Total Users</p>
                        <p class="text-3xl font-bold text-gray-900"><?= $total_users ?></p>
                    </div>
                    <div class="p-4 rounded-xl" style="background-color: <?= $primary_color ?>20;">
                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="color: <?= $primary_color ?>;">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>
                        </svg>
                    </div>
                </div>
            </div>

            <!-- Total Comments -->
            <div class="bg-white rounded-2xl shadow-lg p-6 hover:shadow-xl transition" style="border-left: 4px solid <?= $accent_color ?>;">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-600 text-sm font-medium mb-1">Total Komentar</p>
                        <p class="text-3xl font-bold text-gray-900"><?= $total_comments ?></p>
                    </div>
                    <div class="p-4 rounded-xl" style="background-color: <?= $accent_color ?>20;">
                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="color: <?= $accent_color ?>;">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z"/>
                        </svg>
                    </div>
                </div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="bg-white rounded-2xl shadow-lg p-8 mb-10">
            <h2 class="text-2xl font-bold text-gray-900 mb-6 flex items-center gap-2">
                <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="color: <?= $primary_color ?>;">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                </svg>
                Quick Actions
            </h2>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <a href="site_settings.php" class="group block text-white p-6 rounded-xl hover:shadow-2xl hover:scale-105 transition-all duration-300" style="background: linear-gradient(135deg, <?= $primary_color ?> 0%, <?= $secondary_color ?> 100%);">
                    <div class="flex items-center gap-4 mb-3">
                        <div class="p-3 rounded-lg group-hover:bg-white/30 transition" style="background: rgba(255, 255, 255, 0.2);">
                            <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                            </svg>
                        </div>
                        <h3 class="text-xl font-bold">Pengaturan Website</h3>
                    </div>
                    <p class="text-white/90 text-sm">Customize tampilan dan konten website</p>
                </a>

                <a href="manage_posts.php" class="group block bg-white border-2 p-6 rounded-xl hover:shadow-xl hover:scale-105 transition-all duration-300" style="border-color: <?= $primary_color ?>30;">
                    <div class="flex items-center gap-4 mb-3">
                        <div class="p-3 rounded-lg group-hover:bg-blue-200 transition" style="background-color: <?= $primary_color ?>20;">
                            <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="color: <?= $primary_color ?>;">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                            </svg>
                        </div>
                        <h3 class="text-xl font-bold text-gray-900">Kelola Postingan</h3>
                    </div>
                    <p class="text-gray-600 text-sm">Approve, edit, atau hapus postingan kegiatan</p>
                </a>

                <a href="announcement.php" class="group block bg-white border-2 p-6 rounded-xl hover:shadow-xl hover:scale-105 transition-all duration-300" style="border-color: <?= $primary_color ?>30;">
                    <div class="flex items-center gap-4 mb-3">
                        <div class="p-3 rounded-lg group-hover:bg-blue-200 transition" style="background-color: <?= $primary_color ?>20;">
                            <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="color: <?= $primary_color ?>;">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z"/>
                            </svg>
                        </div>
                        <h3 class="text-xl font-bold text-gray-900">Kelola Pengumuman</h3>
                    </div>
                    <p class="text-gray-600 text-sm">Buat dan kelola pengumuman kelas</p>
                </a>

                <a href="manage_comments.php" class="group block bg-white border-2 p-6 rounded-xl hover:shadow-xl hover:scale-105 transition-all duration-300" style="border-color: <?= $primary_color ?>30;">
                    <div class="flex items-center gap-4 mb-3">
                        <div class="p-3 rounded-lg group-hover:bg-blue-200 transition" style="background-color: <?= $primary_color ?>20;">
                            <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="color: <?= $primary_color ?>;">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z"/>
                            </svg>
                        </div>
                        <h3 class="text-xl font-bold text-gray-900">Kelola Komentar</h3>
                    </div>
                    <p class="text-gray-600 text-sm">Moderasi komentar pada postingan</p>
                </a>

                <a href="manage_gallery.php" class="group block bg-white border-2 p-6 rounded-xl hover:shadow-xl hover:scale-105 transition-all duration-300" style="border-color: <?= $primary_color ?>30;">
                    <div class="flex items-center gap-4 mb-3">
                        <div class="p-3 rounded-lg group-hover:bg-blue-200 transition" style="background-color: <?= $primary_color ?>20;">
                            <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="color: <?= $primary_color ?>;">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                            </svg>
                        </div>
                        <h3 class="text-xl font-bold text-gray-900">Kelola Galeri</h3>
                    </div>
                    <p class="text-gray-600 text-sm">Approve dan kelola foto galeri</p>
                </a>
            </div>
        </div>
    </main>

    <footer class="lg:ml-64 bg-white border-t border-gray-200 py-6 mt-auto">
        <div class="max-w-7xl px-6 text-center text-gray-600">
            &copy; <?= date("Y") ?> Informatics A. All rights reserved.
        </div>
    </footer>


</body>
</html>
