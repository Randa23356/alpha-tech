<?php
// informatics_a/admin/edit_content.php
require_once __DIR__ . "/../src/helpers/session.php";
require_once __DIR__ . "/../src/config/db.php";

// Proteksi: hanya admin yang bisa akses
if (!isLoggedIn() || !isAdmin()) {
    header("Location: /login.php");
    exit();
}

// Section yang bisa diedit
$sections = [
    "header" => "Header",
    "footer" => "Footer",
    "hero" => "Hero Section",
    "about" => "Tentang Kelas",
    "contact" => "Kontak",
];

// Handle update konten
$success = null;
$error = null;
if (
    $_SERVER["REQUEST_METHOD"] === "POST" &&
    isset($_POST["section"], $_POST["content"])
) {
    $section = $_POST["section"];
    $content = trim($_POST["content"]);

    if (!array_key_exists($section, $sections)) {
        $error = "Section tidak valid.";
    } elseif (strlen($content) < 5) {
        $error = "Konten minimal 5 karakter.";
    } else {
        // Cek apakah section sudah ada
        $stmt = $pdo->prepare("SELECT id FROM site_content WHERE section = ?");
        $stmt->execute([$section]);
        $row = $stmt->fetch();
        if ($row) {
            // Update
            $stmt = $pdo->prepare(
                "UPDATE site_content SET content = ? WHERE section = ?",
            );
            $stmt->execute([$content, $section]);
        } else {
            // Insert
            $stmt = $pdo->prepare(
                "INSERT INTO site_content (section, content) VALUES (?, ?)",
            );
            $stmt->execute([$section, $content]);
        }
        $success = "Konten untuk section <strong>{$sections[$section]}</strong> berhasil disimpan!";
    }
}

// Ambil konten yang sudah ada
$stmt = $pdo->query("SELECT section, content FROM site_content");
$existing = [];
foreach ($stmt->fetchAll() as $row) {
    $existing[$row["section"]] = $row["content"];
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Edit Konten Website - Admin Informatics A</title>
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
        <h1 class="text-2xl font-bold text-blue-900 mb-6">Edit Konten Website</h1>
        <p class="mb-6 text-blue-900">Gunakan form di bawah untuk mengubah konten pada bagian utama website seperti header, footer, hero section, dan lain-lain.</p>
        <?php if ($error): ?>
            <div class="mb-4 p-2 bg-red-100 text-red-700 rounded"><?= $error ?></div>
        <?php endif; ?>
        <?php if ($success): ?>
            <div class="mb-4 p-2 bg-green-100 text-green-700 rounded"><?= $success ?></div>
        <?php endif; ?>
        <form action="" method="POST" class="space-y-6">
            <div>
                <label for="section" class="block text-blue-900 font-semibold mb-1">Pilih Section</label>
                <select id="section" name="section" required class="w-full px-3 py-2 border border-blue-300 rounded focus:outline-none focus:ring-2 focus:ring-blue-700">
                    <?php foreach ($sections as $key => $label): ?>
                        <option value="<?= $key ?>" <?= isset(
    $_POST["section"],
) && $_POST["section"] === $key
    ? "selected"
    : "" ?>>
                            <?= $label ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label for="content" class="block text-blue-900 font-semibold mb-1">Konten</label>
                <textarea id="content" name="content" required rows="5" class="w-full px-3 py-2 border border-blue-300 rounded focus:outline-none focus:ring-2 focus:ring-blue-700"><?= htmlspecialchars(
                    $_POST["content"] ??
                        ($existing[$_POST["section"] ?? "header"] ?? ""),
                ) ?></textarea>
                <p class="text-xs text-blue-700 mt-2">Gunakan HTML sederhana jika ingin format khusus (misal: <b>&lt;br&gt;</b> untuk baris baru).</p>
            </div>
            <button type="submit" class="w-full bg-blue-900 text-white font-bold py-2 rounded hover:bg-blue-800 transition">Simpan Konten</button>
        </form>
        <div class="mt-10">
            <h2 class="text-lg font-bold text-blue-900 mb-2">Preview Konten Saat Ini</h2>
            <div class="space-y-4">
                <?php foreach ($sections as $key => $label): ?>
                    <div class="bg-blue-50 p-4 rounded">
                        <strong><?= $label ?>:</strong>
                        <div class="mt-2 text-blue-900"><?= isset(
                            $existing[$key],
                        )
                            ? $existing[$key]
                            : '<span class="text-gray-400">Belum ada konten.</span>' ?></div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        <div class="mt-8 text-center">
            <a href="dashboard.php" class="text-blue-700 font-semibold hover:underline">&larr; Kembali ke Dashboard Admin</a>
        </div>
    </main>
    <footer class="mt-16 py-6 text-center text-blue-100 bg-blue-800">
        &copy; <?= date("Y") ?> Informatics A. All rights reserved.
    </footer>
</body>
</html>
