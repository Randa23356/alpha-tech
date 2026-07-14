<?php
// Check FCM Tokens in Database
require_once __DIR__ . '/src/config/db.php';

echo "<h2>FCM Tokens Database Check</h2>";

try {
    $stmt = $pdo->query("SELECT id, token, device_type, app_version, user_id, is_active, created_at, updated_at FROM fcm_tokens ORDER BY created_at DESC");
    $tokens = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<p><strong>Total tokens:</strong> " . count($tokens) . "</p>";
    
    if (!empty($tokens)) {
        echo "<p><a href='?delete_all=1' style='background:red;color:white;padding:5px 10px;text-decoration:none;border-radius:3px' onclick='return confirm(\"Delete ALL tokens? This cannot be undone.\")'>🗑️ Delete All Tokens</a></p>";
    }
    
    if (empty($tokens)) {
        echo "<p style='color:orange'>⚠️ No FCM tokens found. Install APK and open app to register token.</p>";
    } else {
        echo "<table border='1' cellpadding='10' style='border-collapse:collapse;width:100%'>";
        echo "<tr style='background:#f0f0f0'>";
        echo "<th>ID</th>";
        echo "<th>Token (first 30 chars)</th>";
        echo "<th>Device Type</th>";
        echo "<th>App Version</th>";
        echo "<th>User ID</th>";
        echo "<th>Active</th>";
        echo "<th>Created</th>";
        echo "<th>Updated</th>";
        echo "<th>Actions</th>";
        echo "</tr>";
        
        foreach ($tokens as $token) {
            $isActive = $token['is_active'] == 1;
            $rowColor = $isActive ? '#e8f5e9' : '#ffebee';
            
            echo "<tr style='background:$rowColor'>";
            echo "<td>" . htmlspecialchars($token['id']) . "</td>";
            echo "<td><code>" . htmlspecialchars(substr($token['token'], 0, 30)) . "...</code></td>";
            echo "<td>" . htmlspecialchars($token['device_type']) . "</td>";
            echo "<td>" . htmlspecialchars($token['app_version']) . "</td>";
            echo "<td>" . ($token['user_id'] ? htmlspecialchars($token['user_id']) : '<span style="color:gray">Guest</span>') . "</td>";
            echo "<td>" . ($isActive ? '✅ Active' : '❌ Inactive') . "</td>";
            echo "<td>" . htmlspecialchars($token['created_at']) . "</td>";
            echo "<td>" . htmlspecialchars($token['updated_at']) . "</td>";
            echo "<td>";
            if ($isActive) {
                echo "<a href='?deactivate=" . $token['id'] . "' style='color:red'>Deactivate</a>";
            } else {
                echo "<a href='?activate=" . $token['id'] . "' style='color:green'>Activate</a>";
            }
            echo " | <a href='?delete=" . $token['id'] . "' style='color:darkred' onclick='return confirm(\"Delete this token?\")'>Delete</a>";
            echo "</td>";
            echo "</tr>";
        }
        
        echo "</table>";
    }
    
    // Handle actions
    if (isset($_GET['deactivate'])) {
        $id = (int)$_GET['deactivate'];
        $stmt = $pdo->prepare("UPDATE fcm_tokens SET is_active = 0 WHERE id = ?");
        $stmt->execute([$id]);
        echo "<script>alert('Token deactivated'); window.location.href='check-tokens.php';</script>";
    }
    
    if (isset($_GET['activate'])) {
        $id = (int)$_GET['activate'];
        $stmt = $pdo->prepare("UPDATE fcm_tokens SET is_active = 1 WHERE id = ?");
        $stmt->execute([$id]);
        echo "<script>alert('Token activated'); window.location.href='check-tokens.php';</script>";
    }
    
    if (isset($_GET['delete'])) {
        $id = (int)$_GET['delete'];
        $stmt = $pdo->prepare("DELETE FROM fcm_tokens WHERE id = ?");
        $stmt->execute([$id]);
        echo "<script>alert('Token deleted'); window.location.href='check-tokens.php';</script>";
    }
    
    if (isset($_GET['delete_all'])) {
        $stmt = $pdo->query("DELETE FROM fcm_tokens");
        echo "<script>alert('All tokens deleted'); window.location.href='check-tokens.php';</script>";
    }
    
} catch (PDOException $e) {
    echo "<p style='color:red'>❌ Error: " . htmlspecialchars($e->getMessage()) . "</p>";
}

echo "<hr>";
echo "<p><a href='test-fcm.php'>← Back to FCM Test</a></p>";
?>
