<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
// informatics_a/post_kegiatan.php
session_start();
require_once __DIR__ . "/../../src/helpers/session.php";
require_once __DIR__ . "/../../src/config/db.php";
require_once __DIR__ . "/../../src/config/urls.php";

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

// Proteksi: user dan korti yang bisa akses (bukan admin)
if (!isLoggedIn() || isAdmin()) {
    header("Location: " . url('login'));
    exit();
}

$user = getCurrentUser();
$error = null;
$success = null;

// Proses submit form
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $title = trim($_POST["title"] ?? "");
    $content = trim($_POST["content"] ?? "");
    $date = $_POST["date"] ?? "";
    $image_paths = [];
    $thumbnail_image = null;

    // Validasi
    if (!$title || !$content || !$date) {
        $error = "Semua field wajib diisi.";
    } elseif (strlen($title) < 5) {
        $error = "Judul minimal 5 karakter.";
    } elseif (strlen($content) < 10) {
        $error = "Deskripsi minimal 10 karakter.";
    } else {
        // Handle multiple image uploads
        if (isset($_FILES["images"]) && !empty($_FILES["images"]["name"][0])) {
            $upload_dir = __DIR__ . "/../../public/uploads/";
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }

            $allowed = ["jpg", "jpeg", "png", "gif"];
            $max_files = 30; // Meningkatkan batas maksimal file menjadi 30

            for ($i = 0; $i < count($_FILES["images"]["name"]) && $i < $max_files; $i++) {
                if ($_FILES["images"]["error"][$i] === UPLOAD_ERR_OK) {
                    $img_name = $_FILES["images"]["name"][$i];
                    $img_tmp = $_FILES["images"]["tmp_name"][$i];
                    $img_ext = strtolower(pathinfo($img_name, PATHINFO_EXTENSION));

                    if (in_array($img_ext, $allowed)) {
                        $new_name = uniqid("kegiatan_" . $i . "_", true) . "." . $img_ext;
                        $dest_path = $upload_dir . $new_name;

                        if (move_uploaded_file($img_tmp, $dest_path)) {
                            $image_paths[] = $new_name;
                            // Set first image as thumbnail
                            if ($thumbnail_image === null) {
                                $thumbnail_image = $new_name;
                            }
                        }
                    }
                }
            }

            if (empty($image_paths)) {
                $error = "Gagal upload gambar. Pastikan format file benar.";
            }
        }
    }

    // Jika tidak ada error, simpan ke database
    if (!$error) {
        // Korti langsung approved, user biasa pending
        $status = isKorti() ? 'approved' : 'pending';

        try {
            $pdo->beginTransaction();

            // Insert post
            $stmt = $pdo->prepare(
                "INSERT INTO posts (user_id, title, content, date, status, thumbnail_image) VALUES (?, ?, ?, ?, ?, ?)"
            );
            $result = $stmt->execute([
                $user["id"],
                $title,
                $content,
                $date,
                $status,
                $thumbnail_image
            ]);

            if (!$result) {
                throw new Exception("Gagal menyimpan postingan");
            }

            $post_id = $pdo->lastInsertId();

            // Insert images if any
            if (!empty($image_paths)) {
                $stmt = $pdo->prepare(
                    "INSERT INTO post_images (post_id, image_path, image_order) VALUES (?, ?, ?)"
                );
                foreach ($image_paths as $index => $image_path) {
                    $stmt->execute([$post_id, $image_path, $index]);
                }
            }

            $pdo->commit();

            if (isKorti()) {
                $success = "Posting kegiatan berhasil dipublikasikan!";
            } else {
                $success = "Posting kegiatan berhasil dikirim! Menunggu persetujuan admin/korti.";
            }

        } catch (Exception $e) {
            $pdo->rollBack();
            $error = "Gagal menyimpan data ke database: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Posting Kegiatan Baru - Informatics A</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="<?= url('public/tailwind.css') ?>" rel="stylesheet">
</head>
<body class="min-h-screen" style="background: linear-gradient(135deg, <?= $primary_color ?>10 0%, <?= $secondary_color ?>10 100%);">
    
    <?php include __DIR__ . '/../../includes/navbar.php'; ?>
    
    <main class="max-w-3xl mx-auto px-6 py-10">
        <div class="bg-white rounded-2xl shadow-lg p-8">
            <div class="mb-6">
                <h1 class="text-3xl font-bold mb-2" style="background: linear-gradient(135deg, <?= $primary_color ?> 0%, <?= $secondary_color ?> 100%); -webkit-background-clip: text; background-clip: text; color: transparent;">Posting Kegiatan Baru</h1>
                <p class="text-gray-600">
                    <?php if (isKorti()): ?>
                        Buat postingan kegiatan kelas. Postingan Anda akan langsung dipublikasikan.
                    <?php else: ?>
                        Isi form di bawah untuk memposting kegiatan kelas. Postingan akan menunggu persetujuan admin/korti.
                    <?php endif; ?>
                </p>
            </div>
            <?php if ($error): ?>
                <div class="mb-6 p-4 bg-red-50 border-l-4 border-red-500 rounded-lg">
                    <div class="flex items-center gap-2">
                        <svg class="w-5 h-5 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        <span class="text-red-700 font-medium"><?= htmlspecialchars($error) ?></span>
                    </div>
                </div>
            <?php endif; ?>
            <?php if ($success): ?>
                <div class="mb-6 p-4 bg-green-50 border-l-4 border-green-500 rounded-lg">
                    <div class="flex items-center gap-2">
                        <svg class="w-5 h-5 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        <span class="text-green-700 font-medium"><?= htmlspecialchars($success) ?></span>
                    </div>
                </div>
            <?php endif; ?>
            <form action="" method="POST" enctype="multipart/form-data" class="space-y-6">
                <div>
                    <label for="title" class="block text-gray-700 font-semibold mb-2">Judul Kegiatan <span class="text-red-500">*</span></label>
                    <input type="text" id="title" name="title" required class="w-full px-4 py-3 border-2 border-gray-300 rounded-xl focus:outline-none transition" style="focus:border-color: <?= $primary_color ?>; focus:ring-color: <?= $primary_color ?>20;" placeholder="Contoh: Workshop Web Development" value="<?= htmlspecialchars($_POST["title"] ?? "") ?>">
                    <p class="text-xs text-gray-500 mt-1">Minimal 5 karakter</p>
                </div>
                
                <div>
                    <label for="content" class="block text-gray-700 font-semibold mb-2">Deskripsi Kegiatan <span class="text-red-500">*</span></label>
                    <textarea id="content" name="content" required rows="5" class="w-full px-4 py-3 border-2 border-gray-300 rounded-xl focus:outline-none transition resize-none" style="focus:border-color: <?= $primary_color ?>; focus:ring-color: <?= $primary_color ?>20;" placeholder="Ceritakan detail kegiatan..."><?= htmlspecialchars($_POST["content"] ?? "") ?></textarea>
                    <p class="text-xs text-gray-500 mt-1">Minimal 10 karakter</p>
                </div>
                
                <div>
                    <label for="date" class="block text-gray-700 font-semibold mb-2">Tanggal Kegiatan <span class="text-red-500">*</span></label>
                    <input type="date" id="date" name="date" required class="w-full px-4 py-3 border-2 border-gray-300 rounded-xl focus:outline-none transition" style="focus:border-color: <?= $primary_color ?>; focus:ring-color: <?= $primary_color ?>20;" value="<?= htmlspecialchars($_POST["date"] ?? "") ?>">
                </div>
                
                <div>
                    <label for="images" class="block text-gray-700 font-semibold mb-2">Upload Foto (opsional)</label>
                    <input type="file" id="images" name="images[]" accept="image/*" multiple class="w-full px-4 py-3 border-2 border-gray-300 rounded-xl focus:outline-none transition" style="focus:border-color: <?= $primary_color ?>; focus:ring-color: <?= $primary_color ?>20;">
                    <p class="text-xs text-gray-500 mt-1">Format: JPG, JPEG, PNG, atau GIF. Maksimal 5 foto.</p>
                    <div id="image-preview" class="mt-3 grid grid-cols-2 sm:grid-cols-3 gap-3"></div>
                </div>
                
                <button type="submit" class="w-full text-white font-bold py-3 rounded-xl transition shadow-lg flex items-center justify-center gap-2" style="background: linear-gradient(135deg, <?= $primary_color ?> 0%, <?= $secondary_color ?> 100%);" onmouseover="this.style.background='linear-gradient(135deg, <?= $secondary_color ?> 0%, <?= $primary_color ?> 100%)'" onmouseout="this.style.background='linear-gradient(135deg, <?= $primary_color ?> 0%, <?= $secondary_color ?> 100%)'">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/>
                    </svg>
                    Kirim Kegiatan
                </button>
            </form>
            
            <div class="mt-6 text-center">
                <a href="<?= isKorti() ? url('korti') : url('dashboard') ?>" class="inline-flex items-center gap-2 font-semibold transition" style="color: <?= $primary_color ?>;" onmouseover="this.style.color='<?= $secondary_color ?>'" onmouseout="this.style.color='<?= $primary_color ?>'">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                    </svg>
                    Kembali ke Dashboard
                </a>
            </div>
        </div>
    </main>
    
    <?php include __DIR__ . '/../../includes/footer.php'; ?>

    <script>
        // Image preview functionality
        document.getElementById('images').addEventListener('change', function(e) {
            const preview = document.getElementById('image-preview');
            preview.innerHTML = '';

            const files = Array.from(e.target.files);
            if (files.length > 30) {
                alert('Maksimal 30 foto yang dapat diupload');
                e.target.value = '';
                return;
            }

            files.forEach((file, index) => {
                if (file && file.type.startsWith('image/')) {
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        const div = document.createElement('div');
                        div.className = 'relative group';
                        div.innerHTML = `
                            <img src="${e.target.result}" class="w-full h-24 object-cover rounded-lg border-2 border-gray-200">
                            <button type="button" onclick="removePreview(${index})"
                                class="absolute -top-2 -right-2 bg-red-500 text-white rounded-full w-6 h-6 flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity">
                                ×
                            </button>
                            <div class="absolute bottom-0 left-0 right-0 bg-black bg-opacity-50 text-white text-xs p-1 rounded-b-lg">
                                ${file.name.length > 15 ? file.name.substring(0, 15) + '...' : file.name}
                            </div>
                        `;
                        preview.appendChild(div);
                    };
                    reader.readAsDataURL(file);
                }
            });
        });

        function removePreview(index) {
            const input = document.getElementById('images');
            const dt = new DataTransfer();

            const files = Array.from(input.files);
            files.splice(index, 1);

            files.forEach(file => dt.items.add(file));
            input.files = dt.files;

            // Trigger change event to refresh preview
            input.dispatchEvent(new Event('change'));
        }

        // Form validation
        document.querySelector('form').addEventListener('submit', function(e) {
            const images = document.getElementById('images').files;
            if (images.length > 30) {
                e.preventDefault();
                alert('Maksimal 30 foto yang dapat diupload');
                return false;
            }

            // Show loading state
            const submitBtn = e.target.querySelector('button[type="submit"]');
            const originalText = submitBtn.innerHTML;
            submitBtn.innerHTML = `
                <svg class="w-5 h-5 animate-spin" fill="none" viewBox="0 0 24 24">
                    <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" class="opacity-25"></circle>
                    <path fill="currentColor" class="opacity-75" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                Mengirim...
            `;
            submitBtn.disabled = true;
        });
    </script>
</body>
</html>
