<?php
// korti/create_post.php
session_start();
require_once __DIR__ . "/../src/helpers/session.php";
require_once __DIR__ . "/../src/config/db.php";
require_once __DIR__ . "/../src/config/urls.php";

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

// Proteksi: hanya korti yang bisa akses
if (!isLoggedIn() || getCurrentUser()['role'] !== 'korti') {
    header("Location: " . url('login'));
    exit();
}

$korti = getCurrentUser();
$success = "";
$error = "";

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $title = trim($_POST["title"] ?? "");
    $content = trim($_POST["content"] ?? "");
    $date = $_POST["date"] ?? date("Y-m-d");
    $image_paths = [];
    $thumbnail_image = null;

    // Handle multiple image uploads
    if (isset($_FILES["images"]) && !empty($_FILES["images"]["name"][0])) {
        $upload_dir = __DIR__ . "/../public/uploads/";
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
                    $new_name = uniqid("korti_" . $i . "_", true) . "." . $img_ext;
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
    }

    if (!empty($title) && !empty($content)) {
        try {
            $pdo->beginTransaction();

            // Korti bisa langsung approve postingannya sendiri
            $stmt = $pdo->prepare("INSERT INTO posts (user_id, title, content, date, status, thumbnail_image) VALUES (?, ?, ?, ?, 'approved', ?)");
            $stmt->execute([$korti['id'], $title, $content, $date, $thumbnail_image]);

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
            $success = "Postingan berhasil dibuat dan langsung dipublikasikan!";
            
            // Send Firebase push notification
            require_once __DIR__ . '/../src/helpers/fcm_helper.php';
            notifyNewPost($post_id, $title);

            // Reset form
            $_POST = [];
        } catch (Exception $e) {
            $pdo->rollBack();
            $error = "Gagal membuat postingan: " . $e->getMessage();
        }
    } else {
        $error = "Judul dan konten wajib diisi!";
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Buat Postingan - Korti </title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="<?= url('public/tailwind.css') ?>" rel="stylesheet">
    <?php require_once __DIR__ . '/../includes/favicon.php'; ?>
    <style>
        /* Image preview styles */
        .image-preview-item {
            position: relative;
            transition: all 0.3s ease;
        }

        .image-preview-item:hover {
            transform: scale(1.05);
        }

        .remove-preview {
            position: absolute;
            top: -8px;
            right: -8px;
            background: red;
            color: white;
            border-radius: 50%;
            width: 24px;
            height: 24px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            font-size: 12px;
            font-weight: bold;
        }

        .remove-preview:hover {
            background: darkred;
        }
    </style>
</head>
<body class="min-h-screen" style="background: linear-gradient(135deg, <?= $primary_color ?>10 0%, <?= $secondary_color ?>10 100%);">
    <?php include __DIR__ . '/../includes/navbar.php'; ?>
    
    <?php include __DIR__ . '/../includes/korti_sidebar.php'; ?>

    <!-- Header -->
    <header class="lg:ml-64 text-white shadow-xl py-8 md:py-12 px-4 md:px-6 rounded-xl" style="background: linear-gradient(135deg, <?= $primary_color ?> 0%, <?= $secondary_color ?> 100%);">
        <div class="max-w-7xl mx-auto">
            <div class="flex items-center gap-3 md:gap-4">
                <div class="bg-white/10 backdrop-blur-sm p-2 md:p-3 rounded-xl">
                    <svg class="w-8 h-8 md:w-10 md:h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                    </svg>
                </div>
                <div>
                    <h1 class="text-xl md:text-3xl font-bold mb-1">Buat Postingan Baru</h1>
                    <p class="text-white/90 text-sm md:text-base">Posting kegiatan kelas akan langsung dipublikasikan</p>
                </div>
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <main class="lg:ml-64 pt-16 mb-8">
        <div class="p-4 md:p-8">

            <?php if ($success): ?>
                <div class="bg-green-100 border border-green-400 text-green-700 px-4 md:px-6 py-3 md:py-4 rounded-lg mb-6 flex items-center gap-3">
                    <svg class="w-5 h-5 md:w-6 md:h-6 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <span class="text-sm md:text-base font-medium flex-1 min-w-0">
                        <?= htmlspecialchars($success) ?>
                    </span>
                </div>
            <?php endif; ?>

            <?php if ($error): ?>
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 md:px-6 py-3 md:py-4 rounded-lg mb-6 flex items-center gap-3">
                    <svg class="w-5 h-5 md:w-6 md:h-6 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <span class="text-sm md:text-base font-medium flex-1 min-w-0">
                        <?= htmlspecialchars($error) ?>
                    </span>
                </div>
            <?php endif; ?>

            <!-- Form -->
            <div class="bg-white rounded-xl shadow-md p-6 md:p-8">
                <form method="POST" enctype="multipart/form-data" class="space-y-4 md:space-y-6">
                    <div>
                        <label for="title" class="block text-gray-700 font-semibold mb-2 text-sm md:text-base">Judul Kegiatan *</label>
                        <input type="text" id="title" name="title" required
                            class="w-full px-3 md:px-4 py-2 md:py-3 border border-gray-300 rounded-lg focus:outline-none transition text-sm md:text-base" style="focus:border-color: <?= $primary_color ?>; focus:ring-color: <?= $primary_color ?>20;"
                            placeholder="Contoh: Webinar Teknologi AI">
                    </div>

                    <div>
                        <label for="date" class="block text-gray-700 font-semibold mb-2 text-sm md:text-base">Tanggal Kegiatan *</label>
                        <input type="date" id="date" name="date" value="<?= date('Y-m-d') ?>" required
                            class="w-full px-3 md:px-4 py-2 md:py-3 border border-gray-300 rounded-lg focus:outline-none transition text-sm md:text-base" style="focus:border-color: <?= $primary_color ?>; focus:ring-color: <?= $primary_color ?>20;">
                    </div>

                    <div>
                        <label for="content" class="block text-gray-700 font-semibold mb-2 text-sm md:text-base">Deskripsi Kegiatan *</label>
                        <textarea id="content" name="content" rows="6 md:rows-8" required
                            class="w-full px-3 md:px-4 py-2 md:py-3 border border-gray-300 rounded-lg focus:outline-none transition text-sm md:text-base resize-none" style="focus:border-color: <?= $primary_color ?>; focus:ring-color: <?= $primary_color ?>20;"
                            placeholder="Ceritakan detail kegiatan..."></textarea>
                    </div>

                    <div>
                        <label for="images" class="block text-gray-700 font-semibold mb-2 text-sm md:text-base">Foto Kegiatan (Opsional)</label>
                        <input type="file" id="images" name="images[]" accept="image/*" multiple
                            class="w-full px-3 md:px-4 py-2 md:py-3 border border-gray-300 rounded-lg focus:outline-none transition text-sm md:text-base" style="focus:border-color: <?= $primary_color ?>; focus:ring-color: <?= $primary_color ?>20;">
                        <p class="text-xs md:text-sm text-gray-500 mt-2">Format: JPG, JPEG, PNG, GIF. Maksimal 5 foto.</p>
                        <div id="image-preview" class="mt-3 grid grid-cols-2 sm:grid-cols-3 gap-3"></div>
                    </div>

                    <div class="flex flex-col sm:flex-row gap-3 md:gap-4">
                        <button type="submit"
                            class="flex-1 text-white px-4 md:px-6 py-2 md:py-3 rounded-lg font-bold transition shadow-lg flex items-center justify-center gap-2 text-sm md:text-base" style="background: linear-gradient(135deg, <?= $primary_color ?> 0%, <?= $secondary_color ?> 100%);" onmouseover="this.style.background='linear-gradient(135deg, <?= $secondary_color ?> 0%, <?= $primary_color ?> 100%)'" onmouseout="this.style.background='linear-gradient(135deg, <?= $primary_color ?> 0%, <?= $secondary_color ?> 100%)'">
                            <svg class="w-4 h-4 md:w-5 md:h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                            </svg>
                            Publikasikan Postingan
                        </button>
                        <a href=" <?= url('korti/posts') ?>"
                            class="px-4 md:px-6 py-2 md:py-3 border-2 border-gray-300 text-gray-700 rounded-lg font-bold hover:bg-gray-50 transition flex items-center justify-center gap-2 text-sm md:text-base">
                            <svg class="w-4 h-4 md:w-5 md:h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                            Batal
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </main>

    <!-- Alert Modal -->
    <div id="alertModal" class="fixed inset-0 bg-black bg-opacity-50 hidden z-50">
        <div class="flex items-center justify-center min-h-screen p-4">
            <div class="bg-white rounded-xl shadow-xl max-w-md w-full">
                <!-- Modal Header -->
                <div class="p-6 border-b">
                    <div class="flex items-center gap-3">
                        <div class="bg-blue-100 p-2 rounded-full">
                            <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-lg font-bold text-gray-900">Informasi</h3>
                            <p class="text-sm text-gray-600">Pemberitahuan</p>
                        </div>
                    </div>
                </div>

                <!-- Modal Body -->
                <div class="p-6">
                    <p id="alertMessage" class="text-gray-700 mb-6">
                        Pesan akan muncul di sini.
                    </p>

                    <!-- Modal Footer -->
                    <div class="flex justify-end">
                        <button onclick="hideAlertModal()" class="px-6 py-2 bg-blue-600 text-white rounded-lg font-medium hover:bg-blue-700 transition">
                            OK
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>

<script>
    // Image preview functionality
    document.getElementById('images').addEventListener('change', function(e) {
        const preview = document.getElementById('image-preview');
        preview.innerHTML = '';

        const files = Array.from(e.target.files);
        if (files.length > 30) {
            showAlertModal('Maksimal 30 foto yang dapat diupload');
            e.target.value = '';
            return;
        }

        files.forEach((file, index) => {
            if (file && file.type.startsWith('image/')) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    const div = document.createElement('div');
                    div.className = 'image-preview-item relative';
                    div.innerHTML = `
                        <img src="${e.target.result}" class="w-full h-20 md:h-24 object-cover rounded-lg border-2 border-gray-200">
                        <button type="button" onclick="removePreview(${index})"
                            class="remove-preview">×</button>
                        <div class="absolute bottom-0 left-0 right-0 bg-black bg-opacity-50 text-white text-xs p-1 rounded-b-lg">
                            ${file.name.length > 12 ? file.name.substring(0, 12) + '...' : file.name}
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
            showAlertModal('Maksimal 30 foto yang dapat diupload');
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
            Mempublikasikan...
        `;
        submitBtn.disabled = true;
    });

    // Alert Modal Functions
    function showAlertModal(message) {
        document.getElementById('alertMessage').textContent = message;
        document.getElementById('alertModal').classList.remove('hidden');
    }

    function hideAlertModal() {
        document.getElementById('alertModal').classList.add('hidden');
    }

    // Close modal when clicking outside
    document.getElementById('alertModal').addEventListener('click', function(e) {
        if (e.target === this) {
            hideAlertModal();
        }
    });
</script>
