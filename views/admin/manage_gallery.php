<?php
// informatics_a/admin/manage_gallery.php
require_once __DIR__ . "/../src/helpers/session.php";
require_once __DIR__ . "/../src/config/db.php";

// Proteksi: hanya admin yang bisa akses
if (!isLoggedIn() || !isAdmin()) {
    header("Location: /login.php");
    exit();
}

// Handle aksi approve, delete
if (
    $_SERVER["REQUEST_METHOD"] === "POST" &&
    isset($_POST["action"], $_POST["gallery_id"])
) {
    $gallery_id = intval($_POST["gallery_id"]);
    $action = $_POST["action"];

    if ($action === "approve") {
        $stmt = $pdo->prepare(
            "UPDATE gallery SET status = 'approved' WHERE id = ?",
        );
        $stmt->execute([$gallery_id]);
    } elseif ($action === "delete") {
        // Hapus file gambar dari server
        $stmt = $pdo->prepare("SELECT image FROM gallery WHERE id = ?");
        $stmt->execute([$gallery_id]);
        $row = $stmt->fetch();
        if (
            $row &&
            !empty($row["image"]) &&
            file_exists("../" . $row["image"])
        ) {
            unlink("../" . $row["image"]);
        }
        // Hapus dari database
        $stmt = $pdo->prepare("DELETE FROM gallery WHERE id = ?");
        $stmt->execute([$gallery_id]);
    }
}

// Ambil semua foto galeri
$stmt = $pdo->query(
    "SELECT gallery.*, users.username FROM gallery LEFT JOIN users ON gallery.uploaded_by = users.id ORDER BY gallery.uploaded_at DESC",
);
$gallery = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Kelola Galeri Foto - Admin Informatics A</title>
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
        <h1 class="text-2xl font-bold text-blue-900 mb-6">Kelola Galeri Foto</h1>
        <?php if (empty($gallery)): ?>
            <div class="bg-blue-50 p-4 rounded text-blue-700">Belum ada foto di galeri.</div>
        <?php else: ?>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                <?php foreach ($gallery as $item): ?>
                    <div class="bg-blue-50 rounded-lg shadow p-4 flex flex-col">
                        <?php if ($item["image"]): ?>
                            <img src="/<?= htmlspecialchars($item["image"]) ?>" alt="Foto Galeri" class="h-48 w-full object-cover rounded mb-3">
                        <?php endif; ?>
                        <div class="mb-2">
                            <strong class="text-blue-900">Caption:</strong>
                            <span class="text-blue-800"><?= htmlspecialchars(
                                $item["caption"],
                            ) ?></span>
                        </div>
                        <div class="mb-2 text-sm text-blue-700">
                            <strong>Uploader:</strong> <?= htmlspecialchars(
                                $item["username"] ?? "Admin",
                            ) ?>
                        </div>
                        <div class="mb-2 text-sm text-blue-700">
                            <strong>Tanggal Upload:</strong> <?= htmlspecialchars(
                                date(
                                    "d M Y H:i",
                                    strtotime($item["uploaded_at"]),
                                ),
                            ) ?>
                        </div>
                        <div class="mb-2 text-sm">
                            <strong>Status:</strong>
                            <?php if (
                                isset($item["status"]) &&
                                $item["status"] === "approved"
                            ): ?>
                                <span class="bg-green-200 text-green-800 px-2 py-1 rounded text-xs">Approved</span>
                            <?php else: ?>
                                <span class="bg-yellow-200 text-yellow-800 px-2 py-1 rounded text-xs">Pending</span>
                            <?php endif; ?>
                        </div>
                        <form action="" method="POST" class="flex space-x-2 mt-auto">
                            <input type="hidden" name="gallery_id" value="<?= $item[
                                "id"
                            ] ?>">
                            <?php if (
                                !isset($item["status"]) ||
                                $item["status"] !== "approved"
                            ): ?>
                                <button type="submit" name="action" value="approve" class="bg-green-700 text-white px-2 py-1 rounded hover:bg-green-800 text-xs">Approve</button>
                            <?php endif; ?>
                            <button type="submit" name="action" value="delete" class="bg-red-700 text-white px-2 py-1 rounded hover:bg-red-800 text-xs" onclick="return confirm('Hapus foto ini?')">Delete</button>
                        </form>
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
