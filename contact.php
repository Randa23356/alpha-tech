<?php
// contact.php - Halaman Kontak
session_start();
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
    $stmt = $pdo->query("SELECT setting_key, setting_value FROM site_settings WHERE setting_key IN ('primary_color', 'secondary_color', 'accent_color', 'success_color', 'warning_color', 'danger_color', 'site_name')");
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
    $site_name = $settings['site_name'] ?? 'Informatics A';
} catch (Exception $e) {
    // Use default colors if database fails
    $site_name = 'Informatics A';
}


$success = null;
$error = null;

// Load contact settings from database
try {
    $stmt = $pdo->query("SELECT setting_key, setting_value FROM site_settings WHERE setting_key IN ('contact_email', 'contact_instagram', 'contact_phone', 'contact_address', 'google_maps_embed')");
    $contact_settings = [];
    foreach ($stmt->fetchAll() as $row) {
        $contact_settings[$row['setting_key']] = $row['setting_value'];
    }
} catch (Exception $e) {
    $contact_settings = [];
}

// Default contact values
$contact_email = $contact_settings['contact_email'] ?? 'info@informaticsa.edu';
$contact_instagram = $contact_settings['contact_instagram'] ?? '@informaticsa';
$contact_phone = $contact_settings['contact_phone'] ?? '+62 812-3456-7890';
$contact_address = $contact_settings['contact_address'] ?? 'Jl. Pendidikan No. 123, Jakarta, Indonesia';
$google_maps_embed = $contact_settings['google_maps_embed'] ?? '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = htmlspecialchars(trim($_POST['name'] ?? ''));
    $email = htmlspecialchars(trim($_POST['email'] ?? ''));
    $subject = htmlspecialchars(trim($_POST['subject'] ?? ''));
    $message = htmlspecialchars(trim($_POST['message'] ?? ''));
    
    if (!$name || !$email || !$subject || !$message) {
        $_SESSION['error'] = "Semua field wajib diisi.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $_SESSION['error'] = "Email tidak valid.";
    } else {
        try {
            // Use contact_messages table
            $stmt = $pdo->prepare("INSERT INTO contact_messages (name, email, subject, message) VALUES (?, ?, ?, ?)");
            if ($stmt->execute([$name, $email, $subject, $message])) {
                $_SESSION['success'] = "Pesan Anda berhasil dikirim! Kami akan segera menghubungi Anda.";
                $_POST = []; // Clear form
            } else {
                $_SESSION['error'] = "Gagal mengirim pesan. Silakan coba lagi.";
            }
        } catch (Exception $e) {
            $_SESSION['error'] = "Terjadi kesalahan: " . $e->getMessage();
        }
    }
    header('Location: ' . url('contact'));
    exit;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kontak Kami - Informatics <?= htmlspecialchars($site_name) ?></title>
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
    <link href=" <?= asset('tailwind.css') ?>" rel="stylesheet">
    <?php require_once __DIR__ . '/includes/favicon.php'; ?>
</head>
<body class="bg-gradient-to-br from-gray-50 to-blue-50 min-h-screen">
    
    <?php include __DIR__ . '/includes/navbar.php'; ?>

    <!-- Header -->
    <header class="header-gradient text-white py-16 px-6">
        <div class="max-w-6xl mx-auto text-center">
            <div class="flex justify-center mb-6">
                <div class="w-20 h-20 bg-white/20 backdrop-blur-sm rounded-2xl flex items-center justify-center">
                    <svg class="w-12 h-12" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                    </svg>
                </div>
            </div>
            <h1 class="text-5xl font-bold mb-4">Hubungi Kami</h1>
            <p class="text-xl text-blue-100 max-w-2xl mx-auto">Punya pertanyaan atau saran? Kami siap membantu Anda!</p>
        </div>
    </header>

    <!-- Main Content -->
    <main class="max-w-7xl mx-auto px-6 py-16">
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8 mb-12">
            <!-- Contact Info Cards -->
<div class="bg-white rounded-2xl shadow-lg p-8 hover:shadow-xl transition">
    <div class="w-16 h-16 rounded-xl flex items-center justify-center mb-4" style="background: linear-gradient(135deg, <?= $primary_color ?>20 0%, <?= $secondary_color ?>30 100%);">
        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="color: <?= $primary_color ?>;">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
        </svg>
    </div>
    <h3 class="text-xl font-bold text-gray-900 mb-2">Email</h3>
    <p class="text-gray-600 mb-3">Kirim email kepada kami</p>
    <a href="mailto:<?= htmlspecialchars($contact_email) ?>" class="theme-hover font-semibold transition" style="color: <?= $primary_color ?>;">
        <?= htmlspecialchars($contact_email) ?>
    </a>
</div>


<!-- GANTI bagian Instagram Card -->
<div class="bg-white rounded-2xl shadow-lg p-8 hover:shadow-xl transition">
    <div class="w-16 h-16 rounded-xl flex items-center justify-center mb-4" style="background: linear-gradient(135deg, <?= $accent_color ?>20 0%, <?= $primary_color ?>30 100%);">
        <svg class="w-8 h-8" fill="currentColor" viewBox="0 0 24 24" style="color: <?= $accent_color ?>;">
            <path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zm0-2.163c-3.259 0-3.667.014-4.947.072-4.358.2-6.78 2.618-6.98 6.98-.059 1.281-.073 1.689-.073 4.948 0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98 1.281.058 1.689.072 4.948.072 3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98-1.281-.059-1.69-.073-4.949-.073zm0 5.838c-3.403 0-6.162 2.759-6.162 6.162s2.759 6.163 6.162 6.163 6.162-2.759 6.162-6.163c0-3.403-2.759-6.162-6.162-6.162zm0 10.162c-2.209 0-4-1.79-4-4 0-2.209 1.791-4 4-4s4 1.791 4 4c0 2.21-1.791 4-4 4zm6.406-11.845c-.796 0-1.441.645-1.441 1.44s.645 1.44 1.441 1.44c.795 0 1.439-.645 1.439-1.44s-.644-1.44-1.439-1.44z"/>
        </svg>
    </div>
    <h3 class="text-xl font-bold text-gray-900 mb-2">Instagram</h3>
    <p class="text-gray-600 mb-3">Follow kami di Instagram</p>
    <a href="https://instagram.com/<?= htmlspecialchars(str_replace('@', '', $contact_instagram)) ?>" target="_blank" class="theme-hover font-semibold transition" style="color: <?= $accent_color ?>;">
        <?= htmlspecialchars($contact_instagram) ?>
    </a>
</div>


<!-- GANTI bagian Telepon Card -->
<div class="bg-white rounded-2xl shadow-lg p-8 hover:shadow-xl transition">
    <div class="w-16 h-16 rounded-xl flex items-center justify-center mb-4" style="background: linear-gradient(135deg, <?= $success_color ?>20 0%, <?= $primary_color ?>30 100%);">
        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="color: <?= $success_color ?>;">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
        </svg>
    </div>
    <h3 class="text-xl font-bold text-gray-900 mb-2">Telepon/WhatsApp</h3>
    <p class="text-gray-600 mb-3">Hubungi kami via telepon</p>
    <a href="tel:<?= htmlspecialchars(str_replace([' ', '-'], '', $contact_phone)) ?>" class="theme-hover font-semibold transition" style="color: <?= $success_color ?>;">
        <?= htmlspecialchars($contact_phone) ?>
    </a>
</div>
        </div>

        <!-- Contact Form -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
            <!-- Form -->
            <div class="bg-white rounded-2xl shadow-lg p-8">
                <h2 class="text-3xl font-bold text-gray-900 mb-6">Kirim Pesan</h2>
                
                <?php if (isset($_SESSION['success'])): ?>
                    <div class="mb-6 p-4 bg-green-50 border-l-4 border-green-500 rounded-lg">
                        <div class="flex items-center gap-2">
                            <svg class="w-5 h-5 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            <span class="text-green-700 font-medium"><?= htmlspecialchars($_SESSION['success']) ?></span>
                        </div>
                    </div>
                    <?php unset($_SESSION['success']); ?>
                <?php endif; ?>

                <?php if (isset($_SESSION['error'])): ?>
                    <div class="mb-6 p-4 bg-red-50 border-l-4 border-red-500 rounded-lg">
                        <div class="flex items-center gap-2">
                            <svg class="w-5 h-5 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            <span class="text-red-700 font-medium"><?= htmlspecialchars($_SESSION['error']) ?></span>
                        </div>
                    </div>
                    <?php unset($_SESSION['error']); ?>
                <?php endif; ?>

                <form action=" <?= url('contact') ?>" method="POST" class="space-y-5">
                    <div>
                        <label for="name" class="block text-gray-700 font-semibold mb-2">Nama Lengkap</label>
                        <input type="text" id="name" name="name" required autocomplete="name"
                            class="w-full px-4 py-3 border-2 border-gray-300 rounded-xl focus:outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-200 transition"
                            placeholder="Masukkan nama lengkap Anda"
                            value="<?= htmlspecialchars($_POST['name'] ?? '') ?>">
                    </div>

                    <div>
                        <label for="email" class="block text-gray-700 font-semibold mb-2">Email</label>
                        <input type="email" id="email" name="email" required autocomplete="email"
                            class="w-full px-4 py-3 border-2 border-gray-300 rounded-xl focus:outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-200 transition"
                            placeholder="email@example.com"
                            value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
                    </div>

                    <div>
                        <label for="subject" class="block text-gray-700 font-semibold mb-2">Subjek</label>
                        <input type="text" id="subject" name="subject" required autocomplete="off"
                            class="w-full px-4 py-3 border-2 border-gray-300 rounded-xl focus:outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-200 transition"
                            placeholder="Subjek pesan Anda"
                            value="<?= htmlspecialchars($_POST['subject'] ?? '') ?>">
                    </div>

                    <div>
                        <label for="message" class="block text-gray-700 font-semibold mb-2">Pesan</label>
                        <textarea id="message" name="message" required rows="5" autocomplete="off"
                            class="w-full px-4 py-3 border-2 border-gray-300 rounded-xl focus:outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-200 transition resize-none"
                            placeholder="Tulis pesan Anda di sini..."><?= htmlspecialchars($_POST['message'] ?? '') ?></textarea>
                    </div>

                    <button type="submit"
                        class="w-full py-3 px-4 btn-gradient text-white font-bold rounded-xl hover:btn-gradient transition shadow-lg hover:shadow-xl transform hover:scale-105 flex items-center justify-center gap-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/>
                        </svg>
                        Kirim Pesan
                    </button>
                </form>
            </div>

                        <!-- Map & Info -->
            <div class="space-y-6">
                <div class="bg-white rounded-2xl shadow-lg p-2 overflow-hidden">
                    <?php if (!empty($google_maps_embed)): ?>
                        <!-- Tampilkan Google Maps jika ada embed code -->
                        <div class="w-full h-64 rounded-xl overflow-hidden">
                            <?= htmlspecialchars_decode($google_maps_embed) ?>
                        </div>
                        <div class="p-4 text-center">
                            <p class="text-gray-600 font-medium">Lokasi Kampus</p>
                            <p class="text-sm text-gray-500 mt-1"><?= nl2br(htmlspecialchars($contact_address)) ?></p>
                        </div>
                    <?php else: ?>
                        <!-- Fallback jika tidak ada maps -->
                        <div class="w-full h-64 rounded-xl flex items-center justify-center" style="background: linear-gradient(135deg, <?= $primary_color ?>15 0%, <?= $secondary_color ?>15 100%);">
                            <div class="text-center">
                                <svg class="w-16 h-16 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="color: <?= $primary_color ?>;">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                                </svg>
                                <p class="text-gray-600 font-medium">Lokasi Kampus</p>
                                <p class="text-sm text-gray-500 mt-2"><?= nl2br(htmlspecialchars($contact_address)) ?></p>
                                <p class="text-xs mt-2" style="color: <?= $accent_color ?>;">Admin dapat menambahkan Google Maps di pengaturan</p>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- FAQ Section -->
                <div class="bg-white rounded-2xl shadow-lg p-6 md:p-8 mt-8"> <!-- Tambahkan mt-8 di sini -->
                    <h3 class="text-xl md:text-2xl font-bold text-gray-900 mb-4">FAQ</h3>
                    <div class="space-y-4">
                        <div class="pb-4 border-b border-gray-100">
                            <h4 class="font-bold text-gray-900 mb-2">Bagaimana cara bergabung?</h4>
                            <p class="text-gray-600 text-sm">Anda bisa mendaftar melalui halaman register dan mengisi data diri Anda.</p>
                        </div>
                        <div class="pb-4 border-b border-gray-100">
                            <h4 class="font-bold text-gray-900 mb-2">Siapa yang bisa posting kegiatan?</h4>
                            <p class="text-gray-600 text-sm">Semua anggota yang sudah terdaftar bisa posting kegiatan. Postingan akan direview oleh admin.</p>
                        </div>
                        <div>
                            <h4 class="font-bold text-gray-900 mb-2">Berapa lama respon pesan?</h4>
                            <p class="text-gray-600 text-sm">Kami akan merespon pesan Anda dalam 1x24 jam.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div> <!-- Tutup div grid -->
    </main>
    
    <?php include __DIR__ . '/includes/footer.php'; ?>

</body>
</html>
