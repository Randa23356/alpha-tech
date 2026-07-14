<!-- Admin Navigation -->
<?php require_once __DIR__ . "/../src/config/urls.php"; ?>
<nav class="bg-white shadow-md sticky top-0 z-50">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between items-center h-16">
            <div class="flex items-center gap-3">
                <div class="bg-gradient-to-br from-blue-900 to-blue-700 p-2 rounded-lg">
                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                    </svg>
                </div>
                <div>
                    <div class="text-xl font-bold text-blue-900">Informatics A</div>
                    <div class="text-xs text-gray-600">Admin Panel</div>
                </div>
            </div>
            <div class="flex items-center gap-4">
                <?php if (isAdmin()): ?>
                    <a href="<?= url('admin') ?>" class="hidden md:block text-gray-700 hover:text-blue-900 font-medium transition">Dashboard</a>
                <?php elseif (isKorti()): ?>
                    <a href="<?= url('korti') ?>" class="hidden md:block text-gray-700 hover:text-blue-900 font-medium transition">Dashboard</a>
                <?php endif; ?>
                <div class="hidden md:flex items-center gap-2 bg-blue-50 px-4 py-2 rounded-lg">
                    <svg class="w-5 h-5 text-blue-900" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                    </svg>
                    <span class="text-sm font-semibold text-blue-900"><?= htmlspecialchars($admin["username"] ?? $user["username"] ?? 'Admin') ?></span>
                    <span class="text-xs bg-blue-900 text-white px-2 py-0.5 rounded-full"><?= isAdmin() ? 'Admin' : 'Korti' ?></span>
                </div>
                <a href=" <?= url('logout') ?>" class="bg-red-600 text-white px-4 py-2 rounded-lg hover:bg-red-700 transition font-medium flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                    </svg>
                    <span class="hidden sm:inline">Logout</span>
                </a>
            </div>
        </div>
    </div>
</nav>
