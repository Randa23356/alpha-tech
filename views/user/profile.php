<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
// views/user/profile.php
session_start();
require_once __DIR__ . "/../../src/helpers/session.php";
require_once __DIR__ . "/../../src/config/db.php";
require_once __DIR__ . "/../../src/config/urls.php";

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

// Proteksi: hanya user login yang bisa akses
if (!isLoggedIn()) {
    header("Location: " . url('login'));
    exit();
}

$user = getCurrentUser();
$error = null;
$success = null;

// Ambil data profil user dari database
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user["id"]]);
$profil = $stmt->fetch();

// Proses update profil
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $username = htmlspecialchars(trim($_POST["username"] ?? ""));
    $full_name = htmlspecialchars(trim($_POST["full_name"] ?? ""));
    $email = htmlspecialchars(trim($_POST["email"] ?? ""));
    $bio = trim($_POST["bio"] ?? "");
    $contact = trim($_POST["contact"] ?? "");
    $image_path = $profil["profile_pic"] ?? null;

    // Handle upload foto profil (opsional)
    if (
        isset($_FILES["profile_pic"]) &&
        $_FILES["profile_pic"]["error"] === UPLOAD_ERR_OK
    ) {
        $img_name = $_FILES["profile_pic"]["name"];
        $img_tmp = $_FILES["profile_pic"]["tmp_name"];
        $img_ext = strtolower(pathinfo($img_name, PATHINFO_EXTENSION));
        $allowed = ["jpg", "jpeg", "png", "gif"];
        if (!in_array($img_ext, $allowed)) {
            $error = "Format foto harus jpg, jpeg, png, atau gif.";
        } else {
            $new_name = uniqid("profil_", true) . "." . $img_ext;
            $dest = "../../public/uploads/" . $new_name;
            // Pastikan folder uploads ada
            if (!is_dir("../../public/uploads")) {
                mkdir("../../public/uploads", 0777, true);
            }
            if (move_uploaded_file($img_tmp, $dest)) {
                // Hapus foto lama jika ada
                if (
                    !empty($profil["profile_pic"]) &&
                    file_exists("../../public/uploads/" . basename($profil["profile_pic"]))
                ) {
                    unlink("../../public/uploads/" . basename($profil["profile_pic"]));
                }
                $image_path = $new_name;
            } else {
                $error = "Gagal upload foto profil.";
            }
        }
    }

    // Validasi
    if (!$error) {
        if (!$username) {
            $error = "Username wajib diisi.";
        } elseif (strlen($username) < 3) {
            $error = "Username minimal 3 karakter.";
        } elseif (!preg_match("/^[a-zA-Z0-9_]+$/", $username)) {
            $error = "Username hanya boleh berisi huruf, angka, dan underscore.";
        } elseif (!$full_name) {
            $error = "Nama lengkap wajib diisi.";
        } elseif (!$email || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = "Email tidak valid.";
        } elseif (strlen($bio) > 255) {
            $error = "Bio maksimal 255 karakter.";
        } elseif (strlen($contact) > 100) {
            $error = "Kontak maksimal 100 karakter.";
        } else {
            // Cek username sudah dipakai user lain
            $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ? AND id != ?");
            $stmt->execute([$username, $user["id"]]);
            if ($stmt->fetch()) {
                $error = "Username sudah digunakan user lain.";
            } else {
                // Cek email sudah dipakai user lain
                $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
                $stmt->execute([$email, $user["id"]]);
                if ($stmt->fetch()) {
                    $error = "Email sudah digunakan user lain.";
                } else {
                    // Update ke database
                    $stmt = $pdo->prepare("UPDATE users SET username = ?, full_name = ?, email = ?, bio = ?, contact = ?, profile_pic = ? WHERE id = ?");
                    $result = $stmt->execute([$username, $full_name, $email, $bio, $contact, $image_path, $user["id"]]);

                    if ($result) {
                        $success = "Profil berhasil diperbarui!";
                        // Refresh data profil
                        $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
                        $stmt->execute([$user["id"]]);
                        $profil = $stmt->fetch();
                        // Update session
                        $_SESSION['user']['username'] = $username;
                        $_SESSION['user']['email'] = $email;
                    } else {
                        $error = "Gagal memperbarui profil.";
                    }
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Edit Profil - Informatics A</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="<?= asset('tailwind.css') ?>" rel="stylesheet">
</head>
<body class="min-h-screen" style="background: linear-gradient(135deg, <?= $primary_color ?>10 0%, <?= $secondary_color ?>10 100%);">
    
    <?php include __DIR__ . '/../../includes/navbar.php'; ?>
    
    <main class="max-w-2xl mx-auto px-6 py-10">
        <div class="bg-white rounded-2xl shadow-lg p-8">
        <h1 class="text-3xl font-bold mb-2" style="background: linear-gradient(135deg, <?= $primary_color ?> 0%, <?= $secondary_color ?> 100%); -webkit-background-clip: text; background-clip: text; color: transparent;">Edit Profil</h1>
        <p class="mb-6 text-gray-600">Perbarui informasi profil kamu di sini.</p>
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
        <form action="" method="POST" enctype="multipart/form-data" class="space-y-5">
            <!-- Foto Profil -->
            <div class="flex flex-col items-center mb-6">
                <?php if (!empty($profil["profile_pic"])): ?>
                    <?php if (strpos($profil["profile_pic"], 'http') === 0): ?>
                        <img src="<?= htmlspecialchars($profil["profile_pic"]) ?>" alt="Foto Profil" class="h-32 w-32 object-cover rounded-full mb-4 border-4 shadow-lg" style="border-color: <?= $primary_color ?>;">
                    <?php else: ?>
                        <img src="<?= asset('uploads/' . htmlspecialchars($profil["profile_pic"])) ?>" alt="Foto Profil" class="h-32 w-32 object-cover rounded-full mb-4 border-4 shadow-lg" style="border-color: <?= $primary_color ?>;">
                    <?php endif; ?>
                <?php else: ?>
                    <img src="<?= url('public/default-avatar.php?initial=' . urlencode(substr($user["username"], 0, 1)) . '&color=' . urlencode($primary_color)) ?>" alt="Default Avatar" class="h-32 w-32 object-cover rounded-full mb-4 border-4 shadow-lg" style="border-color: <?= $primary_color ?>;">
                <?php endif; ?>
                <label for="profile_pic" class="block text-gray-700 font-semibold mb-2">Foto Profil (opsional)</label>
                <input type="file" id="profile_pic" name="profile_pic" accept="image/*" class="w-full px-4 py-3 border-2 border-gray-300 rounded-xl focus:outline-none transition" style="focus:border-color: <?= $primary_color ?>; focus:ring-color: <?= $primary_color ?>20;">
            </div>

            <!-- Username -->
            <div>
                <label for="username" class="block text-gray-700 font-semibold mb-2">Username</label>
                <input type="text" id="username" name="username" required
                       class="w-full px-4 py-3 border-2 border-gray-300 rounded-xl focus:outline-none transition"
                       style="focus:border-color: <?= $primary_color ?>; focus:ring-color: <?= $primary_color ?>20;"
                       value="<?= htmlspecialchars($profil["username"]) ?>"
                       placeholder="Masukkan username">
                <p class="text-xs text-gray-500 mt-1">Username minimal 3 karakter, hanya huruf, angka, dan underscore</p>
            </div>

            <!-- Nama Lengkap -->
            <div>
                <label for="full_name" class="block text-gray-700 font-semibold mb-2">Nama Lengkap</label>
                <input type="text" id="full_name" name="full_name" required class="w-full px-4 py-3 border-2 border-gray-300 rounded-xl focus:outline-none transition" style="focus:border-color: <?= $primary_color ?>; focus:ring-color: <?= $primary_color ?>20;" value="<?= htmlspecialchars($profil["full_name"] ?? "") ?>" placeholder="Masukkan nama lengkap">
            </div>

            <!-- Email -->
            <div>
                <label for="email" class="block text-gray-700 font-semibold mb-2">Email</label>
                <input type="email" id="email" name="email" required class="w-full px-4 py-3 border-2 border-gray-300 rounded-xl focus:outline-none transition" style="focus:border-color: <?= $primary_color ?>; focus:ring-color: <?= $primary_color ?>20;" value="<?= htmlspecialchars($profil["email"] ?? "") ?>" placeholder="email@example.com">
            </div>

            <!-- Bio -->
            <div>
                <label for="bio" class="block text-gray-700 font-semibold mb-2">Bio</label>
                <textarea id="bio" name="bio" maxlength="255" rows="3" class="w-full px-4 py-3 border-2 border-gray-300 rounded-xl focus:outline-none transition resize-none" style="focus:border-color: <?= $primary_color ?>; focus:ring-color: <?= $primary_color ?>20;" placeholder="Ceritakan tentang diri Anda..."><?= htmlspecialchars($profil["bio"] ?? "") ?></textarea>
                <p class="text-xs text-gray-500 mt-1">Bio maksimal 255 karakter</p>
            </div>

            <!-- Kontak -->
            <div>
                <label for="contact" class="block text-gray-700 font-semibold mb-2">Kontak (WA, IG, dll)</label>
                <input type="text" id="contact" name="contact" maxlength="100" class="w-full px-4 py-3 border-2 border-gray-300 rounded-xl focus:outline-none transition" style="focus:border-color: <?= $primary_color ?>; focus:ring-color: <?= $primary_color ?>20;" value="<?= htmlspecialchars($profil["contact"] ?? "") ?>" placeholder="08123456789 atau @instagram">
                <p class="text-xs text-gray-500 mt-1">Kontak maksimal 100 karakter</p>
            </div>
            <button type="submit" class="w-full text-white font-bold py-3 rounded-xl transition shadow-lg" style="background: linear-gradient(135deg, <?= $primary_color ?> 0%, <?= $secondary_color ?> 100%);" onmouseover="this.style.background='linear-gradient(135deg, <?= $secondary_color ?> 0%, <?= $primary_color ?> 100%)'" onmouseout="this.style.background='linear-gradient(135deg, <?= $primary_color ?> 0%, <?= $secondary_color ?> 100%)'">
                Simpan Perubahan
            </button>
        </form>

        <!-- Link ke Ubah Password -->
        <div class="mt-6 p-4 rounded-xl" style="background-color: <?= $primary_color ?>20; border-color: <?= $primary_color ?>;">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="color: <?= $primary_color ?>;">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                    </svg>
                    <div>
                        <p class="font-semibold" style="color: <?= $primary_color ?>;">Keamanan Akun</p>
                        <p class="text-sm text-gray-600">Ubah password untuk keamanan akun Anda</p>
                    </div>
                </div>
                <a href=" <?= url('change-password') ?>" class="text-white px-4 py-2 rounded-lg font-semibold transition" style="background: linear-gradient(135deg, <?= $primary_color ?> 0%, <?= $secondary_color ?> 100%);" onmouseover="this.style.background='linear-gradient(135deg, <?= $secondary_color ?> 0%, <?= $primary_color ?> 100%)'" onmouseout="this.style.background='linear-gradient(135deg, <?= $primary_color ?> 0%, <?= $secondary_color ?> 100%)'">
                    Ubah Password
                </a>
            </div>
        </div>

        <div class="mt-6 text-center">
            <a href=" <?= url('dashboard') ?>" class="inline-flex items-center gap-2 font-semibold transition" style="color: <?= $primary_color ?>;" onmouseover="this.style.color='<?= $secondary_color ?>'" onmouseout="this.style.color='<?= $primary_color ?>'">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                Kembali ke Dashboard
            </a>
        </div>
        </div>
    </main>

    <?php include __DIR__ . '/../../includes/footer.php'; ?>

</body>
</html>
