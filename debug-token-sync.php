<?php
session_start();
?>
<!DOCTYPE html>
<html>
<head>
    <title>Debug Token Sync</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <style>
        body { font-family: sans-serif; padding: 20px; background: #f5f5f5; }
        .status { background: white; padding: 15px; margin: 10px 0; border-radius: 8px; border-left: 4px solid #1e3a8a; }
        .ok { border-color: green; }
        .error { border-color: red; }
        .log { background: #f0f0f0; padding: 10px; margin: 5px 0; font-size: 12px; border-radius: 5px; }
        button { width: 100%; padding: 15px; background: #1e3a8a; color: white; border: none; border-radius: 8px; font-size: 16px; margin: 10px 0; }
    </style>
</head>
<body>
    <h2>🔍 Debug Token Sync</h2>
    
    <div class="status">
        <strong>PHP Session:</strong><br>
        <?php if (isset($_SESSION['user_id'])): ?>
            ✅ Logged In (User ID: <?= $_SESSION['user_id'] ?>)
        <?php else: ?>
            ❌ Not Logged In
        <?php endif; ?>
    </div>

    <div class="status" id="cordova-status">
        <strong>Cordova Token:</strong><br>
        <span id="cordova-token-status">Checking...</span>
    </div>

    <div class="status" id="sync-status">
        <strong>Sync Status:</strong><br>
        <span id="sync-result">Not synced yet</span>
    </div>

    <button onclick="manualSync()">🔄 Manual Sync Token</button>

    <div id="logs"></div>

    <script>
        function addLog(message) {
            const logs = document.getElementById('logs');
            const log = document.createElement('div');
            log.className = 'log';
            log.textContent = new Date().toLocaleTimeString() + ': ' + message;
            logs.insertBefore(log, logs.firstChild);
        }

        // Check if Cordova token exists
        if (window.CORDOVA_FCM_TOKEN) {
            document.getElementById('cordova-token-status').innerHTML = '✅ Token found: ' + window.CORDOVA_FCM_TOKEN.substring(0, 20) + '...';
            document.getElementById('cordova-status').className = 'status ok';
            addLog('Found CORDOVA_FCM_TOKEN');
        } else {
            document.getElementById('cordova-token-status').innerHTML = '❌ Token not found';
            document.getElementById('cordova-status').className = 'status error';
            addLog('CORDOVA_FCM_TOKEN not found');
        }

        // Listen for Cordova token event
        document.addEventListener('cordova-fcm-token', function(e) {
            addLog('Received cordova-fcm-token event!');
            document.getElementById('cordova-token-status').innerHTML = '✅ Token received: ' + e.detail.token.substring(0, 20) + '...';
            document.getElementById('cordova-status').className = 'status ok';
            
            <?php if (isset($_SESSION['user_id'])): ?>
            autoSync(e.detail.token);
            <?php endif; ?>
        });

        function autoSync(token) {
            addLog('Auto-syncing token...');
            
            fetch('/api/register-fcm-token.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    token: token,
                    user_id: '<?= $_SESSION['user_id'] ?? '' ?>',
                    device_type: 'android',
                    app_version: '1.0.0'
                })
            })
            .then(response => response.json())
            .then(data => {
                addLog('Sync response: ' + JSON.stringify(data));
                if (data.success) {
                    document.getElementById('sync-result').innerHTML = '✅ Synced successfully!';
                    document.getElementById('sync-status').className = 'status ok';
                } else {
                    document.getElementById('sync-result').innerHTML = '❌ Sync failed: ' + (data.error || 'Unknown error');
                    document.getElementById('sync-status').className = 'status error';
                }
            })
            .catch(err => {
                addLog('Sync error: ' + err.message);
                document.getElementById('sync-result').innerHTML = '❌ Error: ' + err.message;
                document.getElementById('sync-status').className = 'status error';
            });
        }

        function manualSync() {
            const token = window.CORDOVA_FCM_TOKEN;
            if (!token) {
                alert('❌ No token found! Make sure you opened this from the app.');
                addLog('Manual sync failed: No token');
                return;
            }

            <?php if (!isset($_SESSION['user_id'])): ?>
            alert('❌ You are not logged in!');
            addLog('Manual sync failed: Not logged in');
            return;
            <?php endif; ?>

            autoSync(token);
        }

        // Auto-sync on page load if token exists
        window.addEventListener('DOMContentLoaded', function() {
            addLog('Page loaded');
            if (window.CORDOVA_FCM_TOKEN) {
                addLog('Token found on load, will auto-sync');
                <?php if (isset($_SESSION['user_id'])): ?>
                setTimeout(() => autoSync(window.CORDOVA_FCM_TOKEN), 1000);
                <?php endif; ?>
            }
        });
    </script>
</body>
</html>
