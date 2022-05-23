<?php

class Dynamic_Aviation_Admin {

	private $plugin_name;
	private $version;

	public function __construct( $plugin_name, $version, $utilities ) 
	{
		$this->plugin_name = $plugin_name;
		$this->version = $version;
		$this->utilities = $utilities;
		$this->init();
	}

	public function init()
	{
		add_action( 'admin_enqueue_scripts', array(&$this, 'enqueue_styles'), 11);
		add_action( 'admin_enqueue_scripts',  array(&$this, 'enqueue_scripts'));		
		add_action('init', array(&$this, 'custom_rewrite_basic'));
		add_action('init', array(&$this, 'custom_rewrite_tag'), 10, 0);	
		add_action( 'admin_init', array(&$this, 'register_pll_strings'));			
	}

	public function enqueue_styles()
	 {
		wp_enqueue_style( 'handsontableCss', plugin_dir_url( __FILE__ ) . 'css/handsontable.full.min.css', array(), $this->version, 'all' );		 
		wp_enqueue_style( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'css/dynamicaviation-admin.css', array(), time(), 'all' );
	}

	public function enqueue_scripts() {


		global $typenow;

		if(!is_customize_preview() && ('aircrafts' == $typenow))
		{
			
			wp_enqueue_script( 'handsontableJS', plugin_dir_url( __FILE__ ) . 'js/handsontable.full.min.js', array('jquery'), $this->version, true);			
			wp_enqueue_script('algolia', '//cdn.jsdelivr.net/algoliasearch/3/algoliasearch.min.js', array( 'jquery' ), $this->version, false);			
			wp_enqueue_script('algolia_autocomplete', '//cdn.jsdelivr.net/autocomplete.js/0/autocomplete.min.js', array( 'jquery' ), $this->version, false );			
			wp_enqueue_script( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'js/dynamicaviation-admin.js', array( 'jquery', 'algolia', 'algolia_autocomplete', 'handsontableJS'), time(), false );
			wp_add_inline_script('dynamicaviation', $this->utilities->json_src_url(), 'before');
			wp_add_inline_script('dynamicaviation', $this->utilities->algoliasearch_after(), 'before');
		
		}
	}
	
	public function custom_rewrite_basic()
	{
		add_rewrite_rule('^fly/([^/]*)/?', 'index.php?fly=$matches[1]','top');
		add_rewrite_rule('^cacheimg/([^/]*)/?.jpg', 'index.php?cacheimg=$matches[1]','top');
		add_rewrite_rule('^instant_quote/([^/]*)/?', 'index.php?instant_quote=$matches[1]','top');
		add_rewrite_rule('^request_submitted/([^/]*)/?', 'index.php?request_submitted=$matches[1]','top');

		$languages = $this->utilities->get_languages();
		$language_list = array();

		for($x = 0; $x < count($languages); $x++)
		{
			if($languages[$x] != pll_default_language())
			{
				array_push($language_list, $languages[$x]);
			}
		}

		if(count($language_list) > 0)
		{
			$language_list = implode('|', $language_list);
			add_rewrite_rule('('.$language_list.')/fly/([^/]*)/?', 'index.php?fly=$matches[2]','top');
			add_rewrite_rule('('.$language_list.')/aircraft/([^/]*)/?', 'index.php?aircraft=$matches[2]','top');
			add_rewrite_rule('('.$language_list.')/instant_quote/([^/]*)/?', 'index.php?instant_quote=$matches[2]','top');
			add_rewrite_rule('('.$language_list.')/request_submitted/([^/]*)/?', 'index.php?request_submitted=$matches[2]','top');
		}		
	}

	public static function custom_rewrite_tag()
	{
		add_rewrite_tag('%fly%', '([^&]+)');
		add_rewrite_tag('%cacheimg%', '([^&]+)');
		add_rewrite_tag('%aircraft%', '([^&]+)');
		add_rewrite_tag('%instant_quote%', '([^&]+)');
		add_rewrite_tag('%request_submitted%', '([^&]+)');
	}	
	
	public static function register_pll_strings($sting_name)
	{
		if(function_exists('pll_register_string'))
		{
			pll_register_string('aircraft_charter', 'Private Jet Charter');			
		}
	}	

}
