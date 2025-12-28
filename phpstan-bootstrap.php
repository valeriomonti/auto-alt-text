<?php
/**
 * PHPStan bootstrap file
 *
 * This file defines constants that are normally defined in the main plugin file
 * to allow PHPStan to analyze the code without errors.
 */

// Define plugin constants for PHPStan
if (!defined('AATXT_FILE_ABSPATH')) {
    define('AATXT_FILE_ABSPATH', __DIR__ . '/auto-alt-text.php');
}

if (!defined('AATXT_ABSPATH')) {
    define('AATXT_ABSPATH', __DIR__);
}

if (!defined('AATXT_URL')) {
    define('AATXT_URL', 'https://example.com/wp-content/plugins/auto-alt-text/');
}

if (!defined('AATXT_LANGUAGES_RELATIVE_PATH')) {
    define('AATXT_LANGUAGES_RELATIVE_PATH', 'auto-alt-text/languages/');
}
