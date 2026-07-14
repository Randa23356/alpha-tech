<?php
$favicon_path = 'public/images/logo.png';

try {
    require_once __DIR__ . '/../src/config/db.php';
    $stmt = $pdo->query("SELECT setting_key, setting_value FROM site_settings WHERE setting_key = 'navbar_icon_id'");
    $settings = [];
    foreach ($stmt->fetchAll() as $row) {
        $settings[$row['setting_key']] = $row['setting_value'];
    }
    $navbar_icon_id = $settings['navbar_icon_id'] ?? null;
    if ($navbar_icon_id && $navbar_icon_id !== '' && $navbar_icon_id !== '0') {
        $icon_stmt = $pdo->prepare("SELECT file_path FROM navbar_icons WHERE id = ? AND is_active = 1");
        $icon_stmt->execute([$navbar_icon_id]);
        $icon_result = $icon_stmt->fetch(PDO::FETCH_ASSOC);
        if ($icon_result) {
            $favicon_path = $icon_result['file_path'];
        }
    }
} catch (Exception $e) {
    // fallback
}
$favicon_url = url(htmlspecialchars($favicon_path)) . '?v=' . time();
?>
<link rel="icon" type="image/png" href="<?= $favicon_url ?>">
<link rel="shortcut icon" href="<?= $favicon_url ?>">
