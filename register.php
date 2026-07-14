<?php
// register.php
session_start();
require_once __DIR__ . "/src/config/db.php";
require_once __DIR__ . "/src/helpers/session.php";
require_once __DIR__ . "/src/config/urls.php";
require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/src/config/config.php';

// Inisialisasi Google Client
$googleLoginUrl = '#';
try {
    $google = new Google_Client();
    $google->setClientId(GOOGLE_CLIENT_ID);
    $google->setClientSecret(GOOGLE_CLIENT_SECRET);
    $google->setRedirectUri(GOOGLE_REDIRECT_URI);
    $google->addScope('email');
    $google->addScope('profile');
    $googleLoginUrl = $google->createAuthUrl();
} catch (Exception $e) {
    error_log("Google Client Error: " . $e->getMessage());
    $error = "Fitur pendaftaran dengan Google sedang tidak tersedia.";
}

// Load theme colors from database
$primary_color = "#1e3a8a"; // default
$secondary_color = "#1e40af"; // default
$accent_color = "#ec4899"; // default
$site_name = "Informatics A";

try {
    $stmt = $pdo->query(
        "SELECT setting_key, setting_value FROM site_settings WHERE setting_key IN ('primary_color', 'secondary_color', 'accent_color', 'site_name')"
    );
    $settings = [];
    foreach ($stmt->fetchAll() as $row) {
        $settings[$row["setting_key"]] = $row["setting_value"];
    }
    $primary_color = $settings["primary_color"] ?? $primary_color;
    $secondary_color = $settings["secondary_color"] ?? $secondary_color;
    $accent_color = $settings["accent_color"] ?? $accent_color;
    $site_name = $settings["site_name"] ?? $site_name;
} catch (Exception $e) {
    // Use default values if database fails
    error_log("Database error: " . $e->getMessage());
}

// Redirect jika sudah login
if (isLoggedIn()) {
    header("Location: " . url('dashboard'));
    exit();
}

$error = null;

// Proses form register
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = htmlspecialchars(trim($_POST['username'] ?? ''));
    $email = htmlspecialchars(trim($_POST['email'] ?? ''));
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    if (!$username || !$email || !$password || !$confirm_password) {
        $error = "Semua field wajib diisi.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Email tidak valid.";
    } elseif ($password !== $confirm_password) {
        $error = "Konfirmasi password tidak cocok.";
    } elseif (strlen($password) < 6) {
        $error = "Password minimal 6 karakter.";
    } else {
        $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
        $stmt->execute([$username, $email]);
        if ($stmt->fetch()) {
            $error = "Username atau email sudah terdaftar.";
        } else {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO users (username, email, password, role) VALUES (?, ?, ?, 'user')");
            if ($stmt->execute([$username, $email, $hash])) {
                $userId = $pdo->lastInsertId();
                
                // Send registration notification  
                require_once __DIR__ . '/src/helpers/fcm_helper.php';
                notifyRegistration($userId, $username);
                
                loginUser(['id' => $userId, 'username' => $username, 'email' => $email, 'role' => 'user']);
                header("Location: " . url('dashboard'));
                exit();
            } else {
                $error = "Gagal mendaftar, coba lagi.";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - <?= htmlspecialchars($site_name) ?></title>
    <link href="<?= asset('tailwind.css') ?>" rel="stylesheet">
    <?php require_once __DIR__ . '/includes/favicon.php'; ?>
</head>
<body class="min-h-screen flex items-center justify-center p-4" style="background: linear-gradient(135deg, <?= $primary_color ?> 0%, <?= $secondary_color ?> 100%);">
    <div class="w-full max-w-md">
        <!-- Logo/Header -->
        <div class="text-center mb-8">
            <div class="inline-flex items-center justify-center w-20 h-20 bg-white rounded-full shadow-lg mb-4">
                <svg class="w-12 h-12" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="color: <?= $primary_color ?>;">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/>
                </svg>
            </div>
            <h1 class="text-3xl font-bold text-white mb-2"><?= htmlspecialchars($site_name) ?></h1>
            <p class="text-blue-200">Daftar untuk bergabung dengan kelas</p>
        </div>

        <!-- Register Card -->
        <div class="bg-white rounded-2xl shadow-2xl p-8">
            <h2 class="text-2xl font-bold mb-6 text-center" style="color: <?= $primary_color ?>;">Daftar Akun</h2>
            
            <?php if (isset($error)): ?>
                <div class="mb-4 p-4 bg-red-50 border-l-4 border-red-500 rounded">
                    <div class="flex items-center gap-2">
                        <svg class="w-5 h-5 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        <span class="text-red-700 text-sm"><?= htmlspecialchars($error) ?></span>
                    </div>
                </div>
            <?php endif; ?>

            <form action="<?= url('register') ?>" method="POST" class="space-y-4">
                <div>
                    <label for="username" class="block text-gray-700 font-semibold mb-2 flex items-center gap-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="color: <?= $primary_color ?>;">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                        </svg>
                        Username
                    </label>
                    <input type="text" id="username" name="username" required autocomplete="username" 
                        class="w-full px-4 py-3 border-2 border-gray-300 rounded-lg focus:outline-none transition" style="focus:border-color: <?= $primary_color ?>;"
                        placeholder="Pilih username">
                </div>
                
                <div>
                    <label for="email" class="block text-gray-700 font-semibold mb-2 flex items-center gap-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="color: <?= $primary_color ?>;">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                        </svg>
                        Email
                    </label>
                    <input type="email" id="email" name="email" required autocomplete="email" 
                        class="w-full px-4 py-3 border-2 border-gray-300 rounded-lg focus:outline-none transition" style="focus:border-color: <?= $primary_color ?>;"
                        placeholder="email@example.com">
                </div>
                
                <div>
                    <label for="password" class="block text-gray-700 font-semibold mb-2 flex items-center gap-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="color: <?= $primary_color ?>;">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                        </svg>
                        Password
                    </label>
                    <input type="password" id="password" name="password" required autocomplete="new-password" 
                        class="w-full px-4 py-3 border-2 border-gray-300 rounded-lg focus:outline-none transition" style="focus:border-color: <?= $primary_color ?>;"
                        placeholder="Minimal 6 karakter">
                </div>
                
                <div>
                    <label for="confirm_password" class="block text-gray-700 font-semibold mb-2 flex items-center gap-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="color: <?= $primary_color ?>;">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        Konfirmasi Password
                    </label>
                    <input type="password" id="confirm_password" name="confirm_password" required autocomplete="new-password" 
                        class="w-full px-4 py-3 border-2 border-gray-300 rounded-lg focus:outline-none transition" style="focus:border-color: <?= $primary_color ?>;"
                        placeholder="Ulangi password">
                </div>

                <button type="submit" 
                    class="w-full py-3 px-4 text-white font-bold rounded-lg transition shadow-lg flex items-center justify-center gap-2" style="background: linear-gradient(135deg, <?= $primary_color ?> 0%, <?= $secondary_color ?> 100%);" onmouseover="this.style.background='linear-gradient(135deg, <?= $secondary_color ?> 0%, <?= $primary_color ?> 100%)'" onmouseout="this.style.background='linear-gradient(135deg, <?= $primary_color ?> 0%, <?= $secondary_color ?> 100%)'">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/>
                    </svg>
                    Daftar Sekarang
                </button>
            </form>

            <div class="mt-6 text-center">
                <p class="text-gray-600 mb-2">Sudah punya akun?</p>
                <a href="<?= url('login') ?>" class="font-bold hover:underline transition flex items-center justify-center gap-2" style="color: <?= $primary_color ?>;" onmouseover="this.style.color='<?= $secondary_color ?>'" onmouseout="this.style.color='<?= $primary_color ?>'">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"/>
                    </svg>
                    Login di sini
                </a>
            </div>
        </div>

        <div class="flex items-center my-6">
                <div class="flex-1 border-t border-gray-300"></div>
                <span class="px-3 text-gray-500 text-sm">atau</span>
                <div class="flex-1 border-t border-gray-300"></div>
            </div>
            
        <!-- Google Login Button -->
        <a href="<?= htmlspecialchars($googleLoginUrl) ?>" 
               class="w-full flex items-center justify-center gap-3 py-3 px-4 bg-white border-2 border-gray-300 rounded-lg hover:bg-gray-50 transition shadow-sm mb-6">
                <svg class="w-5 h-5" viewBox="0 0 24 24">
                    <path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/>
                    <path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/>
                    <path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z"/>
                    <path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"/>
                </svg>
                <span class="text-gray-700 font-medium">Sign Up dengan Google</span>
            </a>

        <!-- Back to Home -->
        <div class="mt-6 text-center">
            <a href=" <?= url('') ?>" class="text-white hover:text-blue-200 transition flex items-center justify-center gap-2">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                </svg>
                Kembali ke Beranda
            </a>
        </div>
    </div>
</body>
</html>
