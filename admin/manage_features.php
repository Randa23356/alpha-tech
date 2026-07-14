<?php
ini_set("display_errors", 1);
ini_set("display_startup_errors", 1);
error_reporting(E_ALL);
// informatics_a/admin/manage_features.php
session_start();
require_once __DIR__ . "/../src/helpers/session.php";
require_once __DIR__ . "/../src/config/db.php";
require_once __DIR__ . "/../src/config/urls.php";

// Cek apakah user adalah admin
if (!isLoggedIn() || !isAdmin()) {
    header("Location: " . url('login'));
    exit();
}

$success_message = "";
$error_message = "";

// Handle Add About Feature
if (
    $_SERVER["REQUEST_METHOD"] === "POST" &&
    isset($_POST["add_about_feature"])
) {
    $feature_text = trim($_POST["feature_text"] ?? "");
    $display_order = intval($_POST["display_order"] ?? 0);

    if (!empty($feature_text)) {
        $stmt = $pdo->prepare(
            "INSERT INTO about_features (feature_text, display_order) VALUES (?, ?)",
        );
        if ($stmt->execute([$feature_text, $display_order])) {
            $success_message = "Fitur berhasil ditambahkan!";
        } else {
            $error_message = "Gagal menambahkan fitur.";
        }
    }
}

// Handle Delete About Feature
if (isset($_GET["delete_about"]) && is_numeric($_GET["delete_about"])) {
    $stmt = $pdo->prepare("DELETE FROM about_features WHERE id = ?");
    if ($stmt->execute([$_GET["delete_about"]])) {
        $success_message = "Fitur berhasil dihapus!";
    }
}

// Handle Toggle About Feature Status
if (isset($_GET["toggle_about"]) && is_numeric($_GET["toggle_about"])) {
    $stmt = $pdo->prepare(
        "UPDATE about_features SET is_active = NOT is_active WHERE id = ?",
    );
    if ($stmt->execute([$_GET["toggle_about"]])) {
        $success_message = "Status fitur berhasil diubah!";
    }
}

// Handle Add Platform Feature
if (
    $_SERVER["REQUEST_METHOD"] === "POST" &&
    isset($_POST["add_platform_feature"])
) {
    $title = trim($_POST["title"] ?? "");
    $description = trim($_POST["description"] ?? "");
    $icon_name = trim($_POST["icon_name"] ?? "default");
    $display_order = intval($_POST["display_order"] ?? 0);

    if (!empty($title) && !empty($description)) {
        $stmt = $pdo->prepare(
            "INSERT INTO platform_features (title, description, icon_name, display_order) VALUES (?, ?, ?, ?)",
        );
        if (
            $stmt->execute([$title, $description, $icon_name, $display_order])
        ) {
            $success_message = "Fitur platform berhasil ditambahkan!";
        } else {
            $error_message = "Gagal menambahkan fitur platform.";
        }
    }
}

// Handle Delete Platform Feature
if (isset($_GET["delete_platform"]) && is_numeric($_GET["delete_platform"])) {
    $stmt = $pdo->prepare("DELETE FROM platform_features WHERE id = ?");
    if ($stmt->execute([$_GET["delete_platform"]])) {
        $success_message = "Fitur platform berhasil dihapus!";
    }
}

// Handle Toggle Platform Feature Status
if (isset($_GET["toggle_platform"]) && is_numeric($_GET["toggle_platform"])) {
    $stmt = $pdo->prepare(
        "UPDATE platform_features SET is_active = NOT is_active WHERE id = ?",
    );
    if ($stmt->execute([$_GET["toggle_platform"]])) {
        $success_message = "Status fitur platform berhasil diubah!";
    }
}

// Handle Update About Feature
if (
    $_SERVER["REQUEST_METHOD"] === "POST" &&
    isset($_POST["update_about_feature"])
) {
    $id = intval($_POST["id"]);
    $feature_text = trim($_POST["feature_text"] ?? "");
    $display_order = intval($_POST["display_order"] ?? 0);

    if (!empty($feature_text)) {
        $stmt = $pdo->prepare(
            "UPDATE about_features SET feature_text = ?, display_order = ? WHERE id = ?",
        );
        if ($stmt->execute([$feature_text, $display_order, $id])) {
            $success_message = "Fitur berhasil diupdate!";
        }
    }
}

// Handle Update Platform Feature
if (
    $_SERVER["REQUEST_METHOD"] === "POST" &&
    isset($_POST["update_platform_feature"])
) {
    $id = intval($_POST["id"]);
    $title = trim($_POST["title"] ?? "");
    $description = trim($_POST["description"] ?? "");
    $icon_name = trim($_POST["icon_name"] ?? "default");
    $display_order = intval($_POST["display_order"] ?? 0);

    if (!empty($title) && !empty($description)) {
        $stmt = $pdo->prepare(
            "UPDATE platform_features SET title = ?, description = ?, icon_name = ?, display_order = ? WHERE id = ?",
        );
        if (
            $stmt->execute([
                $title,
                $description,
                $icon_name,
                $display_order,
                $id,
            ])
        ) {
            $success_message = "Fitur platform berhasil diupdate!";
        }
    }
}

// Fetch About Features
$stmt = $pdo->query(
    "SELECT * FROM about_features ORDER BY display_order ASC, id ASC",
);
$about_features = $stmt->fetchAll();

// Fetch Platform Features
$stmt = $pdo->query(
    "SELECT * FROM platform_features ORDER BY display_order ASC, id ASC",
);
$platform_features = $stmt->fetchAll();

$current_user = getCurrentUser();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Fitur - Admin Panel</title>
    <link href=" <?= asset('tailwind.css') ?>" rel="stylesheet">
    <?php require_once __DIR__ . '/../includes/favicon.php'; ?>
</head>
<body class="bg-gray-50">
<?php include __DIR__ . "/../includes/navbar.php"; ?>
<?php include __DIR__ . "/sidebar.php"; ?>

        <!-- Sidebar -->


        <!-- Main Content -->
        <main class="lg:ml-64 px-4 sm:px-6 md:px-8 -mt-8 mb-12 max-w-7xl mx-auto">
  <div class="max-w-6xl mx-auto">
    <h1 class="text-3xl font-bold text-gray-800 mb-8">Kelola Fitur Platform</h1>

    <?php if ($success_message): ?>
      <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded-lg mb-6">
        <?= htmlspecialchars($success_message) ?>
      </div>
    <?php endif; ?>

    <?php if ($error_message): ?>
      <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-lg mb-6">
        <?= htmlspecialchars($error_message) ?>
      </div>
    <?php endif; ?>

    <!-- About Features Section -->
    <section class="mb-12">
      <h2 class="text-2xl font-bold text-blue-900 mb-6">Kenapa Memilih Platform Ini?</h2>

      <!-- Add Form -->
      <form method="POST" class="mb-6 bg-blue-50 p-6 rounded-lg">
        <h3 class="font-bold text-gray-800 mb-4">Tambah Fitur Baru</h3>
        <div class="flex flex-col sm:flex-row sm:items-center sm:space-x-4 gap-4">
          <input type="text" name="feature_text" placeholder="Teks fitur..." required
            class="flex-1 px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
          <input type="number" name="display_order" placeholder="Urutan" value="0"
            class="w-24 px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
          <button type="submit" name="add_about_feature"
            class="bg-blue-900 text-white px-6 py-2 rounded-lg hover:bg-blue-800 transition font-semibold whitespace-nowrap">Tambah</button>
        </div>
      </form>

      <!-- List -->
      <div class="space-y-4">
        <?php foreach ($about_features as $feature): ?>
          <div
            class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 p-4 rounded-lg border <?= $feature[
                "is_active"
            ]
                ? "bg-white border-gray-300"
                : "bg-gray-100 border-gray-200" ?>">
            <div class="flex items-center gap-3 flex-1 flex-wrap">
              <span
                class="bg-blue-100 text-blue-900 px-3 py-1 rounded-full text-sm font-semibold shrink-0">#<?= $feature[
                    "display_order"
                ] ?></span>
              <form method="POST" class="flex flex-col sm:flex-row sm:items-center gap-2 flex-1 min-w-0">
                <input type="hidden" name="id" value="<?= $feature["id"] ?>">
                <input type="text" name="feature_text" value="<?= htmlspecialchars(
                    $feature["feature_text"],
                ) ?>"
                  class="flex-1 px-3 py-1 border border-gray-300 rounded min-w-0" required>
                <input type="number" name="display_order" value="<?= $feature[
                    "display_order"
                ] ?>"
                  class="w-20 px-3 py-1 border border-gray-300 rounded" required>
                <button type="submit" name="update_about_feature"
                  class="bg-green-600 text-white px-4 py-1 rounded hover:bg-green-700 text-sm whitespace-nowrap">Update</button>
              </form>
            </div>
            <div class="flex gap-2">
              <a href="?toggle_about=<?= $feature["id"] ?>"
                class="<?= $feature["is_active"]
                    ? "bg-yellow-500 hover:bg-yellow-600"
                    : "bg-green-500 hover:bg-green-600" ?> text-white px-3 py-1 rounded text-sm whitespace-nowrap">
                <?= $feature["is_active"] ? "Nonaktifkan" : "Aktifkan" ?>
              </a>
              <a href="?delete_about=<?= $feature[
                  "id"
              ] ?>" onclick="return confirm('Yakin ingin menghapus?')"
                class="bg-red-600 hover:bg-red-700 text-white px-3 py-1 rounded text-sm whitespace-nowrap">Hapus</a>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    </section>

    <!-- Platform Features Section -->
    <section class="bg-white rounded-xl shadow-md p-6 md:p-8">
      <h2 class="text-2xl font-bold text-blue-900 mb-6">Fitur Platform (Detail)</h2>

      <!-- Add Form -->
      <form method="POST" class="mb-6 bg-blue-50 p-6 rounded-lg space-y-4">
        <h3 class="font-bold text-gray-800 mb-4">Tambah Fitur Platform Baru</h3>
        <div class="flex flex-col md:flex-row md:items-center md:space-x-4 gap-4">
          <input type="text" name="title" placeholder="Judul fitur..." required
            class="flex-1 px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
          <select name="icon_name"
            class="w-full md:w-48 px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
            <option value="document">Document</option>
            <option value="photo">Photo</option>
            <option value="chat">Chat</option>
            <option value="announcement">Announcement</option>
            <option value="default">Default</option>
          </select>
          <input type="number" name="display_order" placeholder="Urutan" value="0"
            class="w-24 px-4 py-2 border border-gray-300 rounded-lg">
        </div>
        <textarea name="description" rows="2" placeholder="Deskripsi fitur..." required
          class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 resize-none"></textarea>
        <button type="submit" name="add_platform_feature"
          class="bg-blue-900 text-white px-6 py-2 rounded-lg hover:bg-blue-800 transition font-semibold w-full md:w-auto">Tambah Fitur Platform</button>
      </form>

      <!-- List -->
      <div class="space-y-4">
        <?php foreach ($platform_features as $feature): ?>
          <div
            class="border border-gray-200 rounded-lg p-4 <?= $feature[
                "is_active"
            ]
                ? "bg-white"
                : "bg-gray-100" ?>">
            <form method="POST" class="space-y-3">
              <input type="hidden" name="id" value="<?= $feature["id"] ?>">
              <div class="flex flex-col sm:flex-row sm:items-center sm:gap-4 flex-wrap">
                <span
                  class="bg-blue-100 text-blue-900 px-3 py-1 rounded-full text-sm font-semibold shrink-0">#<?= $feature[
                      "display_order"
                  ] ?></span>
                <input type="text" name="title" value="<?= htmlspecialchars(
                    $feature["title"],
                ) ?>"
                  class="flex-1 px-3 py-2 border border-gray-300 rounded font-semibold min-w-0" required>
                <select name="icon_name"
                  class="px-3 py-2 border border-gray-300 rounded w-full sm:w-auto shrink-0">
                  <option value="document" <?= $feature["icon_name"] ==
                  "document"
                      ? "selected"
                      : "" ?>>Document
                  </option>
                  <option value="photo" <?= $feature["icon_name"] == "photo"
                      ? "selected"
                      : "" ?>>Photo</option>
                  <option value="chat" <?= $feature["icon_name"] == "chat"
                      ? "selected"
                      : "" ?>>Chat</option>
                  <option value="announcement" <?= $feature["icon_name"] ==
                  "announcement"
                      ? "selected"
                      : "" ?>>Announcement
                  </option>
                  <option value="default" <?= $feature["icon_name"] == "default"
                      ? "selected"
                      : "" ?>>Default</option>
                </select>
                <input type="number" name="display_order" value="<?= $feature[
                    "display_order"
                ] ?>"
                  class="w-20 px-3 py-2 border border-gray-300 rounded shrink-0" required>
              </div>
              <textarea name="description" rows="2"
                class="w-full px-3 py-2 border border-gray-300 rounded resize-none"><?= htmlspecialchars(
                    $feature["description"],
                ) ?></textarea>
              <div class="flex flex-col sm:flex-row gap-2">
                <button type="submit" name="update_platform_feature"
                  class="bg-green-600 text-white px-4 py-2 rounded hover:bg-green-700 flex-1 sm:flex-none whitespace-nowrap">Update</button>
                <a href="?toggle_platform=<?= $feature["id"] ?>"
                  class="<?= $feature["is_active"]
                      ? "bg-yellow-500 hover:bg-yellow-600"
                      : "bg-green-500 hover:bg-green-600" ?> text-white px-4 py-2 rounded flex-1 sm:flex-none text-center whitespace-nowrap">
                  <?= $feature["is_active"] ? "Nonaktifkan" : "Aktifkan" ?>
                </a>
                <a href="?delete_platform=<?= $feature[
                    "id"
                ] ?>" onclick="return confirm('Yakin ingin menghapus?')"
                  class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded flex-1 sm:flex-none text-center whitespace-nowrap">Hapus</a>
              </div>
            </form>
          </div>
        <?php endforeach; ?>
      </div>
    </section>
  </div>
</main>

        <main class="lg:ml-64 px-6 -mt-8 mb-12 max-w-7xl">
</body>
</html>
