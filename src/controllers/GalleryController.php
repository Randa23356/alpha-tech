<?php
// src/controllers/GalleryController.php

function index() {
    global $pdo;
    
    // Fetch approved posts with images
    try {
        $stmt = $pdo->query("SELECT posts.*, users.username FROM posts JOIN users ON posts.user_id = users.id WHERE posts.status = 'approved' AND posts.image IS NOT NULL AND posts.image != '' ORDER BY posts.date DESC");
        $gallery = $stmt->fetchAll();
    } catch (Exception $e) {
        $gallery = [];
    }
    
    require __DIR__ . '/../views/gallery/index.php';
}
