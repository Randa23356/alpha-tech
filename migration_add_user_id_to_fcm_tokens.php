<?php
require_once __DIR__ . '/src/config/db.php';

echo "Checking fcm_tokens table structure...\n";

try {
    // Check if column exists
    $stmt = $pdo->query("SHOW COLUMNS FROM fcm_tokens LIKE 'user_id'");
    $column = $stmt->fetch();

    if ($column) {
        echo "Column 'user_id' already exists in fcm_tokens.\n";
    } else {
        echo "Column 'user_id' missing. Adding it now...\n";
        
        // Add column
        $sql = "ALTER TABLE fcm_tokens 
                ADD COLUMN user_id int(11) DEFAULT NULL AFTER app_version,
                ADD INDEX (user_id),
                ADD CONSTRAINT fcm_tokens_ibfk_1 FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE";
        
        $pdo->exec($sql);
        echo "Successfully added 'user_id' column to fcm_tokens table.\n";
    }

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
