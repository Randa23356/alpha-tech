<?php
// check-uploaded-files.php - Cek versi file yang di-upload
?>
<!DOCTYPE html>
<html>
<head>
    <title>Check Uploaded Files</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <style>
        body { font-family: monospace; padding: 20px; background: #f5f5f5; }
        .file { background: white; padding: 15px; margin: 10px 0; border-radius: 8px; }
        .ok { color: green; }
        .error { color: red; }
        pre { background: #f0f0f0; padding: 10px; border-radius: 5px; overflow-x: auto; }
    </style>
</head>
<body>
    <h2>🔍 Check Uploaded Files</h2>
    
    <div class="file">
        <h3>1. api/register-fcm-token.php</h3>
        <?php
        $file = __DIR__ . '/api/register-fcm-token.php';
        if (file_exists($file)) {
            $content = file_get_contents($file);
            if (strpos($content, "input['user_id']") !== false) {
                echo '<p class="ok">✅ File sudah update (ada user_id dari input)</p>';
            } else {
                echo '<p class="error">❌ File belum update (tidak ada user_id dari input)</p>';
            }
            echo '<p>Last modified: ' . date('Y-m-d H:i:s', filemtime($file)) . '</p>';
        } else {
            echo '<p class="error">❌ File tidak ditemukan</p>';
        }
        ?>
    </div>

    <div class="file">
        <h3>2. login.php</h3>
        <?php
        $file = __DIR__ . '/login.php';
        if (file_exists($file)) {
            $content = file_get_contents($file);
            if (strpos($content, "localStorage.setItem('user_id'") !== false) {
                echo '<p class="ok">✅ File sudah update (ada localStorage user_id)</p>';
            } else {
                echo '<p class="error">❌ File belum update (tidak ada localStorage user_id)</p>';
            }
            echo '<p>Last modified: ' . date('Y-m-d H:i:s', filemtime($file)) . '</p>';
        } else {
            echo '<p class="error">❌ File tidak ditemukan</p>';
        }
        ?>
    </div>

    <div class="file">
        <h3>3. includes/footer.php</h3>
        <?php
        $file = __DIR__ . '/includes/footer.php';
        if (file_exists($file)) {
            $content = file_get_contents($file);
            if (strpos($content, "localStorage.getItem('user_id')") !== false) {
                echo '<p class="ok">✅ File sudah update (ada sync token script)</p>';
            } else {
                echo '<p class="error">❌ File belum update (tidak ada sync token script)</p>';
            }
            echo '<p>Last modified: ' . date('Y-m-d H:i:s', filemtime($file)) . '</p>';
        } else {
            echo '<p class="error">❌ File tidak ditemukan</p>';
        }
        ?>
    </div>

    <div class="file">
        <h3>4. src/helpers/session.php</h3>
        <?php
        $file = __DIR__ . '/src/helpers/session.php';
        if (file_exists($file)) {
            $content = file_get_contents($file);
            if (strpos($content, "session_set_cookie_params") !== false) {
                echo '<p class="ok">✅ File sudah update (ada session cookie params)</p>';
            } else {
                echo '<p class="error">❌ File belum update (tidak ada session cookie params)</p>';
            }
            echo '<p>Last modified: ' . date('Y-m-d H:i:s', filemtime($file)) . '</p>';
        } else {
            echo '<p class="error">❌ File tidak ditemukan</p>';
        }
        ?>
    </div>

    <div class="file">
        <h3>5. FCM Request Log</h3>
        <?php
        $logFile = __DIR__ . '/debug_fcm_requests.log';
        if (file_exists($logFile)) {
            echo '<p class="ok">✅ Log file exists</p>';
            echo '<p>Last modified: ' . date('Y-m-d H:i:s', filemtime($logFile)) . '</p>';
            echo '<h4>Last 10 requests:</h4>';
            $lines = file($logFile);
            $last10 = array_slice($lines, -10);
            echo '<pre>' . htmlspecialchars(implode('', $last10)) . '</pre>';
        } else {
            echo '<p class="error">❌ Log file tidak ada (berarti request belum pernah sampai ke server)</p>';
        }
        ?>
    </div>

    <div style="margin-top: 20px;">
        <a href="debug-session.php" style="display: inline-block; padding: 10px 20px; background: #1e3a8a; color: white; text-decoration: none; border-radius: 5px;">
            Debug Session
        </a>
        <a href="check-tokens.php" style="display: inline-block; padding: 10px 20px; background: #10b981; color: white; text-decoration: none; border-radius: 5px; margin-left: 10px;">
            Check Tokens
        </a>
    </div>
</body>
</html>
