<?php
// views/korti/dashboard.php
session_start();
require_once __DIR__ . "/../../src/helpers/session.php";
require_once __DIR__ . "/../../src/config/db.php";

// Proteksi: hanya korti yang bisa akses
if (!isLoggedIn() || !isKorti()) {
    header("Location: /informatics_a/login");
    exit();
}

$korti = getCurrentUser();

// Ambil statistik
try {
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM posts WHERE status = 'pending'");
    $pending_posts = $stmt->fetch()['total'] ?? 0;
    
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM posts WHERE status = 'approved'");
    $approved_posts = $stmt->fetch()['total'] ?? 0;
    
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM announcements");
    $total_announcements = $stmt->fetch()['total'] ?? 0;
} catch (Exception $e) {
    $pending_posts = 0;
    $approved_posts = 0;
    $total_announcements = 0;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Dashboard Korti - Informatics A</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="/informatics_a/public/tailwind.css" rel="stylesheet">
    <?php require_once __DIR__ . '/../../includes/favicon.php'; ?>
</head>
<body class="bg-gradient-to-br from-gray-50 to-blue-50 min-h-screen">
    <?php include __DIR__ . '/../../includes/navbar.php'; ?>

    <!-- Header -->
    <header class="bg-gradient-to-r from-blue-900 via-blue-800 to-indigo-900 text-white py-12 px-6">
        <div class="max-w-7xl mx-auto">
            <div class="flex items-center gap-4 mb-4">
                <div class="bg-white/10 backdrop-blur-sm p-4 rounded-2xl">
                    <svg class="w-12 h-12" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z"/>
                    </svg>
                </div>
                <div>
                    <h1 class="text-4xl font-bold mb-2">Dashboard Korti</h1>
                    <p class="text-blue-100 text-lg">Selamat datang kembali, <?= htmlspecialchars($korti["username"]) ?>! 👋</p>
                </div>
            </div>
        </div>
    </header>

    <!-- Statistics Cards -->
    <main class="max-w-7xl mx-auto px-6 -mt-8 mb-12">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-10">
            <!-- Pending Posts -->
            <div class="bg-white rounded-2xl shadow-lg p-6 border-l-4 border-yellow-500 hover:shadow-xl transition">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-600 text-sm font-medium mb-1">Postingan Pending</p>
                        <p class="text-3xl font-bold text-gray-900"><?= $pending_posts ?></p>
                    </div>
                    <div class="bg-yellow-100 p-4 rounded-xl">
                        <svg class="w-8 h-8 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                </div>
            </div>

            <!-- Approved Posts -->
            <div class="bg-white rounded-2xl shadow-lg p-6 border-l-4 border-green-500 hover:shadow-xl transition">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-600 text-sm font-medium mb-1">Postingan Approved</p>
                        <p class="text-3xl font-bold text-gray-900"><?= $approved_posts ?></p>
                    </div>
                    <div class="bg-green-100 p-4 rounded-xl">
                        <svg class="w-8 h-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                </div>
            </div>

            <!-- Total Announcements -->
            <div class="bg-white rounded-2xl shadow-lg p-6 border-l-4 border-blue-500 hover:shadow-xl transition">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-600 text-sm font-medium mb-1">Total Pengumuman</p>
                        <p class="text-3xl font-bold text-gray-900"><?= $total_announcements ?></p>
                    </div>
                    <div class="bg-blue-100 p-4 rounded-xl">
                        <svg class="w-8 h-8 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z"/>
                        </svg>
                    </div>
                </div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="bg-white rounded-2xl shadow-lg p-8 mb-10">
            <h2 class="text-2xl font-bold text-gray-900 mb-6 flex items-center gap-2">
                <svg class="w-7 h-7 text-blue-900" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                </svg>
                Fitur Korti
            </h2>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <a href="/informatics_a/admin/announcements" class="group block bg-gradient-to-br from-blue-900 to-blue-800 text-white p-6 rounded-xl hover:shadow-2xl hover:scale-105 transition-all duration-300">
                    <div class="flex items-center gap-4 mb-3">
                        <div class="bg-white/20 p-3 rounded-lg group-hover:bg-white/30 transition">
                            <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z"/>
                            </svg>
                        </div>
                        <h3 class="text-xl font-bold">Kelola Pengumuman</h3>
                    </div>
                    <p class="text-blue-100 text-sm">Buat dan kelola pengumuman kelas</p>
                </a>

                <a href="/informatics_a/admin/posts" class="group block bg-white border-2 border-gray-200 p-6 rounded-xl hover:border-blue-500 hover:shadow-xl hover:scale-105 transition-all duration-300">
                    <div class="flex items-center gap-4 mb-3">
                        <div class="bg-blue-100 p-3 rounded-lg group-hover:bg-blue-200 transition">
                            <svg class="w-8 h-8 text-blue-900" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        </div>
                        <h3 class="text-xl font-bold text-gray-900">Approve Postingan</h3>
                    </div>
                    <p class="text-gray-600 text-sm">Approve postingan kegiatan dari user</p>
                </a>

                <a href="/informatics_a/post" class="group block bg-white border-2 border-gray-200 p-6 rounded-xl hover:border-blue-500 hover:shadow-xl hover:scale-105 transition-all duration-300">
                    <div class="flex items-center gap-4 mb-3">
                        <div class="bg-blue-100 p-3 rounded-lg group-hover:bg-blue-200 transition">
                            <svg class="w-8 h-8 text-blue-900" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                            </svg>
                        </div>
                        <h3 class="text-xl font-bold text-gray-900">Buat Postingan</h3>
                    </div>
                    <p class="text-gray-600 text-sm">Posting kegiatan (langsung approved)</p>
                </a>
            </div>
        </div>
    </main>

    <footer class="bg-white border-t border-gray-200 py-6 mt-auto">
        <div class="max-w-7xl mx-auto px-6 text-center text-gray-600">
            &copy; <?= date("Y") ?> Informatics A. All rights reserved.
        </div>
    </footer>
</body>
</html>
