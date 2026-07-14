<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');

// Database connection
require_once __DIR__ . "/../src/config/db.php";

// Get timestamp from query parameter
$since = isset($_GET['since']) ? $_GET['since'] : date('Y-m-d H:i:s', strtotime('-1 day'));

try {
    // Check for new posts/kegiatan since last check
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as count 
        FROM posts 
        WHERE created_at > :since 
        AND status = 'approved'
        AND deleted_at IS NULL
    ");
    $stmt->execute(['since' => $since]);
    $newPosts = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    // Check for new announcements/pengumuman since last check
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as count 
        FROM announcements 
        WHERE created_at > :since 
        AND is_active = 1
    ");
    $stmt->execute(['since' => $since]);
    $newAnnouncements = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    // Return response
    echo json_encode([
        'success' => true,
        'newPosts' => (int)$newPosts,
        'newAnnouncements' => (int)$newAnnouncements,
        'timestamp' => date('Y-m-d H:i:s')
    ]);
    
} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'error' => 'Database error',
        'newPosts' => 0,
        'newAnnouncements' => 0
    ]);
}
?>
