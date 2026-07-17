<?php
// user_profile.php - View Other User's Profile
// Session is already started in session.php, no need to start again
require_once __DIR__ . "/src/helpers/session.php";
require_once __DIR__ . "/src/config/db.php";
require_once __DIR__ . "/src/config/urls.php";

// Load theme colors from database
$primary_color = "#1e3a8a"; // default
$secondary_color = "#1e40af"; // default
$accent_color = "#ec4899"; // default
$success_color = "#10b981"; // default

try {
    $stmt = $pdo->query(
        "SELECT setting_key, setting_value FROM site_settings WHERE setting_key IN ('primary_color', 'secondary_color', 'accent_color', 'success_color')",
    );
    $settings = [];
    foreach ($stmt->fetchAll() as $row) {
        $settings[$row["setting_key"]] = $row["setting_value"];
    }
    $primary_color = $settings["primary_color"] ?? $primary_color;
    $secondary_color = $settings["secondary_color"] ?? $secondary_color;
    $accent_color = $settings["accent_color"] ?? $accent_color;
    $success_color = $settings["success_color"] ?? $success_color;
} catch (Exception $e) {
    // Use default colors if database fails
}

// Check if user is logged in
if (!isLoggedIn()) {
    header("Location: " . url('login'));
    exit();
}

// Get current user
$currentUser = getCurrentUser();

// Get user ID from URL parameter
$userId = isset($_GET['id']) ? intval($_GET['id']) : 0;

if (!$userId) {
    header("Location: " . url('home'));
    exit();
}

// If viewing own profile, redirect to profile page
if ($userId === intval($currentUser['id'] ?? 0)) {
    header("Location: " . url('profile'));
    exit();
}

// Get user profile data
try {
    $stmt = $pdo->prepare("
        SELECT users.*, COUNT(posts.id) as post_count
        FROM users
        LEFT JOIN posts ON users.id = posts.user_id AND posts.status = 'approved'
        WHERE users.id = ?
        GROUP BY users.id
    ");
    $stmt->execute([$userId]);
    $userProfile = $stmt->fetch();

    if (!$userProfile) {
        header("Location: " . url('home'));
        exit();
    }
} catch (Exception $e) {
    header("Location: " . url('home'));
    exit();
}

// Get user's recent posts
try {
    // Debug: Check current user info
    error_log("Current user info: " . print_r($currentUser, true));
    error_log("Looking for posts for user_id: $userId");

    // First, let's check if the posts table exists and what columns it has
    $columns = $pdo->query("DESCRIBE posts")->fetchAll(PDO::FETCH_ASSOC);
    error_log("Posts table columns: " . print_r($columns, true));

    // Check if there are ANY posts in the database
    $totalPosts = $pdo->query("SELECT COUNT(*) as count FROM posts")->fetch()['count'];
    error_log("Total posts in database: $totalPosts");

    // Check posts for this specific user
    $userPostsCount = $pdo->prepare("SELECT COUNT(*) as count FROM posts WHERE user_id = ?");
    $userPostsCount->execute([$userId]);
    $userPostsTotal = $userPostsCount->fetch()['count'];
    error_log("Posts for user $userId: $userPostsTotal");

    // Get raw posts data for debugging
    $rawPosts = $pdo->prepare("SELECT * FROM posts WHERE user_id = ? ORDER BY created_at DESC LIMIT 5");
    $rawPosts->execute([$userId]);
    $rawPostsData = $rawPosts->fetchAll();
    error_log("Raw posts data for user $userId: " . print_r($rawPostsData, true));

    // First try to get approved posts with counts
    $stmt = $pdo->prepare("
        SELECT posts.*,
               COALESCE(likes_count.count, 0) as like_count,
               COALESCE(comments_count.count, 0) as comment_count
        FROM posts
        LEFT JOIN (
            SELECT post_id, COUNT(*) as count
            FROM likes
            WHERE type = 'post'
            GROUP BY post_id
        ) likes_count ON posts.id = likes_count.post_id
        LEFT JOIN (
            SELECT post_id, COUNT(*) as count
            FROM comments
            GROUP BY post_id
        ) comments_count ON posts.id = comments_count.post_id
        WHERE posts.user_id = ? AND posts.status = 'approved'
        ORDER BY posts.created_at DESC
        LIMIT 6
    ");
    $stmt->execute([$userId]);
    $approvedPostsWithCounts = $stmt->fetchAll();
    error_log("Approved posts with counts found: " . count($approvedPostsWithCounts));

    // If no approved posts, try to get any posts with counts (for debugging)
    if (empty($approvedPostsWithCounts)) {
        $stmt = $pdo->prepare("
            SELECT posts.*,
                   COALESCE(likes_count.count, 0) as like_count,
                   COALESCE(comments_count.count, 0) as comment_count
            FROM posts
            LEFT JOIN (
                SELECT post_id, COUNT(*) as count
                FROM likes
                WHERE type = 'post'
                GROUP BY post_id
            ) likes_count ON posts.id = likes_count.post_id
            LEFT JOIN (
                SELECT post_id, COUNT(*) as count
                FROM comments
                GROUP BY post_id
            ) comments_count ON posts.id = comments_count.post_id
            WHERE posts.user_id = ?
            ORDER BY posts.created_at DESC
            LIMIT 6
        ");
        $stmt->execute([$userId]);
        $allPostsWithCounts = $stmt->fetchAll();
        error_log("All posts with counts found: " . count($allPostsWithCounts));
        if (count($allPostsWithCounts) > 0) {
            error_log("All posts with counts data: " . print_r($allPostsWithCounts, true));
        }

        $userPosts = $allPostsWithCounts;
    } else {
        $userPosts = $approvedPostsWithCounts;
        error_log("Using approved posts with counts");
    }

    if (count($userPosts) > 0) {
        error_log("Sample post: " . print_r($userPosts[0], true));
    }
} catch (Exception $e) {
    error_log("Error fetching user posts: " . $e->getMessage());
    $userPosts = [];
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($userProfile['username']) ?> - Profile - Informatics A</title>
    <link href="<?= url('public/tailwind.css') ?>" rel="stylesheet">
    <?php require_once __DIR__ . '/includes/favicon.php'; ?>
    <style>
        /* Page transition animations */
        .page-transition-enter {
            opacity: 0;
            transform: translateY(20px);
        }

        .page-transition-enter-active {
            opacity: 1;
            transform: translateY(0);
            transition: opacity 0.6s ease-out, transform 0.6s ease-out;
        }

        /* Loading overlay */
        .page-loading-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(255, 255, 255, 0.9);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 9999;
            opacity: 0;
            visibility: hidden;
            transition: opacity 0.3s ease, visibility 0.3s ease;
        }

        .page-loading-overlay.show {
            opacity: 1;
            visibility: visible;
        }

        .loading-spinner {
            width: 40px;
            height: 40px;
            border: 4px solid #e5e7eb;
            border-top: 4px solid <?= $primary_color ?>;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        /* Smooth hover effects for navigation */
        .nav-link {
            position: relative;
            overflow: hidden;
        }

        .nav-link::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: rgba(255, 255, 255, 0.2);
            transition: left 0.5s;
        }

        .nav-link:hover::before {
            left: 100%;
        }

        /* Enhanced card animations */
        .profile-card {
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .profile-card:hover {
            transform: translateY(-8px) scale(1.02);
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.15), 0 0 0 1px <?= $primary_color ?>20;
        }

        .post-card {
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            overflow: hidden;
        }

        .post-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.3), transparent);
            transition: left 0.6s ease;
        }

        .post-card:hover::before {
            left: 100%;
        }

        .post-card:hover {
            transform: translateY(-12px) scale(1.03);
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.15), 0 0 0 1px <?= $primary_color ?>30;
        }

        /* Line clamp utility */
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

        /* Gradient text animation */
        .gradient-text {
            background: linear-gradient(135deg, <?= $primary_color ?> 0%, <?= $secondary_color ?> 100%);
            background-size: 200% 200%;
            background-clip: text;
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            animation: gradient-shift 3s ease-in-out infinite;
        }

        @keyframes gradient-shift {
            0%, 100% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
        }

        /* Pulse animation for loading states */
        @keyframes pulse-glow {
            0%, 100% {
                box-shadow: 0 0 20px <?= $primary_color ?>30;
                transform: scale(1);
            }
            50% {
                box-shadow: 0 0 40px <?= $primary_color ?>50;
                transform: scale(1.05);
            }
        }

        .pulse-glow {
            animation: pulse-glow 2s ease-in-out infinite;
        }

        /* Smooth hover transitions for buttons */
        .btn-hover-effect {
            position: relative;
            overflow: hidden;
        }

        .btn-hover-effect::after {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            width: 0;
            height: 0;
            background: rgba(255, 255, 255, 0.2);
            border-radius: 50%;
            transform: translate(-50%, -50%);
            transition: width 0.3s ease, height 0.3s ease;
        }

        .btn-hover-effect:hover::after {
            width: 300px;
            height: 300px;
        }

        /* Floating animation for profile card */
        @keyframes float {
            0%, 100% { transform: translateY(0px); }
            50% { transform: translateY(-5px); }
        }

        .floating {
            animation: float 3s ease-in-out infinite;
        }

        .post-card:nth-child(1) { animation-delay: 0.1s; }
        .post-card:nth-child(2) { animation-delay: 0.2s; }
        .post-card:nth-child(3) { animation-delay: 0.3s; }
        .post-card:nth-child(4) { animation-delay: 0.4s; }

        /* Enhanced responsive design */
        @media (max-width: 768px) {
            .profile-card:hover {
                transform: none;
            }

            .post-card:hover {
                transform: translateY(-4px);
            }
        }
    </style>
</head>
<body class="min-h-screen" style="background: linear-gradient(135deg, <?= $primary_color ?>10 0%, <?= $secondary_color ?>10 100%);">
    <!-- Loading Overlay -->
    <div id="pageLoadingOverlay" class="page-loading-overlay">
        <div class="loading-spinner"></div>
    </div>

    <?php include __DIR__ . '/includes/navbar.php'; ?>

    <!-- Main Content -->
    <main class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-8 mt-16 page-transition-enter" id="mainContent">
        <!-- Back Button -->
        <div class="mb-8">
            <?php
            // Determine the best back URL
            $backUrl = url('home'); // default fallback

            if (isset($_SERVER['HTTP_REFERER']) && !empty($_SERVER['HTTP_REFERER'])) {
                $referrer = $_SERVER['HTTP_REFERER'];

                // Check if referrer contains activity_detail
                if (strpos($referrer, 'activity_detail') !== false) {
                    // Make sure the referrer URL is properly formatted
                    $referrerUrl = parse_url($referrer);
                    if (isset($referrerUrl['query'])) {
                        // Preserve query parameters from activity_detail
                        $backUrl = $referrer;
                    } else {
                        // Reconstruct URL with current query parameters if missing
                        $backUrl = $referrer . (isset($_SERVER['QUERY_STRING']) ? '?' . $_SERVER['QUERY_STRING'] : '');
                    }
                } else {
                    // For other referrers, use them as-is
                    $backUrl = $referrer;
                }
            }
            ?>
            <a href="<?= htmlspecialchars($backUrl) ?>" class="nav-link inline-flex items-center gap-2 font-medium transition-colors group" style="color: <?= $primary_color ?>;" onmouseover="this.style.color='<?= $secondary_color ?>'" onmouseout="this.style.color='<?= $primary_color ?>'">
                <svg class="w-5 h-5 transition-transform group-hover:-translate-x-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                </svg>
                Kembali
            </a>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <!-- Profile Sidebar -->
            <div class="lg:col-span-1">
                <!-- Profile Card -->
                <div class="bg-white rounded-3xl shadow-2xl overflow-hidden mb-8 profile-card transform hover:scale-[1.02] transition-all duration-300">
                    <!-- Cover Image -->
                    <div class="h-32 relative" style="background: linear-gradient(135deg, <?= $primary_color ?> 0%, <?= $secondary_color ?> 100%);">
                        <div class="absolute inset-0 bg-black/10"></div>
                    </div>

                    <!-- Profile Picture and User Info Section -->
                    <div class="px-8 pb-8 -mt-16 relative">
                        <div class="text-center mb-6">
                            <!-- Profile Picture -->
                            <div class="inline-block mb-4">
                                <div class="w-32 h-32 bg-white rounded-full p-1.5 shadow-2xl ring-4 ring-white">
                                    <?php if (!empty($userProfile['profile_pic'])): ?>
                                        <img src="<?= upload_url(htmlspecialchars($userProfile['profile_pic'])) ?>"
                                             alt="Profile Picture"
                                             class="w-full h-full object-cover rounded-full ring-2 ring-gray-100">
                                    <?php else: ?>
                                        <img src="<?= url('public/default-avatar.php?initial=' . urlencode(substr($userProfile['username'], 0, 1)) . '&color=' . urlencode($primary_color)) ?>"
                                             alt="Default Avatar"
                                             class="w-full h-full object-cover rounded-full ring-2 ring-gray-100">
                                    <?php endif; ?>
                                </div>
                            </div>

                            <!-- User Info -->
                            <h1 class="text-3xl font-bold text-gray-900 mb-2 leading-tight">
                                <?= htmlspecialchars($userProfile['username']) ?>
                            </h1>
                            <?php if (!empty($userProfile['full_name'])): ?>
                                <p class="text-lg text-gray-600 mb-4 font-medium">
                                    <?= htmlspecialchars($userProfile['full_name']) ?>
                                </p>
                            <?php endif; ?>

                            <!-- Email Verification Badge -->
                            <?php if ($userProfile['email_verified']): ?>
                                <div class="inline-flex items-center px-3 py-1 rounded-full text-sm font-semibold mb-4 bg-green-100 text-green-800 border border-green-200">
                                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                    </svg>
                                    Email Terverifikasi
                                </div>
                            <?php elseif ($currentUser['id'] === $userProfile['id']): ?>
                                <div class="inline-flex items-center px-3 py-1 rounded-full text-sm font-semibold mb-4 bg-yellow-100 text-yellow-800 border border-yellow-200">
                                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                    </svg>
                                    Email Belum Verifikasi
                                </div>
                            <?php endif; ?>

                            <!-- Role Badge -->
                            <div class="inline-flex items-center px-4 py-2 rounded-full text-sm font-semibold mb-4 shadow-lg
                                <?php if ($userProfile['role'] === 'admin'): ?>
                                    bg-gradient-to-r from-red-500 to-pink-500 text-white
                                <?php elseif ($userProfile['role'] === 'korti'): ?>
                                    bg-gradient-to-r from-blue-500 to-cyan-500 text-white
                                <?php else: ?>
                                    bg-gradient-to-r from-green-500 to-emerald-500 text-white
                                <?php endif; ?>">
                                <span class="capitalize mr-2">
                                    <?php if ($userProfile['role'] === 'admin'): ?>
                                        👑
                                    <?php elseif ($userProfile['role'] === 'korti'): ?>
                                        ⭐
                                    <?php else: ?>
                                        🎓
                                    <?php endif; ?>
                                </span>
                                <?= ucfirst($userProfile['role']) ?>
                            </div>

                            <!-- Join Date -->
                            <div class="flex items-center justify-center gap-2 text-sm text-gray-600 mb-6">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                </svg>
                                <span class="bg-gray-50 px-3 py-1 rounded-full">Bergabung <?= date('F Y', strtotime($userProfile['created_at'])) ?></span>
                            </div>

                            <!-- Stats Cards -->
                            <div class="grid grid-cols-2 gap-4 mb-6">
                        <div class="bg-gradient-to-br from-emerald-50 via-green-100 to-teal-200 rounded-2xl p-4 text-center border-2 border-emerald-200 shadow-lg hover:shadow-emerald-200/50 transition-all duration-300 hover:scale-105" style="background: linear-gradient(135deg, <?= $success_color ?>20 0%, <?= $primary_color ?>20 100%); border-color: <?= $success_color ?>;">
                                    <div class="text-4xl font-bold mb-1" style="color: <?= $success_color ?>;">
                                        <?= number_format($userProfile['post_count'] ?? 0) ?>
                                    </div>
                                    <div class="text-sm font-semibold bg-emerald-100 px-2 py-1 rounded-full" style="background-color: <?= $success_color ?>30; color: <?= $success_color ?>;">Postingan</div>
                                </div>
                                <?php if ($currentUser['id'] === $userProfile['id']): ?>
                                    <div class="bg-gradient-to-br from-blue-50 via-indigo-100 to-purple-200 rounded-2xl p-4 text-center border border-blue-200 shadow-lg" style="background: linear-gradient(135deg, <?= $primary_color ?>20 0%, <?= $accent_color ?>20 100%); border-color: <?= $primary_color ?>;">
                                        <div class="text-sm font-semibold mb-1" style="color: <?= $primary_color ?>;">
                                            <?= $userProfile['email_verified'] ? '✅ Terverifikasi' : '⏳ Belum Verifikasi' ?>
                                        </div>
                                        <div class="text-xs" style="color: <?= $primary_color ?>;">Email</div>
                                        <?php if (!$userProfile['email_verified']): ?>
                                            <div class="mt-2">
                                                <a href="<?= url('verify_email') ?>" class="inline-flex items-center px-3 py-1 text-white text-xs rounded-full transition" style="background: linear-gradient(135deg, <?= $primary_color ?> 0%, <?= $secondary_color ?> 100%);" onmouseover="this.style.background='linear-gradient(135deg, <?= $secondary_color ?> 0%, <?= $primary_color ?> 100%)'" onmouseout="this.style.background='linear-gradient(135deg, <?= $primary_color ?> 0%, <?= $secondary_color ?> 100%)'"
                                                    Verifikasi Email
                                                </a>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Bio Section -->
                <?php if (!empty($userProfile['bio'])): ?>
                    <div class="bg-white rounded-2xl shadow-xl p-6 mb-6 profile-card hover:shadow-2xl transition-all duration-300">
                        <h3 class="text-lg font-bold text-gray-900 mb-4 flex items-center gap-2">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="color: <?= $primary_color ?>;">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                            </svg>
                            <span style="background: linear-gradient(135deg, <?= $primary_color ?> 0%, <?= $secondary_color ?> 100%); -webkit-background-clip: text; background-clip: text; color: transparent;">Bio</span>
                        </h3>
                        <div class="bg-gray-50 rounded-xl p-4 border-l-4" style="border-color: <?= $primary_color ?>;">
                            <p class="text-gray-700 leading-relaxed">
                                <?= nl2br(htmlspecialchars($userProfile['bio'])) ?>
                            </p>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Contact Section -->
                <?php if (!empty($userProfile['contact'])): ?>
                    <div class="bg-white rounded-2xl shadow-xl p-6 profile-card hover:shadow-2xl transition-all duration-300">
                        <h3 class="text-lg font-bold text-gray-900 mb-4 flex items-center gap-2">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="color: <?= $primary_color ?>;">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                            </svg>
                            <span style="background: linear-gradient(135deg, <?= $primary_color ?> 0%, <?= $secondary_color ?> 100%); -webkit-background-clip: text; background-clip: text; color: transparent;">Kontak</span>
                        </h3>
                        <div class="bg-gray-50 rounded-xl p-4 border-l-4" style="border-color: <?= $success_color ?>;">
                            <p class="text-gray-700 font-medium">
                                <?= htmlspecialchars($userProfile['contact']) ?>
                            </p>
                        </div>
                    </div>
                <?php endif; ?>
            </div>

        <!-- Posts Section -->
        <div class="lg:col-span-2">
            <div class="bg-white rounded-2xl shadow-xl p-8">
                <div class="flex items-center justify-between mb-8">
                    <h2 class="text-lg sm:text-xl lg:text-2xl font-bold text-gray-900 flex items-center gap-3">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="color: <?= $primary_color ?>;">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                        Postingan Terbaru
                    </h2>
                    <span class="inline-flex items-center px-3 py-2 sm:px-6 sm:py-3 rounded-full text-xs sm:text-sm font-bold text-white shadow-lg transition-all duration-300 hover:scale-105 border-2" style="background: linear-gradient(135deg, <?= $success_color ?> 0%, <?= $primary_color ?> 100%); border-color: <?= $success_color ?>;">
                        <svg class="w-3 h-3 sm:w-4 sm:h-4 mr-1 sm:mr-2 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                        <span class="truncate"> <?= count($userPosts) ?> Aktif</span>
                    </span>
                </div>

                <?php if (empty($userPosts)): ?>
                    <div class="text-center py-20">
                        <div class="w-24 h-24 mx-auto mb-8 rounded-full flex items-center justify-center" style="background: linear-gradient(135deg, <?= $primary_color ?>20 0%, <?= $secondary_color ?>20 100%);">
                            <svg class="w-12 h-12" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="color: <?= $primary_color ?>;">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                            </svg>
                        </div>
                        <h3 class="text-2xl font-semibold text-gray-900 mb-3">Belum ada postingan</h3>
                        <p class="text-gray-600 text-lg">Postingan yang dipublikasikan akan muncul di sini.</p>
                    </div>
                <?php else: ?>
                    <div class="grid gap-8 md:grid-cols-1 lg:grid-cols-2 xl:grid-cols-2">
                        <?php foreach ($userPosts as $post): ?>
                            <article class="group bg-white rounded-2xl overflow-hidden border border-gray-100 hover:border-blue-300 hover:shadow-2xl hover:shadow-blue-500/25 transition-all duration-700 post-card transform hover:-translate-y-3 hover:scale-[1.02]">
                                <!-- Post Image -->
                                <?php if (!empty($post['image'])): ?>
                                    <div class="relative aspect-[16/10] overflow-hidden">
                                        <img src="<?= upload_url(htmlspecialchars($post['image'])) ?>"
                                             alt="<?= htmlspecialchars($post['title']) ?>"
                                             class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-700">
                                        <div class="absolute inset-0 bg-gradient-to-t from-black/30 via-transparent to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-500"></div>
                                        <div class="absolute top-4 right-4 bg-white/90 backdrop-blur-sm rounded-full p-2 opacity-0 group-hover:opacity-100 transition-all duration-300 transform translate-x-2 group-hover:translate-x-0">
                                            <svg class="w-5 h-5 text-gray-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                                            </svg>
                                        </div>
                                    </div>
                                <?php endif; ?>

                                <!-- Post Content -->
                                <div class="p-6">
                                    <div class="mb-4">
                                <h3 class="text-xl text-gray-900 mb-3 line-clamp-2 group-hover:text-gray-900 transition-colors duration-300 leading-tight hover:scale-105 transform origin-left" style="color: <?= $primary_color ?> !important;" onmouseover="this.style.color='<?= $secondary_color ?>'" onmouseout="this.style.color='<?= $primary_color ?>'"
                                            <?= htmlspecialchars($post['title']) ?>
                                        </h3>
                                        <p class="text-gray-600 text-sm leading-relaxed line-clamp-3 group-hover:text-gray-700 transition-colors duration-300">
                                            <?= htmlspecialchars(substr(strip_tags($post['content']), 0, 150)) ?>
                                            <?= strlen(strip_tags($post['content'])) > 150 ? '...' : '' ?>
                                        </p>
                                    </div>

                                    <!-- Post Meta -->
                                    <div class="flex items-center justify-between mb-6">
                                        <div class="flex items-center gap-3 text-sm text-gray-500">
                                            <span class="flex items-center gap-2 bg-gray-50 px-3 py-1.5 rounded-full text-xs">
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                                </svg>
                                                <span class="font-medium text-gray-600"> <?= date('d/m/Y', strtotime($post['created_at'])) ?></span>
                                            </span>
                                        </div>
                                        <div class="flex items-center gap-4">
                                            <span class="flex items-center gap-1 text-sm font-semibold text-red-700 px-4 py-2 rounded-full border border-red-200 hover:border-red-300 hover:shadow-lg hover:shadow-red-500/20 transition-all duration-300 hover:scale-105 cursor-pointer" style="background: linear-gradient(135deg, <?= $accent_color ?>20 0%, #ff6b6b20 100%); color: <?= $accent_color ?>; border-color: <?= $accent_color ?>;" onmouseover="this.style.background='linear-gradient(135deg, <?= $accent_color ?>30 0%, #ff6b6b30 100%)'" onmouseout="this.style.background='linear-gradient(135deg, <?= $accent_color ?>20 0%, #ff6b6b20 100%)'"
                                                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24">
                                                    <path d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/>
                                                </svg>
                                                <?= $post['like_count'] ?? 0 ?>
                                            </span>
                                            <span class="flex items-center gap-1 text-sm font-semibold text-blue-700 px-4 py-2 rounded-full border border-blue-200 hover:border-blue-300 hover:shadow-lg hover:shadow-blue-500/20 transition-all duration-300 hover:scale-105 cursor-pointer" style="background: linear-gradient(135deg, <?= $primary_color ?>20 0%, <?= $secondary_color ?>20 100%); color: <?= $primary_color ?>; border-color: <?= $primary_color ?>;" onmouseover="this.style.background='linear-gradient(135deg, <?= $primary_color ?>30 0%, <?= $secondary_color ?>30 100%)'" onmouseout="this.style.background='linear-gradient(135deg, <?= $primary_color ?>20 0%, <?= $secondary_color ?>20 100%)'"
                                                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24">
                                                    <path d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z"/>
                                                </svg>
                                                <?= $post['comment_count'] ?? 0 ?>
                                            </span>
                                        </div>
                                    </div>

                                    <!-- View Post Button -->
                                    <a href="<?= url('activity_detail?id=' . $post['id']) ?>"
                                       class="relative block w-full text-center text-white py-4 px-6 rounded-xl hover:from-blue-700 hover:via-purple-700 hover:to-indigo-700 transition-all duration-300 font-semibold shadow-lg hover:shadow-xl transform hover:scale-105 hover:-translate-y-1 group overflow-hidden" style="background: linear-gradient(135deg, <?= $primary_color ?> 0%, <?= $secondary_color ?> 0%, <?= $accent_color ?> 100%);">
                                        <div class="absolute inset-0 bg-white/10 opacity-0 group-hover:opacity-100 transition-opacity duration-300"></div>
                                        <span class="flex items-center justify-center gap-2 relative z-10">
                                            <span class="font-bold">Lihat Postingan</span>
                                            <svg class="w-4 h-4 transition-transform group-hover:translate-x-1 group-hover:scale-110" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                                            </svg>
                                        </span>
                                    </a>
                                </div>
                            </article>
                        <?php endforeach; ?>
                    </div>

                    <?php if (count($userPosts) >= 6): ?>
                        <div class="text-center mt-8">
                            <button class="relative inline-flex items-center gap-2 bg-slate-600 text-white px-8 py-4 rounded-xl hover:bg-slate-700 transition-all duration-300 font-semibold shadow-lg hover:shadow-xl transform hover:scale-105 hover:-translate-y-1 group overflow-hidden" style="background: linear-gradient(135deg, <?= $primary_color ?> 0%, <?= $secondary_color ?> 100%);" onmouseover="this.style.background='linear-gradient(135deg, <?= $secondary_color ?> 0%, <?= $primary_color ?> 100%)'" onmouseout="this.style.background='linear-gradient(135deg, <?= $primary_color ?> 0%, <?= $secondary_color ?> 100%)'"
                                <div class="absolute inset-0 bg-white/10 opacity-0 group-hover:opacity-100 transition-opacity duration-300"></div>
                                <svg class="w-5 h-5 transition-transform group-hover:translate-y-1 relative z-10" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                                </svg>
                                <span class="relative z-10 font-bold">Muat Lebih Banyak</span>
                            </button>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
        </div>
    </main>

    <?php include __DIR__ . '/includes/footer.php'; ?>

    <script>
        // Page transition and loading management
        document.addEventListener('DOMContentLoaded', function() {
            // Detect if this is browser back/forward navigation
            const navigationType = window.performance.getEntriesByType('navigation')[0]?.type;

            if (navigationType && navigationType.includes('back_forward')) {
                // For browser back/forward - disable all animations and overlays
                disablePageAnimations();
            } else {
                // For normal page loads - enable animations
                initializePageTransitions();
            }

            // Setup navigation links with loading animation (only for normal clicks)
            setupNavigationAnimations();
        });

        // Listen for browser back/forward navigation
        window.addEventListener('pageshow', function(event) {
            // If this is from browser cache (back/forward)
            if (event.persisted) {
                disablePageAnimations();
            }
        });

        // Also listen for popstate events (browser back/forward)
        window.addEventListener('popstate', function(event) {
            disablePageAnimations();
        });

        function disablePageAnimations() {
            // Hide loading overlay immediately and completely
            const loadingOverlay = document.getElementById('pageLoadingOverlay');
            if (loadingOverlay) {
                loadingOverlay.style.display = 'none';
                loadingOverlay.classList.remove('opacity-100', 'visible');
                loadingOverlay.classList.add('opacity-0', 'invisible');
            }

            // Remove animation classes from main content and ensure visibility
            const mainContent = document.getElementById('mainContent');
            if (mainContent) {
                mainContent.classList.remove('page-transition-enter');
                mainContent.style.opacity = '1';
                mainContent.style.transform = 'none';
                mainContent.style.transition = 'none'; // Disable transitions for browser nav
            }

            // Force page to be fully visible
            document.body.style.opacity = '1';
            document.body.style.visibility = 'visible';
        }

        function initializePageTransitions() {
            const mainContent = document.getElementById('mainContent');
            const loadingOverlay = document.getElementById('pageLoadingOverlay');

            // Animate page entrance only if not from browser back/forward
            if (mainContent && !window.performance.getEntriesByType('navigation')[0]?.type?.includes('back_forward')) {
                setTimeout(() => {
                    mainContent.classList.remove('page-transition-enter');
                    mainContent.classList.add('page-transition-enter-active');
                }, 100);
            }

            // Hide loading overlay if visible
            if (loadingOverlay) {
                // Check if we're coming from browser back/forward
                const navigationType = window.performance.getEntriesByType('navigation')[0]?.type;
                if (navigationType && navigationType.includes('back_forward')) {
                    // Hide loading overlay immediately for browser navigation
                    loadingOverlay.classList.remove('opacity-100', 'visible');
                    loadingOverlay.classList.add('opacity-0', 'invisible');
                } else {
                    // Normal page load - hide after animation
                    setTimeout(() => {
                        loadingOverlay.classList.remove('opacity-100', 'visible');
                        loadingOverlay.classList.add('opacity-0', 'invisible');
                    }, 500);
                }
            }
        }

        function setupNavigationAnimations() {
            // Add loading animation to navigation links
            const navLinks = document.querySelectorAll('.nav-link, a[href*="dashboard"], a[href*="announcement"], a[href*="activity_detail"]');

            navLinks.forEach(link => {
                link.addEventListener('click', function(e) {
                    const href = this.getAttribute('href');
                    if (href && !href.startsWith('#') && !href.startsWith('http')) {
                        e.preventDefault();
                        showLoadingAndNavigate(href);
                    }
                });
            });
        }

        function showLoadingAndNavigate(targetUrl) {
            const loadingOverlay = document.getElementById('pageLoadingOverlay');
            const mainContent = document.getElementById('mainContent');

            if (loadingOverlay && mainContent) {
                // Show loading overlay
                loadingOverlay.classList.add('opacity-100', 'visible');
                loadingOverlay.classList.remove('opacity-0', 'invisible');

                // Animate content out
                mainContent.classList.remove('page-transition-enter-active');
                mainContent.classList.add('opacity-0', 'translate-y-4');

                // Navigate after animation
                setTimeout(() => {
                    window.location.href = targetUrl;
                }, 400);
            } else {
                // Fallback if elements not found
                window.location.href = targetUrl;
            }
        }
    </script>
</body>
</html>
