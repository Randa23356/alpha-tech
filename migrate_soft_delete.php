<?php
// Migration script to add deleted_at column to posts table
// Run this once to ensure the soft delete functionality works properly

require_once __DIR__ . '/src/config/db.php';

try {
    // Check if deleted_at column exists
    $result = $pdo->query("SHOW COLUMNS FROM posts LIKE 'deleted_at'");
    $columnExists = $result->fetch();

    if (!$columnExists) {
        // Add deleted_at column to posts table
        $pdo->exec("ALTER TABLE posts ADD COLUMN deleted_at TIMESTAMP NULL DEFAULT NULL");

        // Add index for better performance
        $pdo->exec("CREATE INDEX idx_deleted_at ON posts(deleted_at)");

        echo "✅ Successfully added 'deleted_at' column to posts table!\n";
        echo "🗑️  Trash functionality should now work properly.\n";
    } else {
        echo "ℹ️  'deleted_at' column already exists in posts table.\n";
    }

} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "💡 If you prefer to use status-based soft delete, make sure your posts.status column allows 'deleted' as a value.\n";
}
?>
