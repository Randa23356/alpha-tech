<?php
// src/controllers/ProfileController.php

function index() {
    if (!isLoggedIn()) {
        Router::redirect('/login');
    }
    
    global $pdo;
    $user = getCurrentUser();
    
    // Fetch full user data
    try {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$user['id']]);
        $userData = $stmt->fetch();
    } catch (Exception $e) {
        $userData = $user;
    }
    
    $error = null;
    $success = null;
    
    require __DIR__ . '/../views/profile/index.php';
}

function update() {
    if (!isLoggedIn()) {
        Router::redirect('/login');
    }
    
    global $pdo;
    $user = getCurrentUser();
    $error = null;
    $success = null;
    
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $bio = htmlspecialchars(trim($_POST['bio'] ?? ''));
        $contact = htmlspecialchars(trim($_POST['contact'] ?? ''));
        $email = htmlspecialchars(trim($_POST['email'] ?? ''));
        
        // Handle profile picture removal
        if (isset($_POST['remove_profile_pic']) && $_POST['remove_profile_pic'] === '1') {
            $existingProfilePic = $_POST['existing_profile_pic'] ?? null;
            if (!empty($existingProfilePic)) {
                $oldFilePath = __DIR__ . '/../../public/uploads/profiles/' . basename($existingProfilePic);
                if (file_exists($oldFilePath)) {
                    unlink($oldFilePath);
                }
            }
            $profilePic = null;
        }

        // Handle profile picture upload
        if (isset($_FILES['profile_pic']) && $_FILES['profile_pic']['error'] === UPLOAD_ERR_OK) {
            $uploadDir = __DIR__ . '/../../public/uploads/profiles/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }

            $fileExtension = pathinfo($_FILES['profile_pic']['name'], PATHINFO_EXTENSION);
            $fileName = 'profile_' . $user['id'] . '_' . time() . '.' . $fileExtension;
            $targetPath = $uploadDir . $fileName;

            if (move_uploaded_file($_FILES['profile_pic']['tmp_name'], $targetPath)) {
                // Delete old profile picture if it exists and is not the default
                if (!empty($profilePic) && $profilePic !== $_POST['existing_profile_pic']) {
                    $oldFilePath = __DIR__ . '/../../public/uploads/profiles/' . basename($profilePic);
                    if (file_exists($oldFilePath)) {
                        unlink($oldFilePath);
                    }
                }

                $profilePic = 'public/uploads/profiles/' . $fileName;
            }
        }
        
        // Update user profile
        try {
            $stmt = $pdo->prepare("UPDATE users SET email = ?, bio = ?, contact = ?, profile_pic = ? WHERE id = ?");
            if ($stmt->execute([$email, $bio, $contact, $profilePic, $user['id']])) {
                $success = "Profil berhasil diupdate!";
                
                // Update session
                $_SESSION['user']['email'] = $email;
            } else {
                $error = "Gagal mengupdate profil.";
            }
        } catch (Exception $e) {
            $error = "Terjadi kesalahan: " . $e->getMessage();
        }
    }
    
    // Fetch updated user data
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$user['id']]);
    $userData = $stmt->fetch();
    
    require __DIR__ . '/../views/profile/index.php';
}
