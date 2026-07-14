<?php
session_start();

require_once __DIR__ . "/vendor/autoload.php";
require_once __DIR__ . "/src/config/db.php";
require_once __DIR__ . "/src/helpers/session.php";
require_once __DIR__ . "/src/config/urls.php";
require_once __DIR__ . "/src/config/config.php";

// Inisialisasi Google Client
$client = new Google\Client();
$client->setClientId(GOOGLE_CLIENT_ID);
$client->setClientSecret(GOOGLE_CLIENT_SECRET);
$client->setRedirectUri(GOOGLE_REDIRECT_URI);
$client->addScope('email');
$client->addScope('profile');

// Pastikan ada code dari Google
if (!isset($_GET['code'])) {
    header("Location: " . url('login'));
    exit();
}

// Ambil token dari Google
$token = $client->fetchAccessTokenWithAuthCode($_GET['code']);
if (isset($token['error'])) {
    header("Location: " . url('login'));
    exit();
}

$client->setAccessToken($token);

// Ambil data user dari Google
$google_oauth = new Google_Service_Oauth2($client);
$google_account_info = $google_oauth->userinfo->get();

$email = $google_account_info->email;
$name = $google_account_info->name;
$googleId = $google_account_info->id;
$profilePicture = $google_account_info->picture ?? '';
if (empty($profilePicture)) {
    $profilePicture = url('public/default-avatar.php?initial=' . urlencode(substr($name, 0, 1)));
}
$username = explode('@', $email)[0]; // ambil sebelum @

// Cek apakah user sudah ada di database
$stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
$stmt->execute([$email]);
$user = $stmt->fetch();

if ($user) {
    // Update user lama
    $stmt = $pdo->prepare("UPDATE users SET google_id = COALESCE(google_id, ?), full_name = COALESCE(full_name, ?), profile_pic = COALESCE(NULLIF(profile_pic, ''), ?) WHERE id = ?");
    $stmt->execute([$googleId, $name, $profilePicture, $user['id']]);
    
    // Fetch updated user data
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$user['id']]);
    $updatedUser = $stmt->fetch();
    loginUser($updatedUser);
} else {
    // Buat user baru
    $role = 'user';
    $stmt = $pdo->prepare("INSERT INTO users (username, email, full_name, google_id, profile_pic, role) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->execute([$username, $email, $name, $googleId, $profilePicture, $role]);

    $userId = $pdo->lastInsertId();
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $newUser = $stmt->fetch();
    loginUser($newUser);
}

// Redirect sesuai role
if (isset($_SESSION['user'])) {
    if ($_SESSION['user']['role'] === 'admin') {
        header("Location: " . url('admin'));
    } elseif ($_SESSION['user']['role'] === 'korti') {
        header("Location: " . url('korti'));
    } else {
        header("Location: " . url('dashboard'));
    }
} else {
    header("Location: " . url('login'));
}
exit();