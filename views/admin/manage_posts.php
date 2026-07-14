<?php
// informatics_a/admin/manage_posts.php
require_once __DIR__ . "/../src/helpers/session.php";
require_once __DIR__ . "/../src/config/db.php";

// Proteksi: hanya admin yang bisa akses
if (!isLoggedIn() || !isAdmin()) {
    header("Location: ../login.php");
    exit();
}

// Handle aksi approve, reject, delete
if (
    $_SERVER["REQUEST_METHOD"] === "POST" &&
    isset($_POST["action"], $_POST["post_id"])
) {
    $post_id = intval($_POST["post_id"]);
    $action = $_POST["action"];

    if ($action === "approve") {
        $stmt = $pdo->prepare(
            "UPDATE posts SET status = 'approved' WHERE id = ?",
        );
        $stmt->execute([$post_id]);
    } elseif ($action === "reject") {
        $stmt = $pdo->prepare(
            "UPDATE posts SET status = 'rejected' WHERE id = ?",
        );
        $stmt->execute([$post_id]);
    } elseif ($action === "delete") {
        $stmt = $pdo->prepare("DELETE FROM posts WHERE id = ?");
        $stmt->execute([$post_id]);
    }
}

// Ambil semua postingan kegiatan
$stmt = $pdo->query(
    "SELECT posts.*, users.username FROM posts JOIN users ON posts.user_id = users.id ORDER BY posts.created_at DESC",
);
$posts = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Kelola Postingan Kegiatan - Admin Informatics A</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="../public/tailwind.css" rel="stylesheet">
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
        <h1 class="text-2xl font-bold text-blue-900 mb-6">Kelola Postingan Kegiatan User</h1>
        <?php if (empty($posts)): ?>
            <div class="bg-blue-50 p-4 rounded text-blue-700">Belum ada postingan kegiatan dari user.</div>
        <?php else: ?>
            <div class="overflow-x-auto">
                <table class="min-w-full border border-blue-200 rounded-lg">
                    <thead>
                        <tr class="bg-blue-100 text-blue-900">
                            <th class="py-2 px-3 border-b">Judul</th>
                            <th class="py-2 px-3 border-b">User</th>
                            <th class="py-2 px-3 border-b">Tanggal</th>
                            <th class="py-2 px-3 border-b">Status</th>
                            <th class="py-2 px-3 border-b">Gambar</th>
                            <th class="py-2 px-3 border-b">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($posts as $post): ?>
                        <tr class="border-b hover:bg-blue-50">
                            <td class="py-2 px-3"><?= htmlspecialchars(
                                $post["title"],
                            ) ?></td>
                            <td class="py-2 px-3"><?= htmlspecialchars(
                                $post["username"],
                            ) ?></td>
                            <td class="py-2 px-3"><?= htmlspecialchars(
                                $post["date"],
                            ) ?></td>
                            <td class="py-2 px-3">
                                <?php if ($post["status"] === "pending"): ?>
                                    <span class="bg-yellow-200 text-yellow-800 px-2 py-1 rounded text-xs">Pending</span>
                                <?php elseif (
                                    $post["status"] === "approved"
                                ): ?>
                                    <span class="bg-green-200 text-green-800 px-2 py-1 rounded text-xs">Approved</span>
                                <?php else: ?>
                                    <span class="bg-red-200 text-red-800 px-2 py-1 rounded text-xs">Rejected</span>
                                <?php endif; ?>
                            </td>
                            <td class="py-2 px-3">
                                <?php if ($post["image"]): ?>
                                    <img src="/<?= htmlspecialchars($post["image"]) ?>" alt="Foto Kegiatan" class="h-12 w-12 object-cover rounded">
                                <?php else: ?>
                                    <span class="text-gray-400 text-xs">-</span>
                                <?php endif; ?>
                            </td>
                            <td class="py-2 px-3">
                                <form action="" method="POST" class="flex space-x-2">
                                    <input type="hidden" name="post_id" value="<?= $post[
                                        "id"
                                    ] ?>">
                                    <?php if ($post["status"] === "pending"): ?>
                                        <button type="submit" name="action" value="approve" class="bg-green-700 text-white px-2 py-1 rounded hover:bg-green-800 text-xs">Approve</button>
                                        <button type="submit" name="action" value="reject" class="bg-yellow-700 text-white px-2 py-1 rounded hover:bg-yellow-800 text-xs">Reject</button>
                                    <?php endif; ?>
                                    <button type="submit" name="action" value="delete" class="bg-red-700 text-white px-2 py-1 rounded hover:bg-red-800 text-xs" onclick="return confirm('Hapus postingan ini?')">Delete</button>
                                </form>
                            </td>
                        </tr>
                        <tr>
                            <td colspan="6" class="px-3 pb-4 text-blue-900 text-sm">
                                <strong>Deskripsi:</strong> <?= nl2br(
                                    htmlspecialchars($post["description"]),
                                ) ?>
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
