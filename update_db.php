<?php
require_once __DIR__ . '/src/config/db.php';

try {
    echo "Adding user_id column...\n";
    
    // Check if column exists
    $stmt = $pdo->query("SHOW COLUMNS FROM fcm_tokens LIKE 'user_id'");
    if ($stmt->fetch()) {
        echo "Column user_id already exists.\n";
    } else {
        $pdo->exec("ALTER TABLE fcm_tokens ADD COLUMN user_id INT NULL AFTER token");
        $pdo->exec("ALTER TABLE fcm_tokens ADD INDEX (user_id)");
        echo "Column user_id added successfully.\n";
    }
    
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
