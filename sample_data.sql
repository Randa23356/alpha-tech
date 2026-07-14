-- Sample Data Seeder for Informatics A
-- Insert this data after creating the tables

-- Sample admin user
INSERT INTO `users` (`username`, `email`, `password`, `role`, `created_at`) VALUES
('admin', 'admin@informatics.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', NOW()),
('kordinator1', 'kordinator@informatics.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'kordinator', NOW());

-- Sample regular users
INSERT INTO `users` (`username`, `email`, `password`, `role`, `created_at`) VALUES
('john_doe', 'john@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'user', NOW()),
('jane_smith', 'jane@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'user', NOW()),
('mike_johnson', 'mike@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'user', NOW());

-- Sample site settings
INSERT INTO `site_settings` (`setting_key`, `setting_value`) VALUES
('site_name', 'Informatics A'),
('hero_title', 'Selamat Datang di Informatics A'),
('hero_subtitle', 'Platform kolaborasi dan dokumentasi kelas Informatika terbaik untuk berbagi kegiatan, pengumuman, dan galeri foto.'),
('about_title', 'Tentang Informatics A'),
('about_description', 'Platform digital yang dirancang khusus untuk memfasilitasi dokumentasi, berbagi informasi, dan kolaborasi antar anggota kelas Informatika A.'),
('about_feature_1', 'Interface modern dan mudah digunakan'),
('about_feature_2', 'Sistem approval untuk menjaga kualitas konten'),
('about_feature_3', 'Galeri foto untuk dokumentasi visual'),
('about_feature_4', 'Responsive design untuk semua perangkat'),
('primary_color', '#1e3a8a'),
('secondary_color', '#1e40af'),
('accent_color', '#ec4899'),
('contact_email', 'info@informatics.com'),
('contact_instagram', '@informatics_a');

-- Sample about features
INSERT INTO `about_features` (`title`, `description`, `icon`, `display_order`, `is_active`) VALUES
('Posting Kegiatan', 'Bagikan kegiatan kelas dengan mudah dan cepat. Setiap postingan akan di-review oleh admin sebelum dipublikasikan.', 'document-text', 1, 1),
('Galeri Foto', 'Dokumentasi visual kegiatan dalam satu tempat. Foto otomatis diambil dari kegiatan yang sudah di-approve.', 'photograph', 2, 1),
('Interaksi Sosial', 'Diskusi dan berikan feedback pada setiap kegiatan. Interaksi antar anggota kelas lebih mudah dan menyenangkan.', 'chat-bubble-left-right', 3, 1),
('Sistem Approval', 'Konten berkualitas terjamin melalui sistem review yang ketat sebelum dipublikasikan ke seluruh anggota.', 'shield-check', 4, 1);

-- Sample posts (approved activities)
INSERT INTO `posts` (`user_id`, `title`, `content`, `status`, `date`, `created_at`) VALUES
(3, 'Workshop Pemrograman Web', 'Hari ini kita belajar tentang pengembangan web modern menggunakan HTML5, CSS3, dan JavaScript. Para peserta sangat antusias dan banyak yang berhasil membuat website pertama mereka!', 'approved', '2024-01-15', NOW()),
(4, 'Kunjungan Industri ke Tech Company', 'Kunjungan yang sangat informatif ke perusahaan teknologi terkemuka. Kita belajar tentang bagaimana teknologi diterapkan dalam industri dan prospek karir di bidang IT.', 'approved', '2024-01-20', NOW()),
(5, 'Lomba Programming Competition', 'Tim Informatics A berhasil meraih juara 2 dalam kompetisi programming tingkat kota. Selamat kepada semua peserta yang telah berjuang!', 'approved', '2024-01-25', NOW()),
(3, 'Seminar AI dan Machine Learning', 'Seminar yang sangat menarik tentang kecerdasan buatan dan machine learning. Pembicara dari universitas terkemuka membagikan insight terbaru di bidang AI.', 'approved', '2024-02-01', NOW()),
(4, 'Project Based Learning - Aplikasi Mobile', 'Proyek akhir semester dimana mahasiswa membuat aplikasi mobile menggunakan Flutter. Hasilnya sangat memuaskan dan beberapa aplikasi siap untuk dipublikasikan.', 'approved', '2024-02-10', NOW()),
(5, 'Hackathon 24 Jam', 'Event hackathon selama 24 jam yang sangat seru! Banyak inovasi kreatif yang dihasilkan dan networking yang bermanfaat untuk karir masa depan.', 'approved', '2024-02-15', NOW());

-- Sample post images (make sure these files exist in your uploads folder)
INSERT INTO `post_images` (`post_id`, `image_path`, `image_order`) VALUES
(1, 'kegiatan_1_1.jpg', 1),
(1, 'kegiatan_1_2.jpg', 2),
(1, 'kegiatan_1_3.jpg', 3),
(2, 'kegiatan_2_1.jpg', 1),
(2, 'kegiatan_2_2.jpg', 2),
(3, 'kegiatan_3_1.jpg', 1),
(3, 'kegiatan_3_2.jpg', 2),
(4, 'kegiatan_4_1.jpg', 1),
(5, 'kegiatan_5_1.jpg', 1),
(5, 'kegiatan_5_2.jpg', 2),
(6, 'kegiatan_6_1.jpg', 1),
(6, 'kegiatan_6_2.jpg', 2),
(6, 'kegiatan_6_3.jpg', 3);

-- Sample comments
INSERT INTO `comments` (`post_id`, `user_id`, `comment`, `created_at`) VALUES
(1, 4, 'Kegiatan yang sangat bermanfaat! Saya belajar banyak tentang web development.', NOW()),
(1, 5, 'Materi yang disampaikan sangat mudah dipahami. Terima kasih atas kesempatannya!', NOW()),
(2, 3, 'Kunjungan industri yang sangat informatif. Banyak insight baru tentang industri IT.', NOW()),
(3, 4, 'Selamat atas prestasinya! Semoga bisa menginspirasi yang lain.', NOW()),
(3, 5, 'Bangga sekali dengan pencapaian tim Informatics A!', NOW()),
(4, 3, 'Seminar yang sangat menarik. AI memang masa depan teknologi.', NOW()),
(5, 4, 'Project yang sangat bagus! Aplikasi mobile dengan kualitas tinggi.', NOW()),
(6, 5, 'Hackathon yang sangat seru! Banyak ide kreatif yang muncul.', NOW());

-- Sample likes
INSERT INTO `likes` (`post_id`, `user_id`) VALUES
(1, 3), (1, 4), (1, 5),
(2, 3), (2, 4), (2, 5),
(3, 3), (3, 4), (3, 5),
(4, 3), (4, 4), (4, 5),
(5, 3), (5, 4),
(6, 3), (6, 4), (6, 5);

-- Sample gallery images
INSERT INTO `gallery` (`title`, `description`, `image`, `uploaded_by`, `created_at`) VALUES
('Workshop Foto 1', 'Suasana workshop pemrograman web yang sedang berlangsung', 'gallery_workshop_1.jpg', 1, NOW()),
('Kunjungan Industri', 'Foto bersama dengan tim perusahaan teknologi', 'gallery_industri_1.jpg', 1, NOW()),
('Hackathon Winners', 'Tim pemenang hackathon 24 jam', 'gallery_hackathon_1.jpg', 1, NOW()),
('Seminar AI', 'Pembicara seminar AI sedang presentasi', 'gallery_seminar_1.jpg', 1, NOW());

-- Sample announcements
INSERT INTO `announcements` (`title`, `content`, `is_active`, `created_by`, `created_at`) VALUES
('Jadwal Workshop Bulan Ini', 'Workshop pemrograman web akan dilaksanakan pada tanggal 15 setiap bulan. Pastikan untuk mendaftar terlebih dahulu.', 1, 1, NOW()),
('Pendaftaran Kompetisi Programming', 'Kompetisi programming tingkat nasional akan segera dibuka. Persiapkan diri Anda untuk berkompetisi!', 1, 1, NOW()),
('Pengumuman Libur Semester', 'Libur semester akan dimulai minggu depan. Selamat beristirahat dan tetap semangat belajar!', 1, 1, NOW());
