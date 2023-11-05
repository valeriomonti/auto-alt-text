<?php

/**
 * Plugin Name:     Auto Alt Text
 * Description:     This WordPress plugin allows you to automatically generate an Alt Text for images uploaded to the media library using Azure or OpenaAi AI APIs.
 * Version:         1.0.0
 * Author:          Valerio Monti
 * Author URI:      https://www.vmweb.it
 * Text Domain:     auto-alt-text
 * Domain Path:     /languages
 * License:         GPL v3
 * Requires PHP:    7.4
 * Requires WP:     5.5.0
 * Namespace:       AutoAltText
 */

use ValerioMonti\AutoAltText\App\Setup;
use ValerioMonti\AutoAltText\Config\Constants;

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

define('AUTO_ALT_TEXT_ABSPATH', dirname(__FILE__));
define('AUTO_ALT_TEXT_URL', plugin_dir_url(__FILE__));
define('AUTO_ALT_TEXT_LANGUAGES_RELATIVE_PATH', dirname( plugin_basename( __FILE__ ) ) . '/languages/');

require AUTO_ALT_TEXT_ABSPATH . '/vendor/autoload.php';

Setup::register();
