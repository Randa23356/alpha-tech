<!-- Sidebar Toggle Button (Mobile) -->
<button id="admin-sidebar-toggle" class="lg:hidden fixed bottom-6 right-6 z-50 bg-blue-900 text-white p-4 rounded-full shadow-lg hover:bg-blue-800 transition">
    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
    </svg>
</button>

<!-- Sidebar -->
<div id="admin-sidebar-overlay" class="fixed inset-0 bg-black bg-opacity-50 z-40 hidden lg:hidden"></div>
<aside id="admin-sidebar" class="fixed left-0 top-16 h-[calc(100vh-4rem)] w-64 bg-white shadow-xl transform -translate-x-full lg:translate-x-0 transition-transform duration-300 z-40 overflow-y-auto">
    <div class="p-6">
        <h2 class="text-lg font-bold text-gray-900 mb-4">Admin Menu</h2>
        <nav class="space-y-2">
            <a href="<?= url('admin') ?>" class="flex items-center gap-3 px-4 py-3 <?= basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'bg-blue-50 text-blue-900' : 'text-gray-700 hover:bg-gray-100' ?> rounded-lg font-medium transition">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2H5a2 2 0 00-2-2z"/>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 5a2 2 0 012-2h4a2 2 0 012 2v4H8V5z"/>
                </svg>
                Dashboard Admin
            </a>
            <a href="<?= url('admin/posts') ?>" class="flex items-center gap-3 px-4 py-3 <?= basename($_SERVER['PHP_SELF']) == 'manage_posts.php' ? 'bg-blue-50 text-blue-900' : 'text-gray-700 hover:bg-gray-100' ?> rounded-lg font-medium transition">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
                Kelola Postingan
            </a>
            <a href="<?= url('admin/gallery') ?>" class="flex items-center gap-3 px-4 py-3 <?= basename($_SERVER['PHP_SELF']) == 'manage_gallery.php' ? 'bg-blue-50 text-blue-900' : 'text-gray-700 hover:bg-gray-100' ?> rounded-lg font-medium transition">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                </svg>
                Kelola Galeri
            </a>
            <a href="<?= url('admin/comments') ?>" class="flex items-center gap-3 px-4 py-3 <?= basename($_SERVER['PHP_SELF']) == 'manage_comments.php' ? 'bg-blue-50 text-blue-900' : 'text-gray-700 hover:bg-gray-100' ?> rounded-lg font-medium transition">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z"/>
                </svg>
                Kelola Komentar
            </a>
            <a href="<?= url('admin/announcements') ?>" class="flex items-center gap-3 px-4 py-3 <?= basename($_SERVER['PHP_SELF']) == 'announcement.php' ? 'bg-blue-50 text-blue-900' : 'text-gray-700 hover:bg-gray-100' ?> rounded-lg font-medium transition">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z"/>
                </svg>
                Kelola Pengumuman
            </a>
            <a href="<?= url('admin/users') ?>" class="flex items-center gap-3 px-4 py-3 <?= basename($_SERVER['PHP_SELF']) == 'manage_users.php' ? 'bg-blue-50 text-blue-900' : 'text-gray-700 hover:bg-gray-100' ?> rounded-lg font-medium transition">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z"/>
                </svg>
                Kelola Pengguna
            </a>
            <a href="<?= url('admin/features') ?>" class="flex items-center gap-3 px-4 py-3 <?= basename($_SERVER['PHP_SELF']) == 'manage_features.php' ? 'bg-blue-50 text-blue-900' : 'text-gray-700 hover:bg-gray-100' ?> rounded-lg font-medium transition">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
                </svg>
                Kelola Fitur
            </a>
            <a href="<?= url('admin/settings') ?>" class="flex items-center gap-3 px-4 py-3 <?= basename($_SERVER['PHP_SELF']) == 'site_settings.php' ? 'bg-blue-50 text-blue-900' : 'text-gray-700 hover:bg-gray-100' ?> rounded-lg font-medium transition">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                </svg>
                Pengaturan Situs
            </a>
        </nav>
    </div>
</aside>

<!-- Sidebar Toggle Script -->
<script>
    const adminSidebar = document.getElementById('admin-sidebar');
    const adminSidebarOverlay = document.getElementById('admin-sidebar-overlay');
    const adminSidebarToggle = document.getElementById('admin-sidebar-toggle');

    if (adminSidebarToggle) {
        adminSidebarToggle.addEventListener('click', () => {
            adminSidebar.classList.toggle('-translate-x-full');
            adminSidebarOverlay.classList.toggle('hidden');
        });

        adminSidebarOverlay.addEventListener('click', () => {
            adminSidebar.classList.add('-translate-x-full');
            adminSidebarOverlay.classList.add('hidden');
        });
    }
</script>
