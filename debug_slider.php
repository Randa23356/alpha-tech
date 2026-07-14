<?php
require_once __DIR__ . '/src/config/db.php';
require_once __DIR__ . '/src/helpers/hero_slider.php';

$hero_slides = get_hero_slides($pdo);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Slider Debug</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .debug-info { background: #f5f5f5; padding: 10px; margin: 10px 0; border-radius: 5px; }
        .slide { border: 1px solid #ccc; margin: 10px 0; padding: 10px; }
    </style>
</head>
<body>
    <h1>Hero Slider Debug</h1>

    <div class="debug-info">
        <h3>Database Slides:</h3>
        <pre><?php print_r($hero_slides); ?></pre>
    </div>

    <div class="debug-info">
        <h3>Slide Count: <?= count($hero_slides) ?></h3>
        <?php foreach ($hero_slides as $index => $slide): ?>
            <div class="slide">
                <h4>Slide <?= $index + 1 ?>: <?= htmlspecialchars($slide['title']) ?></h4>
                <p>Background: <?= htmlspecialchars($slide['background_image'] ?? 'None') ?></p>
                <p>Active: <?= $slide['is_active'] ? 'Yes' : 'No' ?></p>
                <?php if (!empty($slide['background_image'])): ?>
                    <p>Image URL: <?= upload_url('hero/' . $slide['background_image']) ?></p>
                    <img src="<?= upload_url('hero/' . $slide['background_image']) ?>" style="max-width: 200px; height: auto;" alt="Slide Image">
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    </div>

    <div class="debug-info">
        <h3>Upload URL Function Test:</h3>
        <?php
        $test_image = 'hero_68eb4180d6729.jpg';
        $test_url = upload_url('hero/' . $test_image);
        echo "<p>Test URL: $test_url</p>";
        echo "<img src='$test_url' style='max-width: 200px; height: auto;' alt='Test Image'>";
        ?>
    </div>
</body>
</html>
