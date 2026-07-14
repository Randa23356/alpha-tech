<?php
// src/helpers/session.php

// Start session if not started
if (session_status() === PHP_SESSION_NONE) {
    // Set session cookie lifetime to 1 year (31536000 seconds)
    ini_set('session.gc_maxlifetime', 31536000);
    session_set_cookie_params([
        'lifetime' => 31536000,
        'path' => '/',
        'domain' => '', // Default to current domain
        'secure' => isset($_SERVER['HTTPS']), // Only secure if HTTPS
        'httponly' => true,
        'samesite' => 'Lax' // Important for cross-site requests if any
    ]);
    session_start();
}

require_once __DIR__ . "/../config/urls.php";

/**
 * Set user session after login
 * @param array $userData - ['id', 'username', 'role', ...]
 */
function loginUser($userData) {
    $_SESSION['user'] = [
        'id' => $userData['id'],
        'username' => $userData['username'],
        'role' => $userData['role'],
        'email' => $userData['email'] ?? null,
        'full_name' => $userData['full_name'] ?? null,
        'google_id' => $userData['google_id'] ?? null,
        'profile_pic' => $userData['profile_pic'] ?? null
    ];
}

/**
 * Logout user and destroy session
 */
function logoutUser() {
    $_SESSION = [];
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    session_destroy();
}

/**
 * Check if user is logged in
 * @return bool
 */
function isLoggedIn() {
    return isset($_SESSION['user']);
}

/**
 * Get current user data
 * @return array|null
 */
function getCurrentUser() {
    return $_SESSION['user'] ?? null;
}

/**
 * Check if current user is admin
 * @return bool
 */
function isAdmin() {
    return isLoggedIn() && ($_SESSION['user']['role'] === 'admin');
}

/**
 * Check if current user is user (not admin)
 * @return bool
 */
function isUser() {
    return isLoggedIn() && ($_SESSION['user']['role'] === 'user');
}

/**
 * Check if current user is korti (koordinator kelas)
 * @return bool
 */
function isKorti() {
    return isLoggedIn() && ($_SESSION['user']['role'] === 'korti');
}

/**
 * Check if current user is admin or korti
 * @return bool
 */
function isAdminOrKorti() {
    return isLoggedIn() && (isAdmin() || isKorti());
}

/**
 * Get user profile picture from database
 * @return string|null
 */
function getUserProfilePic() {
    if (!isset($_SESSION['user']['id'])) {
        return null;
    }
    
    global $pdo;
    try {
        $stmt = $pdo->prepare("SELECT profile_pic FROM users WHERE id = ?");
        $stmt->execute([$_SESSION['user']['id']]);
        $result = $stmt->fetch();
        $profilePic = $result['profile_pic'] ?? null;
        
        if (empty($profilePic)) {
            $initial = substr($_SESSION['user']['full_name'] ?: 'U', 0, 1);
            return url('public/default-avatar.php?initial=' . urlencode($initial));
        }
        
        return $profilePic;
    } catch (Exception $e) {
        error_log("Error getting user profile pic: " . $e->getMessage());
        return null;
    }
}

/**
 * Check if user logged in via Google
 * @return bool
 */
function isGoogleUser() {
    return isLoggedIn() && !empty($_SESSION['user']['google_id']);
}

/**
 * Update session data from database
 * Useful when user data changes (profile update, etc)
 */
function refreshUserSession() {
    if (!isLoggedIn()) {
        return false;
    }
    
    global $pdo;
    try {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$_SESSION['user']['id']]);
        $userData = $stmt->fetch();
        
        if ($userData) {
            loginUser($userData);
            return true;
        }
    } catch (Exception $e) {
        error_log("Error refreshing user session: " . $e->getMessage());
    }
    
    return false;
}

/**
 * Redirect to login if not authenticated
 */
function requireLogin() {
    if (!isLoggedIn()) {
        header("Location: /informatics_a/login");
        exit();
    }
}

/**
 * Redirect to home if already logged in
 */
function requireGuest() {
    if (isLoggedIn()) {
        header("Location: /informatics_a/");
        exit();
    }
}

/**
 * Require specific role access
 */
function requireRole($role) {
    requireLogin();
    
    $allowed = false;
    switch ($role) {
        case 'admin':
            $allowed = isAdmin();
            break;
        case 'korti':
            $allowed = isKorti();
            break;
        case 'user':
            $allowed = isUser();
            break;
        case 'admin_or_korti':
            $allowed = isAdminOrKorti();
            break;
        default:
            $allowed = false;
    }
    
    if (!$allowed) {
        header("Location: /informatics_a/");
        exit();
    }
}

/**
 * Set flash message for one-time display
 */
function setFlashMessage($type, $message) {
    $_SESSION['flash'] = [
        'type' => $type,
        'message' => $message
    ];
}

/**
 * Get and clear flash message
 */
function getFlashMessage() {
    $flash = $_SESSION['flash'] ?? null;
    unset($_SESSION['flash']);
    return $flash;
}

/**
 * Toggle like for a post or comment
 * @param int $targetId - post_id or comment_id
 * @param string $type - 'post' or 'comment'
 * @return bool - true if liked, false if unliked, null on error
 */
function toggleLike($targetId, $type) {
    if (!isLoggedIn()) {
        return null;
    }

    global $pdo;
    $user = getCurrentUser();

    try {
        // Check if already liked
        $stmt = $pdo->prepare("SELECT id FROM likes WHERE user_id = ? AND target_id = ? AND type = ?");
        $stmt->execute([$user['id'], $targetId, $type]);
        $existingLike = $stmt->fetch();

        if ($existingLike) {
            // Unlike - remove the like
            $stmt = $pdo->prepare("DELETE FROM likes WHERE id = ?");
            $stmt->execute([$existingLike['id']]);
            return false;
        } else {
            // Like - add the like
            $stmt = $pdo->prepare("INSERT INTO likes (user_id, target_id, type) VALUES (?, ?, ?)");
            $stmt->execute([$user['id'], $targetId, $type]);
            return true;
        }
    } catch (Exception $e) {
        error_log("Error toggling like: " . $e->getMessage());
        return null;
    }
}

/**
 * Check if current user has liked a post or comment
 * @param int $targetId - post_id or comment_id
 * @param string $type - 'post' or 'comment'
 * @return bool
 */
function isLiked($targetId, $type) {
    if (!isLoggedIn()) {
        return false;
    }

    global $pdo;
    $user = getCurrentUser();

    try {
        $stmt = $pdo->prepare("SELECT id FROM likes WHERE user_id = ? AND target_id = ? AND type = ?");
        $stmt->execute([$user['id'], $targetId, $type]);
        return !empty($stmt->fetch());
    } catch (Exception $e) {
        error_log("Error checking like status: " . $e->getMessage());
        return false;
    }
}

/**
 * Get like count for a post or comment
 * @param int $targetId - post_id or comment_id
 * @param string $type - 'post' or 'comment'
 * @return int
 */
function getLikeCount($targetId, $type) {
    global $pdo;

    try {
        $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM likes WHERE target_id = ? AND type = ?");
        $stmt->execute([$targetId, $type]);
        $result = $stmt->fetch();
        return (int)($result['count'] ?? 0);
    } catch (Exception $e) {
        error_log("Error getting like count: " . $e->getMessage());
        return 0;
    }
}

/**
 * Get users who liked a post or comment
 * @param int $targetId - post_id or comment_id
 * @param string $type - 'post' or 'comment'
 * @param int $limit - maximum number of users to return
 * @return array
 */
function getUsersWhoLiked($targetId, $type, $limit = 10) {
    global $pdo;

    try {
        $stmt = $pdo->prepare("
            SELECT u.id as user_id, u.username, u.profile_pic, l.created_at
            FROM likes l
            JOIN users u ON l.user_id = u.id
            WHERE l.target_id = ? AND l.type = ?
            ORDER BY l.created_at DESC
            LIMIT ?
        ");
        $stmt->execute([$targetId, $type, $limit]);
        return $stmt->fetchAll();
    } catch (Exception $e) {
        error_log("Error getting users who liked: " . $e->getMessage());
        return [];
    }
}