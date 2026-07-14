<?php
ini_set("display_errors", 1);
ini_set("display_startup_errors", 1);
error_reporting(E_ALL);

// Dynamic theme CSS generator
// This file generates CSS variables based on database settings

require_once __DIR__ . "/../../src/config/db.php";

// Get theme colors from database
$primary_color = "#1e3a8a"; // default
$secondary_color = "#1e40af"; // default
$accent_color = "#ec4899"; // default
$success_color = "#10b981"; // default
$warning_color = "#f59e0b"; // default
$danger_color = "#ef4444"; // default

try {
    $stmt = $pdo->query(
        "SELECT setting_key, setting_value FROM site_settings WHERE setting_key IN ('primary_color', 'secondary_color', 'accent_color', 'success_color', 'warning_color', 'danger_color')",
    );
    $settings = [];
    foreach ($stmt->fetchAll() as $row) {
        $settings[$row["setting_key"]] = $row["setting_value"];
    }

    $primary_color = $settings["primary_color"] ?? $primary_color;
    $secondary_color = $settings["secondary_color"] ?? $secondary_color;
    $accent_color = $settings["accent_color"] ?? $accent_color;
    $success_color = $settings["success_color"] ?? $success_color;
    $warning_color = $settings["warning_color"] ?? $warning_color;
    $danger_color = $settings["danger_color"] ?? $danger_color;
} catch (Exception $e) {
    // Use default colors if database fails
}

// Convert hex to RGB for opacity calculations
function hexToRgb($hex)
{
    $hex = ltrim($hex, "#");
    return [
        "r" => hexdec(substr($hex, 0, 2)),
        "g" => hexdec(substr($hex, 2, 2)),
        "b" => hexdec(substr($hex, 4, 2)),
    ];
}

$primary_rgb = hexToRgb($primary_color);
$secondary_rgb = hexToRgb($secondary_color);

// Set content type to CSS
header("Content-Type: text/css");
?>

<style>
    /* Dynamic Theme Variables */
:root {
    --primary-color: <?= $primary_color ?>;
    --secondary-color: <?= $secondary_color ?>;
    --accent-color: <?= $accent_color ?>;
    --success-color: <?= $success_color ?>;
    --warning-color: <?= $warning_color ?>;
    --danger-color: <?= $danger_color ?>;

    /* Primary color variations */
    --primary-50: rgba(<?= $primary_rgb["r"] ?>, <?= $primary_rgb[
    "g"
] ?>, <?= $primary_rgb["b"] ?>, 0.05);
    --primary-100: rgba(<?= $primary_rgb["r"] ?>, <?= $primary_rgb[
    "g"
] ?>, <?= $primary_rgb["b"] ?>, 0.1);
    --primary-200: rgba(<?= $primary_rgb["r"] ?>, <?= $primary_rgb[
    "g"
] ?>, <?= $primary_rgb["b"] ?>, 0.2);
    --primary-300: rgba(<?= $primary_rgb["r"] ?>, <?= $primary_rgb[
    "g"
] ?>, <?= $primary_rgb["b"] ?>, 0.3);
    --primary-400: rgba(<?= $primary_rgb["r"] ?>, <?= $primary_rgb[
    "g"
] ?>, <?= $primary_rgb["b"] ?>, 0.4);
    --primary-500: rgba(<?= $primary_rgb["r"] ?>, <?= $primary_rgb[
    "g"
] ?>, <?= $primary_rgb["b"] ?>, 0.5);
    --primary-600: rgba(<?= $primary_rgb["r"] ?>, <?= $primary_rgb[
    "g"
] ?>, <?= $primary_rgb["b"] ?>, 0.6);
    --primary-700: rgba(<?= $primary_rgb["r"] ?>, <?= $primary_rgb[
    "g"
] ?>, <?= $primary_rgb["b"] ?>, 0.7);
    --primary-800: rgba(<?= $primary_rgb["r"] ?>, <?= $primary_rgb[
    "g"
] ?>, <?= $primary_rgb["b"] ?>, 0.8);
    --primary-900: rgba(<?= $primary_rgb["r"] ?>, <?= $primary_rgb[
    "g"
] ?>, <?= $primary_rgb["b"] ?>, 0.9);

    /* Secondary color variations */
    --secondary-50: rgba(<?= $secondary_rgb["r"] ?>, <?= $secondary_rgb[
    "g"
] ?>, <?= $secondary_rgb["b"] ?>, 0.05);
    --secondary-100: rgba(<?= $secondary_rgb["r"] ?>, <?= $secondary_rgb[
    "g"
] ?>, <?= $secondary_rgb["b"] ?>, 0.1);
    --secondary-200: rgba(<?= $secondary_rgb["r"] ?>, <?= $secondary_rgb[
    "g"
] ?>, <?= $secondary_rgb["b"] ?>, 0.2);
    --secondary-300: rgba(<?= $secondary_rgb["r"] ?>, <?= $secondary_rgb[
    "g"
] ?>, <?= $secondary_rgb["b"] ?>, 0.3);
    --secondary-400: rgba(<?= $secondary_rgb["r"] ?>, <?= $secondary_rgb[
    "g"
] ?>, <?= $secondary_rgb["b"] ?>, 0.4);
    --secondary-500: rgba(<?= $secondary_rgb["r"] ?>, <?= $secondary_rgb[
    "g"
] ?>, <?= $secondary_rgb["b"] ?>, 0.5);
    --secondary-600: rgba(<?= $secondary_rgb["r"] ?>, <?= $secondary_rgb[
    "g"
] ?>, <?= $secondary_rgb["b"] ?>, 0.6);
    --secondary-700: rgba(<?= $secondary_rgb["r"] ?>, <?= $secondary_rgb[
    "g"
] ?>, <?= $secondary_rgb["b"] ?>, 0.7);
    --secondary-800: rgba(<?= $secondary_rgb["r"] ?>, <?= $secondary_rgb[
    "g"
] ?>, <?= $secondary_rgb["b"] ?>, 0.8);
    --secondary-900: rgba(<?= $secondary_rgb["r"] ?>, <?= $secondary_rgb[
    "g"
] ?>, <?= $secondary_rgb["b"] ?>, 0.9);
}

/* Apply theme colors to existing Tailwind classes */
.bg-primary {
    background-color: var(--primary-color);
}

.bg-secondary {
    background-color: var(--secondary-color);
}

.text-primary {
    color: var(--primary-color);
}

.text-secondary {
    color: var(--secondary-color);
}

.border-primary {
    border-color: var(--primary-color);
}

.border-secondary {
    border-color: var(--secondary-color);
}

/* Gradient backgrounds using theme colors */
.bg-gradient-primary {
    background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
}

.bg-gradient-primary-reverse {
    background: linear-gradient(135deg, var(--secondary-color) 0%, var(--primary-color) 100%);
}

/* Theme-based utility classes */
.theme-bg-primary {
    background-color: var(--primary-color);
}

.theme-bg-secondary {
    background-color: var(--secondary-color);
}

.theme-text-primary {
    color: var(--primary-color);
}

.theme-text-secondary {
    color: var(--secondary-color);
}

.theme-border-primary {
    border-color: var(--primary-color);
}

.theme-border-secondary {
    border-color: var(--secondary-color);
}

/* Gradient text effect */
.gradient-text {
    background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
    -webkit-background-clip: text;
    background-clip: text;
    -webkit-text-fill-color: transparent;
    color: transparent;
}

/* Hover effects with theme colors */
.theme-hover-primary:hover {
    background-color: var(--primary-color);
    color: white;
}

.theme-hover-secondary:hover {
    background-color: var(--secondary-color);
    color: white;
}

/* Focus effects with theme colors */
.theme-focus:focus {
    outline: 2px solid var(--primary-color);
    outline-offset: 2px;
}

/* Button styles with theme colors */
.btn-primary {
    background-color: var(--primary-color);
    color: white;
    border: 1px solid var(--primary-color);
    transition: all 0.3s ease;
}

.btn-primary:hover {
    background-color: var(--secondary-color);
    border-color: var(--secondary-color);
}

.btn-secondary {
    background-color: var(--secondary-color);
    color: white;
    border: 1px solid var(--secondary-color);
    transition: all 0.3s ease;
}

.btn-secondary:hover {
    background-color: var(--primary-color);
    border-color: var(--primary-color);
}

.btn-outline-primary {
    background-color: transparent;
    color: var(--primary-color);
    border: 1px solid var(--primary-color);
    transition: all 0.3s ease;
}

.btn-outline-primary:hover {
    background-color: var(--primary-color);
    color: white;
}

.btn-outline-secondary {
    background-color: transparent;
    color: var(--secondary-color);
    border: 1px solid var(--secondary-color);
    transition: all 0.3s ease;
}

.btn-outline-secondary:hover {
    background-color: var(--secondary-color);
    color: white;
}

/* Card hover effects */
.card-hover:hover {
    transform: translateY(-2px);
    box-shadow: 0 10px 25px rgba(<?= $primary_rgb["r"] ?>, <?= $primary_rgb[
    "g"
] ?>, <?= $primary_rgb["b"] ?>, 0.15);
}

/* Animation keyframes */
@keyframes theme-pulse {
    0%, 100% {
        opacity: 1;
    }
    50% {
        opacity: 0.7;
    }
}

@keyframes theme-bounce {
    0%, 100% {
        transform: translateY(-25%);
        animation-timing-function: cubic-bezier(0.8, 0, 1, 1);
    }
    50% {
        transform: none;
        animation-timing-function: cubic-bezier(0, 0, 0.2, 1);
    }
}

.theme-pulse {
    animation: theme-pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
}

.theme-bounce {
    animation: theme-bounce 1s infinite;
}

/* Responsive adjustments */
@media (max-width: 768px) {
    .gradient-text {
        -webkit-text-fill-color: var(--primary-color);
        color: var(--primary-color);
    }
}

/* Dark mode support (optional) */
@media (prefers-color-scheme: dark) {
    :root {
        --bg-color: #1a1a1a;
        --text-color: #ffffff;
    }

    body {
        background-color: var(--bg-color);
        color: var(--text-color);
    }
}

</style>