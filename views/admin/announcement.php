<?php
// informatics_a/admin/announcement.php
require_once __DIR__ . "/../src/helpers/session.php";
require_once __DIR__ . "/../src/config/db.php";

// Proteksi: admin atau korti yang bisa akses
if (!isLoggedIn() || (!isAdmin() && !isKorti())) {
    header("Location: /login.php");
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
        $stmt = $pdo->prepare(
            "INSERT INTO announcements (title, content, created_at) VALUES (?, ?, NOW())",
        );
        if ($stmt->execute([$title, $content])) {
            $success = "Pengumuman berhasil ditambahkan!";
        } else {
            $error = "Gagal menambah pengumuman.";
        }
    }
}

// Handle hapus pengumuman
if (isset($_GET["delete"]) && is_numeric($_GET["delete"])) {
    $del_id = intval($_GET["delete"]);
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
    <link href="../public/tailwind.css" rel="stylesheet">
    <?php require_once __DIR__ . '/../../includes/favicon.php'; ?>
</head>
<body class="bg-blue-900 min-h-screen">
    <nav class="bg-blue-800 text-white px-6 py-4 flex justify-between items-center">
        <div class="font-bold text-xl">Informatics A Admin</div>
        <div>
            <a href="/admin/dashboard.php" class="mr-4 hover:underline">Dashboard</a>
            <a href="/logout.php" class="bg-red-600 px-3 py-1 rounded hover:bg-red-700">Logout</a>
        </div>
    </nav>
    <main class="max-w-3xl mx-auto mt-10 bg-white rounded-lg shadow-lg p-8">
        <h1 class="text-2xl font-bold text-blue-900 mb-6">Kelola Pengumuman Kelas</h1>
        <p class="mb-6 text-blue-900">Buat pengumuman baru untuk kelas atau kelola pengumuman yang sudah ada.</p>
        <?php if ($error): ?>
            <div class="mb-4 p-2 bg-red-100 text-red-700 rounded"><?= htmlspecialchars(
                $error,
            ) ?></div>
        <?php endif; ?>
        <?php if ($success): ?>
            <div class="mb-4 p-2 bg-green-100 text-green-700 rounded"><?= htmlspecialchars(
                $success,
            ) ?></div>
        <?php endif; ?>
        <form action="" method="POST" class="space-y-5 mb-10">
            <div>
                <label for="title" class="block text-blue-900 font-semibold mb-1">Judul Pengumuman</label>
                <input type="text" id="title" name="title" required class="w-full px-3 py-2 border border-blue-300 rounded focus:outline-none focus:ring-2 focus:ring-blue-700">
            </div>
            <div>
                <label for="content" class="block text-blue-900 font-semibold mb-1">Isi Pengumuman</label>
                <textarea id="content" name="content" required rows="4" class="w-full px-3 py-2 border border-blue-300 rounded focus:outline-none focus:ring-2 focus:ring-blue-700"></textarea>
            </div>
            <button type="submit" class="w-full bg-blue-900 text-white font-bold py-2 rounded hover:bg-blue-800 transition">Tambah Pengumuman</button>
        </form>
        <h2 class="text-xl font-bold text-blue-900 mb-4">Daftar Pengumuman</h2>
        <?php if (empty($announcements)): ?>
            <div class="bg-blue-50 p-4 rounded text-blue-700">Belum ada pengumuman.</div>
        <?php else: ?>
            <div class="space-y-6">
                <?php foreach ($announcements as $a): ?>
                    <div class="bg-blue-50 p-4 rounded shadow flex flex-col">
                        <div class="flex justify-between items-center mb-2">
                            <h3 class="text-lg font-bold text-blue-900"><?= htmlspecialchars(
                                $a["title"],
                            ) ?></h3>
                            <a href="?delete=<?= $a[
                                "id"
                            ] ?>" class="bg-red-600 text-white px-3 py-1 rounded hover:bg-red-700 text-xs" onclick="return confirm('Hapus pengumuman ini?')">Hapus</a>
                        </div>
                        <div class="text-blue-800 mb-2"><?= nl2br(
                            htmlspecialchars($a["content"]),
                        ) ?></div>
                        <div class="text-xs text-blue-700 mt-auto text-right">
                            <?= date(
                                "d M Y H:i",
                                strtotime($a["created_at"]),
                            ) ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
        <div class="mt-8 text-center">
            <a href="dashboard.php" class="text-blue-700 font-semibold hover:underline">&larr; Kembali ke Dashboard Admin</a>
        </div>
    </main>
    <footer class="mt-16 py-6 text-center text-blue-100 bg-blue-800">
        &copy; <?= date("Y") ?> Informatics A. All rights reserved.
    </footer>
</body>
</html>
