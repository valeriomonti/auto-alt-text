<?php

/**
 * Plugin Name:     Auto alt text
 * Plugin URI:      https://www.vmweb.it
 * Description:
 * Version:         1.0.0
 * Author:          Valerio Monti
 * Author URI:      https://www.vmweb.it
 * Text Domain:     auto-alt-text
 * Domain Path:     /languages
 * Requires PHP:    8.0
 * Requires WP:     5.5.0
 * Namespace:       AutoAltText
 */


use ValerioMonti\AutoAltText\App\Setup;

defined('ABSPATH') or die('Direct access not allowed');
define('AUTO_ALT_TEXT_ABSPATH', dirname(__FILE__));
define('AUTO_ALT_TEXT_URL', plugin_dir_url(__FILE__));
define('AUTO_ALT_TEXT_LANGUAGES_RELATIVE_PATH', dirname( plugin_basename( __FILE__ ) ) . '/languages/');

require AUTO_ALT_TEXT_ABSPATH . '/vendor/autoload.php';

Setup::register();
