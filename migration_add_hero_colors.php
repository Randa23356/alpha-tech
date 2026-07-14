<?php
// Database migration script for hero slider color fields
// Run this script to add color columns to the hero_slides table

require_once __DIR__ . "/src/config/db.php";

try {
    // Add color columns to hero_slides table
    $sql = "ALTER TABLE hero_slides
            ADD COLUMN title_color VARCHAR(7) DEFAULT '#ffffff',
            ADD COLUMN subtitle_color VARCHAR(7) DEFAULT '#f3f4f6',
            ADD COLUMN description_color VARCHAR(7) DEFAULT '#d1d5db',
            ADD COLUMN icon_color VARCHAR(7) DEFAULT '#ffffff'";

    $pdo->exec($sql);
    echo "✅ Color columns added successfully to hero_slides table!\n";

} catch (PDOException $e) {
    if ($e->getCode() == '42S21') { // Column already exists
        echo "✅ Color columns already exist in hero_slides table!\n";
    } else {
        echo "❌ Error adding color columns: " . $e->getMessage() . "\n";
    }
}

echo "🎨 Hero slider color customization is now ready!\n";
?>
