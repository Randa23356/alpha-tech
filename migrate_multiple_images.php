<?php
// Migration script untuk multiple image upload
require_once __DIR__ . '/src/config/db.php';

try {
    // Cek dan tambahkan kolom thumbnail_image jika belum ada
    $checkColumn = $pdo->query("SHOW COLUMNS FROM posts LIKE 'thumbnail_image'");
    if (!$checkColumn->fetch()) {
        $pdo->exec("ALTER TABLE posts ADD COLUMN thumbnail_image VARCHAR(255) DEFAULT NULL");
        echo "✅ Kolom thumbnail_image berhasil ditambahkan ke tabel posts!\n";
    } else {
        echo "✅ Kolom thumbnail_image sudah ada di tabel posts!\n";
    }

    // Buat tabel post_images untuk menyimpan multiple gambar per postingan
    $sql = "
    CREATE TABLE IF NOT EXISTS `post_images` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `post_id` int(11) NOT NULL,
        `image_path` varchar(255) NOT NULL,
        `image_order` int(11) DEFAULT 0,
        `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        KEY `idx_post_id` (`post_id`),
        KEY `idx_image_order` (`image_order`),
        FOREIGN KEY (`post_id`) REFERENCES `posts`(`id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ";

    $pdo->exec($sql);
    echo "✅ Tabel post_images berhasil dibuat!\n";

    // Update existing posts dengan gambar utama sebagai thumbnail
    $updateResult = $pdo->query("
        UPDATE posts
        SET thumbnail_image = image
        WHERE image IS NOT NULL AND image != '' AND thumbnail_image IS NULL
    ");

    echo "✅ " . $updateResult->rowCount() . " postingan berhasil diupdate dengan thumbnail!\n";

    echo "🎉 Migration selesai! Sistem sekarang mendukung multiple image upload.\n";

} catch (PDOException $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
?>
