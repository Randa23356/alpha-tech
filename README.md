# 🚀 AlphaTech - Informatics Platform

<div align="center">
  <br>
  <img src="https://img.shields.io/badge/version-2.0.0-blue?style=for-the-badge&logo=github" alt="Version">
  <img src="https://img.shields.io/badge/PHP-8.2-777BB4?style=for-the-badge&logo=php" alt="PHP">
  <img src="https://img.shields.io/badge/MySQL-8.0-4479A1?style=for-the-badge&logo=mysql" alt="MySQL">
  <img src="https://img.shields.io/badge/Tailwind_CSS-3.4-06B6D4?style=for-the-badge&logo=tailwindcss" alt="Tailwind">
  <img src="https://img.shields.io/badge/Google_OAuth-4285F4?style=for-the-badge&logo=google" alt="Google OAuth">
  <br><br>
  
  [![Live Demo](https://img.shields.io/badge/🌐_Live_Demo-FF6B6B?style=for-the-badge)](https://alpha-tech.ftwodev.id)
  [![Report Bug](https://img.shields.io/badge/🐛_Report_Bug-333?style=for-the-badge)](https://github.com/randaar/alpha/issues)
  
  <br>
  
  <p>
    <strong>✨ Platform Kolaborasi & Dokumentasi Kelas Informatika Modern ✨</strong>
  </p>
  
  <p>
    <i>"Empowering students to collaborate, document, and thrive together."</i>
  </p>
</div>

---

## 🌟 Fitur Unggulan

<table>
  <tr>
    <td align="center" width="25%">
      <h3>📝</h3>
      <b>Posting Kegiatan</b>
      <br/>
      <sub>Bagikan kegiatan kelas dengan mudah</sub>
    </td>
    <td align="center" width="25%">
      <h3>📸</h3>
      <b>Galeri Foto</b>
      <br/>
      <sub>Dokumentasi visual otomatis</sub>
    </td>
    <td align="center" width="25%">
      <h3>💬</h3>
      <b>Komentar</b>
      <br/>
      <sub>Diskusi & feedback实时</sub>
    </td>
    <td align="center" width="25%">
      <h3>📢</h3>
      <b>Pengumuman</b>
      <br/>
      <sub>Informasi kelas terpusat</sub>
    </td>
  </tr>
  <tr>
    <td align="center">
      <h3>🔐</h3>
      <b>Google OAuth</b>
      <br/>
      <sub>Login cepat & aman</sub>
    </td>
    <td align="center">
      <h3>📱</h3>
      <b>Responsive</b>
      <br/>
      <sub>Mobile-first design</sub>
    </td>
    <td align="center">
      <h3>🎨</h3>
      <b>Kustomisasi</b>
      <br/>
      <sub>Warna & logo dinamis</sub>
    </td>
    <td align="center">
      <h3>🔔</h3>
      <b>FCM Notifikasi</b>
      <br/>
      <sub>Push notification real-time</sub>
    </td>
  </tr>
</table>

## 📸 Screenshots

<div align="center">
  <table>
    <tr>
      <td><img src="https://via.placeholder.com/400x250/1e3a8a/ffffff?text=Login+Page" alt="Login" width="100%"></td>
      <td><img src="https://via.placeholder.com/400x250/1e40af/ffffff?text=Dashboard" alt="Dashboard" width="100%"></td>
    </tr>
    <tr>
      <td align="center"><sub>🔐 Halaman Login</sub></td>
      <td align="center"><sub>📊 Dashboard User</sub></td>
    </tr>
    <tr>
      <td><img src="https://via.placeholder.com/400x250/10b981/ffffff?text=Gallery" alt="Gallery" width="100%"></td>
      <td><img src="https://via.placeholder.com/400x250/8b5cf6/ffffff?text=Admin" alt="Admin" width="100%"></td>
    </tr>
    <tr>
      <td align="center"><sub>🖼️ Galeri Foto</sub></td>
      <td align="center"><sub>⚙️ Panel Admin</sub></td>
    </tr>
  </table>
</div>

## 🛠️ Tech Stack

<div align="center">
  <table>
    <tr>
      <th colspan="2">Frontend</th>
      <th colspan="2">Backend</th>
    </tr>
    <tr>
      <td align="center"><b>HTML5</b></td>
      <td align="center"><b>Tailwind CSS</b></td>
      <td align="center"><b>PHP 8.2</b></td>
      <td align="center"><b>MySQL 8.0</b></td>
    </tr>
    <tr>
      <td align="center"><b>JavaScript</b></td>
      <td align="center"><b>AJAX</b></td>
      <td align="center"><b>Google API</b></td>
      <td align="center"><b>FCM</b></td>
    </tr>
  </table>
</div>

## 🏗️ Arsitektur

```
📂 alpha/
├── 📁 admin/          # Panel admin
├── 📁 api/            # REST API endpoints
├── 📁 includes/       # Komponen reusable
├── 📁 korti/          # Panel koordinator
├── 📁 public/         # Halaman publik & assets
├── 📁 src/            # Core aplikasi
│   ├── 📁 config/     # Konfigurasi
│   ├── 📁 controllers/# Logic controllers
│   └── 📁 helpers/    # Helper functions
├── 📁 views/          # Template views
└── 📁 vendor/         # Dependencies (Composer)
```

## 🚀 Instalasi

### Prerequisites
- PHP 8.0+
- MySQL 8.0+
- Composer
- Web Server (Apache/Nginx)

### Langkah Instalasi

```bash
# 1. Clone repository
git clone https://github.com/randaar/alpha.git
cd alpha

# 2. Install dependencies
composer install

# 3. Setup database
mysql -u root -p -e "CREATE DATABASE alpha;"
mysql -u root -p alpha < database/schema.sql

# 4. Konfigurasi database
cp config/database.example.php config/database.php
# Edit config/database.php dengan kredensial database kamu

# 5. Google OAuth Configuration
echo "Buka Google Cloud Console → APIs & Services → Credentials"
echo "Buat OAuth 2.0 Client ID"
echo "Set redirect URI ke: https://domainkamu.com/google-callback.php"

# 6. Jalankan
php -S localhost:8000
```

## 👥 Role User

| Role | Akses |
|------|-------|
| 🛡️ **Admin** | Full akses, manajemen pengguna, pengaturan |
| 📋 **Korti** | Kelola postingan, komentar, pengumuman |
| 👤 **User** | Posting kegiatan, komentar, galeri |

## ✨ Fitur Admin

- [x] Manajemen slider hero
- [x] Kustomisasi warna tema
- [x] Upload logo navbar
- [x] Manajemen postingan
- [x] Approval konten
- [x] Statistik dashboard
- [x] Manajemen pengguna

## 📱 Fitur Mobile

- ✅ Responsive design
- ✅ Mobile-first approach
- ✅ Touch-friendly interface
- ✅ Optimasi performa mobile

## 🌐 Environment Support

| Environment | Status |
|------------|--------|
| 🖥️ Local (XAMPP) | ✅ Supported |
| ☁️ cPanel Hosting | ✅ Supported |
| 🐳 Docker | 🚧 Coming Soon |
| 📱 Android (WebView) | ✅ Supported |

## 🤝 Kontribusi

Kontribusi selalu diterima! Silakan buat pull request atau buka issue untuk saran dan perbaikan.

1. Fork repository
2. Buat branch baru: `git checkout -b fitur-keren`
3. Commit perubahan: `git commit -m 'feat: tambah fitur keren'`
4. Push: `git push origin fitur-keren`
5. Buat Pull Request 🚀

## 📄 Lisensi

Distributed under the MIT License. See `LICENSE` for more information.

---

<div align="center">
  <br>
  <p>
    <b>Dibuat dengan ❤️ oleh Tim AlphaTech Informatics</b>
  </p>
  <p>
    <a href="https://github.com/randaar">
      <img src="https://img.shields.io/badge/GitHub-randaar-181717?style=for-the-badge&logo=github" alt="GitHub">
    </a>
    <a href="https://alpha-tech.ftwodev.id">
      <img src="https://img.shields.io/badge/🌐_Website-FF6B6B?style=for-the-badge" alt="Website">
    </a>
  </p>
  <br>
  
  ![Footer](https://capsule-render.vercel.app/api?type=waving&color=gradient&height=100&section=footer)
</div>
