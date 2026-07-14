<?php
// informatics_a/komentar.php
require_once __DIR__ . "/src/helpers/session.php";
require_once __DIR__ . "/src/config/db.php";

// Proteksi: hanya user login yang bisa komentar
if (!isLoggedIn() || !isUser()) {
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

// Ambil ID postingan dari GET
$post_id = isset($_GET["post_id"]) ? intval($_GET["post_id"]) : 0;
if ($post_id <= 0) {
    die("Postingan tidak ditemukan.");
}

// Ambil detail postingan
$stmt = $pdo->prepare(
    "SELECT posts.*, users.username FROM posts JOIN users ON posts.user_id = users.id WHERE posts.id = ?",
);
$stmt->execute([$post_id]);
$post = $stmt->fetch();
if (!$post) {
    die("Postingan tidak ditemukan.");
}

// Proses submit komentar
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["comment"])) {
    $comment = trim($_POST["comment"]);
    if (strlen($comment) < 3) {
        $error = "Komentar minimal 3 karakter.";
    } else {
        // Simpan komentar ke database
        $stmt = $pdo->prepare(
            "INSERT INTO comments (post_id, user_id, comment, created_at) VALUES (?, ?, ?, NOW())",
        );
        if ($stmt->execute([$post_id, $user["id"], $comment])) {
            $success = "Komentar berhasil dikirim!";
        } else {
            $error = "Gagal menyimpan komentar.";
        }
    }
}

// Ambil semua komentar untuk postingan ini
$stmt = $pdo->prepare(
    "SELECT comments.*, users.username FROM comments JOIN users ON comments.user_id = users.id WHERE comments.post_id = ? ORDER BY comments.created_at ASC",
);
$stmt->execute([$post_id]);
$comments = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Komentar Kegiatan - Informatics A</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="<?= url('public/tailwind.css') ?>" rel="stylesheet">
</head>
<body class="min-h-screen" style="background: linear-gradient(135deg, <?= $primary_color ?>10 0%, <?= $secondary_color ?>10 100%);">
    <nav class="text-white px-6 py-4 flex justify-between items-center" style="background: linear-gradient(135deg, <?= $primary_color ?> 0%, <?= $secondary_color ?> 100%);">
        <div class="font-bold text-xl">Informatics A</div>
        <div>
            <span class="mr-4">Halo, <?= htmlspecialchars(
                $user["username"],
            ) ?></span>
            <a href="<?= url('dashboard.php') ?>" class="mr-4 hover:underline">Dashboard</a>
            <a href="<?= url('logout.php') ?>" class="bg-red-600 px-3 py-1 rounded hover:bg-red-700">Logout</a>
        </div>
    </nav>
    <main class="max-w-2xl mx-auto mt-10 bg-white rounded-lg shadow-lg p-8">
        <h1 class="text-2xl font-bold mb-4" style="color: <?= $primary_color ?>;">Komentar Kegiatan</h1>
        <section class="mb-8">
            <h2 class="text-lg font-bold mb-2" style="color: <?= $primary_color ?>;"><?= htmlspecialchars(
                $post["title"],
            ) ?></h2>
            <div class="mb-2" style="color: <?= $primary_color ?>; font-weight: 500;"><?= nl2br(
                htmlspecialchars($post["description"]),
            ) ?></div>
            <?php if ($post["image"]): ?>
                <img src="<?= htmlspecialchars(
                    $post["image"],
                ) ?>" alt="Foto Kegiatan" class="h-48 w-full object-cover rounded mb-3">
            <?php endif; ?>
            <div class="text-sm" style="color: <?= $primary_color ?>;">
                <strong>Tanggal:</strong> <?= htmlspecialchars(
                    date("d M Y", strtotime($post["date"])),
                ) ?> |
                <strong>Oleh:</strong> <?= htmlspecialchars(
                    $post["username"],
                ) ?>
            </div>
        </section>
        <section class="mb-8">
            <h2 class="text-lg font-bold mb-2" style="color: <?= $primary_color ?>;">Tulis Komentar</h2>
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
            <form action="" method="POST" class="space-y-4">
                <textarea name="comment" rows="3" required class="w-full px-3 py-2 border rounded focus:outline-none transition" style="border-color: <?= $primary_color ?>; focus:border-color: <?= $primary_color ?>;" placeholder="Tulis komentar..."><?= htmlspecialchars(
                    $_POST["comment"] ?? "",
                ) ?></textarea>
                <button type="submit" class="text-white font-bold py-2 px-6 rounded transition" style="background: linear-gradient(135deg, <?= $primary_color ?> 0%, <?= $secondary_color ?> 100%);" onmouseover="this.style.background='linear-gradient(135deg, <?= $secondary_color ?> 0%, <?= $primary_color ?> 100%)'" onmouseout="this.style.background='linear-gradient(135deg, <?= $primary_color ?> 0%, <?= $secondary_color ?> 100%)'">Kirim Komentar</button>
            </form>
        </section>
        <section>
            <h2 class="text-lg font-bold mb-2" style="color: <?= $primary_color ?>;">Komentar</h2>
            <?php if (empty($comments)): ?>
                <div class="p-4 rounded text-center" style="background: linear-gradient(135deg, <?= $primary_color ?>10 0%, <?= $secondary_color ?>10 100%); color: <?= $primary_color ?>;">Belum ada komentar.</div>
            <?php else: ?>
                <ul class="space-y-4">
                    <?php foreach ($comments as $c): ?>
                        <li class="rounded p-3" style="background: linear-gradient(135deg, <?= $primary_color ?>10 0%, <?= $secondary_color ?>10 100%);">
                            <div class="font-semibold" style="color: <?= $primary_color ?>;"><?= htmlspecialchars(
                                $c["username"],
                            ) ?></div>
                            <div class="mb-1" style="color: <?= $primary_color ?>; font-weight: 500;"><?= nl2br(
                                htmlspecialchars($c["comment"]),
                            ) ?></div>
                            <div class="text-xs" style="color: <?= $primary_color ?>; opacity: 0.8;"><?= date(
                                "d M Y H:i",
                                strtotime($c["created_at"]),
                            ) ?></div>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </section>
        <div class="mt-8 text-center">
            <a href="/dashboard.php" class="font-semibold hover:underline" style="color: <?= $primary_color ?>;" onmouseover="this.style.color='<?= $secondary_color ?>'" onmouseout="this.style.color='<?= $primary_color ?>'">&larr; Kembali ke Dashboard</a>
        </div>
    </main>
    <footer class="mt-16 py-6 text-center bg-blue-800" style="background: linear-gradient(135deg, <?= $primary_color ?> 0%, <?= $secondary_color ?> 100%); color: white;">
        &copy; <?= date("Y") ?> Informatics A. All rights reserved.
    </footer>
</body>
</html>
