<?php

/**
 * The plugin bootstrap file
 *
 * This file is read by WordPress to generate the plugin information in the plugin
 * admin area. This file also includes all of the dependencies used by the plugin,
 * registers the activation and deactivation functions, and defines a function
 * that starts the plugin.
 *
 * @link https://jaimelias.com
 * @since 1.0.0
 * @package Dynamic_Aviation
 *
 * @wordpress-plugin
 * Plugin Name: Dynamic Aviation
 * Plugin URI: https://www.jaimelias.com
 * Description: This is a short description of what the plugin does. It's displayed in the WordPress admin area.
 * Version: 1.0.60
 * Author: Jaimelías
 * Author URI: https://jaimelias.com
 * License: GPL-2.0+
 * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain: dynamicaviation
 * Domain Path: /languages
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) exit;

if(defined('DYNAMICAVIATION_VERSION')) exit;

define('DYNAMICAVIATION_VERSION', '1.0.60');
define('DY_AVIATION_IMAGE_PATHNAME', 'cacheimg');

add_action('plugins_loaded', static function (): void {
    require plugin_dir_path( __FILE__ ) . 'includes/class-dynamicaviation.php';

    new Dynamic_Aviation_Core(__FILE__);
}, 2);