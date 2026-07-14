<?php
// Debug lengkap untuk masalah tampilan about_features
require_once __DIR__ . '/src/config/db.php';

echo "<h1>🔍 Debug Lengkap - About Features</h1>";

try {
    echo "<h2>1. Mengecek Query di about.php</h2>";

    // Query yang sama persis seperti di about.php
    $stmt = $pdo->query("SELECT * FROM about_features WHERE is_active = 1 ORDER BY display_order ASC, id ASC");
    $about_features_list = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo "<p><strong>Query yang dijalankan:</strong></p>";
    echo "<code>SELECT * FROM about_features WHERE is_active = 1 ORDER BY display_order ASC, id ASC</code>";
    echo "<br><br>";

    echo "<p><strong>Hasil query:</strong></p>";
    echo "<pre>";
    var_dump($about_features_list);
    echo "</pre>";

    echo "<p><strong>Jumlah data:</strong> " . count($about_features_list) . "</p>";
    echo "<p><strong>Kondisi !empty():</strong> " . (!empty($about_features_list) ? 'TRUE' : 'FALSE') . "</p>";
    echo "<br>";

    if (!empty($about_features_list)) {
        echo "<h3>✅ Data akan ditampilkan:</h3>";
        echo "<div style='border: 2px solid green; padding: 10px; margin: 10px 0;'>";
        foreach ($about_features_list as $index => $feature) {
            echo ($index + 1) . ". " . htmlspecialchars($feature['feature_text']) . "<br>";
        }
        echo "</div>";
    } else {
        echo "<h3>❌ Tidak ada data aktif - akan tampil pesan 'Fitur akan segera ditambahkan'</h3>";
    }

    echo "<hr>";

    echo "<h2>2. Mengecek Semua Data di Tabel (termasuk non-aktif)</h2>";

    $stmt = $pdo->query("SELECT * FROM about_features ORDER BY id");
    $all_data = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (count($all_data) > 0) {
        echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr style='background: #f0f0f0;'><th>ID</th><th>Feature Text</th><th>Is Active</th><th>Display Order</th><th>Created At</th></tr>";
        foreach ($all_data as $row) {
            $bgcolor = $row['is_active'] == 1 ? '#d4edda' : '#f8d7da';
            echo "<tr style='background: $bgcolor;'>";
            echo "<td>{$row['id']}</td>";
            echo "<td>{$row['feature_text']}</td>";
            echo "<td>{$row['is_active']}</td>";
            echo "<td>{$row['display_order']}</td>";
            echo "<td>{$row['created_at']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p>❌ Tabel about_features kosong sama sekali!</p>";
    }

    echo "<hr>";

    echo "<h2>3. Test Kondisi yang Ditambahkan</h2>";

    // Simulasi kondisi yang ada di about.php
    if (!empty($about_features_list)) {
        echo "<p>✅ Kondisi <code>if (!empty(\$about_features_list))</code> akan TRUE</p>";
        echo "<p>✅ Bagian 'Kenapa Memilih Platform Ini?' akan menampilkan data</p>";
    } else {
        echo "<p>❌ Kondisi <code>if (!empty(\$about_features_list))</code> akan FALSE</p>";
        echo "<p>❌ Bagian 'Kenapa Memilih Platform Ini?' akan menampilkan pesan 'Fitur akan segera ditambahkan'</p>";
    }

    echo "<hr>";

    echo "<h2>4. Saran Perbaikan</h2>";
    if (empty($about_features_list)) {
        echo "<p>🔧 <strong>MASALAH:</strong> Tidak ada data dengan is_active = 1</p>";
        echo "<p>💡 <strong>SOLUSI:</strong> Pastikan ada data dengan is_active = 1 di tabel about_features</p>";
        echo "<p>📋 <strong>Cara cek:</strong> Buka admin panel → Kelola Fitur → Aktifkan data yang diperlukan</p>";
    } else {
        echo "<p>✅ Data sudah benar dan siap ditampilkan</p>";
        echo "<p>💡 Jika masih tidak tampil, mungkin ada masalah dengan kode di about.php</p>";
    }

} catch (Exception $e) {
    echo "<h2 style='color: red;'>❌ Error Database:</h2>";
    echo "<p>" . $e->getMessage() . "</p>";
}
?>
