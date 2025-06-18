<?php

/**
 * Plugin Name:     Auto Alt Text
 * Description:     This plugin allows you to automatically generate an Alt Text for images uploaded into the media library via AI..
 * Version:         2.4.1
 * Author:          Valerio Monti
 * Author URI:      https://www.vmweb.it
 * Text Domain:     auto-alt-text
 * Domain Path:     /languages
 * License:         GPL v3
 * Requires PHP:    7.4
 * Requires WP:     6.0
 * Namespace:       AATXT
 */

use AATXT\App\Setup;

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}
define('AATXT_FILE_ABSPATH', __FILE__);
define('AATXT_ABSPATH', dirname(__FILE__));
define('AATXT_URL', plugin_dir_url(__FILE__));
define('AATXT_LANGUAGES_RELATIVE_PATH', dirname( plugin_basename( __FILE__ ) ) . '/languages/');

require AATXT_ABSPATH . '/vendor/autoload.php';

Setup::register();
