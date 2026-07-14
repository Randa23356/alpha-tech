<?php
// Load theme colors from database for sidebar
$primary_color = "#1e3a8a"; // default
$secondary_color = "#1e40af"; // default

try {
    $stmt = $pdo->query("SELECT setting_key, setting_value FROM site_settings WHERE setting_key IN ('primary_color', 'secondary_color')");
    $settings = [];
    foreach ($stmt->fetchAll() as $row) {
        $settings[$row['setting_key']] = $row['setting_value'];
    }
    $primary_color = $settings['primary_color'] ?? $primary_color;
    $secondary_color = $settings['secondary_color'] ?? $secondary_color;
} catch (Exception $e) {
    // Use default colors if database fails
}
?>

<!-- Sidebar Toggle Button (Mobile) -->
<button id="sidebar-toggle" class="lg:hidden fixed bottom-6 right-6 z-50 text-white p-4 rounded-full shadow-lg hover:bg-opacity-80 transition" style="background-color: <?= $primary_color ?>;">
        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
        </svg>
    </button>

    <!-- Sidebar -->
    <div id="sidebar-overlay" class="fixed inset-0 bg-black bg-opacity-50 z-40 hidden lg:hidden"></div>
    <aside id="sidebar" class="fixed left-0 top-16 h-[calc(100vh-4rem)] w-64 bg-white shadow-xl transform -translate-x-full lg:translate-x-0 transition-transform duration-300 z-40 overflow-y-auto">
        <div class="p-6">
            <h2 class="text-lg font-bold text-gray-900 mb-4">Menu Admin</h2>
            <nav class="space-y-2">
            <a href="<?= url('admin') ?>" class="flex items-center gap-3 px-4 py-3 <?= basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'text-white' : 'text-gray-700 hover:bg-gray-100' ?> rounded-lg font-medium transition" style="background-color: <?= basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? $primary_color : 'transparent' ?>;" data-menu="dashboard">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2H5a2 2 0 00-2-2z"/>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 5a2 2 0 012-2h4a2 2 0 012 2v4H8V5z"/>
                </svg>
                Dashboard Admin
            </a>
                <a href="<?= url('admin/posts') ?>" data-menu="posts" class="flex menu-item items-center gap-3 px-4 py-3 text-gray-700 hover:bg-gray-100 rounded-lg font-medium transition" style="background-color: <?= basename($_SERVER['PHP_SELF']) == 'posts.php' ? $primary_color : 'transparent' ?>;">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                    Kelola Postingan
                </a>
                <a href="<?= url('admin/announcements') ?>" data-menu="announcements" class="flex menu-item items-center gap-3 px-4 py-3 text-gray-700 hover:bg-gray-100 rounded-lg font-medium transition" style="background-color: <?= basename($_SERVER['PHP_SELF']) == 'announcements.php' ? $primary_color : 'transparent' ?>;">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z"/>
                    </svg>
                    Kelola Pengumuman
                </a>
                <a href="<?= url('admin/comments') ?>" data-menu="comments" class="flex menu-item items-center gap-3 px-4 py-3 text-gray-700 hover:bg-gray-100 rounded-lg font-medium transition" style="background-color: <?= basename($_SERVER['PHP_SELF']) == 'comments.php' ? $primary_color : 'transparent' ?>;">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z"/>
                    </svg>
                    Kelola Komentar
                </a>
                <a href="<?= url('admin/gallery') ?>" data-menu="gallery" class="flex menu-item items-center gap-3 px-4 py-3 text-gray-700 hover:bg-gray-100 rounded-lg font-medium transition" style="background-color: <?= basename($_SERVER['PHP_SELF']) == 'gallery.php' ? $primary_color : 'transparent' ?>;">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                    </svg>
                    Kelola Galeri
                </a>
                <a href="<?= url('admin/users') ?>" data-menu="users" class="flex menu-item items-center gap-3 px-4 py-3 text-gray-700 hover:bg-gray-100 rounded-lg font-medium transition" style="background-color: <?= basename($_SERVER['PHP_SELF']) == 'users.php' ? $primary_color : 'transparent' ?>;">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>
                    </svg>
                    Kelola Users
                </a>
                <a href="<?= url('admin/contact_messages') ?>" data-menu="contact-messages" class="flex menu-item items-center gap-3 px-4 py-3 text-gray-700 hover:bg-gray-100 rounded-lg font-medium transition" style="background-color: <?= basename($_SERVER['PHP_SELF']) == 'contact_messages.php' ? $primary_color : 'transparent' ?>;">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                    </svg>
                    Pesan Kontak
                </a>
                <a href="<?= url('admin/features') ?>" data-menu="features" class="flex menu-item items-center gap-3 px-4 py-3 text-gray-700 hover:bg-gray-100 rounded-lg font-medium transition" style="background-color: <?= basename($_SERVER['PHP_SELF']) == 'manage_features.php' ? $primary_color : 'transparent' ?>;">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
                </svg>
                Kelola Fitur
            </a>
                <a href="<?= url('admin/settings') ?>" data-menu="settings" class="flex menu-item items-center gap-3 px-4 py-3 text-gray-700 hover:bg-gray-100 rounded-lg font-medium transition" style="background-color: <?= basename($_SERVER['PHP_SELF']) == 'settings.php' ? $primary_color : 'transparent' ?>;">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                    </svg>
                    Pengaturan Website
                </a>
                <a href="<?= url('admin/navbar_icons') ?>" data-menu="navbar-icons" class="flex menu-item items-center gap-3 px-4 py-3 text-gray-700 hover:bg-gray-100 rounded-lg font-medium transition">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                    </svg>
                    Navbar Icons
                </a>
                <a href="<?= url('admin/hero_slider') ?>" data-menu="hero-slider" class="flex menu-item items-center gap-3 px-4 py-3 text-gray-700 hover:bg-gray-100 rounded-lg font-medium transition">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"/>
                    </svg>
                    Hero Slider
                </a>
            </nav>
        </div>
    </aside>
    <script>
        // Sidebar toggle for mobile
        const sidebarToggle = document.getElementById('sidebar-toggle');
        const sidebar = document.getElementById('sidebar');
        const sidebarOverlay = document.getElementById('sidebar-overlay');

        function toggleSidebar() {
            sidebar.classList.toggle('-translate-x-full');
            sidebarOverlay.classList.toggle('hidden');
        }

        sidebarToggle?.addEventListener('click', toggleSidebar);
        sidebarOverlay?.addEventListener('click', toggleSidebar);
    </script>
    <script>
    // Tangkap semua item menu
    const menuItems = document.querySelectorAll('[data-menu]');

    menuItems.forEach(item => {
        item.addEventListener('click', () => {
            const selectedMenu = item.getAttribute('data-menu');
            sessionStorage.setItem('activeMenu', selectedMenu);
        });
    });

    // Saat halaman dimuat, tandai menu aktif
    const activeMenu = sessionStorage.getItem('activeMenu');
    if (activeMenu) {
        menuItems.forEach(item => {
            const menu = item.getAttribute('data-menu');
            if (menu === activeMenu) {
                item.classList.remove('text-gray-700', 'hover:bg-gray-100');
                item.classList.add('text-white');
                item.style.backgroundColor = '<?= $primary_color ?>';
            } else {
                item.classList.add('text-gray-700', 'hover:bg-gray-100');
                item.classList.remove('text-white');
                item.style.backgroundColor = 'transparent';
            }
        });
    }
</script>
