<?php
// includes/navbar.php - Reusable Navbar Component
require_once __DIR__ . "/../src/config/urls.php";
$current_page = basename($_SERVER['PHP_SELF'], '.php');
$is_logged_in = isset($_SESSION['user']);
$user = $is_logged_in ? $_SESSION['user'] : null;
$user_role = $is_logged_in ? $user['role'] : null;
$is_admin = $is_logged_in && $user_role === 'admin';
$is_korti = $is_logged_in && $user_role === 'korti';
$is_user = $is_logged_in && $user_role === 'user';

// Load theme colors from database
$primary_color = '#1e3a8a'; // default
$secondary_color = '#1e40af'; // default
$accent_color = '#ec4899'; // default
$success_color = '#10b981'; // default
$warning_color = '#f59e0b'; // default
$danger_color = '#ef4444'; // default

// Load navbar-specific colors
$navbar_bg_color = '#1e3a8a'; // default
$navbar_font_color = '#ffffff'; // default

// Load site settings
$site_name = 'Informatics A'; // default
$navbar_icon = 'public/images/logo.png'; // default

try {
    require_once __DIR__ . "/../src/config/db.php";
    $stmt = $pdo->query("SELECT setting_key, setting_value FROM site_settings WHERE setting_key IN ('primary_color', 'secondary_color', 'accent_color', 'success_color', 'warning_color', 'danger_color', 'navbar_bg_color', 'navbar_font_color', 'site_name', 'navbar_icon_id')");
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
    $navbar_bg_color = $settings['navbar_bg_color'] ?? $navbar_bg_color;
    $navbar_font_color = $settings['navbar_font_color'] ?? $navbar_font_color;
    $site_name = $settings['site_name'] ?? $site_name;

    // Load navbar icon from navbar_icons table
    $navbar_icon_id = $settings['navbar_icon_id'] ?? null;
    if ($navbar_icon_id && $navbar_icon_id !== '' && $navbar_icon_id !== '0') {
        try {
            $icon_stmt = $pdo->prepare("SELECT file_path FROM navbar_icons WHERE id = ? AND is_active = 1");
            $icon_stmt->execute([$navbar_icon_id]);
            $icon_result = $icon_stmt->fetch(PDO::FETCH_ASSOC);
            if ($icon_result) {
                $navbar_icon = $icon_result['file_path'];
                error_log("Loaded navbar icon from database: " . $navbar_icon);
            } else {
                error_log("Navbar icon ID $navbar_icon_id not found in database, using default");
                $navbar_icon = 'public/images/logo.png';
            }
        } catch (Exception $e) {
            error_log("Error loading navbar icon from database: " . $e->getMessage());
            $navbar_icon = 'public/images/logo.png';
        }
    } else {
        // Fallback to old method or default
        $navbar_icon = $settings['navbar_icon'] ?? 'public/images/logo.png';
        error_log("No navbar_icon_id found, using fallback: " . $navbar_icon);
    }

    // Debug: Log the navbar_icon value
    error_log("Navbar icon loaded: " . $navbar_icon . " at " . date('Y-m-d H:i:s'));

    // Add cache busting for navbar_icon
    $navbar_icon_url = url(htmlspecialchars($navbar_icon)) . '?v=' . time();

    // Force refresh navbar if needed
    if (isset($_GET['refresh_navbar'])) {
        $navbar_icon_url .= '&refresh=' . time();
    }
} catch (Exception $e) {
    // Use default colors if database fails
    error_log("Database error in navbar.php: " . $e->getMessage());
    $navbar_icon_url = url('public/images/logo.png');
}
?>

<style>
        /* Dynamic theme variables for contact page */
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
<nav class="shadow-lg sticky top-0 z-50" style="background-color: <?= $navbar_bg_color ?> !important;">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between items-center h-16">
            <!-- Logo -->
            <div class="flex items-center gap-3">
                <div class="w-8 h-8 sm:w-10 sm:h-10 rounded-lg flex items-center justify-center flex-shrink-0" style="background: linear-gradient(135deg, <?= $primary_color ?> 0%, <?= $secondary_color ?> 100%) !important;">
                    <img src="<?= $navbar_icon_url ?>" alt="Logo" class="w-8 h-8 sm:w-10 sm:h-10 rounded-lg">
                </div>
                <a href="<?= url('home') ?>" class="text-lg sm:text-xl lg:text-2xl font-bold flex-shrink-0" style="background: linear-gradient(135deg, <?= $primary_color ?> 0%, <?= $secondary_color ?> 100%); -webkit-background-clip: text; background-clip: text; -webkit-text-fill-color: transparent; color: transparent;"><?= htmlspecialchars($site_name) ?></a>
            </div>

            <!-- Navigation Links -->
            <div class="hidden md:flex items-center gap-6">
                <a href="<?= url('home') ?>" class="font-medium transition <?= ($current_page == 'index') ? 'font-bold' : '' ?>" <?= ($current_page == 'index') ? 'style="color: ' . $navbar_font_color . ';"' : 'style="color: ' . $navbar_font_color . ';" onmouseover="this.style.color=\'' . $navbar_font_color . '\'" onmouseout="this.style.color=\'' . $navbar_font_color . '\'"' ?>>Home</a>
                <a href="<?= url('activities') ?>" class="font-medium transition <?= ($current_page == 'activities') ? 'font-bold' : '' ?>" <?= ($current_page == 'activities') ? 'style="color: ' . $navbar_font_color . ';"' : 'style="color: ' . $navbar_font_color . ';" onmouseover="this.style.color=\'' . $navbar_font_color . '\'" onmouseout="this.style.color=\'' . $navbar_font_color . '\'"' ?>>Kegiatan</a>
                <a href="<?= url('about') ?>" class="font-medium transition <?= ($current_page == 'about') ? 'font-bold' : '' ?>" <?= ($current_page == 'about') ? 'style="color: ' . $navbar_font_color . ';"' : 'style="color: ' . $navbar_font_color . ';" onmouseover="this.style.color=\'' . $navbar_font_color . '\'" onmouseout="this.style.color=\'' . $navbar_font_color . '\'"' ?>>Tentang</a>
                <a href="<?= url('gallery') ?>" class="font-medium transition <?= ($current_page == 'gallery') ? 'font-bold' : '' ?>" <?= ($current_page == 'gallery') ? 'style="color: ' . $navbar_font_color . ';"' : 'style="color: ' . $navbar_font_color . ';" onmouseover="this.style.color=\'' . $navbar_font_color . '\'" onmouseout="this.style.color=\'' . $navbar_font_color . '\'"' ?>>Galeri</a>
                <a href="<?= url('contact') ?>" class="font-medium transition <?= ($current_page == 'contact') ? 'font-bold' : '' ?>" <?= ($current_page == 'contact') ? 'style="color: ' . $navbar_font_color . ';"' : 'style="color: ' . $navbar_font_color . ';" onmouseover="this.style.color=\'' . $navbar_font_color . '\'" onmouseout="this.style.color=\'' . $navbar_font_color . '\'"' ?>>Kontak</a>
                
                <?php if ($is_logged_in): ?>
                    <!-- Link Pengumuman untuk semua user login -->
                    <a href="<?= url('announcement') ?>" class="font-medium transition <?= ($current_page == 'announcement') ? 'font-bold' : '' ?>" <?= ($current_page == 'announcement') ? 'style="color: ' . $navbar_font_color . ';"' : 'style="color: ' . $navbar_font_color . ';" onmouseover="this.style.color=\'' . $navbar_font_color . '\'" onmouseout="this.style.color=\'' . $navbar_font_color . '\'"' ?>>Pengumuman</a>
                    <!-- Dropdown Menu -->
                    <div class="relative">
    <button id="user-menu-btn" class="flex items-center gap-2 font-medium transition" style="color: <?= $navbar_font_color ?>;" onmouseover="this.style.color='<?= $navbar_font_color ?>'" onmouseout="this.style.color='<?= $navbar_font_color ?>'">
        <?php 
        // Ambil data user dari session, bukan dari variabel $profil
        $currentUser = $_SESSION['user'] ?? null;
        $userProfilePic = null;
        
        // Coba ambil profile_pic dari database jika perlu
        if ($currentUser && isset($currentUser['id'])) {
            require_once __DIR__ . "/../src/config/db.php";
            $stmt = $pdo->prepare("SELECT profile_pic FROM users WHERE id = ?");
            $stmt->execute([$currentUser['id']]);
            $userData = $stmt->fetch();
            $userProfilePic = $userData['profile_pic'] ?? null;
        }
        ?>
        
        <?php if (!empty($userProfilePic)): ?>
            <?php if (strpos($userProfilePic, 'http') === 0): ?>
                <img src="<?= htmlspecialchars($userProfilePic) ?>" 
                     alt="Foto Profil" 
                     class="w-8 h-8 rounded-full object-cover border-2 rounded-full" style="border-color: <?= $primary_color ?>30;">
            <?php else: ?>
                <img src="<?= upload_url(htmlspecialchars($userProfilePic)) ?>" 
                     alt="Foto Profil" 
                     class="w-8 h-8 rounded-full object-cover border-2 rounded-full" style="border-color: <?= $primary_color ?>30;">
            <?php endif; ?>
        <?php else: ?>
            <img src="<?= url('public/default-avatar.php?initial=' . urlencode(substr($currentUser['username'] ?? 'U', 0, 1)) . '&color=' . urlencode($primary_color)) ?>" 
                 alt="Default Avatar" 
                 class="w-8 h-8 rounded-full object-cover border-2" style="border-color: <?= $primary_color ?>30;">
        <?php endif; ?>
        <span><?= htmlspecialchars($currentUser['username'] ?? 'User') ?></span>
        <svg id="dropdown-arrow" class="w-4 h-4 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
        </svg>
    </button>
      <!-- Dropdown Content -->
                        <div id="user-menu-dropdown" class="hidden absolute right-0 mt-2 w-64 bg-white rounded-xl shadow-2xl border border-gray-200 z-50">
                            <div class="py-2">
                                <!-- User Info Header -->
                                <div class="px-4 py-3 border-b border-gray-200">
                                    <p class="text-sm text-gray-500">Signed in as</p>
                                    <p class="font-semibold text-gray-900"><?= htmlspecialchars($user['username']) ?></p>
                                    <p class="text-xs text-gray-500 mt-1">
                                        <span class="inline-block px-2 py-0.5 rounded-full font-medium" style="background-color: <?= $primary_color ?>20; color: <?= $primary_color ?>;">
                                            <?= ucfirst($user_role) ?>
                                        </span>
                                    </p>
                                </div>
                                
                                <!-- Menu untuk USER biasa -->
                                <?php if ($is_user): ?>
                                    <a href="<?= url('dashboard') ?>" class="block px-4 py-2 text-gray-700 transition" style="color: <?= $primary_color ?>30;" onmouseover="this.style.backgroundColor='<?= $primary_color ?>10'; this.style.color='<?= $primary_color ?>';" onmouseout="this.style.backgroundColor='transparent'; this.style.color='#374151';">
                                        <div class="flex items-center gap-2">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
                                            Dashboard
                                        </div>
                                    </a>
                                    <a href="<?= url('post') ?>" class="block px-4 py-2 text-gray-700 transition" style="color: <?= $primary_color ?>30;" onmouseover="this.style.backgroundColor='<?= $primary_color ?>10'; this.style.color='<?= $primary_color ?>';" onmouseout="this.style.backgroundColor='transparent'; this.style.color='#374151';">
                                        <div class="flex items-center gap-2">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                                            Post Kegiatan
                                        </div>
                                    </a>
                                <?php endif; ?>
                                
                                <!-- Menu untuk KORTI -->
                                <?php if ($is_korti): ?>
                                    <a href="<?= url('korti') ?>" class="block px-4 py-2 text-gray-700 transition" style="color: <?= $primary_color ?>30;" onmouseover="this.style.backgroundColor='<?= $primary_color ?>10'; this.style.color='<?= $primary_color ?>';" onmouseout="this.style.backgroundColor='transparent'; this.style.color='#374151';">
                                        <div class="flex items-center gap-2">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
                                            Dashboard Korti
                                        </div>
                                    </a>
                                <?php endif; ?>
                                
                                <!-- Menu untuk ADMIN -->
                                <?php if ($is_admin): ?>
                                    <a href="<?= url('admin') ?>" class="block px-4 py-2 text-gray-700 transition" style="color: <?= $primary_color ?>30;" onmouseover="this.style.backgroundColor='<?= $primary_color ?>10'; this.style.color='<?= $primary_color ?>';" onmouseout="this.style.backgroundColor='transparent'; this.style.color='#374151';">
                                        <div class="flex items-center gap-2">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                                            Admin Panel
                                        </div>
                                    </a>
                                <?php endif; ?>
                                
                                <hr class="my-2">
                                
                                <a href="<?= url('profile') ?>" class="block px-4 py-2 text-gray-700 transition" style="color: <?= $primary_color ?>30;" onmouseover="this.style.backgroundColor='<?= $primary_color ?>10'; this.style.color='<?= $primary_color ?>';" onmouseout="this.style.backgroundColor='transparent'; this.style.color='#374151';">
                                    <div class="flex items-center gap-2">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                                        Profil Saya
                                    </div>
                                </a>
                                <a href="../belum-tersedia.html" class="block px-4 py-2 text-gray-700 transition" style="color: <?= $primary_color ?>30;" onmouseover="this.style.backgroundColor='<?= $primary_color ?>10'; this.style.color='<?= $primary_color ?>';" onmouseout="this.style.backgroundColor='transparent'; this.style.color='#374151';">
                                    <div class="flex items-center gap-2">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/></svg>
                                        Chat
                                    </div>
                                </a>
                                <a href="<?= url('logout') ?>" class="block px-4 py-2 text-red-600 hover:bg-red-50 transition">
                                    <div class="flex items-center gap-2">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
                                        Logout
                                    </div>
                                </a>
                            </div>
                        </div>
                    </div>
                <?php else: ?>
                    <!-- Menu untuk user yang belum login -->
                    <a href="<?= url('login') ?>" class="font-medium transition" style="color: <?= $navbar_font_color ?>;">Login</a>
                    <a href="<?= url('register') ?>" class="px-6 py-2 btn-gradient rounded-lg text-white font-semibold transition shadow-md">Daftar</a>
                <?php endif; ?>
            </div>

            <!-- Mobile Menu Button -->
            <div class="md:hidden">
                <button id="mobile-menu-btn" class="p-2 rounded-lg transition" style="color: <?= $navbar_font_color ?>;">
                    <svg id="hamburger-icon" class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                    </svg>
                    <svg id="close-icon" class="w-6 h-6 hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
        </div>

        <!-- Mobile Menu -->
        <div id="mobile-menu" class="hidden md:hidden border-t border-opacity-20" style="border-color: <?= $primary_color ?>30; background-color: <?= $navbar_bg_color ?>;">
            <div class="flex flex-col py-3 space-y-1 max-h-[calc(100vh-5rem)] overflow-y-auto">
                <?php if ($is_logged_in): ?>
                    <!-- User Info Mobile -->
                    <div class="px-4 py-3 text-white mx-3 rounded-lg mb-2" style="background: linear-gradient(135deg, <?= $primary_color ?> 0%, <?= $secondary_color ?> 100%);">
                        <div class="flex items-center gap-3">
                            <?php if (!empty($userProfilePic)): ?>
                                <?php if (strpos($userProfilePic, 'http') === 0): ?>
                                    <img src="<?= htmlspecialchars($userProfilePic) ?>" 
                                         alt="Foto Profil" 
                                         class="w-10 h-10 rounded-full object-cover border-2" style="border-color: rgba(255,255,255,0.3);">
                                <?php else: ?>
                                    <img src="<?= upload_url(htmlspecialchars($userProfilePic)) ?>" 
                                         alt="Foto Profil" 
                                         class="w-10 h-10 rounded-full object-cover border-2" style="border-color: rgba(255,255,255,0.3);">
                                <?php endif; ?>
                            <?php else: ?>
                                <img src="<?= url('public/default-avatar.php?initial=' . urlencode(substr($user['username'] ?? 'U', 0, 1)) . '&color=' . urlencode($primary_color)) ?>" 
                                     alt="Default Avatar" 
                                     class="w-10 h-10 rounded-full object-cover border-2" style="border-color: rgba(255,255,255,0.3);">
                            <?php endif; ?>
                            <div>
                                <p class="font-semibold"><?= htmlspecialchars($user['username']) ?></p>
                                <p class="text-xs text-blue-100"><?= ucfirst($user_role) ?></p>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Navigation Links -->
                <a href="<?= url('home') ?>" class="text-gray-700 hover:bg-blue-50 hover:text-blue-900 font-medium px-3 sm:px-4 py-2 sm:py-3 mx-2 sm:mx-3 rounded-lg transition flex items-center gap-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
                    <span class="truncate">Home</span>
                </a>
                <a href="<?= url('activities') ?>" class="text-gray-600 hover:bg-gray-50 hover:text-gray-900 font-medium px-3 sm:px-4 py-2 sm:py-3 mx-2 sm:mx-3 rounded-lg transition flex items-center gap-2 flex-1 min-w-0">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                    <span class="truncate">Kegiatan</span>
                </a>
                <a href="<?= url('about') ?>" class="text-gray-600 hover:bg-gray-50 hover:text-gray-900 font-medium px-3 sm:px-4 py-2 sm:py-3 mx-2 sm:mx-3 rounded-lg transition flex items-center gap-2 flex-1 min-w-0">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    <span class="truncate">Tentang</span>
                </a>
                <a href="<?= url('gallery') ?>" class="text-gray-600 hover:bg-gray-50 hover:text-gray-900 font-medium px-3 sm:px-4 py-2 sm:py-3 mx-2 sm:mx-3 rounded-lg transition flex items-center gap-2 flex-1 min-w-0">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                    <span class="truncate">Galeri</span>
                </a>
                <a href="<?= url('contact') ?>" class="text-gray-600 hover:bg-gray-50 hover:text-gray-900 font-medium px-3 sm:px-4 py-2 sm:py-3 mx-2 sm:mx-3 rounded-lg transition flex items-center gap-2 flex-1 min-w-0">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                    <span class="truncate">Kontak</span>
                </a>

                <?php if ($is_logged_in): ?>
                    <div class="border-t border-gray-200 my-2 mx-3"></div>

                    <!-- Link Pengumuman -->
                    <a href="<?= url('announcement') ?>" class="text-gray-600 hover:bg-gray-50 hover:text-gray-900 font-medium px-3 sm:px-4 py-2 sm:py-3 mx-2 sm:mx-3 rounded-lg transition flex items-center gap-2 flex-1 min-w-0">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z"/></svg>
                        <span class="truncate">Pengumuman</span>
                    </a>

                    <!-- Link Chat -->
                    <a href="../belum-tersedia.html" class="text-gray-600 hover:bg-gray-50 hover:text-gray-900 font-medium px-3 sm:px-4 py-2 sm:py-3 mx-2 sm:mx-3 rounded-lg transition flex items-center gap-2 flex-1 min-w-0">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/></svg>
                        <span class="truncate">Chat</span>
                    </a>

                    <!-- Menu untuk USER biasa -->
                    <?php if ($is_user): ?>
                        <a href="<?= url('dashboard') ?>" class="text-gray-600 hover:bg-gray-50 hover:text-gray-900 font-medium px-3 sm:px-4 py-2 sm:py-3 mx-2 sm:mx-3 rounded-lg transition flex items-center gap-2 flex-1 min-w-0">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
                            Dashboard
                        </a>
                        <a href="<?= url('post') ?>" class="text-gray-600 hover:bg-gray-50 hover:text-gray-900 font-medium px-3 sm:px-4 py-2 sm:py-3 mx-2 sm:mx-3 rounded-lg transition flex items-center gap-2 flex-1 min-w-0">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                            Post Kegiatan
                        </a>
                    <?php endif; ?>

                    <!-- Menu untuk KORTI -->
                    <?php if ($is_korti): ?>
                        <a href="<?= url('korti') ?>" class="text-gray-600 hover:bg-gray-50 hover:text-gray-900 font-medium px-3 sm:px-4 py-2 sm:py-3 mx-2 sm:mx-3 rounded-lg transition flex items-center gap-2 flex-1 min-w-0">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
                            Dashboard Korti
                        </a>
                    <?php endif; ?>

                    <!-- Menu untuk ADMIN -->
                    <?php if ($is_admin): ?>
                        <a href="<?= url('admin') ?>" class="text-gray-600 hover:bg-gray-50 hover:text-gray-900 font-medium px-3 sm:px-4 py-2 sm:py-3 mx-2 sm:mx-3 rounded-lg transition flex items-center gap-2 flex-1 min-w-0">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                            Admin Panel
                        </a>
                    <?php endif; ?>

                    <div class="border-t border-gray-200 my-2 mx-3"></div>

                    <a href="<?= url('profile') ?>" class="text-gray-600 hover:bg-gray-50 hover:text-gray-900 font-medium px-3 sm:px-4 py-2 sm:py-3 mx-2 sm:mx-3 rounded-lg transition flex items-center gap-2 flex-1 min-w-0">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                        Profil Saya
                    </a>
                    <a href="<?= url('logout') ?>" class="bg-red-600 text-white px-3 sm:px-4 py-2 sm:py-3 mx-2 sm:mx-3 rounded-lg hover:bg-red-700 transition font-medium flex items-center justify-center gap-2 flex-1 min-w-0">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
                        Logout
                    </a>
                <?php else: ?>
                    <div class="border-t border-gray-200 my-2 mx-3"></div>

                    <!-- Menu untuk user yang belum login -->
                    <a href="<?= url('login') ?>" class="text-gray-600 hover:bg-gray-50 hover:text-gray-900 font-medium px-3 sm:px-4 py-2 sm:py-3 mx-2 sm:mx-3 rounded-lg transition flex items-center gap-2 flex-1 min-w-0">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"/></svg>
                        <span class="truncate">Login</span>
                    </a>
                    <a href="<?= url('register') ?>" class="text-white px-3 sm:px-4 py-2 sm:py-3 mx-2 sm:mx-3 rounded-lg font-semibold hover:from-blue-800 hover:to-blue-600 transition flex items-center justify-center gap-2 flex-1 min-w-0" style="background: linear-gradient(135deg, <?= $primary_color ?> 0%, <?= $secondary_color ?> 100%);"",
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/></svg>
                        <span class="truncate">Daftar Sekarang</span>
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</nav>

<script>
// Mobile menu toggle with icon change
document.getElementById('mobile-menu-btn')?.addEventListener('click', function() {
    const menu = document.getElementById('mobile-menu');
    const hamburgerIcon = document.getElementById('hamburger-icon');
    const closeIcon = document.getElementById('close-icon');

    menu.classList.toggle('hidden');
    hamburgerIcon.classList.toggle('hidden');
    closeIcon.classList.toggle('hidden');
});

// Close mobile menu when clicking a link
document.querySelectorAll('#mobile-menu a').forEach(link => {
    link.addEventListener('click', function() {
        const menu = document.getElementById('mobile-menu');
        const hamburgerIcon = document.getElementById('hamburger-icon');
        const closeIcon = document.getElementById('close-icon');

        menu.classList.add('hidden');
        hamburgerIcon.classList.remove('hidden');
        closeIcon.classList.add('hidden');
    });
});

// User dropdown toggle
document.getElementById('user-menu-btn')?.addEventListener('click', function(e) {
    e.stopPropagation();
    const dropdown = document.getElementById('user-menu-dropdown');
    const arrow = document.getElementById('dropdown-arrow');
    dropdown.classList.toggle('hidden');
    arrow.classList.toggle('rotate-180');
});

// Close dropdown when clicking outside
document.addEventListener('click', function(e) {
    const dropdown = document.getElementById('user-menu-dropdown');
    const button = document.getElementById('user-menu-btn');
    if (dropdown && !dropdown.contains(e.target) && !button.contains(e.target)) {
        dropdown.classList.add('hidden');
        document.getElementById('dropdown-arrow')?.classList.remove('rotate-180');
    }
});
</script>
