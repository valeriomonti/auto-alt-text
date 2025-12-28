<?php
/**
 * PHPUnit Bootstrap File
 *
 * Loads WordPress and Composer autoloader for testing.
 * For pure unit tests that don't need WordPress, it skips WordPress loading.
 */

declare(strict_types=1);

// Suppress deprecation warnings from PHP-DI BEFORE loading autoloader
// PHP-DI 6.x has deprecation warnings on PHP 8.4+ but is compatible with PHP 7.4+
error_reporting(E_ALL & ~E_DEPRECATED);

// Load Composer autoloader
require_once dirname(__DIR__) . '/vendor/autoload.php';

// Restore full error reporting after autoload (except deprecations from dependencies)
error_reporting(E_ALL & ~E_DEPRECATED);

// Define plugin directory
define('AATXT_PLUGIN_DIR', dirname(__DIR__));

/**
 * Check if we should skip WordPress loading
 *
 * By default, skip WordPress unless explicitly running integration tests
 * or the LOAD_WORDPRESS environment variable is set.
 *
 * @return bool True if we should skip WordPress loading
 */
function _should_skip_wordpress(): bool
{
    global $argv;

    // Explicit environment variable to load WordPress
    if (getenv('LOAD_WORDPRESS') === 'true') {
        return false;
    }

    // Check if we're explicitly running integration tests
    foreach ($argv as $arg) {
        if (strpos($arg, 'tests/Integration') !== false) {
            return false;
        }
        if ($arg === 'Integration') {
            return false;
        }
    }

    // Default: skip WordPress for unit tests
    return true;
}

/**
 * Load WordPress for tests
 *
 * WordPress can be loaded in different ways:
 * 1. From WP_TESTS_DIR environment variable (WordPress test suite)
 * 2. From local WordPress installation
 * 3. Mock WordPress functions for unit tests (without full WordPress)
 */
function _load_wordpress_for_tests(): void
{
    // Skip WordPress loading unless explicitly needed
    if (_should_skip_wordpress()) {
        echo "ℹ WordPress loading skipped - Running unit tests only\n";
        return;
    }

    // Try to load from local WordPress installation
    // Plugin path: /wp-content/plugins/auto-alt-text/
    // Need to go up 4 levels to reach WordPress root
    $wpLoadPath = dirname(__DIR__, 4) . '/wp-load.php';

    if (file_exists($wpLoadPath)) {
        // Prevent WordPress from sending headers during tests
        define('DOING_AJAX', true);
        define('WP_USE_THEMES', false);

        // Suppress output during WordPress loading
        ob_start();
        try {
            require_once $wpLoadPath;
            ob_end_clean();
            echo "✓ WordPress loaded from: {$wpLoadPath}\n";
        } catch (\Throwable $e) {
            ob_end_clean();
            echo "⚠ WordPress loading failed: {$e->getMessage()}\n";
        }
        return;
    }

    // If WordPress test suite is available, load it
    if (getenv('WP_TESTS_DIR')) {
        $testsDir = getenv('WP_TESTS_DIR');
        if (file_exists($testsDir . '/includes/functions.php')) {
            require_once $testsDir . '/includes/functions.php';
            require_once $testsDir . '/includes/bootstrap.php';
            echo "✓ WordPress test suite loaded from: {$testsDir}\n";
            return;
        }
    }

    echo "⚠ WordPress not loaded - Some integration tests may fail\n";
    echo "  For full integration tests, ensure WordPress is accessible\n";
}

_load_wordpress_for_tests();

// Display environment info
echo "\n";
echo "=============================================================\n";
echo " PHPUnit Test Environment\n";
echo "=============================================================\n";
echo " PHP Version:    " . PHP_VERSION . "\n";
echo " PHPUnit:        " . PHPUnit\Runner\Version::id() . "\n";
echo " Plugin Dir:     " . AATXT_PLUGIN_DIR . "\n";
echo " WordPress:      " . (function_exists('get_bloginfo') ? 'Loaded (' . get_bloginfo('version') . ')' : 'Not loaded') . "\n";
echo "=============================================================\n";
echo "\n";
