<?php
// Create a simple default avatar image
header('Content-Type: image/svg+xml');
header('Cache-Control: max-age=86400'); // Cache for 1 day

$initial = isset($_GET['initial']) ? strtoupper(substr($_GET['initial'], 0, 1)) : 'U';
$bgColor = isset($_GET['color']) ? $_GET['color'] : '#1e3a8a';
$textColor = '#ffffff';

echo '<?xml version="1.0" encoding="UTF-8"?>
<svg width="100" height="100" viewBox="0 0 100 100" xmlns="http://www.w3.org/2000/svg">
  <circle cx="50" cy="50" r="50" fill="' . $bgColor . '"/>
  <text x="50" y="65" font-family="Arial, sans-serif" font-size="40" font-weight="bold" text-anchor="middle" fill="' . $textColor . '">' . htmlspecialchars($initial) . '</text>
</svg>';
?>
