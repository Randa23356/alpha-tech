<?php
// informatics_a/public/contact.php
session_start();
require_once __DIR__ . "/../src/config/db.php";
require_once __DIR__ . "/../src/config/urls.php";

// Load theme colors from database
$primary_color = '#1e3a8a'; // default
$secondary_color = '#1e40af'; // default
$accent_color = '#ec4899'; // default
$success_color = '#10b981'; // default
$warning_color = '#f59e0b'; // default
$danger_color = '#ef4444'; // default

try {
    $stmt = $pdo->query("SELECT setting_key, setting_value FROM site_settings WHERE setting_key IN ('primary_color', 'secondary_color', 'accent_color', 'success_color', 'warning_color', 'danger_color')");
    $settings_colors = [];
    foreach ($stmt->fetchAll() as $row) {
        $settings_colors[$row['setting_key']] = $row['setting_value'];
    }
    $primary_color = $settings_colors['primary_color'] ?? $primary_color;
    $secondary_color = $settings_colors['secondary_color'] ?? $secondary_color;
    $accent_color = $settings_colors['accent_color'] ?? $accent_color;
    $success_color = $settings_colors['success_color'] ?? $success_color;
    $warning_color = $settings_colors['warning_color'] ?? $warning_color;
    $danger_color = $settings_colors['danger_color'] ?? $danger_color;
} catch (Exception $e) {
    // Use default colors if database fails
}

// Ambil data pengaturan dari database
$stmt = $pdo->query("SELECT setting_key, setting_value FROM site_settings");
$settings = [];
foreach ($stmt->fetchAll() as $row) {
    $settings[$row['setting_key']] = $row['setting_value'];
}

// Default values jika tidak ada di database
$site_name = $settings['site_name'] ?? 'Informatics A';
$contact_email = $settings['contact_email'] ?? 'info@informaticsa.edu';
$contact_instagram = $settings['contact_instagram'] ?? '@informaticsa';
$contact_phone = $settings['contact_phone'] ?? '+62 812-3456-7890';
$contact_address = $settings['contact_address'] ?? 'Jl. Pendidikan No. 123, Jakarta, Indonesia';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kontak - <?= htmlspecialchars($site_name) ?></title>
    <link href="<?= asset('tailwind.css') ?>" rel="stylesheet">
    <?php require_once __DIR__ . '/../includes/favicon.php'; ?>
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
        .hero-gradient {
            background: linear-gradient(135deg, <?= $primary_color ?> 0%, <?= $accent_color ?> 50%, <?= $secondary_color ?> 100%);
        }

        .btn-gradient {
            background: linear-gradient(135deg, <?= $primary_color ?> 0%, <?= $secondary_color ?> 100%);
        }

        .icon-gradient-1 {
            background: linear-gradient(135deg, <?= $primary_color ?> 0%, <?= $secondary_color ?> 100%);
        }

        .icon-gradient-2 {
            background: linear-gradient(135deg, <?= $success_color ?> 0%, #059669 100%);
        }

        .icon-gradient-3 {
            background: linear-gradient(135deg, <?= $accent_color ?> 0%, #db2777 100%);
        }

        .icon-gradient-4 {
            background: linear-gradient(135deg, <?= $warning_color ?> 0%, #ea580c 100%);
        }

        .icon-gradient-5 {
            background: linear-gradient(135deg, <?= $primary_color ?> 0%, <?= $secondary_color ?> 100%);
        }

        /* Theme hover effects */
        .theme-hover:hover {
            color: <?= $primary_color ?>;
        }

        /* Form focus colors */
        .form-input:focus {
            border-color: <?= $primary_color ?>;
            box-shadow: 0 0 0 3px rgba(<?= hexdec(substr($primary_color, 1, 2)) ?>, <?= hexdec(substr($primary_color, 3, 2)) ?>, <?= hexdec(substr($primary_color, 5, 2)) ?>, 0.1);
        }

        .group-focus-within\:text-indigo-600 {
            color: <?= $primary_color ?>;
        }

        .group-focus-within\:text-indigo-500 {
            color: <?= $primary_color ?>;
        }

        .focus\:border-indigo-500 {
            border-color: <?= $primary_color ?>;
        }

        .focus\:ring-indigo-500\/20 {
            box-shadow: 0 0 0 3px rgba(<?= hexdec(substr($primary_color, 1, 2)) ?>, <?= hexdec(substr($primary_color, 3, 2)) ?>, <?= hexdec(substr($primary_color, 5, 2)) ?>, 0.1);
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

        @keyframes slideInLeft {
            from {
                opacity: 0;
                transform: translateX(-30px);
            }
            to {
                opacity: 1;
                transform: translateX(0);
            }
        }

        .animate-fade-in-up {
            animation: fadeInUp 0.8s ease-out;
        }

        .animate-slide-in-left {
            animation: slideInLeft 0.8s ease-out;
        }

        /* Enhanced form styling */
        .form-input {
            transition: all 0.3s ease;
            border: 2px solid #e5e7eb;
        }

        .form-input:focus {
            border-color: <?= $primary_color ?>;
            box-shadow: 0 0 0 3px rgba(<?= hexdec(substr($primary_color, 1, 2)) ?>, <?= hexdec(substr($primary_color, 3, 2)) ?>, <?= hexdec(substr($primary_color, 5, 2)) ?>, 0.1);
        }

        /* Card hover effects */
        .contact-card {
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .contact-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
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
            <div class="absolute bottom-20 right-20 w-96 h-96 rounded-full blur-3xl animate-pulse delay-1000" style="background-color: <?= $accent_color ?>20;"></div>
            <div class="absolute top-1/2 left-1/2 transform -translate-x-1/2 -translate-y-1/2 w-[800px] h-[800px] rounded-full blur-3xl" style="background: linear-gradient(135deg, <?= $primary_color ?>20 0%, <?= $accent_color ?>20 100%);"></div>
        </div>

        <div class="relative max-w-7xl mx-auto px-6 text-center">
            <div class="animate-fade-in-up">
                <!-- Icon -->
                <div class="mb-8 flex justify-center">
                    <div class="relative">
                        <div class="absolute inset-0 bg-white/20 rounded-full blur-xl"></div>
                        <div class="relative w-20 h-20 bg-white/10 backdrop-blur-sm rounded-2xl flex items-center justify-center">
                            <svg class="w-10 h-10 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                            </svg>
                        </div>
                    </div>
                </div>

                <!-- Main heading -->
                <h1 class="text-5xl md:text-6xl font-bold mb-6 leading-tight">
                    Hubungi Kami
                </h1>

                <!-- Subtitle -->
                <p class="text-xl md:text-2xl text-gray-200 mb-8 max-w-3xl mx-auto leading-relaxed">
                    Ada pertanyaan atau ingin berkolaborasi? Kami siap membantu Anda!
                </p>
            </div>
        </div>
    </section>

    <!-- Contact Section -->
    <section class="py-24 bg-white">
        <div class="max-w-7xl mx-auto px-6">
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-16 items-start">

                <!-- Contact Form -->
                <div class="animate-slide-in-left">
                    <div class="bg-white p-8 rounded-3xl shadow-lg border border-gray-100">
                        <h2 class="text-3xl font-bold text-gray-900 mb-6">Kirim Pesan</h2>
                        <p class="text-gray-600 mb-8">Isi formulir di bawah ini dan kami akan segera merespons pertanyaan Anda.</p>

                        <form class="space-y-8">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                                <!-- Nama -->
                                <div class="group">
                                    <label for="nama" class="block text-sm font-bold text-gray-700 mb-3 group-focus-within:text-indigo-600 transition-colors">
                                        Nama Lengkap *
                                    </label>
                                    <div class="relative">
                                        <input type="text" id="nama" name="nama"
                                               class="w-full px-6 py-4 rounded-2xl form-input bg-gray-50 border-2 border-gray-200 focus:bg-white focus:border-indigo-500 focus:ring-4 focus:ring-indigo-500/20 transition-all duration-300 pl-12"
                                               placeholder="Masukkan nama lengkap Anda" required>
                                        <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                            <svg class="w-5 h-5 text-gray-400 group-focus-within:text-indigo-500 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                                            </svg>
                                        </div>
                                    </div>
                                </div>

                                <!-- Email -->
                                <div class="group">
                                    <label for="email" class="block text-sm font-bold text-gray-700 mb-3 group-focus-within:text-indigo-600 transition-colors">
                                        Email *
                                    </label>
                                    <div class="relative">
                                        <input type="email" id="email" name="email"
                                               class="w-full px-6 py-4 rounded-2xl form-input bg-gray-50 border-2 border-gray-200 focus:bg-white focus:border-indigo-500 focus:ring-4 focus:ring-indigo-500/20 transition-all duration-300 pl-12"
                                               placeholder="nama@email.com" required>
                                        <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                            <svg class="w-5 h-5 text-gray-400 group-focus-within:text-indigo-500 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                                            </svg>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Subjek -->
                            <div class="group">
                                <label for="subjek" class="block text-sm font-bold text-gray-700 mb-3 group-focus-within:text-indigo-600 transition-colors">
                                    Subjek *
                                </label>
                                <div class="relative">
                                    <input type="text" id="subjek" name="subjek"
                                           class="w-full px-6 py-4 rounded-2xl form-input bg-gray-50 border-2 border-gray-200 focus:bg-white focus:border-indigo-500 focus:ring-4 focus:ring-indigo-500/20 transition-all duration-300 pl-12"
                                           placeholder="Apa yang bisa kami bantu?" required>
                                    <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                        <svg class="w-5 h-5 text-gray-400 group-focus-within:text-indigo-500 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/>
                                        </svg>
                                    </div>
                                </div>
                            </div>

                            <!-- Pesan -->
                            <div class="group">
                                <label for="pesan" class="block text-sm font-bold text-gray-700 mb-3 group-focus-within:text-indigo-600 transition-colors">
                                    Pesan *
                                </label>
                                <div class="relative">
                                    <textarea id="pesan" name="pesan" rows="7"
                                              class="w-full px-6 py-4 rounded-2xl form-input bg-gray-50 border-2 border-gray-200 focus:bg-white focus:border-indigo-500 focus:ring-4 focus:ring-indigo-500/20 transition-all duration-300 pl-12 pt-4 resize-none"
                                              placeholder="Tuliskan pesan Anda di sini..." required></textarea>
                                    <div class="absolute top-4 left-4 pointer-events-none">
                                        <svg class="w-5 h-5 text-gray-400 group-focus-within:text-indigo-500 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                                        </svg>
                                    </div>
                                </div>
                            </div>

                            <!-- Submit Button -->
                            <div class="pt-4">
                                <button type="submit"
                                        class="group w-full text-white px-8 py-5 rounded-2xl font-bold text-lg transition-all duration-300 shadow-xl hover:shadow-2xl transform hover:-translate-y-1 flex items-center justify-center gap-3" style="background: linear-gradient(135deg, <?= $primary_color ?> 0%, <?= $secondary_color ?> 100%);">
                                    <svg class="w-6 h-6 transition-transform duration-300 group-hover:scale-110" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/>
                                    </svg>
                                    <span>Kirim Pesan</span>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Contact Information -->
                <div class="animate-fade-in-up space-y-8">

                    <!-- Contact Cards -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">

                        <!-- Email Card -->
                        <div class="contact-card bg-white p-8 rounded-3xl shadow-lg border border-gray-100 hover:shadow-2xl transition-all duration-500">
                            <div class="w-14 h-14 icon-gradient-1 rounded-2xl flex items-center justify-center mb-6">
                                <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                                </svg>
                            </div>
                            <h3 class="text-xl font-bold text-gray-900 mb-3">Email</h3>
                            <a href="mailto:<?= htmlspecialchars($contact_email) ?>"
                               class="theme-hover font-medium transition-colors text-lg"
                               style="color: <?= $primary_color ?>;">
                                <?= htmlspecialchars($contact_email) ?>
                            </a>
                        </div>

                        <!-- Phone Card -->
                        <div class="contact-card bg-white p-8 rounded-3xl shadow-lg border border-gray-100 hover:shadow-2xl transition-all duration-500">
                            <div class="w-14 h-14 icon-gradient-2 rounded-2xl flex items-center justify-center mb-6">
                                <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                                </svg>
                            </div>
                            <h3 class="text-xl font-bold text-gray-900 mb-3">Telepon</h3>
                            <a href="tel:<?= htmlspecialchars($contact_phone) ?>"
                               class="theme-hover font-medium transition-colors text-lg"
                               style="color: #10b981;">
                                <?= htmlspecialchars($contact_phone) ?>
                            </a>
                        </div>

                        <!-- Instagram Card -->
                        <div class="contact-card bg-white p-8 rounded-3xl shadow-lg border border-gray-100 hover:shadow-2xl transition-all duration-500">
                            <div class="w-14 h-14 icon-gradient-3 rounded-2xl flex items-center justify-center mb-6">
                                <svg class="w-7 h-7 text-white" fill="currentColor" viewBox="0 0 24 24">
                                    <path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zm0-2.163c-3.259 0-3.667.014-4.947.072-4.358.2-6.78 2.618-6.98 6.98-.059 1.281-.073 1.689-.073 4.948 0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98 1.281.058 1.689.072 4.948.072 3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98-1.281-.059-1.69-.073-4.949-.073zm0 5.838c-3.403 0-6.162 2.759-6.162 6.162s2.759 6.163 6.162 6.163 6.162-2.759 6.162-6.163c0-3.403-2.759-6.162-6.162-6.162zm0 10.162c-2.209 0-4-1.79-4-4 0-2.209 1.791-4 4-4s4 1.791 4 4c0 2.21-1.791 4-4 4zm6.406-11.845c-.796 0-1.441.645-1.441 1.44s.645 1.44 1.441 1.44c.795 0 1.439-.645 1.439-1.44s-.644-1.44-1.439-1.44z"/>
                                </svg>
                            </div>
                            <h3 class="text-xl font-bold text-gray-900 mb-3">Instagram</h3>
                            <a href="https://instagram.com/<?= htmlspecialchars(str_replace('@', '', $contact_instagram)) ?>"
                               target="_blank"
                               class="font-medium transition-colors text-lg" style="color: <?= $accent_color ?>;" onmouseover="this.style.color='<?= $primary_color ?>'" onmouseout="this.style.color='<?= $accent_color ?>'">
                                <?= htmlspecialchars($contact_instagram) ?>
                            </a>
                        </div>

                        <!-- Address Card -->
                        <div class="contact-card bg-white p-8 rounded-3xl shadow-lg border border-gray-100 hover:shadow-2xl transition-all duration-500">
                            <div class="w-14 h-14 rounded-2xl flex items-center justify-center mb-6" style="background: linear-gradient(135deg, <?= $warning_color ?> 0%, <?= $danger_color ?> 100%);">
                                <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                                </svg>
                            </div>
                            <h3 class="text-xl font-bold text-gray-900 mb-3">Alamat</h3>
                            <p class="text-gray-600 leading-relaxed">
                                <?= htmlspecialchars($contact_address) ?>
                            </p>
                        </div>

                    </div>

                    <!-- FAQ Section -->
                    <div class="bg-gradient-to-br from-slate-50 to-blue-50 p-8 rounded-3xl border border-gray-100">
                        <h3 class="text-2xl font-bold text-gray-900 mb-6 text-center">Pertanyaan Umum</h3>

                        <div class="space-y-6">
                            <div class="bg-white p-6 rounded-xl border border-gray-100">
                                <h4 class="text-lg font-semibold text-gray-900 mb-2">
                                    Bagaimana cara bergabung dengan Informatics A?
                                </h4>
                                <p class="text-gray-600 text-sm">
                                    Anda dapat mendaftar melalui tombol "Daftar Sekarang" di halaman utama atau menghubungi admin untuk informasi lebih lanjut.
                                </p>
                            </div>

                            <div class="bg-white p-6 rounded-xl border border-gray-100">
                                <h4 class="text-lg font-semibold text-gray-900 mb-2">
                                    Apakah platform ini gratis?
                                </h4>
                                <p class="text-gray-600 text-sm">
                                    Ya, <?= htmlspecialchars($site_name) ?> sepenuhnya gratis untuk digunakan oleh seluruh anggota kelas Informatics <?= htmlspecialchars($site_name) ?>.
                                </p>
                            </div>

                            <div class="bg-white p-6 rounded-xl border border-gray-100">
                                <h4 class="text-lg font-semibold text-gray-900 mb-2">
                                    Bagaimana cara mengunggah kegiatan?
                                </h4>
                                <p class="text-gray-600 text-sm">
                                    Setelah login, Anda dapat mengakses menu "Post Kegiatan" dan mengisi formulir dengan detail kegiatan serta foto dokumentasi.
                                </p>
                            </div>
                        </div>
                    </div>

                </div>

            </div>
        </div>
    </section>

    <!-- Map Section (Optional) -->
    <section class="py-16 bg-slate-50">
        <div class="max-w-7xl mx-auto px-6">
            <div class="text-center mb-12">
                <h2 class="text-3xl font-bold text-gray-900 mb-4">Lokasi Kami</h2>
                <p class="text-gray-600">Temukan kami di alamat berikut</p>
            </div>

            <div class="bg-white p-8 rounded-3xl shadow-lg border border-gray-100">
                <div class="aspect-video bg-gradient-to-br from-gray-100 to-gray-200 rounded-2xl flex items-center justify-center">
                    <div class="text-center">
                        <div class="w-16 h-16 rounded-full flex items-center justify-center mx-auto mb-4" style="background: linear-gradient(135deg, <?= $primary_color ?> 0%, <?= $secondary_color ?> 100%);">
                            <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                            </svg>
                        </div>
                        <p class="text-gray-600 font-medium">Peta lokasi akan ditampilkan di sini</p>
                        <p class="text-sm text-gray-500 mt-2">
                            <?= htmlspecialchars($contact_address) ?>
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <?php include __DIR__ . '/../includes/footer.php'; ?>

    <script>
        // Form submission handling
        document.querySelector('form').addEventListener('submit', function(e) {
            e.preventDefault();

            // Show loading state
            const submitBtn = this.querySelector('button[type="submit"]');
            const originalText = submitBtn.innerHTML;
            submitBtn.innerHTML = `
                <svg class="w-5 h-5 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                </svg>
                Mengirim...
            `;
            submitBtn.disabled = true;

            // Simulate form submission
            setTimeout(() => {
                // Reset button
                submitBtn.innerHTML = originalText;
                submitBtn.disabled = false;

                // Show success message (you can replace this with actual form submission)
                alert('Pesan berhasil dikirim! Kami akan segera merespons.');
                this.reset();
            }, 2000);
        });

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

        document.querySelectorAll('.contact-card').forEach(card => {
            observer.observe(card);
        });
    </script>
</body>
</html>
