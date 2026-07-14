# Informatics A Database Setup

## File Overview

### Database Schema Files
- `database_schema.sql` - Main database schema with all basic tables
- `photo_management_tables.sql` - Additional photo management tables
- `sample_data.sql` - Sample data for testing
- `photo_management_data.sql` - Sample data for photo management tables

### Setup Scripts
- `database_setup.php` - Automated setup script (recommended)
- `copy_sample_images.sh` - Script to copy sample images

## Quick Setup (Recommended)

1. **Automated Setup (Easiest)**
   ```bash
   # Run the PHP setup script
   php database_setup.php
   ```

2. **Manual Setup**
   ```bash
   # 1. Import main schema
   mysql -u your_username -p your_database < database_schema.sql

   # 2. Import photo management tables
   mysql -u your_username -p your_database < photo_management_tables.sql

   # 3. Import sample data
   mysql -u your_username -p your_database < sample_data.sql

   # 4. Import photo management data
   mysql -u your_username -p your_database < photo_management_data.sql

   # 5. Copy sample images
   chmod +x copy_sample_images.sh
   ./copy_sample_images.sh
   ```

## Database Structure

### Core Tables
- `users` - User accounts and profiles
- `posts` - Activity posts and announcements
- `post_images` - Images attached to posts
- `comments` - Comments on posts
- `likes` - User likes on posts
- `site_settings` - Site configuration
- `about_features` - About page features

### Photo Management Tables
- `photos` - Comprehensive photo management with metadata
- `photo_categories` - Photo categorization system
- `photo_category_relations` - Many-to-many photo-category relationships
- `photo_albums` - Photo collections/albums
- `photo_album_relations` - Photos in albums
- `photo_stats` - Photo analytics and statistics

## Default Login Credentials

**Admin Account:**
- Username: `admin`
- Password: `password`

**Kordinator Account:**
- Username: `kordinator1`
- Password: `password`

**Test User Accounts:**
- `john_doe` / `password`
- `jane_smith` / `password`
- `mike_johnson` / `password`

## Features Included

✅ User management with roles (admin, kordinator, user)
✅ Post management with approval system
✅ Multiple images per post support
✅ Comment and like system
✅ Site settings and configuration
✅ About page with features
✅ Comprehensive photo management
✅ Photo categorization and albums
✅ Photo analytics and statistics
✅ Gallery system
✅ Announcement system
✅ Contact message management

## File Structure After Setup

Make sure these directories exist and are writable:
```
public/
  uploads/           # Main uploads directory
    announcements/   # Announcement files
    profiles/        # User profile pictures
  css/               # Stylesheets
  js/                # JavaScript files
```

## Troubleshooting

1. **Permission Issues:** Make sure web server has write permissions on `public/uploads/`

2. **Image Not Showing:** Run the `copy_sample_images.sh` script to copy sample images

3. **Database Connection:** Check `src/config/db.php` for correct database credentials

4. **Missing Tables:** Run the setup scripts in the correct order

## Next Steps

1. Login as admin and configure site settings
2. Upload real images to replace sample images
3. Create user accounts for actual users
4. Start posting activities and events
5. Organize photos into categories and albums
