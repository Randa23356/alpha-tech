<?php
// test-token-sync.php
session_start();
require_once __DIR__ . '/src/config/db.php';

$user_id = $_SESSION['user_id'] ?? null;
$username = $_SESSION['user']['username'] ?? 'Unknown';

?>
<!DOCTYPE html>
<html>
<head>
    <title>Force Sync Token</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <style>
        body { font-family: sans-serif; padding: 20px; line-height: 1.6; }
        .btn { display: block; width: 100%; padding: 15px; background: #1e3a8a; color: white; text-align: center; text-decoration: none; border-radius: 8px; margin-top: 20px; }
        .status { padding: 15px; background: #f0f0f0; border-radius: 8px; margin-bottom: 20px; }
    </style>
</head>
<body>
    <h2>🛠️ Force Sync Token</h2>
    
    <div class="status">
        <p><strong>Login Status:</strong> <?= $user_id ? "✅ Logged In (ID: $user_id)" : "❌ Not Logged In" ?></p>
        <p><strong>Username:</strong> <?= htmlspecialchars($username) ?></p>
        <p><strong>Server Time:</strong> <?= date('Y-m-d H:i:s') ?></p>
    </div>

    <?php if ($user_id): ?>
        <p>Klik tombol di bawah ini untuk memaksa update token HP Anda ke akun ini.</p>
        
        <button onclick="syncToken()" class="btn">🔄 Sync Token Sekarang</button>
        
        <div id="result" style="margin-top:20px"></div>

        <script>
            function syncToken() {
                const resultDiv = document.getElementById('result');
                resultDiv.innerHTML = '⏳ Sedang memproses...';
                
                // Ambil token dari localStorage (disimpan oleh script index.js yang baru)
                // ATAU coba ambil token baru jika pakai plugin
                
                const token = localStorage.getItem('fcm_token');
                
                if (!token) {
                    resultDiv.innerHTML = '❌ Token tidak ditemukan di browser. Pastikan Anda membuka ini dari dalam aplikasi Alpha Tech.';
                    return;
                }
                
                resultDiv.innerHTML += '<br>📱 Token ditemukan: ' + token.substring(0, 15) + '...';
                
                fetch('api/register-fcm-token.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        token: token,
                        user_id: <?= $user_id ?>,
                        device_type: 'android',
                        app_version: '1.0.0'
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if(data.success) {
                        resultDiv.innerHTML = '<div style="color:green;font-weight:bold;margin-top:10px">✅ SUKSES! Token berhasil dihubungkan ke akun Anda.</div>';
                        resultDiv.innerHTML += '<p>Silakan coba buat pengumuman sekarang.</p>';
                    } else {
                        resultDiv.innerHTML = '<div style="color:red;margin-top:10px">❌ GAGAL: ' + (data.error || 'Unknown error') + '</div>';
                    }
                })
                .catch(err => {
                    resultDiv.innerHTML = '<div style="color:red;margin-top:10px">❌ ERROR: ' + err.message + '</div>';
                });
            }
        </script>
    <?php else: ?>
        <p style="color:red">Anda belum login. Silakan login terlebih dahulu di aplikasi.</p>
        <a href="login.php" class="btn">Login Sekarang</a>
    <?php endif; ?>
</body>
</html>
