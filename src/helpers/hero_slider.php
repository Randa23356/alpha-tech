<?php
// informatics_a/src/helpers/hero_slider.php

/**
 * Get all active hero slides ordered by slide_order
 */
function get_hero_slides($pdo) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM hero_slides WHERE is_active = 1 ORDER BY slide_order ASC, id ASC");
        $stmt->execute();
        $slides = $stmt->fetchAll(PDO::FETCH_ASSOC);
        error_log('Fetched ' . count($slides) . ' active slides from database');
        return $slides;
    } catch (Exception $e) {
        error_log('Error fetching hero slides: ' . $e->getMessage());
        return [];
    }
}

/**
 * Check if color columns exist in hero_slides table
 */
function check_hero_slide_columns($pdo) {
    try {
        // Check if title_color column exists
        $stmt = $pdo->prepare("SHOW COLUMNS FROM hero_slides LIKE 'title_color'");
        $stmt->execute();
        return $stmt->rowCount() > 0;
    } catch (Exception $e) {
        // If there's an error checking columns, assume they don't exist
        return false;
    }
}

/**
 * Get hero slide by ID
 */
function get_hero_slide_by_id($pdo, $id) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM hero_slides WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        error_log('Error fetching hero slide: ' . $e->getMessage());
        return null;
    }
}

/**
 * Add new hero slide
 */
function add_hero_slide($pdo, $data) {
    try {
        // Check if color columns exist in the database
        $columns_exist = check_hero_slide_columns($pdo);

        error_log("Adding slide with is_active: " . ($data['is_active'] ?? 'not set') . ", columns_exist: " . ($columns_exist ? 'true' : 'false'));

        if ($columns_exist) {
            // Insert with color fields if they exist
            $stmt = $pdo->prepare("INSERT INTO hero_slides (title, subtitle, description, button_text, button_url, background_image, slide_order, is_active, autoplay_duration, title_color, subtitle_color, description_color, icon_color, icon_bg_color) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $result = $stmt->execute([
                $data['title'],
                $data['subtitle'] ?? '',
                $data['description'] ?? '',
                $data['button_text'] ?? 'Learn More',
                $data['button_url'] ?? '#',
                $data['background_image'],
                $data['slide_order'] ?? 0,
                $data['is_active'] ?? 1,
                $data['autoplay_duration'] ?? 5000,
                $data['title_color'] ?? '#ffffff',
                $data['subtitle_color'] ?? '#f3f4f6',
                $data['description_color'] ?? '#d1d5db',
                $data['icon_color'] ?? '#ffffff',
                $data['icon_bg_color'] ?? 'rgba(255,255,255,0.1)'
            ]);
            error_log("Add slide result: " . ($result ? 'success' : 'failed') . ", insert_id: " . $pdo->lastInsertId());
            return $result;
        } else {
            // Insert without color fields if they don't exist
            $stmt = $pdo->prepare("INSERT INTO hero_slides (title, subtitle, description, button_text, button_url, background_image, slide_order, is_active, autoplay_duration) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $result = $stmt->execute([
                $data['title'],
                $data['subtitle'] ?? '',
                $data['description'] ?? '',
                $data['button_text'] ?? 'Learn More',
                $data['button_url'] ?? '#',
                $data['background_image'],
                $data['slide_order'] ?? 0,
                $data['is_active'] ?? 1,
                $data['autoplay_duration'] ?? 5000
            ]);
            error_log("Add slide (no colors) result: " . ($result ? 'success' : 'failed') . ", insert_id: " . $pdo->lastInsertId());
            return $result;
        }
    } catch (Exception $e) {
        error_log('Error adding hero slide: ' . $e->getMessage());
        return false;
    }
}

/**
 * Update hero slide
 */
function update_hero_slide($pdo, $id, $data) {
    try {
        // Check if color columns exist in the database
        $columns_exist = check_hero_slide_columns($pdo);

        error_log("Updating slide {$id} with columns_exist: " . ($columns_exist ? 'true' : 'false'));

        if ($columns_exist) {
            // Update with color fields if they exist
            $stmt = $pdo->prepare("UPDATE hero_slides SET title = ?, subtitle = ?, description = ?, button_text = ?, button_url = ?, background_image = ?, slide_order = ?, is_active = ?, autoplay_duration = ?, title_color = ?, subtitle_color = ?, description_color = ?, icon_color = ?, icon_bg_color = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?");
            $result = $stmt->execute([
                $data['title'],
                $data['subtitle'] ?? '',
                $data['description'] ?? '',
                $data['button_text'] ?? 'Learn More',
                $data['button_url'] ?? '#',
                $data['background_image'],
                $data['slide_order'] ?? 0,
                $data['is_active'] ?? 1,
                $data['autoplay_duration'] ?? 5000,
                $data['title_color'] ?? '#ffffff',
                $data['subtitle_color'] ?? '#f3f4f6',
                $data['description_color'] ?? '#d1d5db',
                $data['icon_color'] ?? '#ffffff',
                $data['icon_bg_color'] ?? 'rgba(255,255,255,0.1)',
                $id
            ]);
            error_log("Update with colors result: " . ($result ? 'success' : 'failed') . ", affected rows: " . $stmt->rowCount());
            return $result;
        } else {
            // Update without color fields if they don't exist
            $stmt = $pdo->prepare("UPDATE hero_slides SET title = ?, subtitle = ?, description = ?, button_text = ?, button_url = ?, background_image = ?, slide_order = ?, is_active = ?, autoplay_duration = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?");
            $result = $stmt->execute([
                $data['title'],
                $data['subtitle'] ?? '',
                $data['description'] ?? '',
                $data['button_text'] ?? 'Learn More',
                $data['button_url'] ?? '#',
                $data['background_image'],
                $data['slide_order'] ?? 0,
                $data['is_active'] ?? 1,
                $data['autoplay_duration'] ?? 5000,
                $id
            ]);
            error_log("Update without colors result: " . ($result ? 'success' : 'failed') . ", affected rows: " . $stmt->rowCount());
            return $result;
        }
    } catch (Exception $e) {
        error_log('Error updating hero slide: ' . $e->getMessage());
        return false;
    }
}

/**
 * Delete hero slide
 */
function delete_hero_slide($pdo, $id) {
    try {
        // First, get the slide data to find the background image
        $slide = get_hero_slide_by_id($pdo, $id);

        // Delete the background image file if it exists
        if ($slide && !empty($slide['background_image'])) {
            $image_path = __DIR__ . '/../../public/uploads/hero/' . $slide['background_image'];
            if (file_exists($image_path)) {
                unlink($image_path);
                error_log('Deleted hero slide background image: ' . $slide['background_image']);
            }
        }

        // Delete from database
        $stmt = $pdo->prepare("DELETE FROM hero_slides WHERE id = ?");
        return $stmt->execute([$id]);
    } catch (Exception $e) {
        error_log('Error deleting hero slide: ' . $e->getMessage());
        return false;
    }
}

/**
 * Handle file upload for hero slide background
 */
function upload_hero_background($file) {
    // Use relative path for hosting compatibility
    $upload_dir = __DIR__ . '/../../public/uploads/hero/';

    // Ensure directory exists and is writable
    if (!file_exists($upload_dir)) {
        if (!mkdir($upload_dir, 0755, true)) {
            return ['error' => 'Cannot create directory. Check parent directory permissions.'];
        }
    }

    // Double-check it's writable
    if (!is_writable($upload_dir)) {
        return ['error' => 'Directory exists but is not writable'];
    }

    $allowed_types = ['image/jpeg', 'image/jpg', 'image/png', 'image/webp'];
    $max_size = 2 * 1024 * 1024; // 2MB limit

    // Validate file
    if (!isset($file['type']) || !in_array($file['type'], $allowed_types)) {
        return ['error' => 'Only JPG, PNG, and WebP images are allowed'];
    }

    if (!isset($file['size']) || $file['size'] > $max_size) {
        return ['error' => 'File size must be less than 2MB'];
    }

    if (!isset($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
        return ['error' => 'File upload failed - no valid temporary file'];
    }

    $file_extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $new_filename = 'hero_' . uniqid() . '.' . $file_extension;
    $upload_path = $upload_dir . $new_filename;

    if (move_uploaded_file($file['tmp_name'], $upload_path)) {
        return ['success' => true, 'filename' => $new_filename];
    } else {
        return ['error' => 'Failed to save file. Check directory permissions and disk space.'];
    }
}

/**
 * Convert PHP size format to bytes
 */
function return_bytes($val) {
    $val = trim($val);
    $last = strtolower($val[strlen($val)-1]);
    $val = (int) $val;

    switch($last) {
        case 'g': $val *= 1024 * 1024 * 1024; break;
        case 'm': $val *= 1024 * 1024; break;
        case 'k': $val *= 1024; break;
    }

    return $val;
}

/**
 * Get hero slider settings from site_settings
 */
function get_hero_slider_settings($pdo) {
    try {
        $settings = [];
        $stmt = $pdo->query("SELECT setting_key, setting_value FROM site_settings WHERE setting_key IN ('hero_autoplay', 'hero_transition', 'hero_show_arrows', 'hero_show_dots')");
        while ($row = $stmt->fetch()) {
            $settings[$row['setting_key']] = $row['setting_value'];
        }
        return $settings;
    } catch (Exception $e) {
        error_log('Error fetching hero slider settings: ' . $e->getMessage());
        return [];
    }
}
?>
