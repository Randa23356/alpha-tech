<?php
// Debug script untuk cek tabel about_features
require_once __DIR__ . '/src/config/db.php';

try {
    echo "<h2>Checking about_features table</h2>";

    // Cek apakah tabel ada
    $stmt = $pdo->query("SHOW TABLES LIKE 'about_features'");
    if ($stmt->rowCount() > 0) {
        echo "<p>✅ Tabel about_features ditemukan</p>";

        // Lihat struktur tabel
        echo "<h3>Struktur tabel:</h3>";
        $stmt = $pdo->query("DESCRIBE about_features");
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo "<table border='1'>";
        echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th></tr>";
        foreach ($columns as $col) {
            echo "<tr>";
            echo "<td>{$col['Field']}</td>";
            echo "<td>{$col['Type']}</td>";
            echo "<td>{$col['Null']}</td>";
            echo "<td>{$col['Key']}</td>";
            echo "<td>{$col['Default']}</td>";
            echo "</tr>";
        }
        echo "</table>";

        // Lihat isi tabel
        echo "<h3>Isi tabel:</h3>";
        $stmt = $pdo->query("SELECT * FROM about_features ORDER BY display_order ASC, id ASC");
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (count($data) > 0) {
            echo "<table border='1'>";
            echo "<tr><th>ID</th><th>Feature Text</th><th>Is Active</th><th>Display Order</th></tr>";
            foreach ($data as $row) {
                echo "<tr>";
                echo "<td>{$row['id']}</td>";
                echo "<td>{$row['feature_text']}</td>";
                echo "<td>{$row['is_active']}</td>";
                echo "<td>{$row['display_order']}</td>";
                echo "</tr>";
            }
            echo "</table>";

            // Cek data yang aktif
            echo "<h3>Data yang aktif (is_active = 1):</h3>";
            $stmt = $pdo->query("SELECT * FROM about_features WHERE is_active = 1 ORDER BY display_order ASC, id ASC");
            $activeData = $stmt->fetchAll(PDO::FETCH_ASSOC);

            if (count($activeData) > 0) {
                echo "<p>✅ Ada " . count($activeData) . " data aktif yang akan ditampilkan</p>";
                echo "<ul>";
                foreach ($activeData as $row) {
                    echo "<li>{$row['feature_text']}</li>";
                }
                echo "</ul>";
            } else {
                echo "<p>❌ Tidak ada data aktif (is_active = 1)</p>";
            }
        } else {
            echo "<p>❌ Tabel kosong - tidak ada data sama sekali</p>";
        }
    } else {
        echo "<p>❌ Tabel about_features tidak ditemukan</p>";
    }

} catch (Exception $e) {
    echo "<p>Error: " . $e->getMessage() . "</p>";
}
?>
