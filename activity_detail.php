<?php
ini_set("display_errors", 1);
ini_set("display_startup_errors", 1);
error_reporting(E_ALL);
// activity_detail.php - Detail Kegiatan
session_start();
require_once __DIR__ . "/src/config/db.php";
require_once __DIR__ . "/src/config/urls.php";
require_once __DIR__ . "/src/helpers/session.php";

// Get activity ID
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if (!$id) {
    exit();
}

// Get activity detail
try {
    // Check if deleted_at column exists
    $deletedAtCheck = $pdo->query("SHOW COLUMNS FROM posts LIKE 'deleted_at'")->fetch();
    $deletedAtCondition = $deletedAtCheck ? 'AND posts.deleted_at IS NULL' : '';

    $stmt = $pdo->prepare("
        SELECT posts.*, users.username, users.profile_pic
        FROM posts
        JOIN users ON posts.user_id = users.id
        WHERE posts.id = ? AND posts.status = 'approved' {$deletedAtCondition}
    ");
    $stmt->execute([$id]);
    $activity = $stmt->fetch();

    if (!$activity) {
        header("Location: " . url('activities'));
        exit();
    }

    // Get all images for this activity
    $stmt = $pdo->prepare("SELECT image_path, image_order FROM post_images WHERE post_id = ? ORDER BY image_order");
    $stmt->execute([$id]);
    $activity['images'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (Exception $e) {
    header("Location: " . url('activities'));
    exit();
}

// Get comments for this activity
try {
    $stmt = $pdo->prepare("
        SELECT comments.*, users.username, users.profile_pic 
        FROM comments 
        JOIN users ON comments.user_id = users.id 
        WHERE comments.post_id = ? 
        ORDER BY comments.created_at DESC
    ");
    $stmt->execute([$id]);
    $comments = $stmt->fetchAll();
} catch (Exception $e) {
    $comments = [];
}

// Load theme colors from database
$primary_color = "#1e3a8a"; // default
$secondary_color = "#1e40af"; // default
$accent_color = "#ec4899"; // default

try {
    $stmt = $pdo->query(
        "SELECT setting_key, setting_value FROM site_settings WHERE setting_key IN ('primary_color', 'secondary_color', 'accent_color', 'site_name')",
    );
    $settings = [];
    foreach ($stmt->fetchAll() as $row) {
        $settings[$row["setting_key"]] = $row["setting_value"];
    }
    $primary_color = $settings["primary_color"] ?? $primary_color;
    $secondary_color = $settings["secondary_color"] ?? $secondary_color;
    $accent_color = $settings["accent_color"] ?? $accent_color;
    $site_name = $settings["site_name"] ?? "Informatics A";
} catch (Exception $e) {
    // Use default colors if database fails
    $site_name = "Informatics A";
}

$comment_success = "";
$comment_error = "";
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_comment']) && isLoggedIn()) {
    $comment_text = trim($_POST['comment'] ?? '');
    
    if (empty($comment_text)) {
        $comment_error = "Komentar tidak boleh kosong.";
    } elseif (strlen($comment_text) < 3) {
        $comment_error = "Komentar minimal 3 karakter.";
    } else {
        try {
            $user = getCurrentUser();
            $stmt = $pdo->prepare("INSERT INTO comments (post_id, user_id, comment) VALUES (?, ?, ?)");
            if ($stmt->execute([$id, $user['id'], $comment_text])) {
                $comment_success = "Komentar berhasil ditambahkan!";
                // Redirect to prevent resubmission on refresh (PRG pattern)
                header("Location: " . $_SERVER['REQUEST_URI']);
                exit();
            }
        } catch (Exception $e) {
            $comment_error = "Gagal menambahkan komentar.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($activity['title']) ?> - <?= htmlspecialchars($site_name) ?></title>
    <link href="<?= url('public/tailwind.css') ?>" rel="stylesheet">
    <?php require_once __DIR__ . '/includes/favicon.php'; ?>
    <style>
        /* Carousel styles for activity detail - Fixed version */
        .carousel-container {
            position: relative;
            width: 100%;
            height: 100%;
        }

        .carousel-slide {
            position: absolute;
            inset: 0; /* top: 0, left: 0, right: 0, bottom: 0 */
            opacity: 0;
            transition: opacity 0.3s ease-in-out;
            z-index: 0;
        }

        .carousel-slide.opacity-100 {
            opacity: 1;
            z-index: 1;
        }

        .carousel-slide.opacity-0 {
            opacity: 0;
            z-index: 0;
        }

        /* Modern Dots Navigation - Hero Style */
        .dots-nav {
            position: absolute;
            bottom: 1.5rem;
            left: 50%;
            transform: translateX(-50%);
            z-index: 10;
            display: flex;
            gap: 0.5rem;
            padding: 0.5rem;
            background: rgba(0, 0, 0, 0.3);
            border-radius: 2rem;
            backdrop-filter: blur(4px);
        }

        /* Mobile responsive dots */
        @media (max-width: 640px) {
            .dots-nav {
                bottom: 0.75rem;
                gap: 0.25rem;
                padding: 0.4rem 0.5rem;
            }

            .carousel-dot {
                width: 0.4rem;
                height: 0.4rem;
                border-radius: 0.2rem;
            }

            .carousel-dot.active {
                width: 1.25rem;
                height: 0.4rem;
                border-radius: 0.2rem;
            }

            .carousel-dot.inactive {
                width: 0.4rem;
                height: 0.4rem;
                opacity: 0.5;
            }
        }

        .carousel-dot {
            width: 0.75rem;
            height: 0.75rem;
            border-radius: 9999px;
            background: rgba(255, 255, 255, 0.3);
            backdrop-filter: blur(12px);
            border: none;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .carousel-dot:hover {
            background: <?= $primary_color ?>99;
            transform: scale(1.1);
        }

        .carousel-dot.active {
            width: 3rem;
            background: <?= $primary_color ?>;
            box-shadow: 0 0 40px <?= $secondary_color ?>99;
        }

        .carousel-dot.inactive {
            width: 0.75rem;
            height: 0.75rem;
            background: rgba(255, 255, 255, 0.5);
        }
        </style>
</head>
<body class="min-h-screen" style="background: linear-gradient(135deg, <?= $primary_color ?>10 0%, <?= $secondary_color ?>10 100%);">
    <?php include __DIR__ . '/includes/navbar.php'; ?>

    <!-- Main Content -->
    <main class="max-w-5xl mx-auto px-6 py-8 mt-16">
        <!-- Back Button -->
        <a href="<?= url('activities') ?>" class="inline-flex items-center gap-2 font-medium mb-6 transition" style="color: <?= $primary_color ?>;" onmouseover="this.style.color='<?= $secondary_color ?>'" onmouseout="this.style.color='<?= $primary_color ?>'">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
            </svg>
            Kembali ke Kegiatan
        </a>

        <!-- Activity Detail Card -->
        <article class="bg-white rounded-2xl shadow-xl overflow-hidden mb-8">
            <!-- Images -->
            <?php if (!empty($activity['images'])): ?>
                <?php if (count($activity['images']) == 1): ?>
                    <!-- Single image -->
                    <div class="w-full overflow-hidden bg-gray-100 flex items-center justify-center p-6">
                        <img src="<?= htmlspecialchars(url('public/uploads/' . $activity['images'][0]['image_path'])) ?>"
                             alt="<?= htmlspecialchars($activity['title']) ?>"
                             class="w-auto h-64 object-contain rounded-lg shadow-md">
                    </div>
                <?php else: ?>
                    <!-- Multiple images carousel -->
                    <div class="w-full bg-gray-100 p-6">
                        <div class="relative max-w-4xl mx-auto">
                            <div class="aspect-[16/9] overflow-hidden rounded-lg bg-white shadow-lg relative">
                                <!-- Carousel Container -->
                                <div class="carousel-container relative w-full h-full" id="activity-carousel-<?= $activity['id'] ?>">
                                    <?php foreach ($activity['images'] as $index => $image): ?>
                                        <div class="carousel-slide absolute inset-0 transition-opacity duration-300 <?= $index === 0 ? 'opacity-100' : 'opacity-0' ?>">
                                            <img src="<?= htmlspecialchars(url('public/uploads/' . $image['image_path'])) ?>"
                                                 alt="<?= htmlspecialchars($activity['title']) ?>"
                                                 class="w-full h-full object-cover">
                                        </div>
                                    <?php endforeach; ?>
                                </div>

                                <?php if (count($activity['images']) > 1): ?>
                                    <!-- Navigation Dots - Hero Style -->
                                    <div class="dots-nav">
                                        <?php foreach ($activity['images'] as $index => $image): ?>
                                            <button class="carousel-dot <?= $index === 0 ? 'active' : 'inactive' ?>"
                                                    onclick="showSlide(<?= $activity['id'] ?>, <?= $index ?>)">
                                            </button>
                                        <?php endforeach; ?>
                                    </div>

                                    <!-- Navigation Arrows -->
                                    <button class="absolute left-4 top-1/2 transform -translate-y-1/2 bg-black/50 hover:bg-black/70 text-white p-3 rounded-full transition-all duration-300 z-10"
                                            onclick="prevSlide(<?= $activity['id'] ?>)">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                                        </svg>
                                    </button>
                                    <button class="absolute right-4 top-1/2 transform -translate-y-1/2 bg-black/50 hover:bg-black/70 text-white p-3 rounded-full transition-all duration-300 z-10"
                                            onclick="nextSlide(<?= $activity['id'] ?>)">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                                        </svg>
                                    </button>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            <?php else: ?>
                <div class="w-full h-96 flex items-center justify-center" style="background: linear-gradient(135deg, <?= $primary_color ?> 0%, <?= $secondary_color ?> 100%);">
                    <svg class="w-32 h-32 text-white opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                </div>
            <?php endif; ?>

            <!-- Content -->
            <div class="p-8">
                <!-- Title -->
                <h1 class="text-4xl font-bold mb-4" style="background: linear-gradient(135deg, <?= $primary_color ?> 0%, <?= $secondary_color ?> 100%); -webkit-background-clip: text; background-clip: text; color: transparent;"><?= htmlspecialchars($activity['title']) ?></h1>

                <!-- Meta Info -->
                <div class="flex flex-wrap items-center gap-6 text-gray-600 mb-6 pb-6 border-b border-gray-200">
                    <div class="flex items-center gap-2">
                        <?php if (isset($activity['profile_pic']) && !empty($activity['profile_pic'])): ?>
                            <img src=" <?= htmlspecialchars(url('public/uploads/' . basename($activity['profile_pic']))) ?>" alt="Author" class="w-8 h-8 rounded-full object-cover">
                        <?php else: ?>
                            <img src="<?= url('public/default-avatar.php?initial=' . urlencode(substr($activity['username'], 0, 1)) . '&color=' . urlencode($primary_color)) ?>" 
                                 alt="Default Avatar" 
                                 class="w-8 h-8 rounded-full object-cover">
                        <?php endif; ?>
                        <div class="flex items-center gap-2">
                            <span class="font-medium">
                                <a href="user_profile.php?id=<?= $activity['user_id'] ?>" class="transition" onmouseover="this.style.color='<?= $secondary_color ?>'" onmouseout="this.style.color='<?= $primary_color ?>'">
                                    <?= htmlspecialchars($activity['username']) ?>
                                </a>
                            </span>
                        </div>
                    </div>
                    <div class="flex items-center gap-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="color: <?= $primary_color ?>;">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                        </svg>
                        <span><?= date('d F Y', strtotime($activity['date'])) ?></span>
                    </div>
                    <div class="flex items-center gap-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="color: <?= $primary_color ?>;">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        <span><?= date('H:i', strtotime($activity['created_at'])) ?> WIB</span>
                    </div>
                </div>

                <!-- Description -->
                <div class="prose max-w-none">
                    <div class="text-gray-700 text-lg leading-relaxed whitespace-pre-line">
                        <?= nl2br(htmlspecialchars($activity['content'])) ?>
                    </div>
                </div>

                <!-- Like Button for Post -->
                <?php if (isLoggedIn()): ?>
                    <?php
                    $postLiked = isLiked($activity['id'], 'post');
                    $postLikeCount = getLikeCount($activity['id'], 'post');
                    ?>
                    <div class="mt-6 pt-6 border-t border-gray-200">
                        <div class="flex items-center gap-4">
                            <button onclick="toggleLike(<?= $activity['id'] ?>, 'post')"
                                    data-target-id="<?= $activity['id'] ?>" data-type="post"
                                    class="inline-flex items-center gap-2 px-4 py-2 rounded-lg transition-all duration-200 <?= $postLiked ? 'bg-red-100 text-red-600 hover:bg-red-200' : 'bg-gray-100 text-gray-600 hover:bg-gray-200' ?>">
                                <svg class="w-5 h-5" fill="<?= $postLiked ? 'currentColor' : 'none' ?>" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/>
                                </svg>
                                <span class="font-medium">Like</span>
                            </button>
                            <?php if ($postLikeCount > 0): ?>
                                <button onclick="showLikers(<?= $activity['id'] ?>, 'post')"
                                        data-target-id="<?= $activity['id'] ?>" data-type="post" data-count="true"
                                        class="font-medium transition-colors" style="color: <?= $primary_color ?>;" onmouseover="this.style.color='<?= $secondary_color ?>'" onmouseout="this.style.color='<?= $primary_color ?>'">
                                    <?= $postLikeCount ?> orang menyukai ini
                                </button>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </article>

        <!-- Comments Section -->
        <div class="bg-white rounded-2xl shadow-xl p-8">
            <h2 class="text-2xl font-bold mb-6 flex items-center gap-2" style="color: <?= $primary_color ?>;">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z"/>
                </svg>
                Komentar (<?= count($comments) ?>)
            </h2>

            <!-- Add Comment Form (Only for logged in users) -->
            <?php if (isLoggedIn()): ?>
                <div class="mb-8 p-6 rounded-xl" style="background: linear-gradient(135deg, <?= $primary_color ?>10 0%, <?= $secondary_color ?>10 100%);">
                    <h3 class="font-semibold text-gray-900 mb-4">Tambah Komentar</h3>
                    
                    <?php if ($comment_success): ?>
                        <div class="mb-4 p-4 bg-green-100 border border-green-400 text-green-700 rounded-lg">
                            <?= htmlspecialchars($comment_success) ?>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($comment_error): ?>
                        <div class="mb-4 p-4 bg-red-100 border border-red-400 text-red-700 rounded-lg">
                            <?= htmlspecialchars($comment_error) ?>
                        </div>
                    <?php endif; ?>
                    
                    <form method="POST" class="space-y-4">
                        <textarea name="comment" rows="3" required 
                                  class="w-full px-4 py-3 border-2 border-gray-300 rounded-lg focus:outline-none resize-none" style="focus:border-color: <?= $primary_color ?>;" placeholder="Tulis komentar Anda..."></textarea>
                        <button type="submit" name="add_comment" 
                                class="px-6 py-2 rounded-lg font-medium transition" style="background: linear-gradient(135deg, <?= $primary_color ?>10 0%, <?= $secondary_color ?>10 100%); color: <?= $primary_color ?>;" onmouseover="this.style.background='linear-gradient(135deg, <?= $primary_color ?> 0%, <?= $secondary_color ?> 100%)'; this.style.color='white';" onmouseout="this.style.background='linear-gradient(135deg, <?= $primary_color ?>10 0%, <?= $secondary_color ?>10 100%)'; this.style.color='<?= $primary_color ?>';">
                            Kirim Komentar
                        </button>
                    </form>
                </div>
            <?php else: ?>
                <div class="mb-8 p-6 bg-gray-100 rounded-xl text-center">
                    <p class="text-gray-600 mb-4">Silakan login untuk memberikan komentar</p>
                    <a href="<?= url('login') ?>" class="inline-block px-6 py-2 rounded-lg font-medium transition" style="background: linear-gradient(135deg, <?= $primary_color ?> 0%, <?= $secondary_color ?> 100%); color: white;" onmouseover="this.style.background='linear-gradient(135deg, <?= $secondary_color ?> 0%, <?= $primary_color ?> 100%)'" onmouseout="this.style.background='linear-gradient(135deg, <?= $primary_color ?> 0%, <?= $secondary_color ?> 100%)'">
                        Login
                    </a>
                </div>
            <?php endif; ?>

            <!-- Comments List -->
            <?php if (empty($comments)): ?>
                <div class="text-center py-12 text-gray-500">
                    <svg class="w-16 h-16 mx-auto mb-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/>
                    </svg>
                    <p class="text-lg">Belum ada komentar. Jadilah yang pertama berkomentar!</p>
                </div>
            <?php else: ?>
                <div class="space-y-4">
                    <?php foreach ($comments as $comment): ?>
                        <div class="p-4 bg-gray-50 rounded-lg hover:bg-gray-100 transition">
                            <div class="flex items-start gap-3">
                                <div class="w-10 h-10 rounded-full flex items-center justify-center text-white font-bold flex-shrink-0 overflow-hidden" style="background: linear-gradient(135deg, <?= $primary_color ?> 0%, <?= $secondary_color ?> 100%);">
                                    <?php if ($comment['profile_pic']): ?>
                                        <img src="<?= htmlspecialchars(url('public/uploads/' . htmlspecialchars($comment['profile_pic']))) ?>" alt="Avatar" class="w-full h-full object-cover">
                                    <?php else: ?>
                                        <img src="<?= url('public/default-avatar.php?initial=' . urlencode(substr($comment['username'], 0, 1)) . '&color=' . urlencode($primary_color)) ?>" 
                                             alt="Default Avatar" 
                                             class="w-full h-full object-cover">
                                    <?php endif; ?>
                                </div>
                                <div class="flex-1">
                                    <div class="flex items-center gap-2 mb-1">
                                        <span class="font-semibold text-gray-900">
                                            <a href="user_profile.php?id=<?= $comment['user_id'] ?>" class="transition" onmouseover="this.style.color='<?= $secondary_color ?>'" onmouseout="this.style.color='<?= $primary_color ?>'">
                                                <?= htmlspecialchars($comment['username']) ?>
                                            </a>
                                        </span>
                                        <span class="text-sm text-gray-500">•</span>
                                        <span class="text-sm text-gray-500"><?= date('d M Y, H:i', strtotime($comment['created_at'])) ?></span>
                                    </div>
                                    <p class="text-gray-700 mb-2"><?= nl2br(htmlspecialchars($comment['comment'])) ?></p>
                                    <!-- Like Button for Comment -->
                                    <?php if (isLoggedIn()): ?>
                                        <?php
                                        $commentLiked = isLiked($comment['id'], 'comment');
                                        $commentLikeCount = getLikeCount($comment['id'], 'comment');
                                        ?>
                                        <div class="flex items-center gap-2">
                                            <button onclick="toggleLike(<?= $comment['id'] ?>, 'comment')"
                                                    data-target-id="<?= $comment['id'] ?>" data-type="comment"
                                                    class="inline-flex items-center gap-1 px-2 py-1 text-sm rounded transition-all duration-200 <?= $commentLiked ? 'bg-red-100 text-red-600 hover:bg-red-200' : 'bg-gray-100 text-gray-600 hover:bg-gray-200' ?>">
                                                <svg class="w-3 h-3" fill="<?= $commentLiked ? 'currentColor' : 'none' ?>" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/>
                                                </svg>
                                                <span>Like</span>
                                            </button>
                                            <?php if ($commentLikeCount > 0): ?>
                                                <button onclick="showLikers(<?= $comment['id'] ?>, 'comment')"
                                                        data-target-id="<?= $comment['id'] ?>" data-type="comment" data-count="true"
                                                        class="text-sm font-medium transition-colors" style="color: <?= $primary_color ?>;" onmouseover="this.style.color='<?= $secondary_color ?>'" onmouseout="this.style.color='<?= $primary_color ?>'">
                                                    <?= $commentLikeCount ?> likes
                                                </button>
                                            <?php endif; ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </main>

    <?php include __DIR__ . '/includes/footer.php'; ?>

    <!-- Likers Modal -->
    <div id="likersModal" class="fixed inset-0 bg-black bg-opacity-50 hidden z-50" onclick="closeLikersModal()">
        <div class="flex items-center justify-center min-h-screen p-4" onclick="event.stopPropagation()">
            <div class="bg-white rounded-2xl max-w-md w-full max-h-[80vh] overflow-hidden shadow-2xl transform transition-all scale-95 opacity-0" id="modalContent">
                <div class="p-6 border-b border-gray-200">
                    <div class="flex items-center justify-between">
                        <h3 class="text-xl font-bold text-gray-900" id="modalTitle">Disukai oleh</h3>
                        <button onclick="closeLikersModal()" class="text-gray-400 hover:text-gray-600 transition-colors p-2 rounded-full hover:bg-gray-100">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>
                </div>
                <div class="p-6 max-h-96 overflow-y-auto" id="likersList">
                    <div class="text-center text-gray-500 py-8">
                        <div class="animate-spin w-8 h-8 border-2 border-blue-600 border-t-transparent rounded-full mx-auto mb-4"></div>
                        <p>Loading...</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
    

    <script>
        // Carousel functions for activity detail - Debug version
        let currentSlide = {};

        function showSlide(activityId, slideIndex) {
            console.log('showSlide called with:', activityId, slideIndex);

            const container = document.getElementById(`activity-carousel-${activityId}`);
            if (!container) {
                console.error('Carousel container not found');
                return;
            }

            const slides = container.querySelectorAll('.carousel-slide');

            // Try multiple ways to find dots - more robust approach
            let dots = [];

            // Method 1: Direct search in container
            dots = container.querySelectorAll('.carousel-dot');

            // Method 2: Search in dots-nav container
            if (dots.length === 0) {
                const dotsNav = container.querySelector('.dots-nav');
                if (dotsNav) {
                    dots = dotsNav.querySelectorAll('.carousel-dot');
                }
            }

            // Method 3: Search in entire document for this carousel
            if (dots.length === 0) {
                dots = document.querySelectorAll(`#activity-carousel-${activityId} .carousel-dot`);
            }

            // Method 4: Search by parent elements
            if (dots.length === 0) {
                const carouselWrapper = container.closest('.aspect-\\[16\\/9\\]');
                if (carouselWrapper) {
                    dots = carouselWrapper.querySelectorAll('.carousel-dot');
                }
            }

            console.log('Found slides:', slides.length, 'dots:', dots.length, 'using method:', dots.length > 0 ? 'success' : 'failed');

            // Update slides
            slides.forEach((slide, index) => {
                if (index === slideIndex) {
                    slide.classList.remove('opacity-0');
                    slide.classList.add('opacity-100');
                    console.log('Show slide', index);
                } else {
                    slide.classList.remove('opacity-100');
                    slide.classList.add('opacity-0');
                    console.log('Hide slide', index);
                }
            });

            // Update dots
            if (dots.length > 0) {
                dots.forEach((dot, index) => {
                    console.log('Processing dot', index, 'current classes:', dot.className);

                    if (index === slideIndex) {
                        // Remove inactive classes and add active class
                        dot.classList.remove('inactive');
                        dot.classList.add('active');

                        // Use responsive sizing
                        if (window.innerWidth <= 640) {
                            // Mobile sizing
                            dot.style.width = '2rem';
                            dot.style.height = '0.5rem';
                            dot.style.borderRadius = '0.25rem';
                        } else {
                            // Desktop sizing
                            dot.style.width = '3rem';
                            dot.style.height = '0.75rem';
                            dot.style.borderRadius = '9999px';
                        }

                        dot.style.background = '<?= $primary_color ?>';
                        dot.style.boxShadow = '0 0 40px <?= $secondary_color ?>99';
                        console.log('Activated dot', index, 'new classes:', dot.className);
                    } else {
                        // Remove active class and add inactive class
                        dot.classList.remove('active');
                        dot.classList.add('inactive');

                        // Use responsive sizing
                        if (window.innerWidth <= 640) {
                            // Mobile sizing
                            dot.style.width = '0.5rem';
                            dot.style.height = '0.5rem';
                            dot.style.borderRadius = '9999px';
                        } else {
                            // Desktop sizing
                            dot.style.width = '0.75rem';
                            dot.style.height = '0.75rem';
                            dot.style.borderRadius = '9999px';
                        }

                        dot.style.background = 'rgba(255, 255, 255, 0.5)';
                        dot.style.boxShadow = 'none';
                        console.log('Deactivated dot', index, 'new classes:', dot.className);
                    }
                });

                // Force style recalculation
                dots.forEach(dot => {
                    dot.offsetHeight; // Trigger reflow
                });
            } else {
                console.error('No dots found to update!');
            }

            currentSlide[activityId] = slideIndex;
            console.log(`Switched to slide ${slideIndex} for activity ${activityId}`);
        }

        function nextSlide(activityId) {
            console.log('nextSlide called for activity:', activityId);

            const container = document.getElementById(`activity-carousel-${activityId}`);
            if (!container) {
                console.error('Carousel container not found');
                return;
            }

            const slides = container.querySelectorAll('.carousel-slide');
            const totalSlides = slides.length;

            if (totalSlides <= 1) {
                console.log('Only one slide, no navigation needed');
                return;
            }

            const currentIndex = currentSlide[activityId] || 0;
            const nextIndex = (currentIndex + 1) % totalSlides;

            console.log(`Next: ${currentIndex} -> ${nextIndex} (total: ${totalSlides})`);
            showSlide(activityId, nextIndex);
        }

        function prevSlide(activityId) {
            console.log('prevSlide called for activity:', activityId);

            const container = document.getElementById(`activity-carousel-${activityId}`);
            if (!container) {
                console.error('Carousel container not found');
                return;
            }

            const slides = container.querySelectorAll('.carousel-slide');
            const totalSlides = slides.length;

            if (totalSlides <= 1) {
                console.log('Only one slide, no navigation needed');
                return;
            }

            const currentIndex = currentSlide[activityId] || 0;
            const prevIndex = currentIndex === 0 ? totalSlides - 1 : currentIndex - 1;

            console.log(`Previous: ${currentIndex} -> ${prevIndex} (total: ${totalSlides})`);
            showSlide(activityId, prevIndex);
        }

        // Initialize carousel when DOM is ready
        document.addEventListener('DOMContentLoaded', function() {
            console.log('DOM Content Loaded - initializing carousel');

            const carousel = document.getElementById('activity-carousel-<?= $activity['id'] ?>');
            if (carousel) {
                console.log('Carousel found, initializing...');

                const slides = carousel.querySelectorAll('.carousel-slide');
                const dots = carousel.querySelectorAll('.carousel-dot');

                console.log('Slides found:', slides.length);
                console.log('Dots found:', dots.length);

                // Set initial state
                slides.forEach((slide, index) => {
                    console.log('Slide', index, 'classes:', slide.className);
                });

                dots.forEach((dot, index) => {
                    console.log('Dot', index, 'classes:', dot.className);
                });

                // Initialize current slide tracking
                currentSlide[<?= $activity['id'] ?? 0 ?>] = 0;

                // Start auto-slide if multiple images
                <?php if (isset($activity['images']) && count($activity['images']) > 1): ?>
                console.log('Starting auto-slide...');
                startAutoSlide(<?= $activity['id'] ?>);
                <?php endif; ?>

            } else {
                console.error('Carousel container not found on DOM ready');
            }
        });

        // Auto-slide functionality (optional)
        <?php if (isset($activity['images']) && count($activity['images']) > 1): ?>
        let autoSlideInterval;

        function startAutoSlide(activityId) {
            console.log('Starting auto-slide for activity:', activityId);

            if (autoSlideInterval) {
                clearInterval(autoSlideInterval);
            }

            autoSlideInterval = setInterval(() => {
                console.log('Auto-slide trigger');
                nextSlide(activityId);
            }, 4000); // Change every 4 seconds
        }

        function stopAutoSlide() {
            console.log('Stopping auto-slide');
            if (autoSlideInterval) {
                clearInterval(autoSlideInterval);
                autoSlideInterval = null;
            }
        }

        // Pause auto-slide on hover (only if carousel exists)
        document.addEventListener('DOMContentLoaded', function() {
            const carousel = document.getElementById('activity-carousel-<?= $activity['id'] ?>');
            if (carousel) {
                carousel.addEventListener('mouseenter', stopAutoSlide);
                carousel.addEventListener('mouseleave', () => {
                    console.log('Mouse leave, restarting auto-slide');
                    startAutoSlide(<?= $activity['id'] ?>);
                });
            }
        });
        <?php endif; ?>

        // Add resize listener to update dots when screen size changes
        window.addEventListener('resize', function() {
            // Re-run showSlide with current slide index to update dot sizes
            <?php if (isset($activity['images']) && count($activity['images']) > 1): ?>
            const currentIndex = currentSlide[<?= $activity['id'] ?>] || 0;
            showSlide(<?= $activity['id'] ?>, currentIndex);
            <?php endif; ?>
        });

        // Like functionality
        function toggleLike(targetId, type) {
            console.log('toggleLike called with:', targetId, type);

            const button = document.querySelector(`button[data-target-id="${targetId}"][data-type="${type}"]`);
            console.log('Button found:', button);

            if (!button) {
                console.error('Button not found for target:', targetId, 'type:', type);
                alert('Button not found');
                return;
            }

            const originalText = button.innerHTML;
            button.innerHTML = '<svg class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" class="opacity-25"></circle><path fill="currentColor" class="opacity-75" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg> Loading...';
            button.disabled = true;

            fetch('<?= url("ajax/toggle_like.php") ?>', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'target_id=' + targetId + '&type=' + type
            })
            .then(response => {
                console.log('Response status:', response.status);
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                return response.json();
            })
            .then(data => {
                console.log('Response data:', data);

                if (data && data.success) {
                    const icon = button.querySelector('svg');
                    const text = button.querySelector('span');

                    if (data.liked) {
                        button.className = button.className.replace(/bg-gray-100 text-gray-600 hover:bg-gray-200/, 'bg-red-100 text-red-600 hover:bg-red-200');
                        if (icon) icon.setAttribute('fill', 'currentColor');
                        if (text) text.textContent = 'Liked';
                    } else {
                        button.className = button.className.replace(/bg-red-100 text-red-600 hover:bg-red-200/, 'bg-gray-100 text-gray-600 hover:bg-gray-200');
                        if (icon) icon.setAttribute('fill', 'none');
                        if (text) text.textContent = 'Like';
                    }

                    updateLikeCount(targetId, type, data.like_count);
                } else {
                    const errorMessage = data && data.message ? data.message : 'Unknown error occurred';
                    alert('Error: ' + errorMessage);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error processing like request: ' + error.message);
            })
            .finally(() => {
                button.innerHTML = originalText;
                button.disabled = false;
            });
        }

        function updateLikeCount(targetId, type, count) {
            const countElements = document.querySelectorAll(`button[data-target-id="${targetId}"][data-type="${type}"][data-count="true"]`);

            if (count > 0) {
                countElements.forEach(element => {
                    element.textContent = count + (type === 'post' ? ' orang menyukai ini' : ' likes');
                    element.style.display = 'inline';
                });
            } else {
                countElements.forEach(element => {
                    element.style.display = 'none';
                });
            }
        }

        function showLikers(targetId, type) {
            console.log('showLikers called with:', targetId, type);

            const modal = document.getElementById('likersModal');
            const modalContent = document.getElementById('modalContent');
            const modalTitle = document.getElementById('modalTitle');
            const likersList = document.getElementById('likersList');

            if (!modal || !modalContent || !modalTitle || !likersList) {
                console.error('Modal elements not found');
                alert('Modal tidak dapat dimuat');
                return;
            }

            modal.classList.remove('hidden');
            setTimeout(() => {
                modalContent.classList.remove('scale-95', 'opacity-0');
                modalContent.classList.add('scale-100', 'opacity-100');
            }, 10);

            const title = type === 'post' ? 'Disukai oleh' : 'Komentar disukai oleh';
            modalTitle.textContent = title;

            likersList.innerHTML = `
                <div class="text-center text-gray-500 py-8">
                    <div class="animate-spin w-8 h-8 border-2 border-blue-600 border-t-transparent rounded-full mx-auto mb-4"></div>
                    <p>Loading...</p>
                </div>
            `;

            fetch('<?= url("ajax/get_likers.php") ?>?target_id=' + targetId + '&type=' + type)
            .then(response => {
                console.log('Likers response status:', response.status);
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                return response.json();
            })
            .then(data => {
                console.log('Likers data:', data);

                if (data && data.success) {
                    let content = '<div class="max-h-96 overflow-y-auto">';
                    if (data.likers && data.likers.length > 0) {
                        data.likers.forEach(liker => {
                            content += `
                                <div class="flex items-center gap-3 py-3 px-2 hover:bg-gray-50 rounded-lg transition-colors">
                                    <div class="w-10 h-10 rounded-full flex items-center justify-center text-white font-bold flex-shrink-0 overflow-hidden" style="background: linear-gradient(135deg, <?= $primary_color ?> 0%, <?= $secondary_color ?> 100%);">
                                        ${liker.profile_pic ?
                                            `<img src="${liker.profile_pic}" alt="${liker.username}" class="w-full h-full object-cover rounded-full">` :
                                            `<span>${liker.username.charAt(0).toUpperCase()}</span>`
                                        }
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <p class="font-semibold text-gray-900 truncate">${liker.username}</p>
                                    </div>
                                </div>
                            `;
                        });
                    } else {
                        content += `
                            <div class="text-center text-gray-500 py-8">
                                <svg class="w-12 h-12 mx-auto mb-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/>
                                </svg>
                                <p>Belum ada yang menyukai</p>
                            </div>
                        `;
                    }
                    content += '</div>';
                    likersList.innerHTML = content;
                } else {
                    likersList.innerHTML = `
                        <div class="text-center text-red-500 py-8">
                            <p>Error loading likers</p>
                            <p class="text-sm mt-2">${data.message || 'Unknown error'}</p>
                        </div>
                    `;
                }
            })
            .catch(error => {
                console.error('Error fetching likers:', error);
                likersList.innerHTML = `
                    <div class="text-center text-red-500 py-8">
                        <p>Error loading likers</p>
                        <p class="text-sm mt-2">${error.message}</p>
                    </div>
                `;
            });
        }

        function closeLikersModal() {
            document.getElementById('modalContent').classList.add('scale-95', 'opacity-0');
            document.getElementById('modalContent').classList.remove('scale-100', 'opacity-100');
            setTimeout(() => {
                document.getElementById('likersModal').classList.add('hidden');
            }, 300);
        }
    </script>
</body>
</html>
