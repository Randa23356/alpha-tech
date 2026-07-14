<?php
// announcement.php - Pengumuman (Protected)
// Session is already started in session.php, no need to start again
require_once __DIR__ . "/src/helpers/session.php";
require_once __DIR__ . "/src/config/db.php";
require_once __DIR__ . "/src/config/urls.php";

// Load theme colors from database
$primary_color = '#1e3a8a'; // default
$secondary_color = '#1e40af'; // default
$accent_color = '#ec4899'; // default
$success_color = '#10b981'; // default
$warning_color = '#f59e0b'; // default
$danger_color = '#ef4444'; // default

try {
    $stmt = $pdo->query("SELECT setting_key, setting_value FROM site_settings WHERE setting_key IN ('primary_color', 'secondary_color', 'accent_color', 'success_color', 'warning_color', 'danger_color')");
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
} catch (Exception $e) {
    // Use default colors if database fails
}

// Proteksi: hanya user login yang bisa akses
if (!isLoggedIn()) {
    header("Location: " . url('login'));
    exit();
}

$user = getCurrentUser();

// Ambil semua pengumuman dari database
$stmt = $pdo->prepare("SELECT * FROM announcements ORDER BY created_at DESC");
$stmt->execute();
$announcements = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Pengumuman Kelas - Informatics A</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="<?= url('public/tailwind.css') ?>" rel="stylesheet">
    <style>
        /* Dynamic theme variables for announcement page */
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
            background: linear-gradient(135deg, <?= $primary_color ?> 0%, <?= $secondary_color ?> 100%);
        }

        .card-gradient {
            background: linear-gradient(135deg, <?= $primary_color ?> 0%, <?= $secondary_color ?> 100%);
        }

        .btn-gradient {
            background: linear-gradient(135deg, <?= $primary_color ?> 0%, <?= $secondary_color ?> 100%);
        }

        .btn-gradient:hover {
            background: linear-gradient(135deg, <?= $secondary_color ?> 0%, <?= $primary_color ?> 100%);
        }

        /* Theme hover effects */
        .theme-hover:hover {
            color: <?= $primary_color ?>;
        }

        /* Theme border colors */
        .theme-border {
            border-color: <?= $primary_color ?>;
        }

        /* Theme background colors */
        .theme-bg-light {
            background-color: <?= $primary_color ?>10;
        }

        /* Theme text colors */
        .theme-text {
            color: <?= $primary_color ?>;
        }
    </style>
</head>
<body class="bg-gradient-to-br from-gray-50 to-blue-50 min-h-screen">
    <!-- Loading Overlay -->
    <div id="pageLoadingOverlay" class="fixed inset-0 bg-white bg-opacity-90 flex items-center justify-center z-50 opacity-0 invisible transition-opacity duration-300">
        <div class="flex flex-col items-center gap-4">
            <div class="w-12 h-12 border-4 border-blue-200 border-t-blue-600 rounded-full animate-spin"></div>
            <p class="text-gray-600 font-medium">Memuat...</p>
        </div>
    </div>
    
    <?php include __DIR__ . '/includes/navbar.php'; ?>

    <!-- Header -->
    <header class="header-gradient text-white py-16 px-6">
        <div class="max-w-4xl mx-auto text-center">
            <div class="flex justify-center mb-4">
                <svg class="w-16 h-16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z"/>
                </svg>
            </div>
            <h1 class="text-4xl font-bold mb-3">Pengumuman Kelas</h1>
            <p class="text-xl text-blue-100">Informasi terbaru dan penting untuk seluruh anggota kelas Informatics <?= $site_name ?></p>
        </div>
    </header>

    <!-- Main Content -->
    <main class="max-w-4xl mx-auto px-4 sm:px-6 md:px-8 py-12 page-transition-enter" id="mainContent">
        <?php if (empty($announcements)): ?>
            <div class="bg-white p-12 rounded-xl shadow-md text-center">
                <svg class="w-20 h-20 mx-auto mb-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"/>
                </svg>
                <p class="text-gray-600 text-lg">Belum ada pengumuman kelas saat ini.</p>
            </div>
        <?php else: ?>
            <div class="space-y-6">
                <?php foreach ($announcements as $item): ?>
                    <div class="bg-white rounded-2xl shadow-lg hover:shadow-xl transition-all duration-300 overflow-hidden theme-border">
                        <div class="p-4 md:p-8">
                        <div class="flex flex-col sm:flex-row items-start gap-4">
                                <div class="flex-shrink-0">
                                <div class="w-10 h-10 sm:w-14 sm:h-14 card-gradient rounded-xl flex items-center justify-center shadow-lg">
                                <svg class="w-6 h-6 sm:w-7 sm:h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z"/>
                                        </svg>
                                    </div>
                                </div>
                                <div class="flex-1">
                                    <div class="flex items-start justify-between mb-3">
                                    <h2 class="text-xl sm:text-2xl font-bold text-gray-900 flex-1 break-words">
                                    <?= htmlspecialchars($item['title']) ?></h2>
                                        <span class="ml-4 px-3 py-1 theme-bg-light theme-text text-xs font-semibold rounded-full" style="background-color: <?= $primary_color ?>10; color: <?= $primary_color ?>;">Pengumuman</span>
                                    </div>
                                    <div class="text-gray-700 mb-4 leading-relaxed text-lg"><?= nl2br(htmlspecialchars($item['content'])) ?></div>
                                    
                                    <?php if (!empty($item['file_path'])): ?>
                                        <div class="mb-4 p-4 theme-bg-light rounded-xl theme-border">
                                            <div class="flex items-center justify-between">
                                                <div class="flex items-center gap-3">
                                                    <div class="w-10 h-10 card-gradient rounded-lg flex items-center justify-center">
                                                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"/>
                                                        </svg>
                                                    </div>
                                                    <div>
                                                        <p class="font-semibold text-gray-900">File Lampiran</p>
                                                        <p class="text-sm text-gray-600"><?= htmlspecialchars($item['file_name']) ?></p>
                                                    </div>
                                                </div>
                                                <a href=" <?= url('download') ?>?file=<?= urlencode($item['file_path']) ?>&name=<?= urlencode($item['file_name']) ?>" class="inline-flex items-center gap-2 text-white px-4 py-2 rounded-lg font-medium transition shadow-md hover:shadow-lg" style="background-color: <?= $primary_color ?>;">
                                                    <svg class="w-5 h-5"  fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                                                    </svg>
                                                    Download
                                                </a>
                                            </div>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <div class="flex items-center gap-6 text-sm text-gray-500 bg-gray-50 rounded-lg p-3">
                                        <span class="flex items-center gap-2">
                                            <svg class="w-4 h-4" style="color: <?= $primary_color ?>;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                            </svg>
                                            <span class="font-medium"><?= date('d M Y, H:i', strtotime($item['created_at'])) ?> WIB</span>
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <div class="mt-8 text-center">
            <?php if (isAdmin()): ?>
                    <a href="<?= url('admin') ?>" class="nav-link inline-flex items-center gap-2 btn-gradient text-white px-8 py-3 rounded-lg font-semibold hover:btn-gradient transition shadow-lg">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                    </svg>
                    Kembali ke Admin Panel
                </a>
            <?php elseif (isKorti()): ?>
                <a href="<?= url('korti') ?>" class="nav-link inline-flex items-center gap-2 btn-gradient text-white px-8 py-3 rounded-lg font-semibold hover:btn-gradient transition shadow-lg">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                    </svg>
                    Kembali ke Dashboard
                </a>
            <?php else: ?>
                <a href="<?= url('dashboard') ?>" class="nav-link inline-flex items-center gap-2 btn-gradient text-white px-8 py-3 rounded-lg font-semibold hover:btn-gradient transition shadow-lg">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                    </svg>
                    Kembali ke Dashboard
                </a>
            <?php endif; ?>
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
            const navLinks = document.querySelectorAll('.nav-link, a[href*="dashboard"], a[href*="admin"], a[href*="korti"], a[href*="announcement"]');

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
