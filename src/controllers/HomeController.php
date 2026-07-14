<?php
// src/controllers/HomeController.php

function index() {
    global $pdo;
    
    // Fetch real data from database
    try {
        // Count approved activities
        $stmt = $pdo->query("SELECT COUNT(*) as total FROM posts WHERE status = 'approved'");
        $total_kegiatan = $stmt->fetch()['total'] ?? 0;
        
        // Count total users
        $stmt = $pdo->query("SELECT COUNT(*) as total FROM users WHERE role = 'user'");
        $total_users = $stmt->fetch()['total'] ?? 0;
        
        // Count total photos
        $stmt = $pdo->query("SELECT COUNT(*) as total FROM posts WHERE status = 'approved' AND image IS NOT NULL AND image != ''");
        $total_photos = $stmt->fetch()['total'] ?? 0;
        
        // Fetch 3 most recent approved activities
        $stmt = $pdo->query("SELECT posts.*, users.username FROM posts JOIN users ON posts.user_id = users.id WHERE posts.status = 'approved' ORDER BY posts.date DESC LIMIT 3");
        $kegiatan = $stmt->fetchAll();
    } catch (Exception $e) {
        $total_kegiatan = 0;
        $total_users = 0;
        $total_photos = 0;
        $kegiatan = [];
    }
    
    // Load view
    require __DIR__ . '/../views/home.php';
}
