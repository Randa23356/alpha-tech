<?php
/**
 * Auto URL Replacer for Production Deployment
 * Automatically replaces hardcoded /informatics_a/ paths in HTML output
 */

// Load URL configuration for all environments
require_once __DIR__ . '/urls.php';

// Only run URL replacement if we're not in local development
if (!isset($_SERVER['SERVER_NAME']) ||
    ($_SERVER['SERVER_NAME'] !== 'localhost' && $_SERVER['SERVER_NAME'] !== '127.0.0.1')) {

    // Start output buffering to capture and modify HTML output
    ob_start(function($buffer) {
        // Simple, effective replacements for production
        $replacements = [
            // HTML href attributes
            '/href="\/informatics_a\//i' => 'href="/',

            // HTML src attributes (CSS, JS, images)
            '/src="\/informatics_a\/public\//i' => 'src="/public/',

            // HTML action attributes (forms)
            '/action="\/informatics_a\//i' => 'action="/',

            // CSS @import and url() references
            '/\/informatics_a\/public\//i' => '/public/',
        ];

        // Apply all replacements
        foreach ($replacements as $pattern => $replacement) {
            $buffer = preg_replace($pattern, $replacement, $buffer);
        }

        return $buffer;
    });
}
