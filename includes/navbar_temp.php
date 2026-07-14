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

// Load navbar-specific colors
$navbar_bg_color = '#1e3a8a'; // default
$navbar_font_color = '#ffffff'; // default

// Load site settings
$site_name = 'Informatics A'; // default

try {
    require_once __DIR__ . "/../src/config/db.php";
    $stmt = $pdo->query("SELECT setting_key, setting_value FROM site_settings WHERE setting_key IN ('primary_color', 'secondary_color', 'navbar_bg_color', 'navbar_font_color', 'site_name')");
    $settings = [];
    foreach ($stmt->fetchAll() as $row) {
        $settings[$row['setting_key']] = $row['setting_value'];
    }
    $primary_color = $settings['primary_color'] ?? $primary_color;
    $secondary_color = $settings['secondary_color'] ?? $secondary_color;
    $navbar_bg_color = $settings['navbar_bg_color'] ?? $navbar_bg_color;
    $navbar_font_color = $settings['navbar_font_color'] ?? $navbar_font_color;
    $site_name = $settings['site_name'] ?? $site_name;
} catch (Exception $e) {
    // Use default colors if database fails
}
?>

<nav class="shadow-lg sticky top-0 z-50" style="background-color: <?= $navbar_bg_color ?>;">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between items-center h-16">
            <!-- Logo -->
            <div class="flex items-center gap-3">
                <div class="w-10 h-10" style="background: linear-gradient(135deg, <?= $primary_color ?> 0%, <?= $secondary_color ?> 100%) !important;" class="rounded-lg flex items-center justify-center">
                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
                    </svg>
                </div>
                <a href="<?= url('home') ?>" class="text-2xl font-bold" style="background: linear-gradient(135deg, <?= $primary_color ?> 0%, <?= $secondary_color ?> 100%); -webkit-background-clip: text; background-clip: text; -webkit-text-fill-color: transparent; color: transparent;"><?= htmlspecialchars($site_name) ?></a>
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
            <img src="<?= upload_url(htmlspecialchars($userProfilePic)) ?>" 
                 alt="Foto Profil" 
                 class="w-8 h-8 rounded-full object-cover border-2 rounded-full" style="border-color: <?= $primary_color ?>30;">
        <?php else: ?>
            <div class="w-8 h-8 rounded-full flex items-center justify-center text-white font-bold text-sm" style="background: linear-gradient(135deg, <?= $primary_color ?> 0%, <?= $secondary_color ?> 100%);">
                <?= strtoupper(substr($currentUser['username'] ?? 'U', 0, 1)) ?>
            </div>
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
                    <a href="<?= url('register') ?>" class="px-6 py-2 rounded-lg font-semibold transition shadow-md" style="background: rgba(255, 255, 255, 0.1); color: <?= $navbar_font_color ?>;">Daftar</a>
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
        <div id="mobile-menu" class="hidden md:hidden border-t border-gray-100 bg-gray-50">
            <div class="flex flex-col py-3 space-y-1 max-h-[calc(100vh-4rem)] overflow-y-auto">
                <?php if ($is_logged_in): ?>
                    <!-- User Info Mobile -->
                    <div class="px-4 py-3 text-white mx-3 rounded-lg mb-2" style="background: linear-gradient(135deg, <?= \$primary_color ?> 0%, <?= \$secondary_color ?> 100%);">
