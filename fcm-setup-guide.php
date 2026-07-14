<!DOCTYPE html>
<html>
<head>
    <title>FCM Setup Guide - Alpha Tech</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 900px; margin: 20px auto; padding: 20px; }
        .error { background: #ffebee; border-left: 4px solid #f44336; padding: 15px; margin: 15px 0; }
        .warning { background: #fff3cd; border-left: 4px solid #ffc107; padding: 15px; margin: 15px 0; }
        .success { background: #e8f5e9; border-left: 4px solid #4caf50; padding: 15px; margin: 15px 0; }
        .info { background: #e3f2fd; border-left: 4px solid #2196f3; padding: 15px; margin: 15px 0; }
        .step { background: #f5f5f5; padding: 15px; margin: 10px 0; border-radius: 5px; }
        code { background: #f5f5f5; padding: 2px 6px; border-radius: 3px; font-family: monospace; }
        pre { background: #263238; color: #aed581; padding: 15px; border-radius: 5px; overflow-x: auto; }
        h1 { color: #1976d2; }
        h2 { color: #424242; border-bottom: 2px solid #e0e0e0; padding-bottom: 10px; }
        .check { color: #4caf50; font-weight: bold; }
        .cross { color: #f44336; font-weight: bold; }
    </style>
</head>
<body>
    <h1>🔧 FCM Setup & Troubleshooting Guide</h1>
    
    <div class="error">
        <h3>❌ Current Error: HTTP 404 - Requested entity was not found</h3>
        <p><strong>Meaning:</strong> Firebase Cloud Messaging API tidak dapat menemukan project atau API belum diaktifkan.</p>
    </div>

    <h2>📋 Checklist Perbaikan</h2>

    <div class="step">
        <h3>Step 1: Verify Project ID</h3>
        <p><strong>Current Project ID:</strong> <code>alpha-tech-9b9af</code></p>
        <p>✅ Project ID matches service account JSON</p>
    </div>

    <div class="step">
        <h3>Step 2: Enable Firebase Cloud Messaging API</h3>
        <p>Ini adalah langkah paling penting! FCM API harus diaktifkan di Google Cloud Console.</p>
        
        <ol>
            <li>Buka: <a href="https://console.cloud.google.com/apis/library/fcm.googleapis.com?project=alpha-tech-9b9af" target="_blank">
                <strong>Firebase Cloud Messaging API</strong>
            </a></li>
            <li>Pastikan project <code>alpha-tech-9b9af</code> dipilih di dropdown atas</li>
            <li>Klik tombol <strong>"ENABLE"</strong> (atau "AKTIFKAN")</li>
            <li>Tunggu beberapa detik sampai API aktif</li>
        </ol>

        <div class="warning">
            <strong>⚠️ PENTING:</strong> Jika tombol sudah "MANAGE" atau "KELOLA", berarti API sudah aktif. Lanjut ke step berikutnya.
        </div>
    </div>

    <div class="step">
        <h3>Step 3: Verify Service Account Permissions</h3>
        <p>Service account harus punya role <code>Firebase Cloud Messaging Admin</code></p>
        
        <ol>
            <li>Buka: <a href="https://console.cloud.google.com/iam-admin/iam?project=alpha-tech-9b9af" target="_blank">
                <strong>IAM & Admin</strong>
            </a></li>
            <li>Cari email: <code>firebase-adminsdk-fbsvc@alpha-tech-9b9af.iam.gserviceaccount.com</code></li>
            <li>Pastikan punya role:
                <ul>
                    <li>✅ <strong>Firebase Admin SDK Administrator Service Agent</strong></li>
                    <li>✅ <strong>Cloud Messaging Admin</strong> (atau Firebase Cloud Messaging Admin)</li>
                </ul>
            </li>
            <li>Jika tidak ada, klik <strong>"GRANT ACCESS"</strong> dan tambahkan role tersebut</li>
        </ol>
    </div>

    <div class="step">
        <h3>Step 4: Alternative - Use Legacy FCM Server Key (Temporary)</h3>
        <p>Jika V1 API masih bermasalah, bisa pakai Legacy Server Key sementara:</p>
        
        <ol>
            <li>Buka: <a href="https://console.firebase.google.com/project/alpha-tech-9b9af/settings/cloudmessaging" target="_blank">
                <strong>Firebase Console → Cloud Messaging</strong>
            </a></li>
            <li>Scroll ke bawah ke section <strong>"Cloud Messaging API (Legacy)"</strong></li>
            <li>Copy <strong>Server Key</strong></li>
            <li>Gunakan Legacy API endpoint (akan saya buatkan scriptnya jika diperlukan)</li>
        </ol>

        <div class="info">
            <strong>ℹ️ Note:</strong> Legacy API akan deprecated 2024, tapi masih bisa dipakai untuk testing.
        </div>
    </div>

    <div class="step">
        <h3>Step 5: Test Again</h3>
        <p>Setelah enable FCM API dan verify permissions:</p>
        <ol>
            <li>Tunggu 1-2 menit (propagation time)</li>
            <li>Refresh halaman: <a href="test-fcm.php"><strong>test-fcm.php</strong></a></li>
            <li>Seharusnya error 404 hilang</li>
        </ol>
    </div>

    <h2>🔍 Quick Links</h2>
    <ul>
        <li><a href="https://console.cloud.google.com/apis/library/fcm.googleapis.com?project=alpha-tech-9b9af" target="_blank">
            Enable FCM API
        </a></li>
        <li><a href="https://console.cloud.google.com/iam-admin/iam?project=alpha-tech-9b9af" target="_blank">
            IAM Permissions
        </a></li>
        <li><a href="https://console.firebase.google.com/project/alpha-tech-9b9af/settings/cloudmessaging" target="_blank">
            Firebase Cloud Messaging Settings
        </a></li>
        <li><a href="test-fcm.php">Test FCM Again</a></li>
        <li><a href="check-tokens.php">Check FCM Tokens</a></li>
    </ul>

    <h2>📝 Expected Result After Fix</h2>
    <div class="success">
        <pre>✅ Test notification sent successfully!
Sent: 2 devices
Failed: 0 devices

Check your phone for notification!</pre>
    </div>

    <h2>🆘 Still Not Working?</h2>
    <p>Jika masih error 404 setelah enable API:</p>
    <ol>
        <li>Cek apakah project ID benar-benar <code>alpha-tech-9b9af</code> di Firebase Console</li>
        <li>Download ulang service account JSON dari Firebase Console</li>
        <li>Pastikan tidak ada typo di <code>FIREBASE_PROJECT_ID</code> di fcm_helper.php</li>
        <li>Coba pakai Legacy Server Key sebagai alternatif</li>
    </ol>

</body>
</html>
