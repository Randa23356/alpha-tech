<?php
// views/user/change_password.php
session_start();
require_once __DIR__ . "/../../src/helpers/session.php";
require_once __DIR__ . "/../../src/config/db.php";
require_once __DIR__ . "/../../src/config/urls.php";

// Proteksi: hanya user login yang bisa akses
if (!isLoggedIn()) {
    header("Location: " . url('login'));
    exit();
}

// Load theme colors from database
$primary_color = "#1e3a8a"; // default
$secondary_color = "#1e40af"; // default
$accent_color = "#ec4899"; // default

try {
    $stmt = $pdo->query(
        "SELECT setting_key, setting_value FROM site_settings WHERE setting_key IN ('primary_color', 'secondary_color', 'accent_color')",
    );
    $settings = [];
    foreach ($stmt->fetchAll() as $row) {
        $settings[$row["setting_key"]] = $row["setting_value"];
    }
    $primary_color = $settings["primary_color"] ?? $primary_color;
    $secondary_color = $settings["secondary_color"] ?? $secondary_color;
    $accent_color = $settings["accent_color"] ?? $accent_color;
} catch (Exception $e) {
    // Use default colors if database fails
}

// Proses ubah password
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $current_password = $_POST["current_password"] ?? "";
    $new_password = $_POST["new_password"] ?? "";
    $confirm_password = $_POST["confirm_password"] ?? "";

    // Validasi
    if (!$current_password || !$new_password || !$confirm_password) {
        $error = "Semua field wajib diisi.";
    } elseif (strlen($new_password) < 6) {
        $error = "Password baru minimal 6 karakter.";
    } elseif ($new_password !== $confirm_password) {
        $error = "Konfirmasi password tidak cocok.";
    } else {
        // Cek password lama
        $user = getCurrentUser();
        $stmt = $pdo->prepare("SELECT password FROM users WHERE id = ?");
        $stmt->execute([$user["id"]]);
        $userData = $stmt->fetch();
        
        if (!password_verify($current_password, $userData["password"])) {
            $error = "Password lama tidak sesuai.";
        } else {
            // Update password
            $hashed = password_hash($new_password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
            $result = $stmt->execute([$hashed, $user["id"]]);
            
            if ($result) {
                $success = "Password berhasil diubah!";
            } else {
                $error = "Gagal mengubah password.";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Ubah Password - Informatics A</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href=" <?= asset('tailwind.css') ?>" rel="stylesheet">
    <?php require_once __DIR__ . '/../../includes/favicon.php'; ?>
</head>
<body class="min-h-screen" style="background: linear-gradient(135deg, <?= $primary_color ?>10 0%, <?= $secondary_color ?>10 100%);">
    
    <?php include __DIR__ . '/../../includes/navbar.php'; ?>
    
    <main class="max-w-xl mx-auto px-6 py-10">
        <div class="bg-white rounded-2xl shadow-lg p-8">
            <div class="flex items-center gap-3 mb-6">
                <div class="p-3 rounded-xl" style="background: linear-gradient(135deg, <?= $primary_color ?> 0%, <?= $secondary_color ?> 100%);">
                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                    </svg>
                </div>
                <div>
                    <h1 class="text-2xl font-bold" style="color: <?= $primary_color ?>;">Ubah Password</h1>
                    <p class="text-gray-600 text-sm">Perbarui password akun Anda</p>
                </div>
            </div>

            <?php if ($error): ?>
                <div class="mb-6 p-4 bg-red-50 border-l-4 border-red-500 rounded-lg">
                    <div class="flex items-center gap-2">
                        <svg class="w-5 h-5 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        <span class="text-red-700 font-medium"><?= htmlspecialchars($error) ?></span>
                    </div>
                </div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="mb-6 p-4 bg-green-50 border-l-4 border-green-500 rounded-lg">
                    <div class="flex items-center gap-2">
                        <svg class="w-5 h-5 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        <span class="text-green-700 font-medium"><?= htmlspecialchars($success) ?></span>
                    </div>
                </div>
            <?php endif; ?>

            <form action="" method="POST" class="space-y-5">
                <!-- Password Lama -->
                <div>
                    <label for="current_password" class="block text-gray-700 font-semibold mb-2">
                        Password Lama <span class="text-red-500">*</span>
                    </label>
                    <input 
                        type="password" 
                        id="current_password" 
                        name="current_password" 
                        required 
                        class="w-full px-4 py-3 border-2 border-gray-300 rounded-xl focus:outline-none transition" style="focus:border-color: <?= $primary_color ?>;" 
                        placeholder="Masukkan password lama">
                </div>

                <!-- Password Baru -->
                <div>
                    <label for="new_password" class="block text-gray-700 font-semibold mb-2">
                        Password Baru <span class="text-red-500">*</span>
                    </label>
                    <input 
                        type="password" 
                        id="new_password" 
                        name="new_password" 
                        required 
                        class="w-full px-4 py-3 border-2 border-gray-300 rounded-xl focus:outline-none transition" style="focus:border-color: <?= $primary_color ?>;" 
                        placeholder="Masukkan password baru">
                    <p class="text-xs text-gray-500 mt-1">Minimal 6 karakter</p>
                </div>

                <!-- Konfirmasi Password -->
                <div>
                    <label for="confirm_password" class="block text-gray-700 font-semibold mb-2">
                        Konfirmasi Password Baru <span class="text-red-500">*</span>
                    </label>
                    <input 
                        type="password" 
                        id="confirm_password" 
                        name="confirm_password" 
                        required 
                        class="w-full px-4 py-3 border-2 border-gray-300 rounded-xl focus:outline-none transition" style="focus:border-color: <?= $primary_color ?>;" 
                        placeholder="Ulangi password baru">
                </div>

                <button 
                    type="submit" 
                    class="w-full text-white font-bold py-3 rounded-xl transition shadow-lg" style="background: linear-gradient(135deg, <?= $primary_color ?> 0%, <?= $secondary_color ?> 100%);" onmouseover="this.style.background='linear-gradient(135deg, <?= $secondary_color ?> 0%, <?= $primary_color ?> 100%)'" onmouseout="this.style.background='linear-gradient(135deg, <?= $primary_color ?> 0%, <?= $secondary_color ?> 100%)'">
                    Ubah Password
                </button>
            </form>

            <div class="mt-6 flex gap-4 justify-center">
                <a href=" <?= url('profile') ?>" class="inline-flex items-center gap-2 font-semibold transition" style="color: <?= $primary_color ?>;" onmouseover="this.style.color='<?= $secondary_color ?>'" onmouseout="this.style.color='<?= $primary_color ?>'">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                    Kembali ke Profil
                </a>
            </div>
        </div>
    </main>

    <?php include __DIR__ . '/../../includes/footer.php'; ?>

</body>
</html>
