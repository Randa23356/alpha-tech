<?php
ini_set("display_errors", 1);
ini_set("display_startup_errors", 1);
error_reporting(E_ALL);

// informatics_a/admin/users_detail.php
require_once __DIR__ . "/../src/helpers/session.php";
require_once __DIR__ . "/../src/helpers/helpers.php";
require_once __DIR__ . "/../src/config/db.php";
require_once __DIR__ . "/../src/config/urls.php";

// Proteksi: hanya admin yang bisa akses
if (!isLoggedIn() || !isAdmin()) {
    header("Location: " . url('login'));
    exit();
}

// Ambil ID user dari URL
$target_user_id = isset($_GET["id"]) ? (int) $_GET["id"] : 0;

if (!$target_user_id) {
    header("Location: " . url('admin/users'));
    exit();
}

// Ambil data user TARGET (yang ingin dilihat)
$stmt = $pdo->prepare("
    SELECT
        id, username, email, full_name, bio, contact, profile_pic, role,
        google_id, email_verified, created_at
    FROM users
    WHERE id = ?
");
$stmt->execute([$target_user_id]);
$target_user = $stmt->fetch();

if (!$target_user) {
    header("Location: " . url('login'));
    exit();
}

// Ambil statistik user TARGET
$stmt = $pdo->prepare("
    SELECT
        COUNT(p.id) as total_posts,
        COUNT(CASE WHEN p.status = 'approved' THEN 1 END) as approved_posts,
        COUNT(CASE WHEN p.status = 'pending' THEN 1 END) as pending_posts,
        COUNT(CASE WHEN p.status = 'rejected' THEN 1 END) as rejected_posts,
        COUNT(c.id) as total_comments
    FROM users u
    LEFT JOIN posts p ON u.id = p.user_id
    LEFT JOIN comments c ON u.id = c.user_id
    WHERE u.id = ?
");
$stmt->execute([$target_user_id]);
$stats = $stmt->fetch();

// Query untuk mengambil postingan user TARGET
$stmt = $pdo->prepare("
    SELECT
        p.id, p.title, p.content, p.status, p.created_at,
        COUNT(c.id) as comment_count
    FROM posts p
    LEFT JOIN comments c ON p.id = c.post_id
    WHERE p.user_id = ?
    GROUP BY p.id
    ORDER BY p.created_at DESC
    LIMIT 10
");
$stmt->execute([$target_user_id]);
$recent_posts = $stmt->fetchAll();

// Role badge color
$role_colors = [
    "admin" => "bg-red-100 text-red-800 border-red-300",
    "korti" => "bg-yellow-100 text-yellow-800 border-yellow-300",
    "user" => "bg-blue-100 text-blue-800 border-blue-300",
];

$status_colors = [
    "approved" => "bg-green-100 text-green-800",
    "pending" => "bg-yellow-100 text-yellow-800",
    "rejected" => "bg-red-100 text-red-800",
];

// Status badge
$verification_status = !empty($target_user["google_id"])
    ? "Google Account"
    : (!empty($target_user["email_verified"])
        ? "Verified"
        : "Not Verified");
$verification_color = !empty($target_user["google_id"])
    ? "bg-purple-100 text-purple-800 border-purple-300"
    : (!empty($target_user["email_verified"])
        ? "bg-green-100 text-green-800 border-green-300"
        : "bg-gray-100 text-gray-800 border-gray-300");
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Detail User - Admin Informatics A</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="<?= asset('tailwind.css') ?>" rel="stylesheet">
</head>
<body class="bg-gradient-to-br from-gray-50 to-blue-50 min-h-screen">
    <?php include __DIR__ . "/../includes/navbar.php"; ?>
    <?php include __DIR__ . "/sidebar.php"; ?>

    <!-- Header -->
    <header class="lg:ml-64 bg-gradient-to-r from-blue-900 via-blue-800 to-indigo-900 text-white py-10 px-6">
        <div class="max-w-7xl mx-auto">
            <div class="flex items-center gap-4">
                <div class="bg-white/10 backdrop-blur-sm p-3 rounded-xl">
                    <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                    </svg>
                </div>
                <div>
                    <h1 class="text-3xl font-bold mb-1">Detail User</h1>
                    <p class="text-blue-100">Informasi lengkap tentang user <?= htmlspecialchars(
                        $target_user["username"],
                    ) ?></p>
                </div>
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <main class="lg:ml-64 max-w-6xl mx-auto px-6 py-10">
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <!-- Sidebar - User Info -->
            <div class="lg:col-span-1">
                <div class="bg-white rounded-xl shadow-lg p-6 sticky top-6">
                    <!-- Profile Photo -->
                    <div class="text-center mb-6">
                        <?php if (!empty($target_user["profile_pic"])): ?>
                            <img src=" <?= BASE_URL ?>/public/uploads/<?= htmlspecialchars(
                                $target_user["profile_pic"],
                            ) ?>"
                                 alt="Foto Profil"
                                 class="w-32 h-32 rounded-full mx-auto mb-4 object-cover border-4 border-blue-200 shadow-lg">
                        <?php else: ?>
                            <div class="w-32 h-32 rounded-full bg-gradient-to-br from-blue-500 to-blue-700 mx-auto mb-4 flex items-center justify-center text-white font-bold text-4xl shadow-lg">
                                <?= strtoupper(
                                    substr($target_user["username"], 0, 1),
                                ) ?>
                            </div>
                        <?php endif; ?>

                        <h2 class="text-xl font-bold text-gray-900 mb-2"><?= htmlspecialchars(
                            $target_user["username"],
                        ) ?></h2>

                        <div class="flex flex-col gap-2 mb-4">
                            <span class="inline-block px-3 py-1 text-sm font-semibold rounded-full border <?= $role_colors[
                                $target_user["role"]
                            ] ?>">
                                <?= ucfirst($target_user["role"]) ?>
                            </span>
                            <span class="inline-block px-3 py-1 text-sm font-semibold rounded-full border <?= $verification_color ?>">
                                <?= $verification_status ?>
                            </span>
                        </div>
                    </div>

                    <!-- User Details -->
                    <div class="space-y-4">
                        <div class="bg-gray-50 p-4 rounded-lg">
                            <h3 class="text-sm font-medium text-gray-500 mb-1">User ID</h3>
                            <p class="text-gray-900 font-mono">#<?= $target_user[
                                "id"
                            ] ?></p>
                        </div>

                        <div>
                            <h3 class="text-sm font-medium text-gray-500 mb-1">Nama Lengkap</h3>
                            <p class="text-gray-900">
                                <?php if (
                                    !empty($target_user["full_name"]) &&
                                    trim($target_user["full_name"]) !== ""
                                ): ?>
                                    <?= htmlspecialchars(
                                        $target_user["full_name"],
                                    ) ?>
                                <?php else: ?>
                                    <span class="text-gray-400 italic">Belum diisi</span>
                                <?php endif; ?>
                            </p>
                        </div>

                        <div>
                            <h3 class="text-sm font-medium text-gray-500 mb-1">Email</h3>
                            <p class="text-gray-900"><?= htmlspecialchars(
                                $target_user["email"],
                            ) ?></p>
                        </div>

                        <?php if (
                            !empty($target_user["bio"]) &&
                            trim($target_user["bio"]) !== ""
                        ): ?>
                        <div>
                            <h3 class="text-sm font-medium text-gray-500 mb-1">Bio</h3>
                            <p class="text-gray-900"><?= htmlspecialchars(
                                $target_user["bio"],
                            ) ?></p>
                        </div>
                        <?php endif; ?>

                        <?php if (
                            !empty($target_user["contact"]) &&
                            trim($target_user["contact"]) !== ""
                        ): ?>
                        <div>
                            <h3 class="text-sm font-medium text-gray-500 mb-1">Kontak</h3>
                            <p class="text-gray-900"><?= htmlspecialchars(
                                $target_user["contact"],
                            ) ?></p>
                        </div>
                        <?php endif; ?>

                        <?php if (!empty($target_user["google_id"])): ?>
                        <div class="bg-purple-50 p-4 rounded-lg">
                            <h3 class="text-sm font-medium text-purple-600 mb-1">Google ID</h3>
                            <p class="text-purple-900 font-mono text-sm"><?= substr(
                                $target_user["google_id"],
                                0,
                                20,
                            ) ?>...</p>
                        </div>
                        <?php endif; ?>

                        <div class="border-t pt-4">
                            <h3 class="text-sm font-medium text-gray-500 mb-1">Tanggal Daftar</h3>
                            <p class="text-gray-900">
                                <?php if (
                                    !empty($target_user["created_at"]) &&
                                    $target_user["created_at"] !=
                                        "0000-00-00 00:00:00"
                                ): ?>
                                    <?= date(
                                        "d M Y H:i",
                                        strtotime($target_user["created_at"]),
                                    ) ?>
                                <?php else: ?>
                                    <span class="text-gray-400 italic">Tidak tersedia</span>
                                <?php endif; ?>
                            </p>
                        </div>
                    </div>

                    <!-- Action Buttons -->
                    <div class="mt-6 space-y-3">
                        <a href=" <?= url('admin/users') ?>"
                           class="w-full bg-gray-100 text-gray-700 px-4 py-3 rounded-lg font-medium hover:bg-gray-200 transition text-center block flex items-center justify-center gap-2">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                            </svg>
                            Kembali ke Daftar User
                        </a>

                        <?php if (
                            $target_user["id"] !== $_SESSION["user"]["id"]
                        ): ?>
                        <form method="POST" action=" <?= url('admin/users') ?>" onsubmit="return confirm('Apakah Anda yakin ingin menghapus user ini? Tindakan ini tidak dapat dibatalkan!')">
                            <input type="hidden" name="user_id" value="<?= $target_user[
                                "id"
                            ] ?>">
                            <button type="submit" name="delete_user"
                                    class="w-full bg-red-600 text-white px-4 py-3 rounded-lg font-medium hover:bg-red-700 transition flex items-center justify-center gap-2">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                </svg>
                                Hapus User
                            </button>
                        </form>
                        <?php else: ?>
                        <div class="w-full bg-blue-100 text-blue-700 px-4 py-3 rounded-lg font-medium text-center">
                            Akun Sendiri
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Main Content - Stats & Posts -->
            <div class="lg:col-span-2 space-y-8">
                                             <!-- Statistics Cards -->
                <div class="grid grid-cols-2 md:grid-cols-2 lg:grid-cols-4 gap-4 md:gap-6">
                    <!-- Card 1: Total Postingan -->
                    <div class="bg-white rounded-xl shadow-lg p-4 md:p-6 border-l-4 border-blue-500 hover:shadow-xl transition">
                        <div class="flex flex-col h-full">
                            <div class="flex items-center justify-between mb-3">
                                <p class="text-gray-600 text-xs md:text-sm font-medium truncate">Total Postingan</p>
                                <div class="bg-blue-100 p-2 rounded-lg flex-shrink-0 ml-2">
                                    <svg class="w-5 h-5 md:w-6 md:h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                    </svg>
                                </div>
                            </div>
                            <p class="text-xl md:text-2xl lg:text-3xl font-bold text-gray-900 mt-auto"><?= $stats[
                                "total_posts"
                            ] ?? 0 ?></p>
                        </div>
                    </div>

                    <!-- Card 2: Postingan Approved -->
                    <div class="bg-white rounded-xl shadow-lg p-4 md:p-6 border-l-4 border-green-500 hover:shadow-xl transition">
                        <div class="flex flex-col h-full">
                            <div class="flex items-center justify-between mb-3">
                                <p class="text-gray-600 text-xs md:text-sm font-medium truncate">Postingan Approved</p>
                                <div class="bg-green-100 p-2 rounded-lg flex-shrink-0 ml-2">
                                    <svg class="w-5 h-5 md:w-6 md:h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                    </svg>
                                </div>
                            </div>
                            <p class="text-xl md:text-2xl lg:text-3xl font-bold text-gray-900 mt-auto"><?= $stats[
                                "approved_posts"
                            ] ?? 0 ?></p>
                        </div>
                    </div>

                    <!-- Card 3: Postingan Pending -->
                    <div class="bg-white rounded-xl shadow-lg p-4 md:p-6 border-l-4 border-yellow-500 hover:shadow-xl transition">
                        <div class="flex flex-col h-full">
                            <div class="flex items-center justify-between mb-3">
                                <p class="text-gray-600 text-xs md:text-sm font-medium truncate">Postingan Pending</p>
                                <div class="bg-yellow-100 p-2 rounded-lg flex-shrink-0 ml-2">
                                    <svg class="w-5 h-5 md:w-6 md:h-6 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                    </svg>
                                </div>
                            </div>
                            <p class="text-xl md:text-2xl lg:text-3xl font-bold text-gray-900 mt-auto"><?= $stats[
                                "pending_posts"
                            ] ?? 0 ?></p>
                        </div>
                    </div>

                    <!-- Card 4: Total Komentar -->
                    <div class="bg-white rounded-xl shadow-lg p-4 md:p-6 border-l-4 border-purple-500 hover:shadow-xl transition">
                        <div class="flex flex-col h-full">
                            <div class="flex items-center justify-between mb-3">
                                <p class="text-gray-600 text-xs md:text-sm font-medium truncate">Total Komentar</p>
                                <div class="bg-purple-100 p-2 rounded-lg flex-shrink-0 ml-2">
                                    <svg class="w-5 h-5 md:w-6 md:h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z"/>
                                    </svg>
                                </div>
                            </div>
                            <p class="text-xl md:text-2xl lg:text-3xl font-bold text-gray-900 mt-auto"><?= $stats[
                                "total_comments"
                            ] ?? 0 ?></p>
                        </div>
                    </div>
                </div>

                <!-- Recent Posts -->
                <div class="bg-white rounded-xl shadow-lg overflow-hidden">
                    <div class="px-6 py-4 border-b border-gray-200 bg-gradient-to-r from-blue-50 to-indigo-50">
                        <h2 class="text-xl font-bold text-gray-900 flex items-center gap-2">
                            <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                            </svg>
                            Postingan Terbaru
                        </h2>
                    </div>

                    <div class="divide-y divide-gray-200">
                        <?php if (empty($recent_posts)): ?>
                            <div class="p-8 text-center text-gray-500">
                                <svg class="w-16 h-16 mx-auto mb-4 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                </svg>
                                <p class="text-lg font-medium text-gray-400">User ini belum membuat postingan</p>
                            </div>
                        <?php else: ?>
                            <?php foreach ($recent_posts as $post): ?>
                            <div class="p-6 hover:bg-gray-50 transition group">
                                <div class="flex items-start justify-between mb-3">
                                    <h3 class="text-lg font-semibold text-gray-900 group-hover:text-blue-600 transition"><?= htmlspecialchars(
                                        $post["title"],
                                    ) ?></h3>
                                    <span class="inline-block px-3 py-1 text-xs font-semibold rounded-full <?= $status_colors[
                                        $post["status"]
                                    ] ?>">
                                        <?= ucfirst($post["status"]) ?>
                                    </span>
                                </div>

                                <p class="text-gray-600 mb-4 line-clamp-2"><?= htmlspecialchars(
                                    substr($post["content"], 0, 150),
                                ) ?>...</p>

                                <div class="flex items-center justify-between text-sm text-gray-500">
                                    <div class="flex items-center gap-4">
                                        <span class="flex items-center gap-1">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                            </svg>
                                            <?= date(
                                                "d M Y",
                                                strtotime($post["created_at"]),
                                            ) ?>
                                        </span>
                                        <span class="flex items-center gap-1">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z"/>
                                            </svg>
                                            <?= $post[
                                                "comment_count"
                                            ] ?> komentar
                                        </span>
                                    </div>
                                    <a href="<?= base_url("admin/manage_posts") ?>" class="text-blue-600 hover:text-blue-800 font-medium flex items-center gap-1 group-hover:gap-2 transition-all">
                                        Lihat Detail
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                                        </svg>
                                    </a>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <!-- Footer -->
    <footer class="lg:ml-64 bg-white border-t border-gray-200 py-6 mt-12">
        <div class="max-w-7xl mx-auto px-6 text-center text-gray-600">
            &copy; <?= date("Y") ?> Informatics A. All rights reserved.
        </div>
    </footer>
</body>
</html>
