<?php

/**
 * The admin-specific functionality of the plugin.
 *
 * @link       http://jaimelias.com
 * @since      1.0.0
 *
 * @package    Dynamic_Aviation
 * @subpackage Dynamic_Aviation/admin
 */

/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    Dynamic_Aviation
 * @subpackage Dynamic_Aviation/admin
 * @author     Jaimelías <jaimelias@about.me>
 */
class Dynamic_Aviation_Admin {

	/**
	 * The ID of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $plugin_name    The ID of this plugin.
	 */
	private $plugin_name;

	/**
	 * The version of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $version    The current version of this plugin.
	 */
	private $version;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 * @param      string    $plugin_name       The name of this plugin.
	 * @param      string    $version    The version of this plugin.
	 */
	public function __construct( $plugin_name, $version ) {

		$this->plugin_name = $plugin_name;
		$this->version = $version;

	}

	/**
	 * Register the stylesheets for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_styles() {

		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in Dynamic_Aviation_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The Dynamic_Aviation_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */

		wp_enqueue_style( 'handsontableCss', plugin_dir_url( __FILE__ ) . 'css/handsontable.full.min.css', array(), $this->version, 'all' );		 
		 
		wp_enqueue_style( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'css/dynamicaviation-admin.css', array(), time(), 'all' );

	}

	/**
	 * Register the JavaScript for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_scripts() {

		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in Dynamic_Aviation_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The Dynamic_Aviation_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */
		global $typenow;
		if(!is_customize_preview() && ('jet' == $typenow || 'destinations' == $typenow))
		{
			
			wp_enqueue_script( 'handsontableJS', plugin_dir_url( __FILE__ ) . 'js/handsontable.full.min.js', array('jquery'), $this->version, true );
			
			wp_enqueue_script('algolia', '//cdn.jsdelivr.net/algoliasearch/3/algoliasearch.min.js', array( 'jquery' ), $this->version, false );
			
			wp_enqueue_script('algolia_autocomplete', '//cdn.jsdelivr.net/autocomplete.js/0/autocomplete.min.js', array( 'jquery' ), $this->version, false );			
			
			wp_enqueue_script( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'js/dynamicaviation-admin.js', array( 'jquery', 'algolia', 'algolia_autocomplete', 'handsontableJS'), time(), false );

			wp_add_inline_script('dynamicaviation', Dynamic_Aviation_Public::json_src_url(), 'before');
			wp_add_inline_script('dynamicaviation', Dynamic_Aviation_Public::algoliasearch_before(), 'before');
			wp_add_inline_script('dynamicaviation', Dynamic_Aviation_Public::algoliasearch_after(), 'before');
		
		}
	}
	
	public static function custom_rewrite_basic()
	{
		add_rewrite_rule('^fly/([^/]*)/?', 'index.php?fly=$matches[1]','top');
		add_rewrite_rule('^cacheimg/([^/]*)/?.jpg', 'index.php?cacheimg=$matches[1]','top');
		add_rewrite_rule('^instant_quote/([^/]*)/?', 'index.php?instant_quote=$matches[1]','top');
		add_rewrite_rule('^request_submitted/([^/]*)/?', 'index.php?request_submitted=$matches[1]','top');

		global $polylang;		
		if(isset($polylang))
		{
			$languages = PLL()->model->get_languages_list();
			$language_list = array();
			
			for($x = 0; $x < count($languages); $x++)
			{
				foreach($languages[$x] as $key => $value)
				{
					if($key == 'slug')
					{
						array_push($language_list, $value);
					}
				}	
			}
			$language_list = implode('|', $language_list);
			
			add_rewrite_rule('('.$language_list.')/fly/([^/]*)/?', 'index.php?fly=$matches[2]','top');
			add_rewrite_rule('('.$language_list.')/jet/([^/]*)/?', 'index.php?jet=$matches[2]','top');
			add_rewrite_rule('('.$language_list.')/instant_quote/([^/]*)/?', 'index.php?instant_quote=$matches[2]','top');
			add_rewrite_rule('('.$language_list.')/request_submitted/([^/]*)/?', 'index.php?request_submitted=$matches[2]','top');
		}				
	}

	public static function custom_rewrite_tag()
	{
		add_rewrite_tag('%fly%', '([^&]+)');
		add_rewrite_tag('%cacheimg%', '([^&]+)');
		add_rewrite_tag('%jet%', '([^&]+)');
		add_rewrite_tag('%instant_quote%', '([^&]+)');
		add_rewrite_tag('%request_submitted%', '([^&]+)');
	}	
	
	public static function register_pll_strings($sting_name)
	{
		if(function_exists('pll_register_string'))
		{
			pll_register_string('jet_charter', 'Private Jet Charter');			
		}
	}	

}
