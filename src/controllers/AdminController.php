<?php
// src/controllers/AdminController.php

// ============================================
// ADMIN DASHBOARD
// ============================================

function dashboard() {
    if (!isLoggedIn() || !isAdminOrKorti()) {
        Router::redirect('/login');
    }
    
    global $pdo;
    $admin = getCurrentUser();
    
    // Fetch statistics
    try {
        $stmt = $pdo->query("SELECT COUNT(*) as total FROM posts WHERE status = 'pending'");
        $pending_posts = $stmt->fetch()['total'] ?? 0;
        
        $stmt = $pdo->query("SELECT COUNT(*) as total FROM posts WHERE status = 'approved'");
        $approved_posts = $stmt->fetch()['total'] ?? 0;
        
        $stmt = $pdo->query("SELECT COUNT(*) as total FROM users WHERE role = 'user'");
        $total_users = $stmt->fetch()['total'] ?? 0;
        
        $stmt = $pdo->query("SELECT COUNT(*) as total FROM comments");
        $total_comments = $stmt->fetch()['total'] ?? 0;
    } catch (Exception $e) {
        $pending_posts = 0;
        $approved_posts = 0;
        $total_users = 0;
        $total_comments = 0;
    }
    
    require __DIR__ . '/../views/admin/dashboard.php';
}

// ============================================
// MANAGE POSTS
// ============================================

function managePosts() {
    if (!isLoggedIn() || !isAdminOrKorti()) {
        Router::redirect('/login');
    }
    
    global $pdo;
    
    // Fetch all posts
    try {
        $stmt = $pdo->query("SELECT posts.*, users.username FROM posts JOIN users ON posts.user_id = users.id ORDER BY posts.created_at DESC");
        $posts = $stmt->fetchAll();
    } catch (Exception $e) {
        $posts = [];
    }
    
    require __DIR__ . '/../views/admin/manage_posts.php';
}

function approvePost($id) {
    if (!isLoggedIn() || !isAdminOrKorti()) {
        Router::redirect('/login');
    }
    
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("UPDATE posts SET status = 'approved' WHERE id = ?");
        $stmt->execute([$id]);
    } catch (Exception $e) {
        // Handle error
    }
    
    Router::redirect('/admin/posts');
}

function rejectPost($id) {
    if (!isLoggedIn() || !isAdminOrKorti()) {
        Router::redirect('/login');
    }
    
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("UPDATE posts SET status = 'rejected' WHERE id = ?");
        $stmt->execute([$id]);
    } catch (Exception $e) {
        // Handle error
    }
    
    Router::redirect('/admin/posts');
}

function deletePost($id) {
    if (!isLoggedIn() || !isAdminOrKorti()) {
        Router::redirect('/login');
    }
    
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("DELETE FROM posts WHERE id = ?");
        $stmt->execute([$id]);
    } catch (Exception $e) {
        // Handle error
    }
    
    Router::redirect('/admin/posts');
}

// ============================================
// MANAGE GALLERY
// ============================================

function manageGallery() {
    if (!isLoggedIn() || !isAdminOrKorti()) {
        Router::redirect('/login');
    }
    
    global $pdo;
    
    // Fetch all posts with images
    try {
        $stmt = $pdo->query("SELECT posts.*, users.username FROM posts JOIN users ON posts.user_id = users.id WHERE posts.image IS NOT NULL AND posts.image != '' ORDER BY posts.created_at DESC");
        $gallery = $stmt->fetchAll();
    } catch (Exception $e) {
        $gallery = [];
    }
    
    $error = null;
    $success = null;
    
    require __DIR__ . '/../views/admin/manage_gallery.php';
}

function uploadGallery() {
    if (!isLoggedIn() || !isAdminOrKorti()) {
        Router::redirect('/login');
    }
    
    global $pdo;
    $admin = getCurrentUser();
    
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $caption = htmlspecialchars(trim($_POST['caption'] ?? ''));
        
        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $uploadDir = __DIR__ . '/../../public/uploads/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }
            
            $fileExtension = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
            $fileName = uniqid() . '_' . time() . '.' . $fileExtension;
            $targetPath = $uploadDir . $fileName;
            
            if (move_uploaded_file($_FILES['image']['tmp_name'], $targetPath)) {
                $imagePath = $fileName; // Simpan hanya nama file
                
                // Insert into gallery table
                try {
                    $stmt = $pdo->prepare("INSERT INTO gallery (image, caption, uploaded_by, status) VALUES (?, ?, ?, 'approved')");
                    $stmt->execute([$imagePath, $caption, $admin['id']]);
                } catch (Exception $e) {
                    // Handle error
                }
            }
        }
    }
    
    Router::redirect('/admin/gallery');
}

function deleteGallery($id) {
    if (!isLoggedIn() || !isAdminOrKorti()) {
        Router::redirect('/login');
    }
    
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("DELETE FROM gallery WHERE id = ?");
        $stmt->execute([$id]);
    } catch (Exception $e) {
        // Handle error
    }
    
    Router::redirect('/admin/gallery');
}

// ============================================
// MANAGE COMMENTS
// ============================================

function manageComments() {
    if (!isLoggedIn() || !isAdminOrKorti()) {
        Router::redirect('/login');
    }
    
    global $pdo;
    
    // Fetch all comments
    try {
        $stmt = $pdo->query("SELECT comments.*, users.username, posts.title FROM comments JOIN users ON comments.user_id = users.id JOIN posts ON comments.post_id = posts.id ORDER BY comments.created_at DESC");
        $comments = $stmt->fetchAll();
    } catch (Exception $e) {
        $comments = [];
    }
    
    require __DIR__ . '/../views/admin/manage_comments.php';
}

function deleteComment($id) {
    if (!isLoggedIn() || !isAdminOrKorti()) {
        Router::redirect('/login');
    }
    
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("DELETE FROM comments WHERE id = ?");
        $stmt->execute([$id]);
    } catch (Exception $e) {
        // Handle error
    }
    
    Router::redirect('/admin/comments');
}

// ============================================
// MANAGE ANNOUNCEMENTS
// ============================================

function manageAnnouncements() {
    if (!isLoggedIn() || !isAdminOrKorti()) {
        Router::redirect('/login');
    }
    
    global $pdo;
    
    // Fetch all announcements
    try {
        $stmt = $pdo->query("SELECT * FROM announcements ORDER BY created_at DESC");
        $announcements = $stmt->fetchAll();
    } catch (Exception $e) {
        $announcements = [];
    }
    
    $error = null;
    $success = null;
    
    require __DIR__ . '/../views/admin/manage_announcements.php';
}

function createAnnouncement() {
    if (!isLoggedIn() || !isAdminOrKorti()) {
        Router::redirect('/login');
    }
    
    global $pdo;
    $admin = getCurrentUser();
    
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $title = htmlspecialchars(trim($_POST['title'] ?? ''));
        $content = htmlspecialchars(trim($_POST['content'] ?? ''));
        
        if ($title && $content) {
            try {
                $stmt = $pdo->prepare("INSERT INTO announcements (title, content, author) VALUES (?, ?, ?)");
                $stmt->execute([$title, $content, $admin['username']]);
            } catch (Exception $e) {
                // Handle error
            }
        }
    }
    
    Router::redirect('/admin/announcements');
}

function deleteAnnouncement($id) {
    if (!isLoggedIn() || !isAdminOrKorti()) {
        Router::redirect('/login');
    }
    
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("DELETE FROM announcements WHERE id = ?");
        $stmt->execute([$id]);
    } catch (Exception $e) {
        // Handle error
    }
    
    Router::redirect('/admin/announcements');
}

// ============================================
// SITE SETTINGS
// ============================================

function siteSettings() {
    if (!isLoggedIn() || !isAdminOrKorti()) {
        Router::redirect('/login');
    }
    
    global $pdo;
    
    // Fetch all settings
    try {
        $stmt = $pdo->query("SELECT * FROM site_settings");
        $settings = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
    } catch (Exception $e) {
        $settings = [];
    }
    
    $error = null;
    $success = null;
    
    require __DIR__ . '/../views/admin/site_settings.php';
}

function updateSettings() {
    if (!isLoggedIn() || !isAdminOrKorti()) {
        Router::redirect('/login');
    }
    
    global $pdo;
    
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        foreach ($_POST as $key => $value) {
            if ($key !== 'submit') {
                $value = htmlspecialchars(trim($value));
                try {
                    $stmt = $pdo->prepare("UPDATE site_settings SET setting_value = ? WHERE setting_key = ?");
                    $stmt->execute([$value, $key]);
                } catch (Exception $e) {
                    // Handle error
                }
            }
        }
    }
    
    Router::redirect('/admin/settings');
}

// ============================================
// MANAGE USERS
// ============================================

function manageUsers() {
    if (!isLoggedIn() || !isAdminOrKorti()) {
        Router::redirect('/login');
    }
    
    global $pdo;
    
    // Fetch all users
    try {
        $stmt = $pdo->query("SELECT * FROM users ORDER BY created_at DESC");
        $users = $stmt->fetchAll();
    } catch (Exception $e) {
        $users = [];
    }
    
    require __DIR__ . '/../views/admin/manage_users.php';
}

function userDetail($id) {
    if (!isLoggedIn() || !isAdminOrKorti()) {
        Router::redirect('/login');
    }
    
    global $pdo;
    
    // Fetch user details
    try {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$id]);
        $user = $stmt->fetch();
        
        if (!$user) {
            Router::redirect('/admin/users');
        }
        
        // Fetch user's posts
        $stmt = $pdo->prepare("SELECT * FROM posts WHERE user_id = ? ORDER BY created_at DESC");
        $stmt->execute([$id]);
        $posts = $stmt->fetchAll();
        
        // Fetch user's comments
        $stmt = $pdo->prepare("SELECT comments.*, posts.title FROM comments JOIN posts ON comments.post_id = posts.id WHERE comments.user_id = ? ORDER BY comments.created_at DESC");
        $stmt->execute([$id]);
        $comments = $stmt->fetchAll();
    } catch (Exception $e) {
        Router::redirect('/admin/users');
    }
    
    require __DIR__ . '/../views/admin/user_detail.php';
}

function deleteUser($id) {
    if (!isLoggedIn() || !isAdminOrKorti()) {
        Router::redirect('/login');
    }
    
    global $pdo;
    $currentUser = getCurrentUser();
    
    // Prevent deleting yourself
    if ($id == $currentUser['id']) {
        Router::redirect('/admin/users');
    }
    
    try {
        $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
        $stmt->execute([$id]);
    } catch (Exception $e) {
        // Handle error
    }
    
    Router::redirect('/admin/users');
}
