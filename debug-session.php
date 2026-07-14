<?php
session_start();
?>
<!DOCTYPE html>
<html>
<head>
    <title>Debug Session & LocalStorage</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <style>
        body { font-family: monospace; padding: 20px; background: #f5f5f5; }
        .box { background: white; padding: 15px; margin: 10px 0; border-radius: 8px; border-left: 4px solid #1e3a8a; }
        .label { font-weight: bold; color: #1e3a8a; }
        .value { color: #333; margin-left: 10px; }
        .error { color: red; }
        .success { color: green; }
    </style>
</head>
<body>
    <h2>🔍 Debug Session & LocalStorage</h2>
    
    <div class="box">
        <div class="label">PHP Session Status:</div>
        <div class="value">
            <?php if (isset($_SESSION['user_id'])): ?>
                <span class="success">✅ Logged In</span><br>
                User ID: <?= $_SESSION['user_id'] ?><br>
                Username: <?= $_SESSION['user']['username'] ?? 'N/A' ?>
            <?php else: ?>
                <span class="error">❌ Not Logged In</span>
            <?php endif; ?>
        </div>
    </div>

    <div class="box">
        <div class="label">Session ID:</div>
        <div class="value"><?= session_id() ?></div>
    </div>

    <div class="box">
        <div class="label">Server Time:</div>
        <div class="value"><?= date('Y-m-d H:i:s') ?></div>
    </div>

    <div class="box">
        <div class="label">LocalStorage Data:</div>
        <div id="localStorage-data" class="value">Loading...</div>
    </div>

    <div class="box">
        <div class="label">FCM Token:</div>
        <div id="fcm-token" class="value">Loading...</div>
    </div>

    <div class="box">
        <div class="label">User ID (from localStorage):</div>
        <div id="user-id-local" class="value">Loading...</div>
    </div>

    <script>
        // Check localStorage
        const fcmToken = localStorage.getItem('fcm_token');
        const userId = localStorage.getItem('user_id');
        const syncedUser = localStorage.getItem('fcm_token_synced_user');

        document.getElementById('fcm-token').innerHTML = fcmToken ? 
            '<span class="success">✅ ' + fcmToken.substring(0, 30) + '...</span>' : 
            '<span class="error">❌ Not found</span>';

        document.getElementById('user-id-local').innerHTML = userId ? 
            '<span class="success">✅ ' + userId + '</span>' : 
            '<span class="error">❌ Not found</span>';

        document.getElementById('localStorage-data').innerHTML = 
            'fcm_token: ' + (fcmToken ? '✅' : '❌') + '<br>' +
            'user_id: ' + (userId ? '✅' : '❌') + '<br>' +
            'fcm_token_synced_user: ' + (syncedUser ? '✅ ' + syncedUser : '❌');

        // Log all localStorage keys
        console.log('All localStorage keys:', Object.keys(localStorage));
        console.log('fcm_token:', fcmToken);
        console.log('user_id:', userId);
    </script>

    <div style="margin-top: 20px;">
        <a href="check-tokens.php" style="display: inline-block; padding: 10px 20px; background: #1e3a8a; color: white; text-decoration: none; border-radius: 5px;">
            Check Tokens
        </a>
        <a href="login.php" style="display: inline-block; padding: 10px 20px; background: #10b981; color: white; text-decoration: none; border-radius: 5px; margin-left: 10px;">
            Login
        </a>
    </div>
</body>
</html>
