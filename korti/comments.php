<?php
// korti/comments.php
session_start();
require_once __DIR__ . "/../src/helpers/session.php";
require_once __DIR__ . "/../src/config/db.php";
require_once __DIR__ . "/../src/config/urls.php";

// Proteksi: hanya korti yang bisa akses
if (!isLoggedIn() || getCurrentUser()['role'] !== 'korti') {
    header("Location: " . url('login'));
    exit();
}

// Handle aksi delete komentar
if (
    $_SERVER["REQUEST_METHOD"] === "POST" &&
    isset($_POST["action"], $_POST["comment_id"])
) {
    $comment_id = intval($_POST["comment_id"]);
    $action = $_POST["action"];

    if ($action === "delete") {
        $stmt = $pdo->prepare("DELETE FROM comments WHERE id = ?");
        $stmt->execute([$comment_id]);
    }
}

// Ambil semua komentar kegiatan
$stmt = $pdo->query(
    "SELECT comments.*, users.username, posts.title
     FROM comments
     LEFT JOIN users ON comments.user_id = users.id
     LEFT JOIN posts ON comments.post_id = posts.id
     ORDER BY comments.created_at DESC",
);
$comments = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Kelola Komentar Kegiatan - Admin Informatics A</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="<?= url('public/tailwind.css') ?>" rel="stylesheet">
    <?php require_once __DIR__ . '/../includes/favicon.php'; ?>
    <script>
        // Delete Modal Functions - Define early so they're available for HTML elements
        let deleteModal = null;
        let deleteId = null;

        function showDeleteModal(id, title) {
            deleteModal = document.getElementById('deleteModal');
            deleteId = id;

            document.getElementById('deleteCommentTitle').textContent = title;

            deleteModal.classList.remove('hidden');
        }

        function hideDeleteModal() {
            if (deleteModal) {
                deleteModal.classList.add('hidden');
                deleteModal = null;
                deleteId = null;
            }
        }

        function submitDeleteForm() {
            if (deleteId) {
                // Create a form and submit it
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = '';

                const commentIdInput = document.createElement('input');
                commentIdInput.type = 'hidden';
                commentIdInput.name = 'comment_id';
                commentIdInput.value = deleteId;

                const actionInput = document.createElement('input');
                actionInput.type = 'hidden';
                actionInput.name = 'action';
                actionInput.value = 'delete';

                form.appendChild(commentIdInput);
                form.appendChild(actionInput);
                document.body.appendChild(form);
                form.submit();
            }
        }
    </script>
</head>
<body class="bg-gradient-to-br from-gray-50 to-blue-50 min-h-screen">
    <?php include __DIR__ . '/../includes/navbar.php'; ?>
    
    <?php include __DIR__ . '/../includes/korti_sidebar.php'; ?>
    
    <!-- Header -->
    <header class="lg:ml-64 bg-gradient-to-r from-blue-900 via-blue-800 to-indigo-900 text-white py-10 px-6">
        <div class="max-w-7xl mx-auto">
            <div class="flex items-center gap-4">
                <div class="bg-white/10 backdrop-blur-sm p-3 rounded-xl">
                    <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z"/>
                    </svg>
                </div>
                <div>
                    <h1 class="text-3xl font-bold mb-1">Kelola Komentar Kegiatan</h1>
                    <p class="text-blue-100">Moderasi komentar pada postingan kegiatan</p>
                </div>
            </div>
        </div>
    </header>

    <main class="lg:ml-64 max-w-7xl mx-auto px-6 py-10">
        <?php if (empty($comments)): ?>
            <div class="bg-white rounded-2xl shadow-lg p-12 text-center">
                <svg class="w-20 h-20 mx-auto mb-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z"/>
                </svg>
                <h3 class="text-xl font-bold text-gray-700 mb-2">Belum Ada Komentar</h3>
                <p class="text-gray-500">Belum ada komentar pada kegiatan.</p>
            </div>
        <?php else: ?>
            <div class="space-y-4">
                <?php foreach ($comments as $comment): ?>
                    <div class="bg-white rounded-2xl shadow-lg p-6 hover:shadow-xl transition">
                        <div class="flex items-start justify-between mb-4">
                            <div class="flex-1">
                                <div class="flex items-center gap-3 mb-2">
                                    <div class="bg-blue-100 p-2 rounded-lg">
                                        <svg class="w-5 h-5 text-blue-900" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                                        </svg>
                                    </div>
                                    <div>
                                        <p class="font-bold text-gray-900"><?= htmlspecialchars($comment["username"] ?? "Anonim") ?></p>
                                        <p class="text-sm text-gray-500">pada: <span class="font-medium text-blue-900"><?= htmlspecialchars($comment["title"] ?? "-") ?></span></p>
                                    </div>
                                </div>
                                <div class="bg-gray-50 p-4 rounded-xl mb-3">
                                    <p class="text-gray-700 leading-relaxed"><?= nl2br(htmlspecialchars($comment["comment"])) ?></p>
                                </div>
                                <div class="flex items-center gap-2 text-sm text-gray-600">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                    </svg>
                                    <?= htmlspecialchars(date("d M Y H:i", strtotime($comment["created_at"]))) ?>
                                </div>
                            </div>
                            <form action="" method="POST">
                                <input type="hidden" name="comment_id" value="<?= $comment["id"] ?>">
                                <button type="submit" name="action" value="delete" class="inline-flex items-center gap-2 bg-red-600 text-white px-4 py-2 rounded-lg hover:bg-red-700 transition font-medium" onclick="showDeleteModal(<?= $comment['id'] ?>, '<?= htmlspecialchars(addslashes($comment['username'] ?? 'Anonim')) ?>'); return false;">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                    </svg>
                                    Hapus
                                </button>
                            </form>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
        <div class="mt-8 text-center">
            <a href="dashboard.php" class="inline-flex items-center gap-2 text-blue-900 font-semibold hover:text-blue-700 transition">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                </svg>
                Kembali ke Dashboard
            </a>
        </div>
    </main>
    
    <!-- Delete Confirmation Modal -->
    <div id="deleteModal" class="fixed inset-0 bg-black bg-opacity-50 hidden z-50">
        <div class="flex items-center justify-center min-h-screen p-4">
            <div class="bg-white rounded-xl shadow-xl max-w-md w-full">
                <!-- Modal Header -->
                <div class="p-6 border-b">
                    <div class="flex items-center gap-3">
                        <div class="bg-red-100 p-2 rounded-full">
                            <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-lg font-bold text-gray-900">Hapus Komentar</h3>
                            <p class="text-sm text-gray-600">Konfirmasi penghapusan komentar</p>
                        </div>
                    </div>
                </div>

                <!-- Modal Body -->
                <div class="p-6">
                    <p class="text-gray-700 mb-4">
                        Apakah Anda yakin ingin menghapus:
                        <strong id="deleteCommentTitle" class="text-red-600"></strong>?
                    </p>
                    <p class="text-sm text-gray-500 mb-6">
                        Tindakan ini tidak dapat dibatalkan. Komentar akan dihapus secara permanen.
                    </p>

                    <!-- Modal Footer -->
                    <div class="flex gap-3">
                        <button onclick="hideDeleteModal()" class="flex-1 px-4 py-2 border border-gray-300 text-gray-700 rounded-lg font-medium hover:bg-gray-50 transition">
                            Batal
                        </button>
                        <button id="confirmDeleteBtn" onclick="submitDeleteForm()" class="flex-1 px-4 py-2 bg-red-600 text-white rounded-lg font-medium hover:bg-red-700 transition">
                            Hapus
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <footer class="lg:ml-64 bg-white border-t border-gray-200 py-6 mt-12">
        <div class="max-w-7xl mx-auto px-6 text-center text-gray-600">
            &copy; <?= date("Y") ?> Informatics A. All rights reserved.
        </div>
    </footer>

    <script>
        // Sidebar toggle for mobile
        const sidebarToggle = document.getElementById('sidebar-toggle');
        const sidebar = document.getElementById('sidebar');
        const sidebarOverlay = document.getElementById('sidebar-overlay');

        function toggleSidebar() {
            sidebar.classList.toggle('-translate-x-full');
            sidebarOverlay.classList.toggle('hidden');
        }

        sidebarToggle?.addEventListener('click', toggleSidebar);
        sidebarOverlay?.addEventListener('click', toggleSidebar);

        // Delete Modal Functions
        let deleteModal = null;
        let deleteId = null;

        function showDeleteModal(id, title) {
            deleteModal = document.getElementById('deleteModal');
            deleteId = id;

            document.getElementById('deleteCommentTitle').textContent = title;

            deleteModal.classList.remove('hidden');
        }

        function hideDeleteModal() {
            if (deleteModal) {
                deleteModal.classList.add('hidden');
                deleteModal = null;
                deleteId = null;
            }
        }

        function submitDeleteForm() {
            if (deleteId) {
                // Create a form and submit it
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = '';

                const commentIdInput = document.createElement('input');
                commentIdInput.type = 'hidden';
                commentIdInput.name = 'comment_id';
                commentIdInput.value = deleteId;

                const actionInput = document.createElement('input');
                actionInput.type = 'hidden';
                actionInput.name = 'action';
                actionInput.value = 'delete';

                form.appendChild(commentIdInput);
                form.appendChild(actionInput);
                document.body.appendChild(form);
                form.submit();
            }
        }

        // Close modal when clicking outside
        document.getElementById('deleteModal').addEventListener('click', function(e) {
            if (e.target === this) {
                hideDeleteModal();
            }
        });
    </script>
</body>
</html>
