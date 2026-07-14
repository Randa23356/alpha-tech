<?php
ini_set("display_errors", 1);
ini_set("display_startup_errors", 1);
error_reporting(E_ALL);
// admin/contact_messages.php - Halaman Admin untuk Lihat Pesan Kontak
session_start();
require_once __DIR__ . '/../src/config/db.php';
require_once __DIR__ . '/../src/helpers/session.php';
require_once __DIR__ . '/../src/controllers/AuthController.php';
require_once __DIR__ . '/../src/controllers/ContactController.php';
require_once __DIR__ . '/../src/config/urls.php';

// Pastikan hanya admin atau korti yang bisa akses

$contactController = new ContactController();
// Handle mark as read
if (isset($_GET['action']) && $_GET['action'] === 'mark_read' && isset($_GET['id'])) {
    $contactController->markAsRead($_GET['id']);
    header('Location: ' . url('admin/contact_messages'));
    exit;
}

// Handle mark all as read
if (isset($_GET['action']) && $_GET['action'] === 'mark_all_read') {
    $contactController->markAllAsRead();
    header('Location: ' . url('admin/contact_messages'));
    exit;
}

$messages = $contactController->getMessages();
$unreadCount = $contactController->getUnreadCount();

// Handle view message detail
$selectedMessage = null;
if (isset($_GET['view']) && $_GET['view'] === 'detail' && isset($_GET['id'])) {
    $selectedMessage = $contactController->getMessage($_GET['id']);
    if ($selectedMessage && $selectedMessage['status'] === 'unread') {
        $contactController->markAsRead($_GET['id']);
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pesan Kontak - Admin Panel</title>
    <link href=" <?= asset('tailwind.css') ?>" rel="stylesheet">
    <?php require_once __DIR__ . '/../includes/favicon.php'; ?>
</head>
<body class="bg-gray-100 min-h-screen">
    <?php include __DIR__ . '/../includes/navbar.php'; ?>

    <div class="flex h-screen">
        <?php include __DIR__ . '/sidebar.php'; ?>

        <!-- Main Chat Interface -->
        <main class="flex-1 flex flex-col lg:ml-64">
            <!-- Header -->
            <header class="bg-white shadow-sm border-b px-6 py-4">
                <div class="flex items-center justify-between">
                    <div>
                        <h1 class="text-2xl font-bold text-gray-900">Pesan Kontak</h1>
                        <p class="text-sm text-gray-600">Kelola pesan dari pengguna</p>
                    </div>
                    <div class="flex items-center gap-3">
                        <?php if ($unreadCount > 0): ?>
                            <a href=" <?= url('admin/contact_messages?action=mark_all_read') ?>"
                               class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition text-sm">
                                Tandai Semua Dibaca
                            </a>
                        <?php endif; ?>
                        <div class="bg-red-100 text-red-800 px-3 py-1 rounded-full text-sm font-semibold">
                            <?= $unreadCount ?> Baru
                        </div>
                    </div>
                </div>
            </header>

            <!-- Chat Interface -->
            <div class="flex-1 flex">
                <!-- Messages List -->
                <div class="w-full lg:w-1/3 bg-white border-r overflow-y-auto">
                    <?php if (empty($messages)): ?>
                        <div class="p-8 text-center">
                            <svg class="w-16 h-16 mx-auto mb-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                            </svg>
                            <h3 class="text-xl font-semibold text-gray-700 mb-2">Belum ada pesan</h3>
                            <p class="text-gray-500">Pesan dari form kontak akan muncul di sini.</p>
                        </div>
                    <?php else: ?>
                        <div class="divide-y divide-gray-200">
                            <?php foreach ($messages as $msg): ?>
                                <div class="p-4 hover:bg-gray-50 cursor-pointer transition <?php if ($msg['status'] === 'unread'): ?>bg-blue-50 border-l-4 border-blue-500<?php endif; ?>"
                                     onclick="viewMessage(<?= $msg['id'] ?>)">
                                    <div class="flex items-start gap-3">
                                        <div class="flex-shrink-0">
                                            <?php if ($msg['status'] === 'unread'): ?>
                                                <div class="w-3 h-3 bg-blue-600 rounded-full"></div>
                                            <?php else: ?>
                                                <div class="w-3 h-3 bg-gray-300 rounded-full"></div>
                                            <?php endif; ?>
                                        </div>
                                        <div class="flex-1 min-w-0">
                                            <div class="flex items-center justify-between mb-1">
                                                <h3 class="font-semibold text-gray-900 truncate">
                                                    <?= htmlspecialchars($msg['name']) ?>
                                                </h3>
                                                <span class="text-xs text-gray-500">
                                                    <?= date('d/m', strtotime($msg['created_at'])) ?>
                                                </span>
                                            </div>
                                            <p class="text-sm font-medium text-gray-700 truncate mb-1">
                                                <?= htmlspecialchars($msg['subject']) ?>
                                            </p>
                                            <p class="text-sm text-gray-600 truncate">
                                                <?= htmlspecialchars(substr($msg['message'], 0, 50)) ?>
                                                <?= strlen($msg['message']) > 50 ? '...' : '' ?>
                                            </p>
                                            <p class="text-xs text-gray-500 mt-1">
                                                <?= htmlspecialchars($msg['email']) ?>
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Message Detail -->
                <div class="hidden lg:flex lg:flex-1 lg:flex-col bg-white">
                    <?php if ($selectedMessage): ?>
                        <div class="border-b p-4">
                            <div class="flex items-center justify-between">
                                <div>
                                    <h2 class="text-xl font-bold text-gray-900">
                                        <?= htmlspecialchars($selectedMessage['subject']) ?>
                                    </h2>
                                    <p class="text-sm text-gray-600">
                                        Dari: <?= htmlspecialchars($selectedMessage['name']) ?> (<?= htmlspecialchars($selectedMessage['email']) ?>)
                                    </p>
                                    <p class="text-xs text-gray-500">
                                        <?= date('d M Y, H:i', strtotime($selectedMessage['created_at'])) ?>
                                    </p>
                                </div>
                                <div class="flex gap-2">
                                    <a href="mailto:<?= htmlspecialchars($selectedMessage['email']) ?>?subject=Re: <?= urlencode($selectedMessage['subject']) ?>"
                                       class="bg-blue-600 text-white px-3 py-2 rounded-lg hover:bg-blue-700 transition text-sm">
                                        Balas Email
                                    </a>
                                    <button onclick="window.print()"
                                            class="bg-gray-600 text-white px-3 py-2 rounded-lg hover:bg-gray-700 transition text-sm">
                                        Print
                                    </button>
                                </div>
                            </div>
                        </div>
                        <div class="flex-1 p-6 overflow-y-auto">
                            <div class="bg-gray-50 rounded-lg p-4">
                                <p class="text-gray-700 whitespace-pre-wrap">
                                    <?= nl2br(htmlspecialchars($selectedMessage['message'])) ?>
                                </p>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="flex-1 flex items-center justify-center">
                            <div class="text-center">
                                <svg class="w-16 h-16 mx-auto mb-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/>
                                </svg>
                                <h3 class="text-xl font-semibold text-gray-700 mb-2">Pilih Pesan</h3>
                                <p class="text-gray-500">Klik pada pesan di sebelah kiri untuk melihat detailnya.</p>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>

    <!-- Modal for Mobile Detail View -->
    <div id="messageModal" class="fixed inset-0 bg-black bg-opacity-50 hidden z-50 lg:hidden">
        <div class="bg-white h-full overflow-y-auto">
            <div class="sticky top-0 bg-white border-b p-4 flex items-center justify-between">
                <h2 class="text-lg font-bold text-gray-900">Detail Pesan</h2>
                <button onclick="closeModal()" class="text-gray-500 hover:text-gray-700">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
            <div id="modalContent" class="p-8">
                <!-- Modal content will be populated by JavaScript -->
            </div>
        </div>
    </div>

    <script>
        // Store messages data for JavaScript
        const messagesData = <?php echo json_encode(array_column($messages, null, 'id')); ?>;

        function viewMessage(id) {
            const message = messagesData[id];
            if (message) {
                // Check if this is the currently open message
                const currentUrlParams = new URLSearchParams(window.location.search);
                const currentId = currentUrlParams.get('id');

                if (currentId === id.toString()) {
                    // Clicking the same message, close the detail view
                    closeDetailView();
                } else {
                    // Open new message
                    showMessageModal(message);
                }
            }
        }

        function showMessageModal(message) {
            if (window.innerWidth < 1024) {
                // Show modal on mobile
                document.getElementById('messageModal').classList.remove('hidden');
                document.getElementById('modalContent').innerHTML = `
                    <div class="mb-4">
                        <h3 class="text-xl font-bold text-gray-900 mb-2">
                            ${message.subject}
                        </h3>
                        <p class="text-sm text-gray-600">
                            Dari: ${message.name} (${message.email})
                        </p>
                        <p class="text-xs text-gray-500">
                            ${new Date(message.created_at).toLocaleDateString('id-ID', { day: 'numeric', month: 'long', year: 'numeric', hour: '2-digit', minute: '2-digit' })}
                        </p>
                    </div>
                    <div class="bg-gray-50 rounded-lg mb-4 border border-gray-200">
                        <div class="p-4">
                            <p class="text-gray-700 leading-relaxed text-base text-left m-0 ml-0 pl-0" style="text-align: left !important;">
                                ${message.message.replace(/\n/g, '<br>')}
                            </p>
                        </div>
                    </div>
                    <div class="flex gap-2">
                        <a href="mailto:${message.email}?subject=Re: ${encodeURIComponent(message.subject)}"
                           class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition text-sm flex-1 text-center">
                            Balas Email
                        </a>
                        <button onclick="window.print()"
                                class="bg-gray-600 text-white px-2 py-2 rounded-lg hover:bg-gray-700 transition text-sm">
                            Print
                        </button>
                    </div>
                `;
            } else {
                // On desktop, update the detail section directly
                updateDetailView(message);
            }
        }

        function updateDetailView(message) {
            const detailSection = document.querySelector('.lg\\:flex-1');
            if (detailSection) {
                detailSection.innerHTML = `
                    <div class="border-b p-4">
                        <div class="flex items-center justify-between">
                            <div>
                                <h2 class="text-xl font-bold text-gray-900">
                                    ${message.subject}
                                </h2>
                                <p class="text-sm text-gray-600">
                                    Dari: ${message.name} (${message.email})
                                </p>
                                <p class="text-xs text-gray-500">
                                    ${new Date(message.created_at).toLocaleDateString('id-ID', { day: 'numeric', month: 'long', year: 'numeric', hour: '2-digit', minute: '2-digit' })}
                                </p>
                            </div>
                            <div class="flex gap-2">
                                <a href="mailto:${message.email}?subject=Re: ${encodeURIComponent(message.subject)}"
                                   class="bg-blue-600 text-white px-3 py-2 rounded-lg hover:bg-blue-700 transition text-sm">
                                    Balas Email
                                </a>
                                <button onclick="window.print()"
                                        class="bg-gray-600 text-white px-3 py-2 rounded-lg hover:bg-gray-700 transition text-sm">
                                    Print
                                </button>
                                <button onclick="closeDetailView()"
                                        class="bg-gray-600 text-white px-3 py-2 rounded-lg hover:bg-gray-700 transition text-sm">
                                    Close
                                </button>
                            </div>
                        </div>
                    </div>
                    <div class="flex-1 p-2 overflow-y-auto">
                        <div class="bg-gray-50 rounded-lg p-4">
                            <p class="text-gray-700 whitespace-pre-wrap leading-relaxed text-base text-left m-0 ml-0 pl-0" style="text-align: left !important;">
                                ${message.message.replace(/\n/g, '<br>')}
                            </p>
                        </div>
                    </div>
                `;
            }
        }

        function closeDetailView() {
            const detailSection = document.querySelector('.lg\\:flex-1');
            if (detailSection) {
                detailSection.innerHTML = `
                    <div class="flex-1 flex items-center justify-center">
                        <div class="text-center">
                            <svg class="w-16 h-16 mx-auto mb-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/>
                            </svg>
                            <h3 class="text-xl font-semibold text-gray-700 mb-2">Pilih Pesan</h3>
                            <p class="text-gray-500">Klik pada pesan di sebelah kiri untuk melihat detailnya.</p>
                        </div>
                    </div>
                `;
            }
        }

        function closeModal() {
            document.getElementById('messageModal').classList.add('hidden');
        }
    </script>
</body>
</html>
