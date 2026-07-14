<?php
// informatics_a/admin/user_detail.php
require_once __DIR__ . "/../src/helpers/helpers.php";
require_once __DIR__ . "/../src/helpers/session.php";
require_once __DIR__ . "/../src/config/db.php";

// Proteksi: hanya admin yang bisa akses
if (!isLoggedIn() || !isAdmin()) {
    header("Location: " . base_url("login"));
    exit();
}

// Ambil ID user dari query string
$user_id = isset($_GET["id"]) ? intval($_GET["id"]) : 0;
if ($user_id <= 0) {
    echo "<div style='margin:2rem auto;max-width:400px;text-align:center;background:#fff;padding:2rem;border-radius:8px;'>ID user tidak valid.</div>";
    exit();
}

// Ambil data user dari database
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

if (!$user) {
    echo "<div style='margin:2rem auto;max-width:400px;text-align:center;background:#fff;padding:2rem;border-radius:8px;'>User tidak ditemukan.</div>";
    exit();
}

// Ambil postingan user
$stmt = $pdo->prepare(
    "SELECT * FROM posts WHERE user_id = ? ORDER BY created_at DESC",
);
$stmt->execute([$user_id]);
$posts = $stmt->fetchAll();

// Ambil foto galeri user
$stmt = $pdo->prepare(
    "SELECT * FROM gallery WHERE uploaded_by = ? ORDER BY uploaded_at DESC",
);
$stmt->execute([$user_id]);
$gallery = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Detail Profil User - Admin Informatics A</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="<?= asset_url("tailwind.css") ?>" rel="stylesheet">
    <?php require_once __DIR__ . '/../includes/favicon.php'; ?>
</head>
<body class="bg-blue-900 min-h-screen">
    <nav class="bg-blue-800 text-white px-6 py-4 flex justify-between items-center">
        <div class="font-bold text-xl">Informatics A Admin</div>
        <div>
            <a href="<?= base_url(
                "admin/manage_users",
            ) ?>" class="mr-4 hover:underline">Kelola User</a>
            <a href="<?= base_url(
                "admin/dashboard",
            ) ?>" class="mr-4 hover:underline">Dashboard</a>
            <a href="<?= base_url(
                "logout",
            ) ?>" class="bg-red-600 px-3 py-1 rounded hover:bg-red-700">Logout</a>
        </div>
    </nav>
    <main class="max-w-3xl mx-auto mt-10 bg-white rounded-lg shadow-lg p-8">
        <h1 class="text-2xl font-bold text-blue-900 mb-6">Detail Profil User</h1>
        <section class="mb-8">
            <div class="flex flex-col md:flex-row items-center md:items-start gap-6">
                <div class="flex-shrink-0">
                    <div class="w-24 h-24 bg-blue-200 rounded-full flex items-center justify-center text-blue-900 font-bold text-3xl">
                        <?= strtoupper(substr($user["username"], 0, 2)) ?>
                    </div>
                </div>
                <div>
                    <div class="mb-2">
                        <span class="font-semibold text-blue-900">Username:</span>
                        <span class="text-blue-800"><?= htmlspecialchars(
                            $user["username"],
                        ) ?></span>
                    </div>
                    <div class="mb-2">
                        <span class="font-semibold text-blue-900">Email:</span>
                        <span class="text-blue-800"><?= htmlspecialchars(
                            $user["email"],
                        ) ?></span>
                    </div>
                    <div class="mb-2">
                        <span class="font-semibold text-blue-900">Role:</span>
                        <span class="text-blue-800"><?= htmlspecialchars(
                            $user["role"],
                        ) ?></span>
                    </div>
                    <div class="mb-2">
                        <span class="font-semibold text-blue-900">Tanggal Daftar:</span>
                        <span class="text-blue-800"><?= date(
                            "d M Y H:i",
                            strtotime($user["created_at"]),
                        ) ?></span>
                    </div>
                </div>
            </div>
        </section>
        <section class="mb-8">
            <h2 class="text-lg font-bold text-blue-900 mb-2">Postingan Kegiatan User</h2>
            <?php if (empty($posts)): ?>
                <div class="bg-blue-50 p-4 rounded text-blue-700">Belum ada postingan kegiatan.</div>
            <?php else: ?>
                <ul class="space-y-4">
                    <?php foreach ($posts as $post): ?>
                        <li class="bg-blue-50 p-4 rounded shadow">
                            <div class="flex justify-between items-center mb-1">
                                <span class="font-semibold text-blue-900"><?= htmlspecialchars(
                                    $post["title"],
                                ) ?></span>
                                <span class="text-xs px-2 py-1 rounded
                                    <?php if ($post["status"] === "approved") {
                                        echo "bg-green-200 text-green-800";
                                    } elseif ($post["status"] === "pending") {
                                        echo "bg-yellow-200 text-yellow-800";
                                    } else {
                                        echo "bg-red-200 text-red-800";
                                    } ?>">
                                    <?= ucfirst($post["status"]) ?>
                                </span>
                            </div>
                            <div class="text-blue-800 mb-1"><?= nl2br(
                                htmlspecialchars($post["description"]),
                            ) ?></div>
                            <div class="text-xs text-blue-700"><?= date(
                                "d M Y",
                                strtotime($post["date"]),
                            ) ?></div>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </section>
        <section>
            <h2 class="text-lg font-bold text-blue-900 mb-2">Foto Galeri User</h2>
            <?php if (empty($gallery)): ?>
                <div class="bg-blue-50 p-4 rounded text-blue-700">Belum ada foto galeri dari user ini.</div>
            <?php else: ?>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                    <?php foreach ($gallery as $item): ?>
                        <div class="bg-blue-50 rounded-lg shadow p-4 flex flex-col">
                            <img src="<?= asset_url(
                                htmlspecialchars($item["image"]),
                            ) ?>" alt="Foto Galeri" class="h-32 w-full object-cover rounded mb-2">
                            <div class="text-blue-900 font-semibold mb-1"><?= htmlspecialchars(
                                $item["caption"],
                            ) ?></div>
                            <div class="text-blue-700 text-xs"><?= date(
                                "d M Y H:i",
                                strtotime($item["uploaded_at"]),
                            ) ?></div>
..                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>
        <div class="mt-8 text-center">
            <a href="<?= base_url(
                "admin/manage_users",
            ) ?>" class="text-blue-700 font-semibold hover:underline">&larr; Kembali ke Kelola User</a>
        </div>
    </main>
    <footer class="mt-16 py-6 text-center text-blue-100 bg-blue-800">
        &copy; <?= date("Y") ?> Informatics A. All rights reserved.
    </footer>
</body>
</html>
