<?php
// includes/footer.php - Reusable Footer Component
require_once __DIR__ . "/../src/config/db.php";
require_once __DIR__ . "/../src/config/urls.php";

// Load theme colors from database
$primary_color = "#1e3a8a"; // default
$secondary_color = "#1e40af"; // default
$accent_color = "#ec4899"; // default

try {
    $stmt = $pdo->query(
        "SELECT setting_key, setting_value FROM site_settings WHERE setting_key IN ('primary_color', 'secondary_color', 'accent_color', 'site_tagline')",
    );
    $theme_settings = [];
    foreach ($stmt->fetchAll() as $row) {
        $theme_settings[$row["setting_key"]] = $row["setting_value"];
    }
    $primary_color = $theme_settings["primary_color"] ?? $primary_color;
    $secondary_color = $theme_settings["secondary_color"] ?? $secondary_color;
    $accent_color = $theme_settings["accent_color"] ?? $accent_color;
    $site_tagline = $theme_settings["site_tagline"] ?? 'Platform kolaborasi dan dokumentasi kelas Informatika terbaik';
} catch (Exception $e) {
    // Use default colors if database fails
    $site_tagline = 'Platform kolaborasi dan dokumentasi kelas Informatika terbaik';
}

// Ambil logo navbar (navbar_icon) dari database
$footer_logo = 'public/images/logo.png';
try {
    $stmt = $pdo->query("SELECT setting_key, setting_value FROM site_settings WHERE setting_key = 'navbar_icon_id'");
    $settings_navbar = [];
    foreach ($stmt->fetchAll() as $row) {
        $settings_navbar[$row['setting_key']] = $row['setting_value'];
    }
    $navbar_icon_id = $settings_navbar['navbar_icon_id'] ?? null;
    if ($navbar_icon_id && $navbar_icon_id !== '' && $navbar_icon_id !== '0') {
        $icon_stmt = $pdo->prepare("SELECT file_path FROM navbar_icons WHERE id = ? AND is_active = 1");
        $icon_stmt->execute([$navbar_icon_id]);
        $icon_result = $icon_stmt->fetch(PDO::FETCH_ASSOC);
        if ($icon_result) {
            $footer_logo = $icon_result['file_path'];
        }
    }
} catch (Exception $e) {
    // fallback
}
$footer_logo_url = url(htmlspecialchars($footer_logo)) . '?v=' . time();

// Default values jika tidak ada di database
$site_name = $settings['site_name'] ?? 'Informatics A';
$site_tagline = $settings['site_tagline'] ?? 'Platform kolaborasi dan dokumentasi kelas Informatika terbaik';
$footer_text = $settings['footer_text'] ?? 'All rights reserved. Built with ❤️ by AlphaTech Informatics Team';
$contact_email = $settings['contact_email'] ?? 'info@informaticsa.edu';
$contact_instagram = $settings['contact_instagram'] ?? '@informaticsa';
$contact_phone = $settings['contact_phone'] ?? '+62 812-3456-7890';
$contact_address = $settings['contact_address'] ?? 'Jl. Pendidikan No. 123, Jakarta, Indonesia';
$show_facebook_icon = $settings['show_facebook_icon'] ?? 1;
$show_twitter_icon = $settings['show_twitter_icon'] ?? 1;
$show_github_icon = $settings['show_github_icon'] ?? 1;
?>

<footer class="text-white py-12 mt-16" style="background: linear-gradient(135deg, <?= $primary_color ?> 0%, <?= $secondary_color ?> 100%);">
    <div class="max-w-7xl mx-auto px-6">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-8 mb-8">
            <!-- About -->
            <div>
                <div class="flex items-center gap-2 mb-4">
                    <img src="<?= $footer_logo_url ?>" alt="<?= htmlspecialchars($site_name) ?>" class="w-10 h-10 rounded-lg object-cover">
                    <h3 class="text-xl font-bold"><?= htmlspecialchars($site_name) ?></h3>
                </div>
                <p class="text-blue-200 text-sm"><?= htmlspecialchars($site_tagline) ?></p>
            </div>

            <!-- Quick Links -->
            <div>
                <h4 class="font-bold mb-4 text-lg">Menu</h4>
                <ul class="space-y-2 text-blue-200 text-sm">
                    <li><a href="<?= url('home') ?>" class="hover:text-white transition">Home</a></li>
                    <li><a href="<?= url('gallery') ?>" class="hover:text-white transition">Galeri</a></li>
                    <li><a href="<?= url('announcement') ?>" class="hover:text-white transition">Pengumuman</a></li>
                    <li><a href="<?= url('contact') ?>" class="hover:text-white transition">Kontak</a></li>
                </ul>
            </div>

            <!-- Account -->
            <div>
                <h4 class="font-bold mb-4 text-lg">Akun</h4>
                <ul class="space-y-2 text-blue-200 text-sm">
                    <?php if (isset($_SESSION['user'])): ?>
                        <li><a href="<?= url('dashboard') ?>" class="hover:text-white transition">Dashboard</a></li>
                        <li><a href="<?= url('profile') ?>" class="hover:text-white transition">Profil</a></li>
                        <li><a href="<?= url('post') ?>" class="hover:text-white transition">Posting Kegiatan</a></li>
                        <li><a href="<?= url('logout') ?>" class="hover:text-white transition">Logout</a></li>
                    <?php else: ?>
                        <li><a href="<?= url('login') ?>" class="hover:text-white transition">Login</a></li>
                        <li><a href="<?= url('register') ?>" class="hover:text-white transition">Daftar</a></li>
                    <?php endif; ?>
                </ul>
            </div>

            <!-- Contact Info -->
            <div>
                <h4 class="font-bold mb-4 text-lg">Kontak</h4>
                <ul class="space-y-3 text-blue-200 text-sm">
                    <li class="flex items-center gap-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                        </svg>
                        <a href="mailto:<?= htmlspecialchars($contact_email) ?>" class="hover:text-white transition">
                            <?= htmlspecialchars($contact_email) ?>
                        </a>
                    </li>
                    <li class="flex items-center gap-2">
                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zm0-2.163c-3.259 0-3.667.014-4.947.072-4.358.2-6.78 2.618-6.98 6.98-.059 1.281-.073 1.689-.073 4.948 0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98 1.281.058 1.689.072 4.948.072 3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98-1.281-.059-1.69-.073-4.949-.073zm0 5.838c-3.403 0-6.162 2.759-6.162 6.162s2.759 6.163 6.162 6.163 6.162-2.759 6.162-6.163c0-3.403-2.759-6.162-6.162-6.162zm0 10.162c-2.209 0-4-1.79-4-4 0-2.209 1.791-4 4-4s4 1.791 4 4c0 2.21-1.791 4-4 4zm6.406-11.845c-.796 0-1.441.645-1.441 1.44s.645 1.44 1.441 1.44c.795 0 1.439-.645 1.439-1.44s-.644-1.44-1.439-1.44z"/>
                        </svg>
                        <a href="https://instagram.com/<?= htmlspecialchars(str_replace('@', '', $contact_instagram)) ?>" target="_blank" class="hover:text-white transition">
                            <?= htmlspecialchars($contact_instagram) ?>
                        </a>
                    </li>
<li class="flex items-center gap-2">
    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
    </svg>
    <span><?= htmlspecialchars($contact_phone) ?></span> 
</li>
<li class="flex items-start gap-2">
    <svg class="w-5 h-5 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
    </svg>
    <span class="break-words"><?= htmlspecialchars($contact_address) ?></span>
</li>
                </ul>
            </div>
        </div>

        <!-- Bottom Bar -->
        <div class="border-t border-white/20 pt-8 flex flex-col md:flex-row justify-between items-center gap-4">
            <p class="text-blue-200 text-sm text-center md:text-left">
                &copy; <?= date("Y") ?> <?= htmlspecialchars($site_name) ?>. <?= htmlspecialchars($footer_text) ?>
            </p>
            <div class="flex gap-4">
                <?php if ($show_facebook_icon): ?>
                <a href="#" class="text-blue-200 hover:text-white transition">
                    <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/>
                    </svg>
                </a>
                <?php endif; ?>

                <?php if ($show_twitter_icon): ?>
                <a href="#" class="text-blue-200 hover:text-white transition">
                    <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M23.953 4.57a10 10 0 01-2.825.775 4.958 4.958 0 002.163-2.723c-.951.555-2.005.959-3.127 1.184a4.92 4.92 0 00-8.384 4.482C7.69 8.095 4.067 6.13 1.64 3.162a4.822 4.822 0 00-.666 2.475c0 1.71.87 3.213 2.188 4.096a4.904 4.904 0 01-2.228-.616v.06a4.923 4.923 0 003.946 4.827 4.996 4.996 0 01-2.212.085 4.936 4.936 0 004.604 3.417 9.867 9.867 0 01-6.102 2.105c-.39 0-.779-.023-1.17-.067a13.995 13.995 0 007.557 2.209c9.053 0 13.998-7.496 13.998-13.985 0-.21 0-.42-.015-.63A9.935 9.935 0 0024 4.59z"/>
                    </svg>
                </a>
                <?php endif; ?>

                <?php if ($show_github_icon): ?>
                <a href="#" class="text-blue-200 hover:text-white transition">
                    <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M12 0C5.374 0 0 5.373 0 12c0 5.302 3.438 9.8 8.207 11.387.599.111.793-.261.793-.577v-2.234c-3.338.726-4.033-1.416-4.033-1.416-.546-1.387-1.333-1.756-1.333-1.756-1.089-.745.083-.729.083-.729 1.205.084 1.839 1.237 1.839 1.237 1.07 1.834 2.807 1.304 3.492.997.107-.775.418-1.305.762-1.604-2.665-.305-5.467-1.334-5.467-5.931 0-1.311.469-2.381 1.236-3.221-.124-.303-.535-1.524.117-3.176 0 0 1.008-.322 3.301 1.23A11.509 11.509 0 0112 5.803c1.02.005 2.047.138 3.006.404 2.291-1.552 3.297-1.23 3.297-1.23.653 1.653.242 2.874.118 3.176.77.84 1.235 1.911 1.235 3.221 0 4.609-2.807 5.624-5.479 5.921.43.372.823 1.102.823 2.222v3.293c0 .319.192.694.801.576C20.566 21.797 24 17.3 24 12c0-6.627-5.373-12-12-12z"/>
                    </svg>
                </a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</footer>

<?php if (isset($_SESSION['user_id'])): ?>
<script>
    // Listen for FCM token from Cordova
    document.addEventListener('cordova-fcm-token', function(e) {
        console.log('Received FCM token from Cordova:', e.detail.token.substring(0, 20) + '...');
        
        const token = e.detail.token;
        const userId = '<?= $_SESSION['user_id'] ?>';
        
        console.log('Auto-syncing token for user:', userId);
        
        fetch('<?= url('api/register-fcm-token.php') ?>', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                token: token,
                user_id: userId,
                device_type: 'android',
                app_version: '1.0.0'
            })
        })
        .then(response => response.json())
        .then(data => {
            console.log('✅ FCM Token auto-synced:', data);
        })
        .catch(err => console.error('❌ FCM Sync Error:', err));
    });
    
    // Also check if token already available in window.CORDOVA_FCM_TOKEN
    document.addEventListener('DOMContentLoaded', function() {
        if (window.CORDOVA_FCM_TOKEN) {
            console.log('Found CORDOVA_FCM_TOKEN, auto-syncing...');
            const token = window.CORDOVA_FCM_TOKEN;
            const userId = '<?= $_SESSION['user_id'] ?>';
            
            fetch('<?= url('api/register-fcm-token.php') ?>', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    token: token,
                    user_id: userId,
                    device_type: 'android',
                    app_version: '1.0.0'
                })
            })
            .then(response => response.json())
            .then(data => {
                console.log('✅ FCM Token synced from window var:', data);
            })
            .catch(err => console.error('❌ FCM Sync Error:', err));
        }
    });
</script>
<?php endif; ?>