<?php

class Dynamic_Aviation_Admin {

	private $plugin_name;
	private $version;

	public function __construct( $plugin_name, $version, $utilities ) 
	{
		$this->plugin_name = $plugin_name;
		$this->version = $version;
		$this->utilities = $utilities;
		
		add_action('init', array(&$this, 'init'), 1);
		add_action( 'admin_enqueue_scripts', array(&$this, 'enqueue_styles'), 11);
		add_action( 'admin_enqueue_scripts',  array(&$this, 'enqueue_scripts'));		
		add_action('init', array(&$this, 'add_rewrite_rule'));
		add_action('init', array(&$this, 'add_rewrite_tag'), 10, 0);
		add_filter('query_vars', array(&$this, 'registering_custom_query_var'));
	}

	public function init()
	{
		$this->get_languages = get_languages();
	}

	public function enqueue_styles()
	 {
		wp_enqueue_style( 'handsontableCss', plugin_dir_url( __FILE__ ) . 'css/handsontable.full.min.css', array(), $this->version, 'all' );		 
		wp_enqueue_style( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'css/dynamicaviation-admin.css', array(), time(), 'all' );
	}

	public function enqueue_scripts() {

		global $typenow;
		$post_types = array('aircrafts', 'destinations');

		if(!is_customize_preview() && in_array($typenow, $post_types))
		{
			wp_enqueue_script( 'handsontableJS', plugin_dir_url( __FILE__ ) . 'js/handsontable.full.min.js', array('jquery'), $this->version, true);			
			wp_enqueue_script('algolia', '//cdn.jsdelivr.net/algoliasearch/3/algoliasearch.min.js', array( 'jquery' ), $this->version, true);			
			wp_enqueue_script('algolia_autocomplete', '//cdn.jsdelivr.net/autocomplete.js/0/autocomplete.min.js', array( 'jquery' ), $this->version, true );			
			wp_enqueue_script( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'js/dynamicaviation-admin.js', array( 'jquery', 'algolia', 'algolia_autocomplete', 'handsontableJS'), time(), true );
			wp_add_inline_script('dynamicaviation', $this->utilities->json_src_url(), 'before');
			wp_add_inline_script('dynamicaviation', $this->utilities->algoliasearch_after(), 'before');
		}
	}
	
	public function add_rewrite_rule()
	{
		add_rewrite_rule('^fly/([a-z0-9-]+)[/]?$', 'index.php?fly=$matches[1]','top');
		
		add_rewrite_rule('^instant_quote/([a-z0-9-]+)[/]?$', 'index.php?instant_quote=$matches[1]','top');

		$languages = $this->get_languages;
		$arr = array();

		for($x = 0; $x < count($languages); $x++)
		{
			if($languages[$x] != pll_default_language())
			{
				$arr[] = $languages[$x];
			}
		}

		if(count($arr) > 0)
		{
			$arr = implode('|', $arr);
			add_rewrite_rule('('.$arr.')/fly/([a-z0-9-]+)[/]?$', 'index.php?fly=$matches[2]','top');
			add_rewrite_rule('('.$arr.')/instant_quote/([a-z0-9-]+)[/]?$', 'index.php?instant_quote=$matches[2]','top');
		}		
	}

	public function add_rewrite_tag()
	{
		add_rewrite_tag('%fly%', '([^&]+)');
		add_rewrite_tag('%instant_quote%', '([^&]+)');
	}

	public function registering_custom_query_var($query_vars)
	{
		$query_vars[] = 'fly';
		$query_vars[] = 'instant_quote';
		return $query_vars;
	}
}
