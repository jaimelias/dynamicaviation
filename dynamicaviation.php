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
	
	
	if(! function_exists('var_error_log'))
	{
		function var_error_log( $object=null ){
			ob_start();
			var_dump( $object );
			$contents = ob_get_contents();
			ob_end_clean();
			return $contents;
		}
	}
	
	function write_log ( $log )  {
		
		$output = '';
		$request_uri = sanitize_text_field($_SERVER['REQUEST_URI']);
		$user_agent = sanitize_text_field($_SERVER['HTTP_USER_AGENT']);
		
		if ( is_array( $log ) || is_object( $log ) ) {

			$output = print_r(var_error_log($log), true);
			$output .= ' '.$request_uri;  
			$output .= ' '.$user_agent;  
			error_log( $output );
		}
		else
		{
			$output = $log;
			$output .= ' '.$request_uri;  
			$output .= ' '.$user_agent;
			error_log( $log );
		}
	}
}


function aviation_field($name, $this_id = null)
{
	if($this_id == null)
	{		
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



if(!function_exists('get_languages'))
{
	function get_languages()
	{
		global $polylang;
		$output = array();
		$which_var = 'wp_core_get_languages';
		global $$which_var;

		if(isset($$which_var))
		{
			$output = $$which_var;
		}
		else
		{
			if(isset($polylang))
			{
				$languages = PLL()->model->get_languages_list();

				for($x = 0; $x < count($languages); $x++)
				{
					foreach($languages[$x] as $key => $value)
					{
						if($key == 'slug')
						{
							$output[] = $value;
						}
					}	
				}
			}

			if(count($output) === 0)
			{
				$locale_str = get_locale();

				if(strlen($locale_str) === 5)
				{
					$output[] = substr($locale_str, 0, -3);
				}
				else if(strlen($locale_str) === 2)
				{
					$output[] = $locale_str;
				}
			}

			$GLOBALS[$which_var] = $output;
		}


		return $output;
	}	
}

if(!function_exists('current_language'))
{
	function current_language()
	{
		global $polylang;
		$output = '';
		$which_var = 'wp_core_current_language';
		global $$which_var;

		if($$which_var)
		{
			$output = $$which_var;
		}
		else
		{
			if(isset($polylang))
			{
				$output = pll_current_language();
			}
			else
			{
				$locale = get_locale();
				$locale_strlen = strlen($locale);

				if($locale_strlen === 5)
				{
					$output = substr($locale, 0, -3);
				}
				if($locale_strlen === 2)
				{
					$output = $locale;
				}			
			}

			$GLOBALS[$which_var] = $output;
		}


		return $output;
	}
}
