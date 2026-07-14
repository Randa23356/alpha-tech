<?php
/**
 * Auto URL Replacer for Production Deployment
 * Automatically replaces hardcoded /informatics_a/ paths with dynamic BASE_URL
 */

// Only run URL replacement if we're not in local development
if (!isset($_SERVER['SERVER_NAME']) ||
    ($_SERVER['SERVER_NAME'] !== 'localhost' && $_SERVER['SERVER_NAME'] !== '127.0.0.1')) {

    // Start output buffering to capture and modify HTML output
    ob_start(function($buffer) {
        // Replace hardcoded paths with dynamic BASE_URL
        $replacements = [
            // HTML attributes
            '/href="\/informatics_a\//i' => 'href="' . BASE_URL . '/',
            '/action="\/informatics_a\//i' => 'action="' . BASE_URL . '/',
            '/src="\/informatics_a\/public\//i' => 'src="' . BASE_URL . '/public/',
            '/href="\/informatics_a\/public\//i' => 'href="' . BASE_URL . '/public/',

            // PHP header() redirects
            '/header\("Location: \/informatics_a\//i' => 'header("Location: ' . BASE_URL . '/',

            // CSS/JS links
            '/\/informatics_a\/public\//i' => BASE_URL . '/public/',
        ];

        // Apply all replacements
        foreach ($replacements as $pattern => $replacement) {
            $buffer = preg_replace($pattern, $replacement, $buffer);
        }

        return $buffer;
    });
}

// Note: This approach automatically handles URL replacement for all PHP files
// No need to manually update each file - the output buffer does it automatically!
