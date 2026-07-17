<?php
// informatics_a/gallery.php
require_once __DIR__ . "/src/helpers/session.php";
require_once __DIR__ . "/src/config/db.php";
require_once __DIR__ . "/src/config/urls.php";

// Load theme colors from database
$primary_color = '#1e3a8a'; // default
$secondary_color = '#1e40af'; // default
$accent_color = '#ec4899'; // default
$success_color = '#10b981'; // default
$warning_color = '#f59e0b'; // default
$danger_color = '#ef4444'; // default

try {
    $stmt = $pdo->query("SELECT setting_key, setting_value FROM site_settings WHERE setting_key IN ('primary_color', 'secondary_color', 'accent_color', 'success_color', 'warning_color', 'danger_color')");
    $settings = [];
    foreach ($stmt->fetchAll() as $row) {
        $settings[$row['setting_key']] = $row['setting_value'];
    }
    $primary_color = $settings['primary_color'] ?? $primary_color;
    $secondary_color = $settings['secondary_color'] ?? $secondary_color;
    $accent_color = $settings['accent_color'] ?? $accent_color;
    $success_color = $settings['success_color'] ?? $success_color;
    $warning_color = $settings['warning_color'] ?? $warning_color;
    $danger_color = $settings['danger_color'] ?? $danger_color;
} catch (Exception $e) {
    // Use default colors if database fails
}

// Gallery bisa diakses PUBLIC (tanpa login)
$is_logged_in = isLoggedIn();
$user = $is_logged_in ? getCurrentUser() : null;

// Ambil semua foto dari kegiatan yang sudah di-approve
try {
    // Check if deleted_at column exists
    $deletedAtCheck = $pdo->query("SHOW COLUMNS FROM posts LIKE 'deleted_at'")->fetch();
    $deletedAtCondition = $deletedAtCheck ? 'AND posts.deleted_at IS NULL' : '';

    // First get all approved posts
    $stmt = $pdo->query(
        "SELECT DISTINCT posts.id, posts.title as caption, posts.content as description, posts.date as uploaded_at, users.username, users.id as user_id, users.profile_pic
         FROM posts
         JOIN users ON posts.user_id = users.id
         WHERE posts.status = 'approved' {$deletedAtCondition}
         ORDER BY posts.date DESC"
    );
    $posts = $stmt->fetchAll();

    // Then get all images for each post
    $photos = [];
    foreach ($posts as &$post) {
        $stmt = $pdo->prepare("SELECT image_path, image_order FROM post_images WHERE post_id = ? ORDER BY image_order");
        $stmt->execute([$post['id']]);
        $images = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($images as $image) {
            $photos[] = [
                'id' => $post['id'],
                'image' => $image['image_path'],
                'title' => $post['caption'],
                'description' => $post['description'],
                'username' => $post['username'],
                'user_id' => $post['user_id'],
                'date' => $post['uploaded_at'],
                'image_order' => $image['image_order'],
                'profile_pic' => $post['profile_pic']
            ];
        }
    }
    // Juga ambil gambar dari hero slider
    try {
        $stmt = $pdo->query("SELECT id, title, subtitle, background_image, slide_order FROM hero_slides WHERE is_active = 1 AND background_image IS NOT NULL AND background_image != '' ORDER BY slide_order ASC");
        $hero_slides = $stmt->fetchAll();
        foreach ($hero_slides as $slide) {
            $photos[] = [
                'id' => 'slide_' . $slide['id'],
                'image' => 'hero/' . $slide['background_image'],
                'title' => $slide['title'] ?: ($slide['subtitle'] ?: 'Slider'),
                'description' => '',
                'username' => 'Admin',
                'user_id' => 0,
                'date' => date('Y-m-d'),
                'image_order' => $slide['slide_order'],
                'profile_pic' => ''
            ];
        }
    } catch (Exception $e) {
        // skip jika tabel tidak ada
    }
} catch (Exception $e) {
    // Jika tabel belum ada, set empty array
    $photos = [];
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Galeri Foto Kegiatan - Informatics <?= htmlspecialchars($site_name) ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href=" <?= asset('tailwind.css') ?>" rel="stylesheet">
    <?php require_once __DIR__ . '/includes/favicon.php'; ?>
    <link href=" <?= url('public/css/dynamic-theme.php') ?>" rel="stylesheet">
    <style>
        /* Fallback styles using PHP variables for better compatibility */
        .bg-gradient-primary {
            background: linear-gradient(135deg, <?= $primary_color ?> 0%, <?= $secondary_color ?> 100%) !important;
        }
        .btn-primary {
            background-color: <?= $primary_color ?> !important;
            border-color: <?= $primary_color ?> !important;
        }
        .btn-primary:hover {
            background-color: <?= $secondary_color ?> !important;
            border-color: <?= $secondary_color ?> !important;
        }
        .theme-bg-primary {
            background-color: <?= $primary_color ?> !important;
        }
        .theme-text-secondary {
            color: <?= $secondary_color ?> !important;
        }
        .theme-hover-primary:hover {
            background-color: <?= $primary_color ?> !important;
            color: white !important;
        }
        .theme-hover-secondary:hover {
            background-color: <?= $secondary_color ?> !important;
            color: white !important;
        }
    </style>
</head>
<body class="bg-gray-50 min-h-screen">
    
    <?php include __DIR__ . '/includes/navbar.php'; ?>

    <!-- Header -->
    <header class="bg-gradient-primary text-white py-16 px-6">
        <div class="max-w-6xl mx-auto text-center">
            <div class="flex justify-center mb-4">
                <svg class="w-16 h-16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                </svg>
            </div>
            <h1 class="text-4xl font-bold mb-3">Galeri Foto Kegiatan</h1>
            <p class="text-xl text-white">Dokumentasi visual kegiatan kelas Informatics <?= htmlspecialchars($site_name) ?>.</p>
        </div>
    </header>

    <!-- Main Content -->
    <main class="max-w-7xl mx-auto px-6 py-12">
        <?php if (empty($photos)): ?>
            <div class="bg-white p-12 rounded-xl shadow-md text-center">
                <svg class="w-20 h-20 mx-auto mb-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                </svg>
                <p class="text-gray-600 text-lg mb-4">Belum ada foto di galeri.</p>
                <p class="text-gray-500">Foto akan muncul otomatis dari kegiatan yang sudah di-approve admin.</p>
            </div>
        <?php else: ?>
            <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 gap-4">
                <?php foreach ($photos as $photo): ?>
                    <div class="relative aspect-square overflow-hidden rounded-lg shadow-md hover:shadow-2xl transition-all duration-300 group cursor-pointer" 
                         onclick='openModal(<?= json_encode([
                             "id" => $photo["id"],
                             "image" => $photo["image"],
                             "title" => $photo["caption"],
                             "description" => $photo["description"],
                             "username" => $photo["username"],
                             "user_id" => $photo["user_id"],
                             "date" => $photo["uploaded_at"],
                             "profile_pic" => $photo["profile_pic"]
                         ]) ?>)'>
                        <img src=" <?= url('public/uploads/' . htmlspecialchars($photo["image"])) ?>" 
                             alt="<?= htmlspecialchars($photo["caption"]) ?>" 
                             class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-500">
                        
                        <!-- Overlay on hover -->
                        <div class="absolute inset-0 bg-gradient-to-t from-black/70 via-black/20 to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-300 flex items-end p-4">
                            <div class="text-white">
                                <p class="font-semibold text-sm line-clamp-2"><?= htmlspecialchars($photo["caption"]) ?></p>
                                <p class="text-xs text-gray-300 mt-1"><?= htmlspecialchars($photo["username"]) ?></p>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <div class="mt-12 text-center">
            <a href=" <?= $is_logged_in ? url('dashboard') : url('') ?>" class="inline-flex items-center gap-2 bg-gradient-primary text-white px-8 py-3 rounded-lg font-semibold theme-hover-secondary transition shadow-lg">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                </svg>
                Kembali ke <?= $is_logged_in ? 'Dashboard' : 'Beranda' ?>
            </a>
        </div>
    </main>

    <?php include __DIR__ . '/includes/footer.php'; ?>

    <!-- Modal Popup -->
    <div id="photoModal" class="fixed inset-0 z-50 hidden flex items-center justify-center p-4 bg-black bg-opacity-50" onclick="closeModal(event)">
        <div class="relative max-w-xl w-full bg-white rounded-2xl shadow-2xl overflow-hidden transform transition-all" onclick="event.stopPropagation()">
            <!-- Close Button -->
            <button onclick="closeModal()" class="absolute top-3 right-3 z-10 bg-white text-gray-900 w-8 h-8 rounded-full hover:bg-gray-100 transition shadow-lg flex items-center justify-center">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>

            <!-- Image Section -->
            <div class="bg-gray-100 p-4 flex items-center justify-center min-h-[200px]">
                <img id="modalImage" src="" alt="" class="max-w-full max-h-96 object-contain rounded-lg" onload="console.log('Image loaded')" onerror="console.log('Image failed to load')">
            </div>

            <!-- Info Section -->
            <div class="p-4 bg-white flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <img id="modalAvatar" src="" alt="Avatar" class="w-8 h-8 rounded-full object-cover hidden">
                    <img id="modalAvatarDefault" src="" alt="Default Avatar" class="w-8 h-8 rounded-full object-cover hidden">
                    <div class="cursor-pointer hover:bg-gray-50 rounded-lg p-2 transition-colors" onclick="redirectToUserProfile()">
                        <p id="modalUsername" class="font-semibold text-gray-900 text-sm">
                            <a href="" id="modalProfileLink" class="theme-hover-primary transition">Username</a>
                        </p>
                        <p id="modalDate" class="text-xs text-gray-500"></p>
                    </div>
                </div>

                <a id="modalDownload" href="" download class="btn-primary flex items-center gap-2 text-sm">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                    </svg>
                    Download
                </a>
            </div>
        </div>
    </div>

    <script>
        let currentModalData = null; // Store modal data globally

        function openModal(data) {
            console.log('openModal called with data:', data);
            currentModalData = data; // Store data for later use
            const modal = document.getElementById('photoModal');

            if (!modal) {
                console.error('Modal element not found!');
                return;
            }

            const imagePath = '<?= upload_url('') ?>' + data.image;
            console.log('Image path:', imagePath);

            // Set modal content with error checking
            const modalImage = document.getElementById('modalImage');
            const modalUsername = document.getElementById('modalUsername');
            const modalUserInitial = document.getElementById('modalUserInitial');
            const modalDate = document.getElementById('modalDate');
            const modalDownload = document.getElementById('modalDownload');
            const modalProfileLink = document.getElementById('modalProfileLink');

            if (modalImage) modalImage.src = imagePath;
            if (modalImage) modalImage.alt = data.title || 'Gallery Image';
            if (modalUsername) modalUsername.textContent = data.username || 'Unknown User';
            if (modalUserInitial) modalUserInitial.textContent = (data.username || 'U').charAt(0).toUpperCase();
            if (modalDate) modalDate.textContent = data.date ? new Date(data.date).toLocaleDateString('id-ID', {
                day: 'numeric',
                month: 'long',
                year: 'numeric'
            }) : '';
            if (modalDownload) modalDownload.href = imagePath;

            // Set profile avatar
            const modalAvatar = document.getElementById('modalAvatar');
            const modalAvatarDefault = document.getElementById('modalAvatarDefault');
            if (data.profile_pic) {
                if (data.profile_pic.startsWith('http')) {
                    modalAvatar.src = data.profile_pic;
                } else {
                    modalAvatar.src = '<?= BASE_URL ?>/public/uploads/' + data.profile_pic.replace(/^.*\//, '');
                }
                modalAvatar.classList.remove('hidden');
                modalAvatarDefault.classList.add('hidden');
            } else {
                modalAvatarDefault.src = '<?= url("public/default-avatar.php?initial=") ?>' + (data.username || 'U').charAt(0).toUpperCase() + '&color=<?= urlencode($primary_color) ?>';
                modalAvatarDefault.classList.remove('hidden');
                modalAvatar.classList.add('hidden');
            }

            // Set profile link if user is logged in
            if (modalProfileLink) {
                <?php if ($is_logged_in): ?>
                    modalProfileLink.href = 'user_profile.php?id=' + (data.user_id || '');
                    modalProfileLink.textContent = data.username || 'User';
                    modalProfileLink.style.display = 'inline'; // Make sure it's visible
                <?php else: ?>
                    modalProfileLink.href = '<?= url('login') ?>';
                    modalProfileLink.textContent = (data.username || 'User') + ' (Login untuk melihat profil)';
                    modalProfileLink.style.display = 'inline'; // Make sure it's visible
                <?php endif; ?>
                console.log('Profile link set:', modalProfileLink.href);
            } else {
                console.error('modalProfileLink element not found!');
            }

            console.log('Showing modal...');
            modal.classList.remove('hidden');
            modal.classList.add('flex');
            document.body.style.overflow = 'hidden';
            console.log('Modal should be visible now');
        }

        function redirectToUserProfile() {
            if (currentModalData) {
                <?php if ($is_logged_in): ?>
                    window.location.href = 'user_profile.php?id=' + currentModalData.user_id;
                <?php else: ?>
                    window.location.href = '<?= url('login') ?>';
                <?php endif; ?>
            } else {
                console.error('No modal data available');
            }
        }

        function closeModal(event) {
            // Allow closing from overlay click
            if (event && event.currentTarget !== event.target) return;

            const modal = document.getElementById('photoModal');
            if (modal) {
                modal.classList.add('hidden');
                modal.classList.remove('flex');
                document.body.style.overflow = 'auto';
            }
        }

        // Close modal with ESC key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closeModal();
            }
        });
    </script>

</body>
</html>
