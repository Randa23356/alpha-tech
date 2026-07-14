<?php
ini_set("display_errors", 1);
ini_set("display_startup_errors", 1);
error_reporting(E_ALL);
// admin/navbar_icons.php - Navbar Icon Management
session_start();
require_once __DIR__ . "/../src/config/db.php";
require_once __DIR__ . "/../src/config/urls.php";
require_once __DIR__ . "/../src/helpers/session.php";

// DEBUG: Always log when this file is accessed
error_log("navbar_icons.php accessed at " . date('Y-m-d H:i:s'));

// Proteksi: hanya admin yang bisa akses
if (!isLoggedIn() || !isAdmin()) {
    header("Location: " . url('login'));
    exit();
}

// Handle actions
$success = $_GET['success'] ?? null;
$error = $_GET['error'] ?? null;

error_log("navbar_icons.php - GET params: " . print_r($_GET, true));
error_log("navbar_icons.php - POST data: " . print_r($_POST, true));
error_log("navbar_icons.php - FILES data: " . print_r($_FILES, true));

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    error_log("POST request received in navbar_icons.php");

    if (isset($_POST['action'])) {
        $action = $_POST['action'];
        error_log("Action: " . $action);

        if ($action === 'upload' && isset($_FILES['icon_file'])) {
            $upload_dir = __DIR__ . '/../public/images/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0755, true);
                error_log("Created upload directory: " . $upload_dir);
            }

            $file = $_FILES['icon_file'];
            error_log("File info: " . print_r($file, true));

            if ($file['error'] === UPLOAD_ERR_OK) {
                $file_extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
                $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif'];

                if (in_array($file_extension, $allowed_extensions)) {
                    if ($file['size'] <= 2 * 1024 * 1024) { // 2MB max
                        $new_filename = 'navbar_icon_' . time() . '.' . $file_extension;
                        $upload_path = $upload_dir . $new_filename;

                        error_log("Attempting upload: " . $file['tmp_name'] . " -> " . $upload_path);

                        if (move_uploaded_file($file['tmp_name'], $upload_path)) {
                            // Get image dimensions
                            $image_info = getimagesize($upload_path);
                            $width = $image_info[0] ?? null;
                            $height = $image_info[1] ?? null;

                            error_log("File uploaded successfully, dimensions: " . $width . "x" . $height);

                            // Save to database
                            $stmt = $pdo->prepare(
                                "INSERT INTO navbar_icons (filename, original_filename, file_path, file_size, mime_type, width, height, alt_text, is_active, sort_order)
                                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, 1, 0)"
                            );
                            $result = $stmt->execute([
                                $new_filename,
                                $file['name'],
                                'public/images/' . $new_filename,
                                $file['size'],
                                $file['type'],
                                $width,
                                $height,
                                'Navbar Icon'
                            ]);

                            if ($result) {
                                $success = "Icon berhasil diupload!";
                                error_log("Database insert successful");
                            } else {
                                $error = "Gagal menyimpan ke database";
                                error_log("Database insert failed");
                            }
                        } else {
                            $error = "Gagal mengupload file. Error: " . $file['error'];
                            error_log("move_uploaded_file failed. Upload path: " . $upload_path . ", Temp file: " . $file['tmp_name']);
                        }
                    } else {
                        $error = "Ukuran file terlalu besar (maksimal 2MB)";
                    }
                } else {
                    $error = "Format file tidak didukung (gunakan JPG, PNG, atau GIF)";
                }
            } else {
                $error = "Error saat upload file: " . $file['error'];
                error_log("Upload error code: " . $file['error']);
            }
        } elseif ($action === 'delete' && isset($_POST['icon_id'])) {
            $icon_id = intval($_POST['icon_id']);

            // Get icon info before deleting
            $stmt = $pdo->prepare("SELECT file_path FROM navbar_icons WHERE id = ?");
            $stmt->execute([$icon_id]);
            $icon = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($icon) {
                // Delete file
                $file_path = __DIR__ . '/../' . $icon['file_path'];
                if (file_exists($file_path)) {
                    unlink($file_path);
                }

                // Delete from database
                $stmt = $pdo->prepare("DELETE FROM navbar_icons WHERE id = ?");
                $stmt->execute([$icon_id]);

                header("Location: " . $_SERVER['PHP_SELF'] . "?success=" . urlencode("Icon berhasil dihapus!"));
                exit();
            }
        } elseif ($action === 'toggle_active' && isset($_POST['icon_id'])) {
            $icon_id = intval($_POST['icon_id']);
            $stmt = $pdo->prepare("UPDATE navbar_icons SET is_active = !is_active WHERE id = ?");
            $stmt->execute([$icon_id]);

            header("Location: " . $_SERVER['PHP_SELF'] . "?success=" . urlencode("Status icon berhasil diubah!"));
            exit();
        }
    }
}

// Get all navbar icons
$navbar_icons = [];
try {
    $stmt = $pdo->query("SELECT * FROM navbar_icons ORDER BY sort_order, created_at DESC");
    $navbar_icons = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $navbar_icons = [];
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Manajemen Icon Navbar - Admin Informatics A</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="<?= asset('tailwind.css') ?>" rel="stylesheet">
    <?php require_once __DIR__ . '/../includes/favicon.php'; ?>
</head>
<body class="bg-gradient-to-br from-gray-50 to-blue-50 min-h-screen">
    <?php include __DIR__ . '/../includes/navbar.php'; ?>
    <?php include __DIR__ . '/sidebar.php'; ?>

    <!-- Header -->
    <header class="lg:ml-64 bg-gradient-to-r from-blue-900 via-blue-800 to-indigo-900 text-white py-10 px-6">
        <div class="max-w-7xl mx-auto">
            <div class="flex items-center gap-4">
                <div class="bg-white/10 backdrop-blur-sm p-3 rounded-xl">
                    <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                    </svg>
                </div>
                <div>
                    <h1 class="text-3xl font-bold mb-1">Manajemen Icon Navbar</h1>
                    <p class="text-blue-100">Upload dan kelola logo navbar website</p>
                </div>
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <main class="lg:ml-64 max-w-6xl mx-auto px-6 py-10">
        <?php if ($error): ?>
            <div class="mb-6 p-4 bg-red-100 border-l-4 border-red-500 text-red-700 rounded">
                <div class="flex items-center gap-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <?= htmlspecialchars($error) ?>
                </div>
            </div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="mb-6 p-4 bg-green-100 border-l-4 border-green-500 text-green-700 rounded">
                <div class="flex items-center gap-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <?= htmlspecialchars($success) ?>
                </div>
            </div>
        <?php endif; ?>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
            <!-- Upload Form -->
            <div class="bg-white rounded-xl shadow-md p-6">
                <h2 class="text-xl font-bold text-blue-900 mb-4 flex items-center gap-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M9 19l3 3m0 0l3-3m-3 3V10"/>
                    </svg>
                    Upload Icon Baru
                </h2>

                <form action="" method="POST" enctype="multipart/form-data" class="space-y-4" onsubmit="console.log('Form submitted'); return true;">
                    <input type="hidden" name="action" value="upload">

                    <div>
                        <label for="icon_file" class="block text-gray-700 font-semibold mb-2">Pilih File Icon</label>
                        <input type="file" id="icon_file" name="icon_file" accept="image/*" required
                               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <p class="text-sm text-gray-500 mt-1">Format: JPG, PNG, GIF • Maksimal: 2MB</p>
                    </div>

                    <button type="submit" class="w-full bg-blue-900 text-white px-6 py-3 rounded-lg font-bold hover:bg-blue-800 transition">
                        Upload Icon
                    </button>
                </form>
            </div>

            <!-- Icons List -->
            <div class="bg-white rounded-xl shadow-md p-6">
                <h2 class="text-xl font-bold text-blue-900 mb-4 flex items-center gap-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"/>
                    </svg>
                    Icon yang Tersedia (<?= count($navbar_icons) ?>)
                </h2>

                <?php if (empty($navbar_icons)): ?>
                    <p class="text-gray-500 text-center py-8">Belum ada icon yang diupload</p>
                <?php else: ?>
                    <div class="space-y-4 max-h-96 overflow-y-auto">
                        <?php foreach ($navbar_icons as $icon): ?>
                            <div class="flex items-center gap-4 p-4 border border-gray-200 rounded-lg">
                                <img src="<?= url($icon['file_path']) ?>" alt="Icon" class="w-12 h-12 object-cover rounded-lg border">

                                <div class="flex-1">
                                    <h3 class="font-semibold text-gray-900">
                                        <?= htmlspecialchars($icon['original_filename']) ?>
                                    </h3>
                                    <p class="text-sm text-gray-500">
                                        <?= htmlspecialchars($icon['filename']) ?> •
                                        <?= date('d/m/Y H:i', strtotime($icon['created_at'])) ?> •
                                        <?= number_format($icon['file_size'] / 1024, 1) ?> KB
                                    </p>
                                    <?php if ($icon['width'] && $icon['height']): ?>
                                        <p class="text-xs text-gray-400">
                                            <?= $icon['width'] ?> × <?= $icon['height'] ?> px
                                        </p>
                                    <?php endif; ?>
                                </div>

                                <div class="flex gap-2">
                                    <form method="POST" class="inline" onsubmit="return confirm('Yakin ingin menghapus icon ini?')">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="icon_id" value="<?= $icon['id'] ?>">
                                        <button type="submit" class="px-3 py-1 text-red-600 hover:bg-red-50 rounded transition">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                            </svg>
                                        </button>
                                    </form>

                                    <form method="POST" class="inline">
                                        <input type="hidden" name="action" value="toggle_active">
                                        <input type="hidden" name="icon_id" value="<?= $icon['id'] ?>">
                                        <button type="submit" class="px-3 py-1 <?= $icon['is_active'] ? 'text-green-600 hover:bg-green-50' : 'text-gray-400 hover:bg-gray-50' ?> rounded transition">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                            </svg>
                                        </button>
                                    </form>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Back Button -->
        <div class="mt-8 text-center">
            <a href="/admin/site_settings.php" class="inline-flex items-center gap-2 px-6 py-3 border-2 border-blue-300 text-blue-700 rounded-lg font-bold hover:bg-blue-50 transition">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                </svg>
                Kembali ke Pengaturan Website
            </a>
        </div>
    </main>

    <!-- Footer -->
    <footer class="lg:ml-64 bg-white border-t border-gray-200 py-6 mt-12">
        <div class="max-w-7xl mx-auto px-6 text-center text-gray-600">
            &copy; <?= date("Y") ?> Informatics A. All rights reserved.
        </div>
    </footer>
</body>
</html>
