<?php
// src/controllers/DashboardController.php

function index() {
    // Check if user is logged in
    if (!isLoggedIn() || !isUser()) {
        Router::redirect('/login');
    }
    
    global $pdo;
    $user = getCurrentUser();
    
    // Fetch user's posts
    try {
        $stmt = $pdo->prepare("SELECT * FROM posts WHERE user_id = ? ORDER BY created_at DESC");
        $stmt->execute([$user['id']]);
        $posts = $stmt->fetchAll();
    } catch (Exception $e) {
        $posts = [];
    }
    
    require __DIR__ . '/../views/dashboard/index.php';
}
