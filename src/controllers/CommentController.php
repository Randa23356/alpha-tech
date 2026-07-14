<?php
// src/controllers/CommentController.php

function index($post_id) {
    if (!isLoggedIn()) {
        Router::redirect('/login');
    }
    
    global $pdo;
    $user = getCurrentUser();
    
    // Fetch post details
    try {
        $stmt = $pdo->prepare("SELECT posts.*, users.username FROM posts JOIN users ON posts.user_id = users.id WHERE posts.id = ?");
        $stmt->execute([$post_id]);
        $post = $stmt->fetch();
        
        if (!$post) {
            Router::redirect('/dashboard');
        }
        
        // Fetch comments
        $stmt = $pdo->prepare("SELECT comments.*, users.username FROM comments JOIN users ON comments.user_id = users.id WHERE comments.post_id = ? ORDER BY comments.created_at DESC");
        $stmt->execute([$post_id]);
        $comments = $stmt->fetchAll();
    } catch (Exception $e) {
        Router::redirect('/dashboard');
    }
    
    $error = null;
    require __DIR__ . '/../views/comments/index.php';
}

function store($post_id) {
    if (!isLoggedIn()) {
        Router::redirect('/login');
    }
    
    global $pdo;
    $user = getCurrentUser();
    $error = null;
    
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $comment = htmlspecialchars(trim($_POST['comment'] ?? ''));
        
        if (!$comment) {
            $error = "Komentar tidak boleh kosong.";
        } else {
            try {
                $stmt = $pdo->prepare("INSERT INTO comments (post_id, user_id, comment) VALUES (?, ?, ?)");
                if ($stmt->execute([$post_id, $user['id'], $comment])) {
                    Router::redirect('/comment/' . $post_id);
                } else {
                    $error = "Gagal menambahkan komentar.";
                }
            } catch (Exception $e) {
                $error = "Terjadi kesalahan: " . $e->getMessage();
            }
        }
    }
    
    // Redirect back to comments page
    Router::redirect('/comment/' . $post_id);
}
