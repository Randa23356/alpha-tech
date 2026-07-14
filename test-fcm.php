<?php
// Test FCM Notification
require_once __DIR__ . '/src/config/db.php';
require_once __DIR__ . '/src/helpers/fcm_helper.php';

echo "<h2>Testing Firebase Cloud Messaging</h2>";

// Test 1: Check service account file
echo "<h3>1. Checking Service Account File</h3>";
$serviceAccountPath = __DIR__ . '/firebase-service-account.json';
if (file_exists($serviceAccountPath)) {
    echo "✅ firebase-service-account.json found<br>";
} else {
    echo "❌ firebase-service-account.json NOT found at: $serviceAccountPath<br>";
    exit;
}

// Test 2: Check FCM tokens table
echo "<h3>2. Checking FCM Tokens Table</h3>";
try {
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM fcm_tokens");
    $count = $stmt->fetch()['count'];
    echo "✅ fcm_tokens table exists<br>";
    echo "📱 Active tokens: $count<br>";
    
    if ($count == 0) {
        echo "<p style='color:orange'>⚠️ No FCM tokens yet. Install APK on device first.</p>";
    }
} catch (PDOException $e) {
    echo "❌ Error: " . $e->getMessage() . "<br>";
    echo "<p>Run this SQL to create table:</p>";
    echo "<pre>" . file_get_contents(__DIR__ . '/database/fcm_tokens_table.sql') . "</pre>";
    exit;
}

// Test 3: Try to get access token
echo "<h3>3. Testing FCM Access Token</h3>";
$accessToken = getFCMAccessToken();
if ($accessToken) {
    echo "✅ Successfully got FCM access token<br>";
    echo "Token (first 50 chars): " . substr($accessToken, 0, 50) . "...<br>";
} else {
    echo "❌ Failed to get access token. Check error log.<br>";
    exit;
}

// Test 4: Send test notification if tokens available
if ($count > 0) {
    echo "<h3>4. Sending Test Notification</h3>";
    
    $result = sendFCMNotification(
        'Test Notification 🔔',
        'Ini adalah test notifikasi dari server Alpha Tech!',
        ['type' => 'test', 'timestamp' => date('Y-m-d H:i:s')]
    );
    
    echo "<p><strong>Sent:</strong> {$result['sent']} devices</p>";
    echo "<p><strong>Failed:</strong> {$result['failed']} devices</p>";
    
    if ($result['success']) {
        echo "<p style='color:green;font-size:18px'><strong>✅ Test notification sent successfully!</strong></p>";
        echo "<p style='color:green'>Check your phone for notification!</p>";
    } else {
        echo "<p style='color:red;font-size:18px'><strong>❌ Failed to send notification</strong></p>";
    }
    
    // Show errors if any
    if (!empty($result['errors'])) {
        echo "<h4 style='color:red'>Error Details:</h4>";
        echo "<div style='background:#fff3cd;border:1px solid #ffc107;padding:15px;border-radius:5px;margin:10px 0'>";
        foreach ($result['errors'] as $error) {
            echo "<p style='margin:5px 0;font-family:monospace'>❌ " . htmlspecialchars($error) . "</p>";
        }
        echo "</div>";
        
        echo "<h4>Common Solutions:</h4>";
        echo "<ul>";
        echo "<li><strong>INVALID_ARGUMENT:</strong> Token format invalid - Reinstall APK and reopen app</li>";
        echo "<li><strong>UNREGISTERED:</strong> Token expired - Delete old tokens in <a href='check-tokens.php'>check-tokens.php</a> and reinstall app</li>";
        echo "<li><strong>PERMISSION_DENIED:</strong> Check firebase-service-account.json permissions</li>";
        echo "<li><strong>NOT_FOUND:</strong> Verify FIREBASE_PROJECT_ID in fcm_helper.php</li>";
        echo "</ul>";
    }
} else {
    echo "<h3>4. Ready to Send Notifications</h3>";
    echo "<p>✅ System is ready! Install APK on device and tokens will be registered automatically.</p>";
}

echo "<hr>";
echo "<h3>Summary</h3>";
echo "<ul>";
echo "<li>✅ Firebase service account configured</li>";
echo "<li>✅ Database table ready</li>";
echo "<li>✅ FCM API connection working</li>";
echo "<li>" . ($count > 0 ? "✅" : "⏳") . " Device tokens registered: $count</li>";
echo "</ul>";

echo "<h3>Next Steps</h3>";
echo "<ol>";
echo "<li>Install APK on Android device</li>";
echo "<li>Open app (will auto-register FCM token)</li>";
echo "<li>Run this test page again to send test notification</li>";
echo "<li>Add notification calls to your post/announcement creation code</li>";
echo "</ol>";
?>
