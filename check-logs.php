<?php
// check-logs.php - Quick check for recent logs
require_once __DIR__ . '/src/config/db.php';

echo "<h2>Recent FCM Logs</h2>";

// Check FCM request log
$logFile = __DIR__ . '/debug_fcm_requests.log';
if (file_exists($logFile)) {
    echo "<h3>Last 5 FCM Requests:</h3>";
    $lines = file($logFile);
    $last5 = array_slice($lines, -5);
    echo "<pre>" . htmlspecialchars(implode('', $last5)) . "</pre>";
} else {
    echo "<p>No log file found</p>";
}

// Check if user is logged in
session_start();
echo "<h3>Current Session:</h3>";
echo "<pre>";
echo "Logged in: " . (isset($_SESSION['user_id']) ? 'YES' : 'NO') . "\n";
if (isset($_SESSION['user_id'])) {
    echo "User ID: " . $_SESSION['user_id'] . "\n";
    echo "Username: " . ($_SESSION['user']['username'] ?? 'N/A') . "\n";
}
echo "Session ID: " . session_id() . "\n";
echo "</pre>";

// Check tokens
echo "<h3>Current Tokens:</h3>";
$stmt = $pdo->query("SELECT id, LEFT(token, 20) as token_preview, user_id, created_at FROM fcm_tokens ORDER BY created_at DESC LIMIT 3");
echo "<table border='1' cellpadding='5'>";
echo "<tr><th>ID</th><th>Token</th><th>User ID</th><th>Created</th></tr>";
foreach ($stmt->fetchAll() as $row) {
    echo "<tr>";
    echo "<td>" . $row['id'] . "</td>";
    echo "<td>" . $row['token_preview'] . "...</td>";
    echo "<td>" . ($row['user_id'] ?? 'NULL') . "</td>";
    echo "<td>" . $row['created_at'] . "</td>";
    echo "</tr>";
}
echo "</table>";
?>
