<?php
// Script untuk melihat HTML source yang dihasilkan
require_once __DIR__ . '/src/config/db.php';

echo "<h1>🔍 HTML Source Inspector - About Features</h1>";

try {
    // Query yang sama seperti di about.php
    $stmt = $pdo->query("SELECT * FROM about_features WHERE is_active = 1 ORDER BY display_order ASC, id ASC");
    $about_features_list = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo "<h2>1. Data dari Database:</h2>";
    echo "<pre>";
    print_r($about_features_list);
    echo "</pre>";

    echo "<h2>2. HTML yang Akan Dihasilkan:</h2>";
    echo "<div style='border: 2px solid blue; padding: 20px; margin: 20px 0; background: #f0f8ff;'>";

    if (!empty($about_features_list)) {
        echo "<div style='background: #d4edda; padding: 10px; margin: 10px 0; border-radius: 5px;'>";
        echo "<h3>✅ Kondisi TRUE - Data akan ditampilkan:</h3>";
        echo "<div class='grid grid-cols-1 md:grid-cols-2 gap-8'>";

        foreach ($about_features_list as $index => $feature) {
            echo "<div style='background: white; padding: 20px; border-radius: 10px; border: 2px solid green; margin: 10px 0;'>";
            echo "<div style='display: flex; gap: 20px; align-items: start;'>";
            echo "<div style='width: 64px; height: 64px; background: linear-gradient(135deg, #1e3a8a 0%, #1e40af 100%); border-radius: 10px; display: flex; align-items: center; justify-content: center;'>";
            echo "<svg style='width: 32px; height: 32px; color: white;' fill='none' stroke='currentColor' viewBox='0 0 24 24'>";
            echo "<path stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M5 13l4 4L19 7'/>";
            echo "</svg>";
            echo "</div>";
            echo "<div style='flex: 1;'>";
            echo "<div style='width: 48px; height: 4px; background: linear-gradient(135deg, #1e3a8a 0%, #1e40af 100%); border-radius: 2px; margin-bottom: 16px;'></div>";
            echo "<p style='color: #374151; font-size: 18px; line-height: 1.6;'>";
            echo htmlspecialchars($feature['feature_text']);
            echo "</p>";
            echo "</div>";
            echo "</div>";
            echo "</div>";
        }

        echo "</div>";
        echo "</div>";
    } else {
        echo "<div style='background: #f8d7da; padding: 10px; margin: 10px 0; border-radius: 5px;'>";
        echo "<h3>❌ Kondisi FALSE - Pesan akan ditampilkan:</h3>";
        echo "<div style='text-align: center; padding: 32px;'>";
        echo "<div style='width: 64px; height: 64px; background: #f3f4f6; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 16px;'>";
        echo "<svg style='width: 32px; height: 32px; color: #9ca3af;' fill='none' stroke='currentColor' viewBox='0 0 24 24'>";
        echo "<path stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z'/>";
        echo "</svg>";
        echo "</div>";
        echo "<p style='color: #6b7280;'>Fitur akan segera ditambahkan</p>";
        echo "</div>";
        echo "</div>";
    }

    echo "</div>";

    echo "<h2>3. Saran Troubleshooting:</h2>";
    echo "<ul>";
    echo "<li>✅ Jika HTML di atas terlihat benar → masalah di CSS atau cache browser</li>";
    echo "<li>✅ Jika HTML kosong → masalah di PHP atau database</li>";
    echo "<li>🔧 Coba: <strong>Ctrl+F5</strong> untuk hard refresh</li>";
    echo "<li>🔧 Coba: Buka Developer Tools (F12) → Console untuk lihat error</li>";
    echo "<li>🔧 Coba: Inspect element pada bagian yang kosong</li>";
    echo "</ul>";

} catch (Exception $e) {
    echo "<h2 style='color: red;'>❌ Error:</h2>";
    echo "<p>" . $e->getMessage() . "</p>";
}
?>
