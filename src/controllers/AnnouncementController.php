<?php
// src/controllers/AnnouncementController.php

function publicIndex() {
    global $pdo;
    
    // Fetch all announcements
    try {
        $stmt = $pdo->query("SELECT * FROM announcements ORDER BY created_at DESC");
        $announcements = $stmt->fetchAll();
    } catch (Exception $e) {
        $announcements = [];
    }
    
    require __DIR__ . '/../views/announcements/index.php';
}
