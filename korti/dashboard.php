<?php
// korti/dashboard.php atau korti/index.php
session_start();
require_once __DIR__ . "/../src/helpers/session.php";
require_once __DIR__ . "/../src/config/db.php";
require_once __DIR__ . "/../src/config/urls.php";

// DEBUG: Check what's happening
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<!-- DEBUG INFO START -->\n";
echo "<!-- SESSION: " . (isset($_SESSION['user']) ? 'Logged in' : 'Not logged in') . " -->\n";
if (isset($_SESSION['user'])) {
    echo "<!-- USER: " . $_SESSION['user']['username'] . ", ROLE: " . $_SESSION['user']['role'] . " -->\n";
}
echo "<!-- REQUEST_METHOD: " . $_SERVER['REQUEST_METHOD'] . " -->\n";
echo "<!-- PHP_SELF: " . $_SERVER['PHP_SELF'] . " -->\n";
echo "<!-- DEBUG INFO END -->\n";

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
        $settings[$row["setting_key"]] = $row["setting_value"];
    }
    $primary_color = $settings["primary_color"] ?? $primary_color;
    $secondary_color = $settings["secondary_color"] ?? $secondary_color;
    $accent_color = $settings["accent_color"] ?? $accent_color;
    $success_color = $settings["success_color"] ?? $success_color;
    $warning_color = $settings["warning_color"] ?? $warning_color;
    $danger_color = $settings["danger_color"] ?? $danger_color;
    $site_name = $settings["site_name"] ?? "Informatics A";
} catch (Exception $e) {
    echo "<!-- DB ERROR: " . $e->getMessage() . " -->\n";
    // Use default colors if database fails
    $site_name = "Informatics A";
}

// Proteksi: hanya korti yang bisa akses
$currentUser = getCurrentUser();
if (!$currentUser || $currentUser["role"] !== "korti") {
    echo "<!-- REDIRECTING: Not korti or not logged in -->\n";
    header("Location: " . url("login"));
    exit();
}

$korti = $currentUser;

// Debug: Check what's in the korti variable
if ($korti) {
    echo "<!-- DEBUG: Korti data: " . json_encode($korti) . " -->\n";
} else {
    echo "<!-- DEBUG: Korti variable is null or empty -->\n";
}

// Ambil statistik
try {
    echo "<!-- QUERYING STATS -->\n";
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

    $stmt = $pdo->query("SELECT COUNT(*) as total FROM announcements");
    $total_announcements = $stmt->fetch()["total"] ?? 0;

    echo "<!-- STATS: PENDING=$pending_posts, APPROVED=$approved_posts, USERS=$total_users, ANNOUNCEMENTS=$total_announcements -->\n";
} catch (Exception $e) {
    echo "<!-- STATS ERROR: " . $e->getMessage() . " -->\n";
    $pending_posts = 0;
    $approved_posts = 0;
    $total_users = 0;
    $total_announcements = 0;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Dashboard Korti - <?= htmlspecialchars($site_name) ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="<?= url("public/tailwind.css") ?>" rel="stylesheet">
</head>
<body class="min-h-screen" style="background: linear-gradient(135deg, <?= $primary_color ?>10 0%, <?= $secondary_color ?>10 100%);">
<?php include __DIR__ . "/../includes/navbar.php"; ?>

    <?php include __DIR__ . "/../includes/korti_sidebar.php"; ?>
<!-- Header -->
    <header class="lg:ml-64 text-white shadow-xl py-8 md:py-12 px-4 md:px-6 rounded-xl" style="background: linear-gradient(135deg, <?= $primary_color ?> 0%, <?= $secondary_color ?> 100%);">
        <div class="max-w-7xl mx-auto">
            <div class="flex items-center gap-3 md:gap-4">
                <div class="bg-white/10 backdrop-blur-sm p-2 md:p-3 rounded-xl">
                    <svg class="w-8 h-8 md:w-10 md:h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z"/>
                    </svg>
                </div>
                <div>
                    <h1 class="text-xl md:text-3xl font-bold mb-1">Dashboard Koordinator</h1>
                    <p class="text-white/90 text-sm md:text-base">Selamat datang, <?= htmlspecialchars($korti['username'] ?? 'Korti User') ?>!</p>
                </div>
            </div>
        </div>
    </header>
    <!-- Main Content -->
    <main class="lg:ml-64 pt-16">
        <div class="p-4 md:p-8">

            <!-- Stats Cards -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 md:gap-6 mb-8">
                <!-- Pending Posts -->
                <div class="bg-white rounded-xl shadow-md p-4 md:p-6 border-l-4" style="border-color: <?= $warning_color ?>;">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-gray-600 text-xs md:text-sm font-medium">Postingan Pending</p>
                            <p class="text-2xl md:text-3xl font-bold text-gray-800 mt-1 md:mt-2 leading-tight"><?= $pending_posts ?></p>
                        </div>
                        <div class="p-3 md:p-4 rounded-full flex-shrink-0" style="background-color: <?= $warning_color ?>20;">
                            <svg class="w-6 h-6 md:w-8 md:h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="color: <?= $warning_color ?>;">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        </div>
                    </div>
                </div>

                <!-- Approved Posts -->
                <div class="bg-white rounded-xl shadow-md p-4 md:p-6 border-l-4" style="border-color: <?= $success_color ?>;">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-gray-600 text-xs md:text-sm font-medium">Postingan Disetujui</p>
                            <p class="text-2xl md:text-3xl font-bold text-gray-800 mt-1 md:mt-2 leading-tight"><?= $approved_posts ?></p>
                        </div>
                        <div class="p-3 md:p-4 rounded-full flex-shrink-0" style="background-color: <?= $success_color ?>20;">
                            <svg class="w-6 h-6 md:w-8 md:h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="color: <?= $success_color ?>;">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        </div>
                    </div>
                </div>

                <!-- Total Users -->
                <div class="bg-white rounded-xl shadow-md p-4 md:p-6 border-l-4" style="border-color: <?= $primary_color ?>;">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-gray-600 text-xs md:text-sm font-medium">Total Anggota</p>
                            <p class="text-2xl md:text-3xl font-bold text-gray-800 mt-1 md:mt-2 leading-tight"><?= $total_users ?></p>
                        </div>
                        <div class="p-3 md:p-4 rounded-full flex-shrink-0" style="background-color: <?= $primary_color ?>20;">
                            <svg class="w-6 h-6 md:w-8 md:h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="color: <?= $primary_color ?>;">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>
                            </svg>
                        </div>
                    </div>
                </div>

                <!-- Announcements -->
                <div class="bg-white rounded-xl shadow-md p-4 md:p-6 border-l-4" style="border-color: <?= $accent_color ?>;">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-gray-600 text-xs md:text-sm font-medium">Total Pengumuman</p>
                            <p class="text-2xl md:text-3xl font-bold text-gray-800 mt-1 md:mt-2 leading-tight"><?= $total_announcements ?></p>
                        </div>
                        <div class="p-3 md:p-4 rounded-full flex-shrink-0" style="background-color: <?= $accent_color ?>20;">
                            <svg class="w-6 h-6 md:w-8 md:h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="color: <?= $accent_color ?>;">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z"/>
                            </svg>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="bg-white rounded-xl shadow-md p-4 md:p-6 mb-8">
                <h2 class="text-lg md:text-xl font-bold text-gray-800 mb-4">Aksi Cepat</h2>
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3 md:gap-4">
                    <a href="<?= url('korti/create_post.php') ?>" class="flex items-center gap-3 p-3 md:p-4 rounded-lg transition" style="background-color: <?= $primary_color ?>20;">
                        <svg class="w-5 h-5 md:w-6 md:h-6 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="color: <?= $primary_color ?>;">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                        </svg>
                        <span class="font-medium text-gray-700 text-sm md:text-base">Buat Postingan</span>
                    </a>
                    <a href="<?= url('korti/posts.php') ?>" class="flex items-center gap-3 p-3 md:p-4 rounded-lg transition" style="background-color: <?= $secondary_color ?>20;">
                        <svg class="w-5 h-5 md:w-6 md:h-6 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="color: <?= $secondary_color ?>;">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                        <span class="font-medium text-gray-700 text-sm md:text-base">Review Postingan</span>
                    </a>
                    <a href="<?= url('korti/announcements.php') ?>" class="flex items-center gap-3 p-3 md:p-4 rounded-lg transition" style="background-color: <?= $accent_color ?>20;">
                        <svg class="w-5 h-5 md:w-6 md:h-6 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="color: <?= $accent_color ?>;">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z"/>
                        </svg>
                        <span class="font-medium text-gray-700 text-sm md:text-base">Buat Pengumuman</span>
                    </a>
                    <a href="<?= url('korti/comments.php') ?>" class="flex items-center gap-3 p-3 md:p-4 rounded-lg transition" style="background-color: <?= $success_color ?>20;">
                        <svg class="w-5 h-5 md:w-6 md:h-6 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="color: <?= $success_color ?>;">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z"/>
                        </svg>
                        <span class="font-medium text-gray-700 text-sm md:text-base">Moderasi Komentar</span>
                    </a>
                </div>
            </div>
        </div>
    </main>
</body>
</html>
