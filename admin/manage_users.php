<?php
ini_set("display_errors", 1);
ini_set("display_startup_errors", 1);
error_reporting(E_ALL);
// informatics_a/admin/manage_users.php
require_once __DIR__ . "/../src/helpers/session.php";
require_once __DIR__ . "/../src/config/db.php";
require_once __DIR__ . "/../src/config/urls.php";

// Load theme colors from database
$primary_color = "#1e3a8a"; // default
$secondary_color = "#1e40af"; // default

try {
    $stmt = $pdo->query("SELECT setting_key, setting_value FROM site_settings WHERE setting_key IN ('primary_color', 'secondary_color')");
    $settings = [];
    foreach ($stmt->fetchAll() as $row) {
        $settings[$row['setting_key']] = $row['setting_value'];
    }
    $primary_color = $settings['primary_color'] ?? $primary_color;
    $secondary_color = $settings['secondary_color'] ?? $secondary_color;
} catch (Exception $e) {
    // Use default colors if database fails
}

// Proteksi: hanya admin yang bisa akses
if (!isLoggedIn() || !isAdmin()) {
    header("Location: " . url('login'));
    exit();
}

$success = null;
$error = null;

// Handle actions (delete, update role, add user)
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    if (isset($_POST["delete_user"])) {
        $user_id = (int) $_POST["user_id"];

        // Prevent admin from deleting themselves
        if ($user_id === $_SESSION["user"]["id"]) {
            $error = "Tidak bisa menghapus akun sendiri!";
        } else {
            $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
            if ($stmt->execute([$user_id])) {
                $success = "User berhasil dihapus!";
            } else {
                $error = "Gagal menghapus user.";
            }
        }
    }

    if (isset($_POST["update_role"])) {
        $user_id = (int) $_POST["user_id"];
        $new_role = $_POST["role"];

        // Prevent admin from changing their own role
        if ($user_id === $_SESSION["user"]["id"]) {
            $error = "Tidak bisa mengubah role akun sendiri!";
        } else {
            $stmt = $pdo->prepare("UPDATE users SET role = ? WHERE id = ?");
            if ($stmt->execute([$new_role, $user_id])) {
                $success = "Role user berhasil diupdate!";
            } else {
                $error = "Gagal mengupdate role user.";
            }
        }
    }

    if (isset($_POST["add_user"])) {
        $username = trim($_POST["username"]);
        $email = trim($_POST["email"]);
        $password = $_POST["password"];
        $role = $_POST["role"];

        $add_user_error = null;

        // Validate input
        if (empty($username) || empty($email) || empty($password)) {
            $add_user_error = "Semua field harus diisi!";
        } elseif (strlen($username) < 3) {
            $add_user_error = "Username minimal 3 karakter!";
        } elseif (strlen($password) < 6) {
            $add_user_error = "Password minimal 6 karakter!";
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $add_user_error = "Format email tidak valid!";
        } else {
            // Check if username or email already exists
            $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
            $stmt->execute([$username, $email]);
            if ($stmt->rowCount() > 0) {
                $add_user_error = "Username atau email sudah digunakan!";
            } else {
                // Hash password and insert user
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("INSERT INTO users (username, email, password, role) VALUES (?, ?, ?, ?)");
                if ($stmt->execute([$username, $email, $hashed_password, $role])) {
                    $success = "User berhasil ditambahkan!";
                } else {
                    $add_user_error = "Gagal menambahkan user!";
                }
            }
        }
    }
}

// Ambil data users dengan jumlah postingan
$stmt = $pdo->query("
    SELECT u.*,
           COALESCE(post_counts.post_count, 0) as post_count
    FROM users u
    LEFT JOIN (
        SELECT user_id, COUNT(*) as post_count
        FROM posts
        WHERE deleted_at IS NULL
        GROUP BY user_id
    ) post_counts ON u.id = post_counts.user_id
    ORDER BY u.created_at DESC
");
$users = $stmt->fetchAll();

// Hitung statistik
$total_users = count($users);
$admin_count = 0;
$korti_count = 0;
$user_count = 0;

foreach ($users as $user) {
    switch ($user["role"]) {
        case "admin":
            $admin_count++;
            break;
        case "korti":
            $korti_count++;
            break;
        case "user":
            $user_count++;
            break;
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Kelola User - Admin Informatics A</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="<?= asset('tailwind.css') ?>" rel="stylesheet">
    <?php require_once __DIR__ . '/../includes/favicon.php'; ?>
</head>
<body class="bg-gradient-to-br from-gray-50 to-blue-50 min-h-screen">
    <?php include __DIR__ . "/../includes/navbar.php"; ?>
    <?php include __DIR__ . "/sidebar.php"; ?>

    <!-- Header -->
    <header class="lg:ml-64 text-white py-10 px-6" style="background: linear-gradient(135deg, <?= $primary_color ?> 0%, <?= $secondary_color ?> 100%);">
        <div class="max-w-7xl mx-auto">
            <div class="flex items-center gap-4">
                <div class="bg-white/10 backdrop-blur-sm p-3 rounded-xl">
                    <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>
                    </svg>
                </div>
                <div>
                    <h1 class="text-3xl font-bold mb-1">Kelola User</h1>
                    <p class="text-white/90">Manage semua user yang terdaftar di sistem</p>
                </div>
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <main class="lg:ml-64 max-w-7xl mx-auto px-6 py-10">
        <?php if ($error): ?>
            <div class="mb-6 p-4 border-l-4 rounded" style="background-color: #fef2f2; border-color: #dc2626; color: #dc2626;">
                <div class="flex items-center gap-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <?= htmlspecialchars($error) ?>
                </div>
            </div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="mb-6 p-4 border-l-4 rounded" style="background-color: #f0fdf4; border-color: #16a34a; color: #16a34a;">
                <div class="flex items-center gap-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <?= htmlspecialchars($success) ?>
                </div>
            </div>
        <?php endif; ?>

        <!-- Statistics Cards -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
            <div class="bg-white rounded-xl shadow-lg p-6 border-l-4" style="border-color: <?= $primary_color ?>;">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-600 text-sm font-medium mb-1">Total Users</p>
                        <p class="text-3xl font-bold text-gray-900 leading-tight" style="color: <?= $primary_color ?>;"><?=$total_users ?></p>
                    </div>
                    <div class="p-3 rounded-lg" style="background-color: <?= $primary_color ?>20;">
                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="color: <?= $primary_color ?>;">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>
                        </svg>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-xl shadow-lg p-6 border-l-4" style="border-color: #10b981;">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-600 text-sm font-medium mb-1">Admin</p>
                        <p class="text-3xl font-bold text-gray-900 leading-tight" style="color: #10b981;"><?=$admin_count ?></p>
                                     </div>
                    <div class="p-3 rounded-lg" style="background-color: #10b98120;">
                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="color: #10b981;">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-xl shadow-lg p-6 border-l-4" style="border-color: #f59e0b;">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-600 text-sm font-medium mb-1">Koordinator</p>
                        <p class="text-3xl font-bold text-gray-900 leading-tight" style="color: #f59e0b;"><?=$korti_count ?></p>
                    </div>
                    <div class="p-3 rounded-lg" style="background-color: #f59e0b20;">
                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="color: #f59e0b;">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                        </svg>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-xl shadow-lg p-6 border-l-4" style="border-color: #8b5cf6;">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-600 text-sm font-medium mb-1">User Biasa</p>
                        <p class="text-3xl font-bold text-gray-900 leading-tight" style="color: #8b5cf6;"><?=$user_count ?></p>
                    </div>
                    <div class="p-3 rounded-lg" style="background-color: #8b5cf620;">
                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="color: #8b5cf6;">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                        </svg>
                    </div>
                </div>
            </div>
        </div>

        <!-- Users Table -->
        <div class="bg-white rounded-xl shadow-lg overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200 flex justify-between items-center">
                <h2 class="text-xl font-bold text-gray-900">Daftar User</h2>
                <button id="addUserBtn" class="inline-flex items-center gap-2 px-4 py-2 rounded-lg hover:opacity-90 transition font-medium" style="background: linear-gradient(135deg, <?= $primary_color ?> 0%, <?= $secondary_color ?> 100%); color: white;">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                    </svg>
                    Tambah User
                </button>
            </div>

            <?php if (empty($users)): ?>
                <div class="p-8 text-center text-gray-500">
                    <p>Tidak ada users ditemukan.</p>
                </div>
            <?php else: ?>
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">User</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Role</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Postingan</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tanggal Daftar</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php foreach ($users as $user): ?>
                            <tr class="hover:bg-gray-50 transition">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center">
                                        <div class="flex-shrink-0 h-10 w-10">
                                            <?php if (!empty($user["profile_pic"])): ?>
                                                <?php if (strpos($user["profile_pic"], 'http') === 0): ?>
                                                    <img class="h-10 w-10 rounded-full object-cover" src="<?= htmlspecialchars($user["profile_pic"]) ?>" alt="">
                                                <?php else: ?>
                                                    <img class="h-10 w-10 rounded-full object-cover" src=" <?= BASE_URL ?>/public/uploads/<?= basename(htmlspecialchars($user["profile_pic"])) ?>" alt="">
                                                <?php endif; ?>
                                            <?php else: ?>
                                                <img class="h-10 w-10 rounded-full object-cover" src="<?= url('public/default-avatar.php?initial=' . urlencode(substr($user["username"], 0, 1)) . '&color=' . urlencode('#1e3a8a')) ?>" alt="Default Avatar">
                                            <?php endif; ?>
                                        </div>
                                        <div class="ml-4">
                                            <div class="text-sm font-medium text-gray-900">
                                                <?= htmlspecialchars($user["username"]) ?>
                                            </div>
                                            <div class="text-sm text-gray-500">
                                                <?= htmlspecialchars($user["email"]) ?>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <form method="POST" class="flex items-center gap-2">
                                        <input type="hidden" name="user_id" value="<?= $user["id"] ?>">
                                        <select name="role" onchange="this.form.submit()"
                                            class="text-sm border border-gray-300 rounded px-2 py-1 focus:outline-none focus:ring-2 focus:ring-blue-500 <?= $user["role"] === "admin" ? "bg-red-100 text-red-800" : ($user["role"] === "korti" ? "bg-yellow-100 text-yellow-800" : "bg-blue-100 text-blue-800") ?>"
                                            <?= $user["id"] === $_SESSION["user"]["id"] ? "disabled" : "" ?>>
                                            <option value="user" <?= $user["role"] === "user" ? "selected" : "" ?>>User</option>
                                            <option value="korti" <?= $user["role"] === "korti" ? "selected" : "" ?>>Koordinator</option>
                                            <option value="admin" <?= $user["role"] === "admin" ? "selected" : "" ?>>Admin</option>
                                        </select>
                                        <input type="hidden" name="update_role" value="1">
                                    </form>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    <?= $user["post_count"] ?> postingan
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    <?= date("d M Y", strtotime($user["created_at"])) ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                <a href="<?= url('admin/users/' . $user["id"]) ?>" class="hover:opacity-80 transition mr-3" style="color: <?= $primary_color ?>;">Detail</a>
                                    <?php if ($user["id"] !== $_SESSION["user"]["id"]): ?>
                                        <form method="POST" onsubmit="return confirm('Apakah Anda yakin ingin menghapus user ini?')" class="inline">
                                            <input type="hidden" name="user_id" value="<?= $user["id"] ?>">
                                            <button type="submit" name="delete_user" class="hover:opacity-80 transition" style="color: #dc2626;">Hapus</button>
                                        </form>
                                    <?php else: ?>
                                        <span class="text-gray-400">Akun sendiri</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </main>

    <!-- Add User Modal -->
    <div id="addUserModal" class="fixed inset-0 bg-black bg-opacity-50 hidden z-50">
        <div class="flex items-center justify-center min-h-screen p-4">
            <div class="bg-white rounded-xl shadow-xl max-w-md w-full">
                <div class="p-6">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-lg font-bold text-gray-900">Tambah User Baru</h3>
                        <button id="closeAddUserModal" class="text-gray-400 hover:text-gray-600">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>

                    <?php if (isset($add_user_error)): ?>
                        <div class="mb-4 p-3 bg-red-100 border border-red-400 text-red-700 rounded-lg">
                            <?= htmlspecialchars($add_user_error) ?>
                        </div>
                    <?php endif; ?>

                    <form method="POST" class="space-y-4">
                        <div>
                            <label for="add_username" class="block text-sm font-medium text-gray-700 mb-1">Username</label>
                            <input type="text" id="add_username" name="username" required
                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2"
                                   style="focus:ring-color: <?= $primary_color ?>; border-color: <?= $primary_color ?>20;"
                                   placeholder="Masukkan username">
                        </div>

                        <div>
                            <label for="add_email" class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                            <input type="email" id="add_email" name="email" required
                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2"
                                   style="focus:ring-color: <?= $primary_color ?>; border-color: <?= $primary_color ?>20;"
                                   placeholder="Masukkan email">
                        </div>

                        <div>
                            <label for="add_password" class="block text-sm font-medium text-gray-700 mb-1">Password</label>
                            <input type="password" id="add_password" name="password" required
                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2"
                                   style="focus:ring-color: <?= $primary_color ?>; border-color: <?= $primary_color ?>20;"
                                   placeholder="Masukkan password">
                        </div>

                        <div>
                            <label for="add_role" class="block text-sm font-medium text-gray-700 mb-1">Role</label>
                            <select id="add_role" name="role" required
                                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2"
                                    style="focus:ring-color: <?= $primary_color ?>; border-color: <?= $primary_color ?>20;">
                                <option value="user">User</option>
                                <option value="korti">Koordinator</option>
                                <option value="admin">Admin</option>
                            </select>
                        </div>

                        <div class="flex gap-3 pt-4">
                            <button type="submit" name="add_user" class="flex-1 py-2 px-4 rounded-lg hover:opacity-90 transition font-medium" style="background: linear-gradient(135deg, <?= $primary_color ?> 0%, <?= $secondary_color ?> 100%); color: white;">
                                Tambah User
                            </button>
                            <button type="button" id="cancelAddUser" class="flex-1 bg-gray-300 text-gray-700 py-2 px-4 rounded-lg hover:bg-gray-400 transition font-medium">
                                Batal
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <footer class="lg:ml-64 bg-white border-t border-gray-200 py-6 mt-12">
        <div class="max-w-7xl mx-auto px-6 text-center text-gray-600">
            &copy; <?= date("Y") ?> Informatics A. All rights reserved.
        </div>
    </footer>
</body>
</html>

<script>
    // Modal functionality for adding users
    const addUserBtn = document.getElementById('addUserBtn');
    const addUserModal = document.getElementById('addUserModal');
    const closeAddUserModal = document.getElementById('closeAddUserModal');
    const cancelAddUser = document.getElementById('cancelAddUser');

    function openAddUserModal() {
        addUserModal.classList.remove('hidden');
        document.getElementById('add_username').focus();
    }

    function closeAddUserModalFunc() {
        addUserModal.classList.add('hidden');
        // Reset form
        document.querySelector('#addUserModal form').reset();
        // Clear any error messages
        const errorDiv = document.querySelector('#addUserModal .bg-red-100');
        if (errorDiv) {
            errorDiv.remove();
        }
    }

    addUserBtn?.addEventListener('click', openAddUserModal);
    closeAddUserModal?.addEventListener('click', closeAddUserModalFunc);
    cancelAddUser?.addEventListener('click', closeAddUserModalFunc);

    // Close modal when clicking outside
    addUserModal?.addEventListener('click', function(e) {
        if (e.target === addUserModal) {
            closeAddUserModalFunc();
        }
    });

    // Close modal with Escape key
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && !addUserModal.classList.contains('hidden')) {
            closeAddUserModalFunc();
        }
    });

    // Auto-refresh page after successful user addition
    <?php if (isset($_POST['add_user']) && !isset($add_user_error)): ?>
        setTimeout(function() {
            window.location.reload();
        }, 1500);
    <?php endif; ?>
</script>
