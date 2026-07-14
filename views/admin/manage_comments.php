<?php
// informatics_a/admin/manage_comments.php
require_once __DIR__ . "/../src/helpers/session.php";
require_once __DIR__ . "/../src/config/db.php";

// Proteksi: hanya admin yang bisa akses
if (!isLoggedIn() || !isAdmin()) {
    header("Location: /login.php");
    exit();
}

// Handle aksi delete komentar
if (
    $_SERVER["REQUEST_METHOD"] === "POST" &&
    isset($_POST["action"], $_POST["comment_id"])
) {
    $comment_id = intval($_POST["comment_id"]);
    $action = $_POST["action"];

    if ($action === "delete") {
        $stmt = $pdo->prepare("DELETE FROM comments WHERE id = ?");
        $stmt->execute([$comment_id]);
    }
}

// Ambil semua komentar kegiatan
$stmt = $pdo->query(
    "SELECT comments.*, users.username, posts.title
     FROM comments
     LEFT JOIN users ON comments.user_id = users.id
     LEFT JOIN posts ON comments.post_id = posts.id
     ORDER BY comments.created_at DESC",
);
$comments = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Kelola Komentar Kegiatan - Admin Informatics A</title>
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
    <main class="max-w-5xl mx-auto mt-10 bg-white rounded-lg shadow-lg p-8">
        <h1 class="text-2xl font-bold text-blue-900 mb-6">Kelola Komentar Kegiatan</h1>
        <?php if (empty($comments)): ?>
            <div class="bg-blue-50 p-4 rounded text-blue-700">Belum ada komentar pada kegiatan.</div>
        <?php else: ?>
            <div class="overflow-x-auto">
                <table class="min-w-full border border-blue-200 rounded-lg">
                    <thead>
                        <tr class="bg-blue-100 text-blue-900">
                            <th class="py-2 px-3 border-b">Kegiatan</th>
                            <th class="py-2 px-3 border-b">User</th>
                            <th class="py-2 px-3 border-b">Komentar</th>
                            <th class="py-2 px-3 border-b">Tanggal</th>
                            <th class="py-2 px-3 border-b">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($comments as $comment): ?>
                        <tr class="border-b hover:bg-blue-50">
                            <td class="py-2 px-3"><?= htmlspecialchars(
                                $comment["title"] ?? "-",
                            ) ?></td>
                            <td class="py-2 px-3"><?= htmlspecialchars(
                                $comment["username"] ?? "Anonim",
                            ) ?></td>
                            <td class="py-2 px-3"><?= nl2br(
                                htmlspecialchars($comment["comment"]),
                            ) ?></td>
                            <td class="py-2 px-3"><?= htmlspecialchars(
                                date(
                                    "d M Y H:i",
                                    strtotime($comment["created_at"]),
                                ),
                            ) ?></td>
                            <td class="py-2 px-3">
                                <form action="" method="POST" class="flex space-x-2">
                                    <input type="hidden" name="comment_id" value="<?= $comment[
                                        "id"
                                    ] ?>">
                                    <button type="submit" name="action" value="delete" class="bg-red-700 text-white px-2 py-1 rounded hover:bg-red-800 text-xs" onclick="return confirm('Hapus komentar ini?')">Delete</button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
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
