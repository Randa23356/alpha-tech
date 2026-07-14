<?php
// informatics_a/admin/manage_hero_slider.php
session_start();
require_once __DIR__ . "/../src/config/db.php";
require_once __DIR__ . "/../src/config/urls.php";
require_once __DIR__ . "/../src/helpers/session.php";
require_once __DIR__ . '/../src/helpers/hero_slider.php';

// Proteksi: hanya admin yang bisa akses
if (!isLoggedIn() || !isAdmin()) {
    header("Location: " . url('login'));
    exit();
}

// Handle hero slider actions
$slide_action = $_GET['slide_action'] ?? '';
$slide_id = $_GET['slide_id'] ?? '';

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['slide_action'])) {
    $slide_action = $_POST['slide_action'];

    if ($slide_action === 'add' || $slide_action === 'edit') {
        $slide_data = [
            'title' => trim($_POST['slide_title'] ?? ''),
            'subtitle' => trim($_POST['slide_subtitle'] ?? ''),
            'description' => trim($_POST['slide_description'] ?? ''),
            'button_text' => trim($_POST['slide_button_text'] ?? 'Learn More'),
            'button_url' => trim($_POST['slide_button_url'] ?? '#'),
            'slide_order' => intval($_POST['slide_order'] ?? 0),
            'is_active' => isset($_POST['slide_is_active']) ? 1 : 0,
            'autoplay_duration' => intval($_POST['slide_autoplay_duration'] ?? 5000),
            'title_color' => trim($_POST['slide_title_color'] ?? '#ffffff'),
            'subtitle_color' => trim($_POST['slide_subtitle_color'] ?? '#f3f4f6'),
            'description_color' => trim($_POST['slide_description_color'] ?? '#d1d5db'),
            'icon_color' => trim($_POST['slide_icon_color'] ?? '#ffffff'),
            'icon_bg_color' => trim($_POST['slide_icon_bg_color'] ?? 'rgba(255,255,255,0.1)')
        ];

        if ($slide_action === 'add') {
            if (isset($_FILES['slide_background']) && $_FILES['slide_background']['error'] === UPLOAD_ERR_OK) {
                $upload_result = upload_hero_background($_FILES['slide_background']);
                if ($upload_result['success']) {
                    $slide_data['background_image'] = $upload_result['filename'];
                    if (add_hero_slide($pdo, $slide_data)) {
                        error_log("Slide added successfully with data: " . json_encode($slide_data));
                        $success = "Slide berhasil ditambahkan!";
                    } else {
                        error_log("Failed to add slide to database. Slide data: " . json_encode($slide_data));
                        $error = "Gagal menambahkan slide ke database";
                    }
                } else {
                    $error = $upload_result['error'];
                }
            } else {
                $error = "Background image is required for new slides";
            }
        } else {
            // Edit slide
            $slide_id = intval($_POST['slide_id']);
            if (isset($_FILES['slide_background']) && $_FILES['slide_background']['error'] === UPLOAD_ERR_OK) {
                $upload_result = upload_hero_background($_FILES['slide_background']);
                if ($upload_result['success']) {
                    $slide_data['background_image'] = $upload_result['filename'];
                } else {
                    $error = $upload_result['error'];
                }
            } else {
                $current_slide = get_hero_slide_by_id($pdo, $slide_id);
                $slide_data['background_image'] = $current_slide['background_image'];
            }

            if (!isset($error) && update_hero_slide($pdo, $slide_id, $slide_data)) {
                error_log("Slide {$slide_id} updated successfully with data: " . json_encode($slide_data));
                $success = "Slide berhasil diperbarui!";
            } else {
                error_log("Failed to update slide {$slide_id}. Error: " . ($error ?? 'Database update failed'));
                $error = $error ?? "Gagal memperbarui slide";
            }
        }
    } elseif ($slide_action === 'delete') {
        $slide_id = intval($_POST['slide_id']);
        if (delete_hero_slide($pdo, $slide_id)) {
            $success = "Slide berhasil dihapus!";
        } else {
            $error = "Gagal menghapus slide";
        }
    }

    // Redirect to avoid form resubmission
    $redirect_url = $_SERVER['PHP_SELF'] . "?success=" . urlencode($success ?? '') . "&error=" . urlencode($error ?? '') . "&t=" . time();
    error_log("Redirecting to: " . $redirect_url);
    header("Location: " . $redirect_url);
    exit();
}

// Handle hero slider settings
$hero_autoplay = 'true';
$hero_transition = 'fade';
$hero_show_arrows = 'true';
$hero_show_dots = 'true';
$hero_show_arrows_mobile = 'false';
$hero_show_arrows_tablet = 'true';
$hero_show_arrows_desktop = 'true';

// Update hero slider settings
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['hero_slider_settings'])) {
    $hero_settings = [
        'hero_autoplay' => $_POST['hero_autoplay'] ?? 'true',
        'hero_transition' => $_POST['hero_transition'] ?? 'fade',
        'hero_show_arrows' => $_POST['hero_show_arrows'] ?? 'true',
        'hero_show_dots' => $_POST['hero_show_dots'] ?? 'true',
        'hero_show_arrows_mobile' => $_POST['hero_show_arrows_mobile'] ?? 'false',
        'hero_show_arrows_tablet' => $_POST['hero_show_arrows_tablet'] ?? 'true',
        'hero_show_arrows_desktop' => $_POST['hero_show_arrows_desktop'] ?? 'true'
    ];

    foreach ($hero_settings as $key => $value) {
        $stmt = $pdo->prepare(
            "INSERT INTO site_settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = ?",
        );
        $stmt->execute([$key, $value, $value]);
    }

    $success = "Pengaturan hero slider berhasil disimpan!";
    header("Location: " . $_SERVER['PHP_SELF'] . "?success=" . urlencode($success));
    exit();
}

// Handle AJAX request for slide data
if (isset($_GET['get_slide_data']) && isset($_GET['slide_id'])) {
    $slide_id = intval($_GET['slide_id']);
    $slide = get_hero_slide_by_id($pdo, $slide_id);

    if ($slide) {
        // Debug: Log the slide data
        error_log('Slide data fetched: ' . json_encode($slide));

        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'slide' => $slide]);
    } else {
        error_log('Slide not found: ' . $slide_id);
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'error' => 'Slide not found']);
    }
    exit();
}

// Get success/error messages from redirect
$success = $_GET['success'] ?? null;
$error = $_GET['error'] ?? null;

// Get all hero slides
$hero_slides = get_hero_slides($pdo);
error_log('Total slides in admin panel: ' . count($hero_slides));

// Also check all slides (including inactive) for debugging
try {
    $stmt = $pdo->prepare("SELECT * FROM hero_slides ORDER BY slide_order ASC, id ASC");
    $stmt->execute();
    $all_slides = $stmt->fetchAll(PDO::FETCH_ASSOC);
    error_log('Total slides in database (including inactive): ' . count($all_slides));
    foreach ($all_slides as $slide) {
        error_log("Slide {$slide['id']}: active={$slide['is_active']}, title='{$slide['title']}'");
    }
} catch (Exception $e) {
    error_log('Error fetching all slides: ' . $e->getMessage());
}

// Get current settings for form defaults
$stmt = $pdo->query("SELECT setting_key, setting_value FROM site_settings WHERE setting_key IN ('hero_autoplay', 'hero_transition', 'hero_show_arrows', 'hero_show_dots', 'hero_show_arrows_mobile', 'hero_show_arrows_tablet', 'hero_show_arrows_desktop')");
$current_slider_settings = [];
while ($row = $stmt->fetch()) {
    $current_slider_settings[$row['setting_key']] = $row['setting_value'];
}

$hero_autoplay = $current_slider_settings['hero_autoplay'] ?? 'true';
$hero_transition = $current_slider_settings['hero_transition'] ?? 'fade';
$hero_show_arrows = $current_slider_settings['hero_show_arrows'] ?? 'true';
$hero_show_dots = $current_slider_settings['hero_show_dots'] ?? 'true';
$hero_show_arrows_mobile = $current_slider_settings['hero_show_arrows_mobile'] ?? 'false';
$hero_show_arrows_tablet = $current_slider_settings['hero_show_arrows_tablet'] ?? 'true';
$hero_show_arrows_desktop = $current_slider_settings['hero_show_arrows_desktop'] ?? 'true';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Manage Hero Slider - Admin Informatics A</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="<?= asset('tailwind.css') ?>" rel="stylesheet">
</head>
<body class="bg-gradient-to-br from-gray-50 to-blue-50 min-h-screen">
    <?php include __DIR__ . '/../includes/navbar.php'; ?>

    <?php include __DIR__ . '/sidebar.php'; ?>

    <!-- Header -->
    <header class="lg:ml-64 bg-gradient-to-r from-blue-900 via-blue-800 to-indigo-900 text-white py-10 px-6">
        <div class="max-w-7xl mx-auto">
            <div class="flex items-center gap-4">
                <div class="bg-white/10 backdrop-blur-sm p-3 rounded-xl">
                    <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"/>
                    </svg>
                </div>
                <div>
                    <h1 class="text-3xl font-bold mb-1">Hero Slider Management</h1>
                    <p class="text-blue-100">Kelola slide hero untuk halaman utama</p>
                </div>
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <main class="lg:ml-64 max-w-6xl mx-auto px-6 py-10">
        <?php if ($error): ?>
            <div class="mb-6 p-4 bg-red-100 border-l-4 border-red-500 text-red-700 rounded">
                <div class="flex items-center gap-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <?= htmlspecialchars($error) ?>
                </div>
            </div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="mb-6 p-4 bg-green-100 border-l-4 border-green-500 text-green-700 rounded">
                <div class="flex items-center gap-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <?= htmlspecialchars($success) ?>
                </div>
            </div>
        <?php endif; ?>

        <!-- Hero Slider Settings -->
        <div class="bg-white rounded-xl shadow-md p-8 mb-8">
            <h2 class="text-2xl font-bold text-blue-900 mb-6 flex items-center gap-2">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 100 4m0-4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 100 4m0-4v2m0-6V4"/>
                </svg>
                Slider Settings
            </h2>

            <form action="" method="POST" class="space-y-6">
                <input type="hidden" name="hero_slider_settings" value="1">

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-gray-700 font-semibold mb-2">Autoplay</label>
                        <select name="hero_autoplay" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <option value="true" <?= $hero_autoplay === 'true' ? 'selected' : '' ?>>Enabled</option>
                            <option value="false" <?= $hero_autoplay === 'false' ? 'selected' : '' ?>>Disabled</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-gray-700 font-semibold mb-2">Transition Effect</label>
                        <select name="hero_transition" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <option value="slide" <?= $hero_transition === 'slide' ? 'selected' : '' ?>>Slide (Modern)</option>
                            <option value="fade" <?= $hero_transition === 'fade' ? 'selected' : '' ?>>Fade (Classic)</option>
                            <option value="scale" <?= $hero_transition === 'scale' ? 'selected' : '' ?>>Scale (Dynamic)</option>
                            <option value="flip" <?= $hero_transition === 'flip' ? 'selected' : '' ?>>Flip (3D Effect)</option>
                        </select>
                        <p class="text-xs text-gray-500 mt-1">Choose the animation style for slide transitions</p>
                    </div>
                    <div>
                        <label class="block text-gray-700 font-semibold mb-2">Show Navigation Arrows</label>
                        <select name="hero_show_arrows" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <option value="true" <?= $hero_show_arrows === 'true' ? 'selected' : '' ?>>Show</option>
                            <option value="false" <?= $hero_show_arrows === 'false' ? 'selected' : '' ?>>Hide</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-gray-700 font-semibold mb-2">Show Pagination Dots</label>
                        <select name="hero_show_dots" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <option value="true" <?= $hero_show_dots === 'true' ? 'selected' : '' ?>>Show</option>
                            <option value="false" <?= $hero_show_dots === 'false' ? 'selected' : '' ?>>Hide</option>
                        </select>
                    </div>
                </div>

                <!-- Responsive Arrow Settings -->
                <div class="bg-gray-50 p-4 rounded-lg">
                    <h3 class="text-lg font-semibold text-gray-800 mb-4">Responsive Arrow Settings</h3>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <label class="block text-gray-700 font-semibold mb-2">Show Arrows on Mobile</label>
                            <select name="hero_show_arrows_mobile" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                                <option value="true" <?= $hero_show_arrows_mobile === 'true' ? 'selected' : '' ?>>Show</option>
                                <option value="false" <?= $hero_show_arrows_mobile === 'false' ? 'selected' : '' ?>>Hide</option>
                            </select>
                            <p class="text-xs text-gray-500 mt-1">Screen width &lt; 640px</p>
                        </div>
                        <div>
                            <label class="block text-gray-700 font-semibold mb-2">Show Arrows on Tablet</label>
                            <select name="hero_show_arrows_tablet" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                                <option value="true" <?= $hero_show_arrows_tablet === 'true' ? 'selected' : '' ?>>Show</option>
                                <option value="false" <?= $hero_show_arrows_tablet === 'false' ? 'selected' : '' ?>>Hide</option>
                            </select>
                            <p class="text-xs text-gray-500 mt-1">Screen width 641px - 1024px</p>
                        </div>
                        <div>
                            <label class="block text-gray-700 font-semibold mb-2">Show Arrows on Desktop</label>
                            <select name="hero_show_arrows_desktop" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                                <option value="true" <?= $hero_show_arrows_desktop === 'true' ? 'selected' : '' ?>>Show</option>
                                <option value="false" <?= $hero_show_arrows_desktop === 'false' ? 'selected' : '' ?>>Hide</option>
                            </select>
                            <p class="text-xs text-gray-500 mt-1">Screen width &gt; 1024px</p>
                        </div>
                    </div>
                </div>

                <button type="submit" class="bg-blue-600 text-white px-6 py-3 rounded-lg font-bold hover:bg-blue-700 transition flex items-center gap-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                    </svg>
                    Save Settings
                </button>
            </form>
        </div>

        <!-- Hero Slides Management -->
        <div class="bg-white rounded-xl shadow-md p-8">
            <div class="flex justify-between items-center mb-6">
                <h2 class="text-2xl font-bold text-blue-900 flex items-center gap-2">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
                    </svg>
                    Manage Slides
                </h2>
                <button onclick="showAddSlideModal()" class="bg-green-600 text-white px-6 py-3 rounded-lg font-bold hover:bg-green-700 transition flex items-center gap-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                    </svg>
                    Add New Slide
                </button>
            </div>

            <?php if (empty($hero_slides)): ?>
                <div class="bg-gray-100 p-12 rounded-lg text-center">
                    <svg class="w-20 h-20 text-gray-400 mx-auto mb-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                    </svg>
                    <h3 class="text-xl font-bold text-gray-600 mb-3">No Slides Yet</h3>
                    <p class="text-gray-500 text-lg mb-6">Create your first hero slide to get started</p>
                    <button onclick="showAddSlideModal()" class="bg-blue-600 text-white px-6 py-3 rounded-lg font-bold hover:bg-blue-700 transition inline-flex items-center gap-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                        </svg>
                        Add Your First Slide
                    </button>
                </div>
            <?php else: ?>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <?php foreach ($hero_slides as $slide): ?>
                        <div class="bg-gray-50 border border-gray-200 rounded-lg p-6 shadow-sm hover:shadow-md transition-shadow">
                            <div class="flex items-start justify-between mb-4">
                                <div class="flex-1">
                                    <?php if (!empty($slide['background_image'])): ?>
                                        <img src="<?= url('public/uploads/hero/' . htmlspecialchars($slide['background_image'])) ?>"
                                             alt="Slide background" class="w-full h-32 object-cover rounded mb-4">
                                    <?php endif; ?>

                                    <h3 class="font-bold text-gray-800 text-lg mb-2">
                                        <?= htmlspecialchars($slide['title']) ?>
                                        <?php if (!$slide['is_active']): ?>
                                            <span class="ml-2 text-xs bg-gray-200 text-gray-600 px-2 py-1 rounded">Inactive</span>
                                        <?php endif; ?>
                                    </h3>

                                    <?php if (!empty($slide['subtitle'])): ?>
                                        <p class="text-gray-600 text-sm mb-2 font-medium">
                                            <?= htmlspecialchars($slide['subtitle']) ?>
                                        </p>
                                    <?php endif; ?>

                                    <?php if (!empty($slide['description'])): ?>
                                        <p class="text-gray-500 text-sm mb-3 line-clamp-2">
                                            <?= htmlspecialchars(substr($slide['description'], 0, 100)) ?><?= strlen($slide['description']) > 100 ? '...' : '' ?>
                                        </p>
                                    <?php endif; ?>

                                    <div class="flex items-center gap-4 text-xs text-gray-500 mb-4">
                                        <span>Order: <?= $slide['slide_order'] ?></span>
                                        <span>Duration: <?= $slide['autoplay_duration'] / 1000 ?>s</span>
                                    </div>
                                </div>
                            </div>

                            <div class="flex gap-2">
                                <button onclick="editSlide(<?= $slide['id'] ?>)" class="flex-1 bg-blue-600 text-white px-3 py-2 rounded text-sm font-medium hover:bg-blue-700 transition">
                                    Edit
                                </button>
                                <button onclick="deleteSlide(<?= $slide['id'] ?>)" class="flex-1 bg-red-600 text-white px-3 py-2 rounded text-sm font-medium hover:bg-red-700 transition">
                                    Delete
                                </button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </main>

    <!-- Slide Modal -->
    <div id="slideModal" class="fixed inset-0 bg-black bg-opacity-50 hidden z-50">
        <div class="flex items-center justify-center min-h-screen p-4">
            <div class="bg-white rounded-xl shadow-xl max-w-2xl w-full max-h-[95vh] flex flex-col">
                <!-- Fixed Header -->
                <div class="p-4 border-b flex-shrink-0 bg-white">
                    <div class="flex justify-between items-center">
                        <h3 class="text-lg font-bold text-gray-800" id="modalTitle">Add New Slide</h3>
                        <button onclick="hideSlideModal()" class="text-gray-400 hover:text-gray-600">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>
                </div>

                <!-- Scrollable Content Area -->
                <div class="flex-1 overflow-y-auto min-h-0">
                    <form id="slideForm" method="POST" enctype="multipart/form-data" class="p-6 space-y-4">
                        <input type="hidden" name="slide_action" id="slideAction" value="add">
                        <input type="hidden" name="slide_id" id="slideId" value="">

                        <!-- Active Checkbox -->
                        <div class="flex items-center">
                            <input type="checkbox" id="slide_is_active" name="slide_is_active" value="1" checked class="mr-2">
                            <label for="slide_is_active" class="text-gray-700 font-medium">Active</label>
                        </div>

                        <!-- Title and Subtitle -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label for="slide_title" class="block text-gray-700 font-medium mb-1">Title *</label>
                                <input type="text" id="slide_title" name="slide_title"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 text-sm">
                            </div>
                            <div>
                                <label for="slide_subtitle" class="block text-gray-700 font-medium mb-1">Subtitle</label>
                                <input type="text" id="slide_subtitle" name="slide_subtitle"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 text-sm">
                            </div>
                        </div>

                        <!-- Description -->
                        <div>
                            <label for="slide_description" class="block text-gray-700 font-medium mb-1">Description</label>
                            <textarea id="slide_description" name="slide_description" rows="2"
                                      class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 text-sm"></textarea>
                        </div>

                        <!-- Button Text and URL -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label for="slide_button_text" class="block text-gray-700 font-medium mb-1">Button Text</label>
                                <input type="text" id="slide_button_text" name="slide_button_text" value="Learn More"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 text-sm">
                            </div>
                            <div>
                                <label for="slide_button_url" class="block text-gray-700 font-medium mb-1">Button URL</label>
                                <input type="url" id="slide_button_url" name="slide_button_url" value="#"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 text-sm"
                                       placeholder="https://example.com or /internal-page">
                                <p class="text-xs text-gray-500 mt-1">Examples: /register, /activities, https://external-site.com</p>
                            </div>
                        </div>

                        <!-- Order and Duration -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label for="slide_order" class="block text-gray-700 font-medium mb-1">Display Order</label>
                                <input type="number" id="slide_order" name="slide_order" min="0" value="0"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 text-sm">
                            </div>
                            <div>
                                <label for="slide_autoplay_duration" class="block text-gray-700 font-medium mb-1">Autoplay Duration (ms)</label>
                                <input type="number" id="slide_autoplay_duration" name="slide_autoplay_duration" min="1000" step="500" value="5000"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 text-sm">
                            </div>
                        </div>

                        <!-- Background Image -->
                        <div>
                            <label for="slide_background" class="block text-gray-700 font-medium mb-1">Background Image *</label>
                            <input type="file" id="slide_background" name="slide_background" accept="image/*"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 text-sm">
                            <p class="text-xs text-gray-500 mt-1">Recommended size: 1920x1080px. Max file size: 2MB (JPG, PNG, WebP only)</p>
                            <div id="currentImagePreview" class="mt-2 hidden">
                                <img id="currentImage" src="" alt="Current background" class="w-24 h-16 object-cover rounded border">
                            </div>
                        </div>

                        <!-- Color Settings -->
                        <div class="bg-gray-50 p-4 rounded-lg">
                            <h4 class="text-md font-semibold text-gray-800 mb-3">Color Settings</h4>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label for="slide_title_color" class="block text-gray-700 font-medium mb-1">Title Color</label>
                                    <input type="color" id="slide_title_color" name="slide_title_color" value="#ffffff"
                                           class="w-full h-10 px-2 py-1 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                                </div>
                                <div>
                                    <label for="slide_subtitle_color" class="block text-gray-700 font-medium mb-1">Subtitle Color</label>
                                    <input type="color" id="slide_subtitle_color" name="slide_subtitle_color" value="#f3f4f6"
                                           class="w-full h-10 px-2 py-1 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                                </div>
                                <div>
                                    <label for="slide_description_color" class="block text-gray-700 font-medium mb-1">Description Color</label>
                                    <input type="color" id="slide_description_color" name="slide_description_color" value="#d1d5db"
                                           class="w-full h-10 px-2 py-1 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                                </div>
                                <div>
                                    <label for="slide_icon_color" class="block text-gray-700 font-medium mb-1">Icon Color</label>
                                    <input type="color" id="slide_icon_color" name="slide_icon_color" value="#ffffff"
                                           class="w-full h-10 px-2 py-1 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                                </div>
                                <div>
                                    <label for="slide_icon_bg_color" class="block text-gray-700 font-medium mb-1">Icon Background</label>
                                    <input type="text" id="slide_icon_bg_color" name="slide_icon_bg_color" value="rgba(255,255,255,0.1)"
                                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 text-sm"
                                           placeholder="rgba(255,255,255,0.1) or #ffffff">
                                    <p class="text-xs text-gray-500 mt-1">Use rgba() for transparency or hex color</p>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>

                <!-- Fixed Footer -->
                <div class="p-4 border-t bg-gray-50 flex-shrink-0">
                    <div class="flex gap-3">
                        <button type="submit" form="slideForm" class="flex-1 bg-blue-600 text-white px-4 py-2 rounded-md font-semibold hover:bg-blue-700 transition text-sm">
                            <span id="submitButtonText">Add Slide</span>
                        </button>
                        <button type="button" onclick="hideSlideModal()" class="px-4 py-2 border border-gray-300 rounded-md font-semibold hover:bg-gray-50 transition text-sm">
                            Cancel
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer class="lg:ml-64 bg-white border-t border-gray-200 py-6 mt-12">
        <div class="max-w-7xl mx-auto px-6 text-center text-gray-600">
            &copy; <?= date("Y") ?> Informatics A. All rights reserved.
        </div>
    </footer>

    <script>
        // Modal functions
        function showAddSlideModal() {
            document.getElementById('slideModal').classList.remove('hidden');
            document.getElementById('modalTitle').textContent = 'Add New Slide';
            document.getElementById('slideAction').value = 'add';
            document.getElementById('slideId').value = '';
            document.getElementById('slideForm').reset();
            document.getElementById('submitButtonText').textContent = 'Add Slide';
            document.getElementById('currentImagePreview').classList.add('hidden');
        }

        function hideSlideModal() {
            document.getElementById('slideModal').classList.add('hidden');
        }

        function editSlide(slideId) {
            // Fetch slide data via AJAX
            fetch(`<?= $_SERVER['PHP_SELF'] ?>?slide_id=${slideId}&get_slide_data=1`)
                .then(response => response.json())
                .then(data => {
                    console.log('Edit slide response:', data); // Debug log
                    if (data.success) {
                        const slide = data.slide;
                        console.log('Slide data:', slide); // Debug log

                        document.getElementById('slideModal').classList.remove('hidden');
                        document.getElementById('modalTitle').textContent = 'Edit Slide';
                        document.getElementById('slideAction').value = 'edit';
                        document.getElementById('slideId').value = slide.id;
                        document.getElementById('slide_title').value = slide.title;
                        document.getElementById('slide_subtitle').value = slide.subtitle || '';
                        document.getElementById('slide_description').value = slide.description || '';
                        document.getElementById('slide_button_text').value = slide.button_text;
                        document.getElementById('slide_button_url').value = slide.button_url;
                        document.getElementById('slide_order').value = slide.slide_order;
                        document.getElementById('slide_autoplay_duration').value = slide.autoplay_duration;
                        document.getElementById('slide_is_active').checked = slide.is_active == 1;

                        // Set color values with fallbacks
                        document.getElementById('slide_title_color').value = slide.title_color || '#ffffff';
                        document.getElementById('slide_subtitle_color').value = slide.subtitle_color || '#f3f4f6';
                        document.getElementById('slide_description_color').value = slide.description_color || '#d1d5db';
                        document.getElementById('slide_icon_color').value = slide.icon_color || '#ffffff';
                        document.getElementById('slide_icon_bg_color').value = slide.icon_bg_color || 'rgba(255,255,255,0.1)';

                        if (slide.background_image) {
                            document.getElementById('currentImage').src = `<?= url('public/uploads/hero/' . htmlspecialchars($slide['background_image'])) ?>`;
                            document.getElementById('currentImagePreview').classList.remove('hidden');
                        }

                        document.getElementById('submitButtonText').textContent = 'Update Slide';
                    } else {
                        console.error('Failed to fetch slide data:', data.error);
                        alert('Error loading slide data: ' + data.error);
                    }
                })
                .catch(error => {
                    console.error('Error fetching slide data:', error);
                    alert('Error loading slide data');
                });
        }

        function deleteSlide(slideId) {
            if (confirm('Are you sure you want to delete this slide?')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `
                    <input type="hidden" name="slide_action" value="delete">
                    <input type="hidden" name="slide_id" value="${slideId}">
                `;
                document.body.appendChild(form);
                form.submit();
            }
        }
    </script>
</body>
</html>
