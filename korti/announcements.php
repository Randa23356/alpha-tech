<?php
ini_set("display_errors", 1);
ini_set("display_startup_errors", 1);
error_reporting(E_ALL);
// korti/announcements.php
session_start();
require_once __DIR__ . "/../src/helpers/session.php";
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
    $site_name = $settings['site_name'] ?? "Informatics A";
} catch (Exception $e) {
    // Use default colors if database fails
    $site_name = "Informatics A";
}

// Proteksi: hanya korti yang bisa akses
if (!isLoggedIn() || getCurrentUser()["role"] !== "korti") {
    header("Location: " . url('login'));
    exit();
}

$user = getCurrentUser();

// Handle tambah pengumuman
$success = null;
$error = null;
if (
    $_SERVER["REQUEST_METHOD"] === "POST" &&
    isset($_POST["title"], $_POST["content"])
) {
    $title = trim($_POST["title"]);
    $content = trim($_POST["content"]);

    if (!$title || !$content) {
        $error = "Judul dan isi pengumuman wajib diisi.";
    } elseif (strlen($title) < 5) {
        $error = "Judul minimal 5 karakter.";
    } elseif (strlen($content) < 10) {
        $error = "Isi pengumuman minimal 10 karakter.";
    } else {
        // Handle file upload
        $file_path = null;
        $file_name = null;
        $file_type = null;

        if (
            isset($_FILES["attachment"]) &&
            $_FILES["attachment"]["error"] === 0
        ) {
            $allowed_types = [
                "image/jpeg",
                "image/png",
                "image/gif",
                "application/pdf",
                "application/msword",
                "application/vnd.openxmlformats-officedocument.wordprocessingml.document",
                "application/vnd.ms-excel",
                "application/vnd.openxmlformats-officedocument.spreadsheetml.sheet",
                "application/zip",
                "application/x-rar-compressed",
            ];
            $max_size = 10 * 1024 * 1024; // 10MB

            $file_tmp = $_FILES["attachment"]["tmp_name"];
            $file_name = $_FILES["attachment"]["name"];
            $file_size = $_FILES["attachment"]["size"];
            $file_type = $_FILES["attachment"]["type"];

            if (!in_array($file_type, $allowed_types)) {
                $error =
                    "Tipe file tidak diizinkan. Hanya JPG, PNG, GIF, PDF, DOC, DOCX, XLS, XLSX, ZIP, RAR yang diperbolehkan.";
            } elseif ($file_size > $max_size) {
                $error = "Ukuran file terlalu besar. Maksimal 10MB.";
            } else {
                $ext = pathinfo($file_name, PATHINFO_EXTENSION);
                $new_filename = uniqid() . "_" . time() . "." . $ext;
                $upload_path =
                    __DIR__ .
                    "/../public/uploads/announcements/" .
                    $new_filename;

                if (move_uploaded_file($file_tmp, $upload_path)) {
                    $file_path = "announcements/" . $new_filename;
                } else {
                    $error = "Gagal mengupload file.";
                }
            }
        }

        if (!$error) {
            $stmt = $pdo->prepare(
                "INSERT INTO announcements (title, content, file_path, file_name, created_by) VALUES (?, ?, ?, ?, ?)",
            );
            if (
                $stmt->execute([
                    $title,
                    $content,
                    $file_path,
                    $file_name,
                    $user["id"],
                ])
            ) {
                $success = "Pengumuman berhasil ditambahkan!";
                
                // Send Firebase push notification
                $announcementId = $pdo->lastInsertId();
                require_once __DIR__ . '/../src/helpers/fcm_helper.php';
                notifyNewAnnouncement($announcementId, $title);
            } else {
                $error = "Gagal menambah pengumuman.";
            }
        }
    }
}

// Handle hapus pengumuman dengan modal confirmation
if (isset($_GET["delete"]) && is_numeric($_GET["delete"])) {
    $del_id = intval($_GET["delete"]);

    // Pastikan Korti hanya bisa hapus pengumuman yang mereka buat
    $stmt = $pdo->prepare("SELECT created_by, title FROM announcements WHERE id = ?");
    $stmt->execute([$del_id]);
    $announcement = $stmt->fetch();

    if ($announcement && $announcement['created_by'] === $user["id"]) {
        $stmt = $pdo->prepare("DELETE FROM announcements WHERE id = ? AND created_by = ?");
        $stmt->execute([$del_id, $user["id"]]);
        $success = "Pengumuman '" . htmlspecialchars($announcement['title']) . "' berhasil dihapus!";
    } else {
        $error = "Pengumuman tidak ditemukan atau Anda tidak memiliki izin untuk menghapusnya.";
    }
}

// Ambil semua pengumuman
$stmt = $pdo->query("SELECT * FROM announcements ORDER BY created_at DESC");
$announcements = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Kelola Pengumuman Kelas - Korti <?= htmlspecialchars($site_name) ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="<?= url('public/tailwind.css') ?>" rel="stylesheet">
    <script>
        // Delete Modal Functions - Define early so they're available for HTML elements
        let deleteModal = null;
        let deleteId = null;

        function showDeleteModal(id, title) {
            deleteModal = document.getElementById('deleteModal');
            deleteId = id;

            document.getElementById('deleteAnnouncementTitle').textContent = title;
            document.getElementById('confirmDeleteBtn').href = '?delete=' + id;

            deleteModal.classList.remove('hidden');
        }

        function hideDeleteModal() {
            if (deleteModal) {
                deleteModal.classList.add('hidden');
                deleteModal = null;
                deleteId = null;
            }
        }
    </script>
    <style>
        /* Dynamic theme variables for korti announcements page */
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
</head>
<body class="min-h-screen" style="background: linear-gradient(135deg, <?= $primary_color ?>10 0%, <?= $secondary_color ?>10 100%);">
    <?php include __DIR__ . "/../includes/navbar.php"; ?>

    <?php include __DIR__ . "/../includes/korti_sidebar.php"; ?>
    <!-- Header -->
    <header class="lg:ml-64 text-white shadow-xl py-8 md:py-12 px-4 md:px-6 rounded-xl" style="background: linear-gradient(135deg, <?= $primary_color ?> 0%, <?= $secondary_color ?> 100%);">
        <div class="max-w-7xl mx-auto">
            <div class="flex items-center gap-3 md:gap-4">
                <div class="bg-white/10 backdrop-blur-sm p-2 md:p-3 rounded-xl">
                    <svg class="w-8 h-8 md:w-10 md:h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z"/>
                    </svg>
                </div>
                <div>
                    <h1 class="text-xl md:text-3xl font-bold mb-1">Kelola Pengumuman</h1>
                    <p class="text-white/90 text-sm md:text-base">Buat dan kelola pengumuman untuk kelas</p>
                </div>
            </div>
        </div>
    </header>

    <main class="lg:ml-64 max-w-5xl mx-auto px-4 md:px-6 py-8 md:py-10">
        <?php if ($error): ?>
            <div class="mb-6 p-4 bg-red-100 border-l-4 rounded-lg" style="border-color: <?= $danger_color ?>;">
                <div class="flex items-center gap-2">
                    <svg class="w-4 h-4 md:w-5 md:h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="color: <?= $danger_color ?>;">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <span class="font-medium text-sm md:text-base" style="color: <?= $danger_color ?>;"><?= htmlspecialchars(
                        $error,
                    ) ?></span>
                </div>
            </div>
        <?php endif; ?>
        <?php if ($success): ?>
            <div class="mb-6 p-4 bg-green-100 border-l-4 rounded-lg" style="border-color: <?= $success_color ?>;">
                <div class="flex items-center gap-2">
                    <svg class="w-4 h-4 md:w-5 md:h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="color: <?= $success_color ?>;">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <span class="font-medium text-sm md:text-base" style="color: <?= $success_color ?>;"><?= htmlspecialchars(
                        $success,
                    ) ?></span>
                </div>
            </div>
        <?php endif; ?>
        <div class="bg-white rounded-2xl shadow-lg p-6 md:p-8 mb-10">
            <h2 class="text-xl md:text-2xl font-bold mb-6 flex items-center gap-2" style="color: <?= $primary_color ?>;">
                <svg class="w-5 h-5 md:w-6 md:h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                Buat Pengumuman Baru
            </h2>
            <form action="" method="POST" enctype="multipart/form-data" class="space-y-4 md:space-y-5">
                <div>
                    <label for="title" class="block text-gray-700 font-semibold mb-2 text-sm md:text-base">Judul Pengumuman</label>
                    <input type="text" id="title" name="title" required class="w-full px-3 md:px-4 py-2 md:py-3 border-2 rounded-xl focus:outline-none transition text-sm md:text-base" style="border-color: <?= $primary_color ?>20; focus:border-color: <?= $primary_color ?>;" placeholder="Masukkan judul pengumuman">
                </div>
                <div>
                    <label for="content" class="block text-gray-700 font-semibold mb-2 text-sm md:text-base">Isi Pengumuman</label>
                    <textarea id="content" name="content" required rows="4 md:rows-5" class="w-full px-3 md:px-4 py-2 md:py-3 border-2 rounded-xl focus:outline-none focus:ring-2 transition resize-none text-sm md:text-base" style="border-color: <?= $primary_color ?>20; focus:border-color: <?= $primary_color ?>;" placeholder="Tulis isi pengumuman di sini..."></textarea>
                </div>
                <div>
                    <label for="attachment" class="block text-gray-700 font-semibold mb-2 text-sm md:text-base">Lampiran File (Opsional)</label>
                    <input type="file" id="attachment" name="attachment" accept=".jpg,.jpeg,.png,.gif,.pdf,.doc,.docx,.xls,.xlsx,.zip,.rar" class="w-full px-3 md:px-4 py-2 md:py-3 border-2 rounded-xl focus:outline-none transition text-sm md:text-base" style="border-color: <?= $primary_color ?>20; focus:border-color: <?= $primary_color ?>;">
                    <p class="text-xs md:text-sm text-gray-500 mt-2">Format: JPG, PNG, GIF, PDF, DOC, DOCX, XLS, XLSX, ZIP, RAR (Max 10MB)</p>
                </div>
                <button type="submit" class="w-full btn-gradient text-white font-bold py-2 md:py-3 rounded-xl hover:btn-gradient transition shadow-lg flex items-center justify-center gap-2 text-sm md:text-base">
                    <svg class="w-4 h-4 md:w-5 md:h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                    </svg>
                    Tambah Pengumuman
                </button>
            </form>
        </div>
        <div class="bg-white rounded-2xl shadow-lg p-6 md:p-8">
            <h2 class="text-xl md:text-2xl font-bold mb-6 flex items-center gap-2" style="color: <?= $primary_color ?>;">
                <svg class="w-5 h-5 md:w-6 md:h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                </svg>
                Daftar Pengumuman
            </h2>
            <?php if (empty($announcements)): ?>
                <div class="text-center py-8 md:py-12">
                    <svg class="w-16 h-16 md:w-20 md:h-20 mx-auto mb-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"/>
                    </svg>
                    <h3 class="text-lg md:text-xl font-bold text-gray-700 mb-2">Belum Ada Pengumuman</h3>
                    <p class="text-gray-500">Buat pengumuman pertama Anda di atas</p>
                </div>
            <?php else: ?>
                <div class="space-y-4 md:space-y-6">
                    <?php foreach ($announcements as $a): ?>
                        <div class="p-4 md:p-6 rounded-xl border-l-4 hover:shadow-md transition" style="background: linear-gradient(135deg, <?= $primary_color ?>10 0%, <?= $secondary_color ?>10 100%); border-color: <?= $primary_color ?>;">
                            <div class="flex flex-col sm:flex-row sm:justify-between sm:items-start gap-3 mb-3">
                                <h3 class="text-lg md:text-xl font-bold flex-1 leading-tight" style="color: <?= $primary_color ?>; line-clamp-2;">
                                    <?= htmlspecialchars($a["title"]) ?>
                                </h3>
                                <div class="flex gap-2 flex-shrink-0">
                                    <?php if (!empty($a["file_path"])): ?>
                                        <a href=" <?= url('download') ?>?file=<?= urlencode($a["file_path"]) ?>&name=<?= urlencode($a["file_name"]) ?>"
                                           class="inline-flex items-center justify-center gap-1 px-2 py-1 rounded-md text-xs font-medium transition w-8 h-8 md:w-auto md:h-auto md:px-2 md:py-1"
                                           style="background: linear-gradient(135deg, <?= $primary_color ?> 0%, <?= $secondary_color ?> 100%); color: white;"
                                           onmouseover="this.style.background='linear-gradient(135deg, <?= $secondary_color ?> 0%, <?= $primary_color ?> 100%)'"
                                           onmouseout="this.style.background='linear-gradient(135deg, <?= $primary_color ?> 0%, <?= $secondary_color ?> 100%)'"
                                           title="Download File">
                                            <svg class="w-3 h-3 md:w-4 md:h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                                            </svg>
                                            <span class="hidden md:inline ml-1">Download</span>
                                        </a>
                                    <?php endif; ?>
                                    <a href="javascript:void(0)" onclick="showDeleteModal(<?= $a['id'] ?>, '<?= htmlspecialchars(addslashes($a['title'])) ?>')" class="inline-flex items-center justify-center gap-1 px-2 py-1 rounded-md text-xs font-medium transition w-8 h-8 md:w-auto md:h-auto md:px-2 md:py-1"
                                       style="background-color: <?= $danger_color ?>; color: white;"
                                       onmouseover="this.style.backgroundColor='<?= $danger_color ?>dd'"
                                       onmouseout="this.style.backgroundColor='<?= $danger_color ?>'"
                                       title="Hapus Pengumuman">
                                        <svg class="w-3 h-3 md:w-4 md:h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                        </svg>
                                        <span class="hidden md:inline ml-1">Hapus</span>
                                    </a>
                                </div>
                            </div>
                            <div class="text-gray-700 mb-3 leading-relaxed text-sm md:text-base" style="color: <?= $primary_color ?>dd; line-clamp-3;">
                                <?= nl2br(htmlspecialchars($a["content"])) ?>
                            </div>

                            <div class="flex items-center gap-2 text-xs md:text-sm text-gray-600">
                                <svg class="w-3 h-3 md:w-4 md:h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="color: <?= $primary_color ?>;">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                                <span class="truncate">
                                    <?= date("d M Y H:i", strtotime($a["created_at"])) ?>
                                </span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
        <div class="mt-6 md:mt-8 text-center">
            <a href="dashboard.php" class="inline-flex items-center gap-2 font-semibold transition text-sm md:text-base" style="color: <?= $primary_color ?>;" onmouseover="this.style.color='<?= $secondary_color ?>'" onmouseout="this.style.color='<?= $primary_color ?>'">
                <svg class="w-4 h-4 md:w-5 md:h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                </svg>
                Kembali ke Dashboard
            </a>
        </div>
    <!-- Delete Confirmation Modal -->
    <div id="deleteModal" class="fixed inset-0 bg-black bg-opacity-50 hidden z-50">
        <div class="flex items-center justify-center min-h-screen p-4">
            <div class="bg-white rounded-xl shadow-xl max-w-md w-full">
                <!-- Modal Header -->
                <div class="p-6 border-b">
                    <div class="flex items-center gap-3">
                        <div class="bg-red-100 p-2 rounded-full">
                            <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-lg font-bold text-gray-900">Hapus Pengumuman</h3>
                            <p class="text-sm text-gray-600">Konfirmasi penghapusan pengumuman</p>
                        </div>
                    </div>
                </div>

                <!-- Modal Body -->
                <div class="p-6">
                    <p class="text-gray-700 mb-4">
                        Apakah Anda yakin ingin menghapus pengumuman:
                        <strong id="deleteAnnouncementTitle" class="text-red-600"></strong>?
                    </p>
                    <p class="text-sm text-gray-500 mb-6">
                        Tindakan ini tidak dapat dibatalkan. Pengumuman akan dihapus secara permanen.
                    </p>

                    <!-- Modal Footer -->
                    <div class="flex gap-3">
                        <button onclick="hideDeleteModal()" class="flex-1 px-4 py-2 border border-gray-300 text-gray-700 rounded-lg font-medium hover:bg-gray-50 transition">
                            Batal
                        </button>
                        <a id="confirmDeleteBtn" href="#" class="flex-1 px-4 py-2 bg-red-600 text-white rounded-lg font-medium hover:bg-red-700 transition text-center">
                            Hapus
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Sidebar toggle for mobile (if not already defined by korti_sidebar.php)
        if (typeof sidebarToggle === 'undefined') {
            const sidebarToggle = document.getElementById('sidebar-toggle');
            const sidebar = document.getElementById('sidebar');
            const sidebarOverlay = document.getElementById('sidebar-overlay');

            function toggleSidebar() {
                sidebar.classList.toggle('-translate-x-full');
                sidebarOverlay.classList.toggle('hidden');
            }

            sidebarToggle?.addEventListener('click', toggleSidebar);
            sidebarOverlay?.addEventListener('click', toggleSidebar);
        }

        // Close modal when clicking outside
        document.getElementById('deleteModal').addEventListener('click', function(e) {
            if (e.target === this) {
                hideDeleteModal();
            }
        });
    </script>
</body>
</html>
