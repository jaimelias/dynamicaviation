<?php

/**
 * The plugin bootstrap file
 *
 * This file is read by WordPress to generate the plugin information in the plugin
 * admin area. This file also includes all of the dependencies used by the plugin,
 * registers the activation and deactivation functions, and defines a function
 * that starts the plugin.
 *
 * @link              https://jaimelias.com
 * @since             1.0.0
 * @package           Dynamic_Aviation
 *
 * @wordpress-plugin
 * Plugin Name:       Dynamic Aviation
 * Plugin URI:        https://www.jaimelias.com
 * Description:       This is a short description of what the plugin does. It's displayed in the WordPress admin area.
 * Version:           1.0.0
 * Author:            Jaimelías
 * Author URI:        https://jaimelias.com
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       dynamicaviation
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-dynamicaviation-activator.php
 */
function activate_dynamicaviation() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-dynamicaviation-activator.php';
	Dynamic_Aviation_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-dynamicaviation-deactivator.php
 */
function deactivate_dynamicaviation() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-dynamicaviation-deactivator.php';
	Dynamic_Aviation_Deactivator::deactivate();
}

register_activation_hook( __FILE__, 'activate_dynamicaviation' );
register_deactivation_hook( __FILE__, 'deactivate_dynamicaviation' );

if(!defined('DY_CORE_FUNCTIONS'))
{
	require plugin_dir_path( __FILE__ ) . 'submodules/dy-core/loader.php';
}

// admin and public
require plugin_dir_path( __FILE__ ) . 'includes/class-dynamicaviation.php';


function run_dynamicaviation() {

	define('DY_AVIATION_IMAGE_PATHNAME', 'cacheimg');
	
	$plugin = new Dynamic_Aviation();
	$plugin->run();

}
run_dynamicaviation();


function aviation_field($name, $this_id = null)
{
    static $cache = [];

    if (!is_int($this_id) || intval($this_id) <= 0) {

        global $post;

        if (isset($post) && $post instanceof WP_Post && $post->post_type === 'aircrafts') {
            $this_id = $post->ID;
        }
    }

    if (!$this_id) {
        return '';
    }

    $cache_key = $this_id . ':' . $name; // delimiter avoids collisions

    if (array_key_exists($cache_key, $cache)) {
        return $cache[$cache_key];
    }

    $value = get_post_meta($this_id, $name, true);

    if (is_array($value)) {
        return $cache[$cache_key] = $value; // don't force-cast arrays to string
    }

    return $cache[$cache_key] = (string) $value;
}