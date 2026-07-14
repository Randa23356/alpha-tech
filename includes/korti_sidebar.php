<?php
// Load theme colors from database for korti sidebar
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
        <h2 class="text-lg font-bold text-gray-900 mb-4">Menu Korti</h2>
        <nav class="space-y-2">
            <a href="<?= url('korti') ?>" class="flex items-center gap-3 px-4 py-3 <?= basename($_SERVER['PHP_SELF']) == 'index.php' || basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'text-white' : 'text-gray-700 hover:bg-gray-100' ?> rounded-lg font-medium transition" style="background-color: <?= (basename($_SERVER['PHP_SELF']) == 'index.php' || basename($_SERVER['PHP_SELF']) == 'dashboard.php') ? $primary_color : 'transparent' ?>;">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                </svg>
                Dashboard
            </a>
            <a href="<?= url('korti/posts') ?>" class="flex items-center gap-3 px-4 py-3 <?= basename($_SERVER['PHP_SELF']) == 'posts.php' ? 'text-white' : 'text-gray-700 hover:bg-gray-100' ?> rounded-lg font-medium transition" style="background-color: <?= basename($_SERVER['PHP_SELF']) == 'posts.php' ? $primary_color : 'transparent' ?>;">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
                Kelola Postingan
            </a>
            <a href="<?= url('korti/create-post') ?>" class="flex items-center gap-3 px-4 py-3 <?= basename($_SERVER['PHP_SELF']) == 'create_post.php' ? 'text-white' : 'text-gray-700 hover:bg-gray-100' ?> rounded-lg font-medium transition" style="background-color: <?= basename($_SERVER['PHP_SELF']) == 'create_post.php' ? $primary_color : 'transparent' ?>;">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                Buat Postingan
            </a>
            <a href="<?= url('korti/announcements') ?>" class="flex items-center gap-3 px-4 py-3 <?= basename($_SERVER['PHP_SELF']) == 'announcements.php' ? 'text-white' : 'text-gray-700 hover:bg-gray-100' ?> rounded-lg font-medium transition" style="background-color: <?= basename($_SERVER['PHP_SELF']) == 'announcements.php' ? $primary_color : 'transparent' ?>;">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z"/>
                </svg>
                Kelola Pengumuman
            </a>
            <a href="<?= url('korti/comments') ?>" class="flex items-center gap-3 px-4 py-3 <?= basename($_SERVER['PHP_SELF']) == 'comments.php' ? 'text-white' : 'text-gray-700 hover:bg-gray-100' ?> rounded-lg font-medium transition" style="background-color: <?= basename($_SERVER['PHP_SELF']) == 'comments.php' ? $primary_color : 'transparent' ?>;">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z"/>
                </svg>
                Kelola Komentar
            </a>
        </nav>
    </div>
</aside>

<!-- Sidebar Toggle Script -->
<script>
    const sidebar = document.getElementById('sidebar');
    const sidebarOverlay = document.getElementById('sidebar-overlay');
    const sidebarToggle = document.getElementById('sidebar-toggle');

    if (sidebarToggle) {
        sidebarToggle.addEventListener('click', () => {
            sidebar.classList.toggle('-translate-x-full');
            sidebarOverlay.classList.toggle('hidden');
        });

        sidebarOverlay.addEventListener('click', () => {
            sidebar.classList.add('-translate-x-full');
            sidebarOverlay.classList.add('hidden');
        });
    }
</script>
