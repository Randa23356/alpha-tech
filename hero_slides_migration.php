<?php
// informatics_a/hero_slides_migration.php
require_once __DIR__ . "/src/config/db.php";

try {
    // Create hero_slides table
    $sql = "CREATE TABLE IF NOT EXISTS `hero_slides` (
      `id` int(11) NOT NULL AUTO_INCREMENT,
      `title` varchar(255) NOT NULL,
      `subtitle` text,
      `description` text,
      `button_text` varchar(100) DEFAULT 'Learn More',
      `button_url` varchar(255) DEFAULT '#',
      `background_image` varchar(255) NOT NULL,
      `slide_order` int(11) DEFAULT 0,
      `is_active` tinyint(1) DEFAULT 1,
      `autoplay_duration` int(11) DEFAULT 5000,
      `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
      `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
      PRIMARY KEY (`id`),
      KEY `slide_order` (`slide_order`),
      KEY `is_active` (`is_active`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";

    $pdo->exec($sql);
    echo "Hero slides table created successfully!\n";

    // Insert sample data
    $sample_slides = [
        [
            'title' => 'Selamat Datang di Informatics A',
            'subtitle' => 'Platform Kolaborasi Terbaik',
            'description' => 'Bergabunglah dengan komunitas kelas Informatika untuk berbagi kegiatan, pengumuman, dan galeri foto terbaik.',
            'button_text' => 'Mulai Sekarang',
            'button_url' => '/informatics_a/register',
            'background_image' => 'hero-bg-1.jpg',
            'slide_order' => 1,
            'is_active' => 1,
            'autoplay_duration' => 5000
        ],
        [
            'title' => 'Dokumentasi Kegiatan Kelas',
            'subtitle' => 'Abadikan Momen Berharga',
            'description' => 'Dokumentasikan setiap kegiatan kelas dengan mudah dan bagikan dengan seluruh anggota komunitas.',
            'button_text' => 'Lihat Kegiatan',
            'button_url' => '/informatics_a/#activities',
            'background_image' => 'hero-bg-2.jpg',
            'slide_order' => 2,
            'is_active' => 1,
            'autoplay_duration' => 6000
        ],
        [
            'title' => 'Komunitas Informatika A',
            'subtitle' => 'Bersama Kita Lebih Kuat',
            'description' => 'Platform digital yang dirancang khusus untuk memfasilitasi kolaborasi dan interaksi antar anggota kelas.',
            'button_text' => 'Bergabung Sekarang',
            'button_url' => '/informatics_a/register',
            'background_image' => 'hero-bg-3.jpg',
            'slide_order' => 3,
            'is_active' => 1,
            'autoplay_duration' => 5500
        ]
    ];

    foreach ($sample_slides as $slide) {
        $stmt = $pdo->prepare("INSERT INTO hero_slides (title, subtitle, description, button_text, button_url, background_image, slide_order, is_active, autoplay_duration) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            $slide['title'],
            $slide['subtitle'],
            $slide['description'],
            $slide['button_text'],
            $slide['button_url'],
            $slide['background_image'],
            $slide['slide_order'],
            $slide['is_active'],
            $slide['autoplay_duration']
        ]);
    }

    echo "Sample hero slides inserted successfully!\n";
    echo "Migration completed!\n";

} catch (PDOException $e) {
    echo "Migration failed: " . $e->getMessage() . "\n";
}
?>
