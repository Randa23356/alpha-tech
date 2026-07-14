<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Informatics A - Kegiatan Kelas</title>
    <link href="/informatics_a/public/tailwind.css" rel="stylesheet">
</head>
<body class="bg-gray-50">
    <!-- Navigation -->
    <nav class="bg-white shadow-lg sticky top-0 z-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center h-16">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 bg-gradient-to-br from-blue-900 to-blue-700 rounded-lg flex items-center justify-center">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
                        </svg>
                    </div>
                    <span class="text-2xl font-bold bg-gradient-to-r from-blue-900 to-blue-700 bg-clip-text text-transparent">Informatics A</span>
                </div>
                <div class="hidden md:flex space-x-8">
                    <a href="<?= url('home') ?>" class="text-blue-900 font-bold border-b-2 border-blue-900 pb-1">Home</a>
                    <a href="<?= url('gallery') ?>" class="text-gray-700 hover:text-blue-900 font-medium transition">Galeri</a>
                    <a href="<?= url('announcement') ?>" class="text-gray-700 hover:text-blue-900 font-medium transition">Pengumuman</a>
                </div>
                <div>
                    <a href="<?= url('login') ?>" class="bg-gradient-to-r from-blue-900 to-blue-700 text-white px-6 py-2 rounded-lg font-semibold hover:from-blue-800 hover:to-blue-600 transition shadow-md">Login</a>
                </div>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="bg-gradient-to-br from-blue-900 via-blue-800 to-indigo-900 text-white py-20 px-6 relative overflow-hidden">
        <div class="absolute inset-0 opacity-10">
            <div class="absolute top-10 left-10 w-72 h-72 bg-blue-400 rounded-full blur-3xl"></div>
            <div class="absolute bottom-10 right-10 w-96 h-96 bg-indigo-400 rounded-full blur-3xl"></div>
        </div>
        <div class="max-w-6xl mx-auto text-center relative z-10">
            <h1 class="text-5xl md:text-6xl font-bold mb-6 animate-fade-in">Selamat Datang di Informatics A</h1>
            <p class="text-xl md:text-2xl mb-8 text-blue-100 max-w-3xl mx-auto">Platform digital untuk berbagi kegiatan, dokumentasi, dan kolaborasi kelas Informatika terbaik!</p>
            <div class="flex flex-col sm:flex-row gap-4 justify-center">
                <a href="<?= url('register') ?>" class="bg-white text-blue-900 px-8 py-3 rounded-lg font-bold hover:bg-blue-50 transition shadow-lg inline-flex items-center justify-center gap-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/>
                    </svg>
                    Mulai Sekarang
                </a>
                <a href="#activities" class="bg-transparent border-2 border-white text-white px-8 py-3 rounded-lg font-bold hover:bg-white hover:text-blue-900 transition inline-flex items-center justify-center gap-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                    </svg>
                    Lihat Kegiatan
                </a>
            </div>
        </div>
    </section>

    <!-- Stats Section -->
    <section class="py-16 bg-white">
        <div class="max-w-6xl mx-auto px-6">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-8 text-center">
                <div class="p-8 bg-gradient-to-br from-blue-50 via-white to-blue-50 rounded-2xl shadow-lg hover:shadow-xl transition">
                    <div class="text-6xl font-bold bg-gradient-to-r from-blue-900 to-blue-700 bg-clip-text text-transparent mb-2"><?= $total_kegiatan ?></div>
                    <div class="text-gray-600 font-semibold text-lg">Kegiatan Terdokumentasi</div>
                </div>
                <div class="p-8 bg-gradient-to-br from-indigo-50 via-white to-indigo-50 rounded-2xl shadow-lg hover:shadow-xl transition">
                    <div class="text-6xl font-bold bg-gradient-to-r from-indigo-900 to-indigo-700 bg-clip-text text-transparent mb-2"><?= $total_users ?></div>
                    <div class="text-gray-600 font-semibold text-lg">Anggota Aktif</div>
                </div>
                <div class="p-8 bg-gradient-to-br from-purple-50 via-white to-purple-50 rounded-2xl shadow-lg hover:shadow-xl transition">
                    <div class="text-6xl font-bold bg-gradient-to-r from-purple-900 to-purple-700 bg-clip-text text-transparent mb-2"><?= $total_photos ?></div>
                    <div class="text-gray-600 font-semibold text-lg">Foto & Dokumentasi</div>
                </div>
            </div>
        </div>
    </section>

    <!-- Activities Section -->
    <section id="activities" class="py-16 bg-gradient-to-b from-gray-50 to-white">
        <div class="max-w-6xl mx-auto px-6">
            <div class="text-center mb-12">
                <h2 class="text-4xl font-bold bg-gradient-to-r from-blue-900 to-indigo-900 bg-clip-text text-transparent mb-4">Kegiatan Terbaru</h2>
                <p class="text-gray-600 text-lg">Dokumentasi kegiatan seru kelas Informatics A</p>
            </div>
            <?php if (empty($kegiatan)): ?>
                <div class="bg-white p-12 rounded-2xl shadow-lg text-center">
                    <div class="text-6xl mb-4">📚</div>
                    <p class="text-gray-600 text-lg">Belum ada kegiatan yang dipublikasikan. Tunggu update dari admin!</p>
                </div>
            <?php else: ?>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                    <?php foreach ($kegiatan as $item): ?>
                        <div class="bg-white rounded-2xl shadow-lg hover:shadow-2xl transition-all duration-300 overflow-hidden group">
                            <?php if ($item["image"]): ?>
                                <div class="h-48 overflow-hidden bg-gradient-to-br from-blue-100 to-indigo-100">
                                    <img src="<?= url('uploads/' . htmlspecialchars($item["image"])) ?>" alt="Foto Kegiatan" class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-300">
                                </div>
                            <?php else: ?>
                                <div class="h-48 bg-gradient-to-br from-blue-100 to-indigo-200 flex items-center justify-center">
                                    <span class="text-6xl">🎓</span>
                                </div>
                            <?php endif; ?>
                            <div class="p-6">
                                <h3 class="text-xl font-bold text-gray-900 mb-3 line-clamp-2"><?= htmlspecialchars($item["title"]) ?></h3>
                                <p class="text-gray-600 mb-4 line-clamp-3"><?= htmlspecialchars(substr($item["description"], 0, 120)) ?>...</p>
                                <div class="flex justify-between items-center text-sm text-gray-500 border-t pt-4">
                                    <span class="flex items-center gap-1">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                                        <?= date("d M Y", strtotime($item["date"])) ?>
                                    </span>
                                    <span class="flex items-center gap-1">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
                                        <?= htmlspecialchars($item["username"]) ?>
                                    </span>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </section>

    <!-- Features Section -->
    <section class="py-16 bg-white">
        <div class="max-w-6xl mx-auto px-6">
            <div class="text-center mb-12">
                <h2 class="text-4xl font-bold bg-gradient-to-r from-blue-900 to-indigo-900 bg-clip-text text-transparent mb-4">Fitur Platform</h2>
                <p class="text-gray-600 text-lg">Semua yang kamu butuhkan untuk kolaborasi kelas</p>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                <div class="text-center p-8 rounded-2xl bg-gradient-to-br from-blue-50 to-blue-100 hover:shadow-xl transition">
                    <div class="text-5xl mb-4">📝</div>
                    <h3 class="text-xl font-bold text-blue-900 mb-3">Posting Kegiatan</h3>
                    <p class="text-gray-600">Bagikan kegiatan kelas dengan mudah dan cepat</p>
                </div>
                <div class="text-center p-8 rounded-2xl bg-gradient-to-br from-indigo-50 to-indigo-100 hover:shadow-xl transition">
                    <div class="text-5xl mb-4">📸</div>
                    <h3 class="text-xl font-bold text-indigo-900 mb-3">Galeri Foto</h3>
                    <p class="text-gray-600">Dokumentasi visual kegiatan dalam satu tempat</p>
                </div>
                <div class="text-center p-8 rounded-2xl bg-gradient-to-br from-purple-50 to-purple-100 hover:shadow-xl transition">
                    <div class="text-5xl mb-4">💬</div>
                    <h3 class="text-xl font-bold text-purple-900 mb-3">Komentar</h3>
                    <p class="text-gray-600">Diskusi dan berikan feedback pada setiap kegiatan</p>
                </div>
            </div>
        </div>
    </section>

    <!-- About Section -->
    <section class="py-16 bg-gradient-to-br from-blue-900 via-indigo-900 to-purple-900 text-white">
        <div class="max-w-6xl mx-auto px-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-12 items-center">
                <div>
                    <h2 class="text-4xl font-bold mb-6">Tentang Informatics A</h2>
                    <p class="text-blue-100 text-lg mb-6">Platform digital yang dirancang khusus untuk memfasilitasi dokumentasi, berbagi informasi, dan kolaborasi antar anggota kelas Informatika A.</p>
                    <p class="text-blue-100 text-lg mb-6">Dengan fitur-fitur modern dan antarmuka yang user-friendly, kami memudahkan setiap anggota untuk berkontribusi dan tetap terhubung.</p>
                    <a href="<?= url('register') ?>" class="inline-block bg-white text-blue-900 px-8 py-3 rounded-lg font-bold hover:bg-blue-50 transition shadow-lg">Bergabung Sekarang</a>
                </div>
                <div class="bg-white/10 backdrop-blur-sm rounded-2xl p-8">
                    <h3 class="text-2xl font-bold mb-6">Kenapa Memilih Platform Ini?</h3>
                    <ul class="space-y-4">
                        <li class="flex items-start gap-3">
                            <svg class="w-6 h-6 text-green-400 flex-shrink-0 mt-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                            <span class="text-blue-100">Interface modern dan mudah digunakan</span>
                        </li>
                        <li class="flex items-start gap-3">
                            <svg class="w-6 h-6 text-green-400 flex-shrink-0 mt-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                            <span class="text-blue-100">Sistem approval untuk menjaga kualitas konten</span>
                        </li>
                        <li class="flex items-start gap-3">
                            <svg class="w-6 h-6 text-green-400 flex-shrink-0 mt-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                            <span class="text-blue-100">Galeri foto untuk dokumentasi visual</span>
                        </li>
                        <li class="flex items-start gap-3">
                            <svg class="w-6 h-6 text-green-400 flex-shrink-0 mt-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                            <span class="text-blue-100">Responsive design untuk semua perangkat</span>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="bg-gray-900 text-white py-12">
        <div class="max-w-6xl mx-auto px-6">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-8 mb-8">
                <div>
                    <h3 class="text-2xl font-bold mb-4 bg-gradient-to-r from-blue-400 to-indigo-400 bg-clip-text text-transparent">Informatics A</h3>
                    <p class="text-gray-400">Platform kolaborasi dan dokumentasi kelas Informatika terbaik.</p>
                </div>
                <div>
                    <h4 class="font-bold mb-4">Menu</h4>
                    <ul class="space-y-2 text-gray-400">
                        <li><a href="<?= url('home') ?>" class="hover:text-white transition">Home</a></li>
                        <li><a href="<?= url('gallery') ?>" class="hover:text-white transition">Galeri</a></li>
                        <li><a href="<?= url('announcement') ?>" class="hover:text-white transition">Pengumuman</a></li>
                        <li><a href="<?= url('login') ?>" class="hover:text-white transition">Login</a></li>
                    </ul>
                </div>
                <div>
                    <h4 class="font-bold mb-4">Kontak</h4>
                    <p class="text-gray-400 mb-2">Email: info@informaticsa.edu</p>
                    <p class="text-gray-400">Instagram: @informaticsa</p>
                </div>
            </div>
            <div class="border-t border-gray-800 pt-8 text-center text-gray-400">
                <p>&copy; <?= date("Y") ?> Informatics A. All rights reserved. Built with ❤️ by Informatics A Team</p>
            </div>
        </div>
    </footer>
</body>
</html>
