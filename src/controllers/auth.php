<?php
// src/controllers/auth.php

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/urls.php';
session_start();

/**
 * Helper: redirect to URL
 */
function redirect($url) {
    header("Location: $url");
    exit;
}

/**
 * Helper: sanitize input
 */
function sanitize($data) {
    return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
}

/**
 * Register logic
 */
function handle_register() {
    global $pdo;
    $error = null;

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $username = sanitize($_POST['username'] ?? '');
        $email = sanitize($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';

        // Validasi
        if (!$username || !$email || !$password || !$confirm_password) {
            $error = "Semua field wajib diisi.";
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = "Email tidak valid.";
        } elseif ($password !== $confirm_password) {
            $error = "Konfirmasi password tidak cocok.";
        } elseif (strlen($password) < 6) {
            $error = "Password minimal 6 karakter.";
        } else {
            // Cek username/email sudah ada
            $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
            $stmt->execute([$username, $email]);
            if ($stmt->fetch()) {
                $error = "Username atau email sudah terdaftar.";
            } else {
                // Hash password
                $hash = password_hash($password, PASSWORD_DEFAULT);
                // Insert user baru (role default: user)
                $stmt = $pdo->prepare("INSERT INTO users (username, email, password, role) VALUES (?, ?, ?, 'user')");
                if ($stmt->execute([$username, $email, $hash])) {
                    // Auto login setelah register
                    $_SESSION['user'] = [
                        'id' => $pdo->lastInsertId(),
                        'username' => $username,
                        'email' => $email,
                        'role' => 'user'
                    ];
                    redirect(BASE_URL . '/dashboard');
                } else {
                    $error = "Gagal mendaftar, coba lagi.";
                }
            }
        }
    }

    // Tampilkan halaman register
    include __DIR__ . '/../views/auth/register.php';
}

/**
 * Login logic
 */
function handle_login() {
    global $pdo;
    $error = null;

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $username = sanitize($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';

        if (!$username || !$password) {
            $error = "Username dan password wajib diisi.";
        } else {
            // Cari user
            $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
            $stmt->execute([$username]);
            $user = $stmt->fetch();
            if ($user && password_verify($password, $user['password'])) {
                // Set session
                $_SESSION['user'] = [
                    'id' => $user['id'],
                    'username' => $user['username'],
                    'email' => $user['email'],
                    'role' => $user['role']
                ];
                // Redirect sesuai role
                if ($user['role'] === 'admin') {
                    redirect(BASE_URL . '/admin');
                } else {
                    redirect(BASE_URL . '/dashboard');
                }
            } else {
                $error = "Username atau password salah.";
            }
        }
    }

    // Tampilkan halaman login
    include __DIR__ . '/../views/auth/login.php';
}

/**
 * Logout logic
 */
function handle_logout() {
    session_destroy();
    redirect(BASE_URL . '/login');
}

/**
 * Routing sederhana untuk auth
 * Contoh penggunaan:
 *   require 'src/controllers/auth.php';
 *   route_auth('login'); // atau 'register', 'logout'
 */
function route_auth($action) {
    switch ($action) {
        case 'register':
            handle_register();
            break;
        case 'login':
            handle_login();
            break;
        case 'logout':
            handle_logout();
            break;
        default:
            handle_login();
            break;
    }
}
