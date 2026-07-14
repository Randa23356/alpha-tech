<?php
// src/controllers/AuthController.php

function showLogin() {
    // If already logged in, redirect
    if (isLoggedIn()) {
        if (isAdmin()) {
            Router::redirect('/admin');
        } else {
            Router::redirect('/dashboard');
        }
    }
    
    $error = null;
    require __DIR__ . '/../views/auth/login.php';
}

function login() {
    global $pdo;
    $error = null;
    
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $username = htmlspecialchars(trim($_POST['username'] ?? ''));
        $password = $_POST['password'] ?? '';
        
        if (!$username || !$password) {
            $error = "Username dan password wajib diisi.";
        } else {
            // Find user
            $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
            $stmt->execute([$username]);
            $user = $stmt->fetch();
            
            if ($user && password_verify($password, $user['password'])) {
                // Set session
                loginUser($user);
                
                // Redirect based on role
                if ($user['role'] === 'admin' || $user['role'] === 'korti') {
                    Router::redirect('/admin');
                } else {
                    Router::redirect('/dashboard');
                }
            } else {
                $error = "Username atau password salah.";
            }
        }
    }
    
    require __DIR__ . '/../views/auth/login.php';
}

function showRegister() {
    // If already logged in, redirect
    if (isLoggedIn()) {
        Router::redirect('/dashboard');
    }
    
    $error = null;
    require __DIR__ . '/../views/auth/register.php';
}

function register() {
    global $pdo;
    $error = null;
    
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $username = htmlspecialchars(trim($_POST['username'] ?? ''));
        $email = htmlspecialchars(trim($_POST['email'] ?? ''));
        $password = $_POST['password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';
        
        // Validation
        if (!$username || !$email || !$password || !$confirm_password) {
            $error = "Semua field wajib diisi.";
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = "Email tidak valid.";
        } elseif ($password !== $confirm_password) {
            $error = "Konfirmasi password tidak cocok.";
        } elseif (strlen($password) < 6) {
            $error = "Password minimal 6 karakter.";
        } else {
            // Check if username/email already exists
            $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
            $stmt->execute([$username, $email]);
            if ($stmt->fetch()) {
                $error = "Username atau email sudah terdaftar.";
            } else {
                // Hash password
                $hash = password_hash($password, PASSWORD_DEFAULT);
                
                // Insert new user
                $stmt = $pdo->prepare("INSERT INTO users (username, email, password, role) VALUES (?, ?, ?, 'user')");
                if ($stmt->execute([$username, $email, $hash])) {
                    // Auto login after registration
                    $userId = $pdo->lastInsertId();
                    loginUser([
                        'id' => $userId,
                        'username' => $username,
                        'email' => $email,
                        'role' => 'user'
                    ]);
                    
                    Router::redirect('/dashboard');
                } else {
                    $error = "Gagal mendaftar, coba lagi.";
                }
            }
        }
    }
    
    require __DIR__ . '/../views/auth/register.php';
}

function logout() {
    logoutUser();
    Router::redirect('/login');
}
