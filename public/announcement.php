<?php
// informatics_a/public/announcement.php
require_once __DIR__ . '/../src/config/db.php';
require_once __DIR__ . '/../src/config/urls.php';

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

// Ambil semua pengumuman dari database (tabel: announcements)
$stmt = $pdo->prepare("SELECT * FROM announcements ORDER BY created_at DESC");
$stmt->execute();
$announcements = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pengumuman - Informatics A</title>
    <link href="<?= asset('tailwind.css') ?>" rel="stylesheet">
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
        .hero-gradient {
            background: linear-gradient(135deg, <?= $primary_color ?> 0%, <?= $accent_color ?> 50%, <?= $secondary_color ?> 100%);
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
    </style>
        /* Modern animations */
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .animate-fade-in-up {
            animation: fadeInUp 0.8s ease-out;
        }

        /* Announcement card hover effects */
        .announcement-card {
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .announcement-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 20px 40px rgba(59, 130, 246, 0.15);
        }
    </style>
</head>
<body class="bg-slate-50">
    <?php include __DIR__ . '/../includes/navbar.php'; ?>

    <!-- Modern Hero Section -->
    <section class="relative py-24 hero-gradient text-white overflow-hidden">
        <!-- Animated background elements -->
        <div class="absolute inset-0">
            <div class="absolute top-20 left-20 w-72 h-72 bg-white/10 rounded-full blur-3xl animate-pulse"></div>
            <div class="absolute bottom-20 right-20 w-96 h-96 bg-purple-400/20 rounded-full blur-3xl animate-pulse delay-1000"></div>
            <div class="absolute top-1/2 left-1/2 transform -translate-x-1/2 -translate-y-1/2 w-[800px] h-[800px] bg-gradient-to-r from-blue-400/20 to-purple-600/20 rounded-full blur-3xl"></div>
        </div>

        <div class="relative max-w-7xl mx-auto px-6 text-center">
            <div class="animate-fade-in-up">
                <!-- Icon -->
                <div class="mb-8 flex justify-center">
                    <div class="relative">
                        <div class="absolute inset-0 bg-white/20 rounded-full blur-xl"></div>
                        <div class="relative w-20 h-20 bg-white/10 backdrop-blur-sm rounded-2xl flex items-center justify-center">
                            <svg class="w-10 h-10 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z"/>
                            </svg>
                        </div>
                    </div>
                </div>

                <!-- Main heading -->
                <h1 class="text-5xl md:text-6xl font-bold mb-6 leading-tight">
                    Pengumuman Kelas
                </h1>

                <!-- Subtitle -->
                <p class="text-xl md:text-2xl text-gray-200 mb-8 max-w-3xl mx-auto leading-relaxed">
                    Informasi terbaru dan penting untuk seluruh anggota kelas Informatics A
                </p>

                <!-- Stats -->
                <div class="inline-flex items-center gap-8 bg-white/10 backdrop-blur-sm rounded-2xl px-8 py-4">
                    <div class="text-center">
                        <div class="text-2xl font-bold text-white">
                            <?= count($announcements) ?>
                        </div>
                        <div class="text-sm text-gray-300">Pengumuman</div>
                    </div>
                    <div class="w-px h-8 bg-white/20"></div>
                    <div class="text-center">
                        <div class="text-2xl font-bold text-white">
                            Aktif
                        </div>
                        <div class="text-sm text-gray-300">Update Terbaru</div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Main Content -->
    <main class="max-w-7xl mx-auto px-6 py-24">
        <?php if (empty($announcements)): ?>
            <div class="bg-white p-16 rounded-3xl shadow-lg text-center animate-fade-in-up">
                <div class="w-24 h-24 bg-blue-100 rounded-full flex items-center justify-center mx-auto mb-6">
                    <svg class="w-12 h-12 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z"/>
                    </svg>
                </div>
                <h3 class="text-2xl font-bold text-gray-700 mb-3">Belum Ada Pengumuman</h3>
                <p class="text-gray-500 text-lg">Belum ada pengumuman kelas saat ini. Pengumuman terbaru akan muncul di sini.</p>
            </div>
        <?php else: ?>
            <!-- Announcements Grid -->
            <div class="space-y-8">
                <?php foreach ($announcements as $index => $item): ?>
                    <article class="bg-white rounded-3xl shadow-xl hover:shadow-2xl transition-all duration-500 overflow-hidden announcement-card group animate-fade-in-up"
                             style="animation-delay: <?= $index * 0.1 ?>s;">

                        <!-- Header with Gradient -->
                        <div class="card-gradient text-white p-8 relative overflow-hidden">
                            <!-- Background Pattern -->
                            <div class="absolute inset-0 opacity-10">
                                <div class="absolute inset-0 bg-[url('data:image/svg+xml,%3Csvg width=\"60\" height=\"60\" viewBox=\"0 0 60 60\" xmlns=\"http://www.w3.org/2000/svg\"%3E%3Cg fill=\"none\" fill-rule=\"evenodd\"%3E%3Cg fill=\"%23ffffff\" fill-opacity=\"0.1\"%3E%3Ccircle cx=\"7\" cy=\"7\" r=\"1\"/%3E%3C/g%3E%3C/g%3E%3C/svg%3E')]"></div>
                            </div>

                            <div class="relative">
                                <div class="flex items-center justify-between mb-4">
                                    <div class="flex items-center gap-4">
                                        <div class="w-14 h-14 bg-white/20 backdrop-blur-sm rounded-2xl flex items-center justify-center">
                                            <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z"/>
                                            </svg>
                                        </div>
                                        <div>
                                            <h2 class="text-2xl font-bold mb-1">
                                                <?= htmlspecialchars($item['title']) ?>
                                            </h2>
                                            <div class="flex items-center gap-4 text-indigo-100">
                                                <span class="flex items-center gap-2">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                                    </svg>
                                                    <?= date('d F Y \p\a\d\a H:i', strtotime($item['created_at'])) ?>
                                                </span>
                                                <?php if (!empty($item['author'])): ?>
                                                    <span class="flex items-center gap-2">
                                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                                                        </svg>
                                                        <?= htmlspecialchars($item['author']) ?>
                                                    </span>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Priority Badge -->
                                    <?php if (isset($item['priority']) && $item['priority'] === 'high'): ?>
                                        <div class="bg-red-500 text-white px-4 py-2 rounded-full text-sm font-bold animate-pulse">
                                            PENTING
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>

                        <!-- Content -->
                        <div class="p-8 lg:p-10">
                            <div class="prose prose-xl max-w-none">
                                <div class="text-gray-700 leading-relaxed text-lg whitespace-pre-line">
                                    <?= nl2br(htmlspecialchars($item['content'])) ?>
                                </div>
                            </div>

                            <!-- Enhanced Actions -->
                            <div class="mt-8 pt-8 border-t border-gray-100">
                                <div class="flex flex-col sm:flex-row items-center justify-between gap-6">
                                    <div class="text-sm text-gray-500 bg-gray-50 px-4 py-2 rounded-xl">
                                        <span class="font-semibold text-gray-700">Dipublikasikan:</span>
                                        <?= date('d F Y \p\a\d\a H:i', strtotime($item['created_at'])) ?>
                                    </div>

                                    <?php if (!empty($item['attachment'])): ?>
                                        <a href="<?= htmlspecialchars($item['attachment']) ?>"
                                           class="inline-flex items-center gap-3 btn-gradient text-white px-6 py-3 rounded-2xl font-bold hover:btn-gradient transition-all duration-300 shadow-lg hover:shadow-xl transform hover:-translate-y-1">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l4-4m-4 4l-4-4m14 2V7a2 2 0 00-2-2H6a2 2 0 00-2 2v10a2 2 0 002 2h14a2 2 0 002-2z"/>
                                            </svg>
                                            Download Lampiran
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <!-- Back Button -->
        <div class="mt-16 text-center animate-fade-in-up">
            <a href=" <?= url('') ?>" class="inline-flex items-center gap-3 bg-white text-gray-900 px-8 py-4 rounded-2xl font-bold hover:bg-gray-50 transition-all duration-300 shadow-lg hover:shadow-xl transform hover:-translate-y-1 border border-gray-200">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                </svg>
                Kembali ke Beranda
            </a>
        </div>
    </main>

    <?php include __DIR__ . '/../includes/footer.php'; ?>

    <script>
        // Enhanced animations on scroll
        const observerOptions = {
            threshold: 0.1,
            rootMargin: '0px 0px -50px 0px'
        };

        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('animate-fade-in-up');
                }
            });
        }, observerOptions);

        document.querySelectorAll('.announcement-card').forEach(card => {
            observer.observe(card);
        });
    </script>
</body>
</html>
