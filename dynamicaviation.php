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

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require plugin_dir_path( __FILE__ ) . 'includes/class-dynamicaviation.php';

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    1.0.0
 */
function run_dynamicaviation() {

	$plugin = new Dynamic_Aviation();
	$plugin->run();

}
run_dynamicaviation();


if ( ! function_exists('write_log')) {
	function write_log ( $log )  {
		
		if ( is_array( $log ) || is_object( $log ) ) {

			$log .= ' '.sanitize_text_field($_SERVER['REQUEST_URI']);  
			$log .= ' '.sanitize_text_field($_SERVER['HTTP_USER_AGENT']);  
			error_log( print_r( $log, true ) );
		}
		else
		{
			$log .= ' '.sanitize_text_field($_SERVER['REQUEST_URI']);  
			$log .= ' '.sanitize_text_field($_SERVER['HTTP_USER_AGENT']);  
			error_log( $log );
		}
	}
}


function aviation_field($name, $this_id = null)
{
	if($this_id == null)
	{		
		global $polylang;		
		global $post;
		
		if(isset($post))
		{
			$this_id = $post->ID;
		}
	}
	
	$which_var = $name.'_'.$this_id;
	global $$which_var; 
	
	if(isset($$which_var))
	{
		return $$which_var;
	}
	else
	{
		$package_field = get_post_meta($this_id, $name, true);
		$GLOBALS[$which_var] = $package_field;
		return $package_field;
	}	
}