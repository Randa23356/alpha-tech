<?php
// Database migration script for hero slider icon background color field
// Run this script to add icon_bg_color column to the hero_slides table

require_once __DIR__ . "/src/config/db.php";

try {
    // Check if icon_bg_color column exists
    $stmt = $pdo->prepare("SHOW COLUMNS FROM hero_slides LIKE 'icon_bg_color'");
    $stmt->execute();
    $column_exists = $stmt->rowCount() > 0;

    if (!$column_exists) {
        // Add icon_bg_color column to hero_slides table
        $sql = "ALTER TABLE hero_slides ADD COLUMN icon_bg_color VARCHAR(50) DEFAULT 'rgba(255,255,255,0.1)'";
        $pdo->exec($sql);
        echo "✅ Icon background color column added successfully to hero_slides table!\n";
    } else {
        echo "✅ Icon background color column already exists in hero_slides table!\n";
    }

} catch (PDOException $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}

echo "🎨 Hero slider icon background color customization is now ready!\n";
?>
