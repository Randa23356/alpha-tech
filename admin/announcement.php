<?php
ini_set("display_errors", 1);
ini_set("display_startup_errors", 1);
error_reporting(E_ALL);

require_once __DIR__ . "/../src/helpers/session.php";
require_once __DIR__ . "/../src/helpers/helpers.php";
require_once __DIR__ . "/../src/config/db.php";
require_once __DIR__ . "/../src/config/urls.php";

// Proteksi: hanya admin atau korti
if (!isLoggedIn() || (!isAdmin() && !isKorti())) {
    header("Location: " . url('login'));
    exit();
}

$user = getCurrentUser();

$success = null;
$error = null;

// Handle tambah pengumuman
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
            $max_size = 10 * 1024 * 1024;

            $file_tmp = $_FILES["attachment"]["tmp_name"];
            $file_name = $_FILES["attachment"]["name"];
            $file_size = $_FILES["attachment"]["size"];
            $file_type = $_FILES["attachment"]["type"];

            if (!in_array($file_type, $allowed_types)) {
                $error = "Tipe file tidak diizinkan.";
            } elseif ($file_size > $max_size) {
                $error = "Ukuran file maksimal 10MB.";
            } else {
                $ext = pathinfo($file_name, PATHINFO_EXTENSION);
                $new_filename = uniqid() . "_" . time() . "." . $ext;

                // Ensure announcements directory exists
                $upload_dir = __DIR__ . "/../public/uploads/announcements/";
                if (!is_dir($upload_dir)) {
                    mkdir($upload_dir, 0777, true);
                }

                $upload_path = $upload_dir . $new_filename;

                if (move_uploaded_file($file_tmp, $upload_path)) {
                    $file_path = "announcements/" . $new_filename;
                } else {
                    $error = "Gagal upload file.";
                }
            }
        }

        if (!$error) {
            $stmt = $pdo->prepare(
                "INSERT INTO announcements (title, content, file_path, file_name, is_active, created_by, created_at) VALUES (?, ?, ?, ?, ?, ?, NOW())",
            );
            if (
                $stmt->execute([
                    $title,
                    $content,
                    $file_path,
                    $file_name,
                    1, // is_active
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

// Handle hapus pengumuman
if (isset($_GET["delete"]) && is_numeric($_GET["delete"])) {
    $del_id = intval($_GET["delete"]);

    // First, get the announcement data to find the attachment file
    $stmt = $pdo->prepare("SELECT file_path FROM announcements WHERE id = ?");
    $stmt->execute([$del_id]);
    $announcement = $stmt->fetch();

    // Delete the attachment file if it exists
    if ($announcement && !empty($announcement['file_path'])) {
        $file_path = __DIR__ . "/../public/uploads/" . $announcement['file_path'];
        if (file_exists($file_path)) {
            unlink($file_path);
        }
    }

    // Delete from database
    $stmt = $pdo->prepare("DELETE FROM announcements WHERE id = ?");
    $stmt->execute([$del_id]);
    header("Location: announcement.php");
    exit();
}

// Ambil semua pengumuman
$stmt = $pdo->query("SELECT * FROM announcements ORDER BY created_at DESC");
$announcements = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Kelola Pengumuman Kelas - Admin Informatics A</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href=" <?= asset('tailwind.css') ?>" rel="stylesheet">
</head>
<body class="bg-gradient-to-br from-gray-50 to-blue-50 min-h-screen">
    <?php include __DIR__ . "/../includes/navbar.php"; ?>

    <?php include __DIR__ . "/sidebar.php"; ?>

    <!-- Header -->
    <header class="lg:ml-64 bg-gradient-to-r from-blue-900 via-blue-800 to-indigo-900 text-white py-10 px-6">
        <div class="max-w-7xl mx-auto">
            <div class="flex items-center gap-4">
                <div class="bg-white/10 backdrop-blur-sm p-3 rounded-xl">
                    <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z"/>
                    </svg>
                </div>
                <div>
                    <h1 class="text-3xl font-bold mb-1">Kelola Pengumuman Kelas</h1>
                    <p class="text-blue-100">Buat dan kelola pengumuman untuk kelas</p>
                </div>
            </div>
        </div>
    </header>

    <main class="lg:ml-64 max-w-5xl mx-auto px-6 py-10">
        <?php if ($error): ?>
            <div class="mb-6 p-4 bg-red-50 border-l-4 border-red-500 rounded-lg">
                <div class="flex items-center gap-2">
                    <svg class="w-5 h-5 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <span class="text-red-700 font-medium"><?= htmlspecialchars(
                        $error,
                    ) ?></span>
                </div>
            </div>
        <?php endif; ?>
        <?php if ($success): ?>
            <div class="mb-6 p-4 bg-green-50 border-l-4 border-green-500 rounded-lg">
                <div class="flex items-center gap-2">
                    <svg class="w-5 h-5 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <span class="text-green-700 font-medium"><?= htmlspecialchars(
                        $success,
                    ) ?></span>
                </div>
            </div>
        <?php endif; ?>
        <div class="bg-white rounded-2xl shadow-lg p-8 mb-10">
            <h2 class="text-2xl font-bold text-gray-900 mb-6 flex items-center gap-2">
                <svg class="w-6 h-6 text-blue-900" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                Buat Pengumuman Baru
            </h2>
            <form action="" method="POST" enctype="multipart/form-data" class="space-y-5">
    <div>
        <label for="title" class="block text-gray-700 font-semibold mb-2">Judul Pengumuman</label>
        <input type="text" id="title" name="title" required
            class="w-full px-4 py-3 border-2 border-gray-300 rounded-xl focus:outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-200 transition"
            placeholder="Masukkan judul pengumuman">
    </div>

    <div>
        <label for="content" class="block text-gray-700 font-semibold mb-2">Isi Pengumuman</label>
        <textarea id="content" name="content" required rows="5"
            class="w-full px-4 py-3 border-2 border-gray-300 rounded-xl focus:outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-200 transition resize-none"
            placeholder="Tulis isi pengumuman di sini..."></textarea>
    </div>

    <div>
        <label for="attachment" class="block text-gray-700 font-semibold mb-2">Lampiran File (Opsional)</label>
        <input type="file" id="attachment" name="attachment"
            accept=".jpg,.jpeg,.png,.gif,.pdf,.doc,.docx,.xls,.xlsx,.zip,.rar"
            class="w-full px-4 py-3 border-2 border-gray-300 rounded-xl focus:outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-200 transition">
        <p class="text-sm text-gray-500 mt-2">Format: JPG, PNG, GIF, PDF, DOC, DOCX, XLS, XLSX, ZIP, RAR (Max 10MB)</p>
    </div>

    <button type="submit"
        class="w-full bg-gradient-to-r from-blue-900 to-indigo-900 text-white font-bold py-3 rounded-xl hover:from-blue-800 hover:to-indigo-800 transition shadow-lg flex items-center justify-center gap-2">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
        </svg>
        Tambah Pengumuman
    </button>
</form>

        </div>
        <div class="bg-white rounded-2xl shadow-lg p-8">
            <h2 class="text-2xl font-bold text-gray-900 mb-6 flex items-center gap-2">
                <svg class="w-6 h-6 text-blue-900" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                </svg>
                Daftar Pengumuman
            </h2>
            <?php if (empty($announcements)): ?>
    <div class="text-center py-12">
        <svg class="w-20 h-20 mx-auto mb-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4" />
        </svg>
        <h3 class="text-xl font-bold text-gray-700 mb-2">Belum Ada Pengumuman</h3>
        <p class="text-gray-500">Buat pengumuman pertama Anda di atas</p>
    </div>
<?php else: ?>
    <div class="space-y-4">
        <?php foreach ($announcements as $a): ?>
            <div class="bg-gradient-to-r from-blue-50 to-indigo-50 p-6 rounded-xl border-l-4 border-blue-900 hover:shadow-md transition">
                <div class="flex justify-between items-start mb-3">
                    <h3 class="text-xl font-bold text-blue-900 flex-1 break-words">
                        <?= htmlspecialchars($a["title"]) ?>
                    </h3>
                    <a href="?delete=<?= $a["id"] ?>"
                       class="inline-flex items-center gap-1 bg-red-600 text-white px-3 py-1.5 rounded-lg hover:bg-red-700 text-sm font-medium transition"
                       onclick="return confirm('Hapus pengumuman ini?')">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                        </svg>
                        Hapus
                    </a>
                </div>
                <div class="text-gray-700 mb-3 leading-relaxed break-words">
                    <?= nl2br(htmlspecialchars($a["content"])) ?>
                </div>

                <?php if (!empty($a["file_path"])): ?>
                    <div class="mb-3 p-3 bg-white rounded-lg border border-blue-200">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-2">
                                <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"/>
                                </svg>
                                <span class="text-sm font-medium text-gray-700">
                                    <?= htmlspecialchars($a["file_name"]) ?>
                                </span>
                            </div>
                            <a href=" <?= url('download') ?>?file=<?=
                                    urlencode($a["file_path"])
                                    ?>&name=<?=
                                    urlencode($a["file_name"])?>"
                               class="inline-flex items-center gap-1 bg-blue-600 text-white px-3 py-1.5 rounded-lg hover:bg-blue-700 text-sm font-medium transition">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                          d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                                </svg>
                                Download
                            </a>
                        </div>
                    </div>
                <?php endif; ?>

                <div class="flex items-center gap-2 text-sm text-gray-600">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <?= date("d M Y H:i", strtotime($a["created_at"])) ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

        </div>
        <div class="mt-8 text-center">
            <a href="
                "dashboard",
            .php" class="inline-flex items-center gap-2 text-blue-900 font-semibold hover:text-blue-700 transition">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                </svg>
                Kembali ke Dashboard
            </a>
        </div>
    </main>

    <footer class="lg:ml-64 bg-white border-t border-gray-200 py-6 mt-12">
        <div class="max-w-7xl mx-auto px-6 text-center text-gray-600">
            &copy; <?= date("Y") ?> Informatics A. All rights reserved.
        </div>
    </footer>



</body>
</html>
