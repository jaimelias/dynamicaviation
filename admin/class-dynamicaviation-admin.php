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
		add_action( 'wp_headers', array(&$this, 'cacheimage_header') );
		add_action( 'plugins_loaded', array(&$this, 'cacheimage') );
		add_filter('query_vars', array(&$this, 'registering_custom_query_var'));
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
	
	public function custom_rewrite_basic()
	{
		add_rewrite_rule('^fly/([^/]*)/?', 'index.php?fly=$matches[1]','top');
		add_rewrite_rule('^cacheimg/([^/]*)/?.png', 'index.php?cacheimg=$matches[1]','top');
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
			add_rewrite_rule('('.$language_list.')/instant_quote/([^/]*)/?', 'index.php?instant_quote=$matches[2]','top');
			add_rewrite_rule('('.$language_list.')/request_submitted/([^/]*)/?', 'index.php?request_submitted=$matches[2]','top');
		}		
	}

	public function custom_rewrite_tag()
	{
		add_rewrite_tag('%fly%', '([^&]+)');
		add_rewrite_tag('%cacheimg%', '([^&]+)');
		add_rewrite_tag('%instant_quote%', '([^&]+)');
		add_rewrite_tag('%request_submitted%', '([^&]+)');
	}

	public function cacheimage_header($headers)
	{
		$path = pathinfo($_SERVER['REQUEST_URI']);
		$dirname = $path['dirname'];
		$basename = $path['basename'];
		$dirname_arr = array_values(array_filter(explode('/', $dirname)));
		$filename = $path['filename'];

		if(is_array($dirname_arr))
		{
			if(count($dirname_arr) > 0)
			{
				if(in_array('cacheimg', $dirname_arr) && str_ends_with($basename, '.png'))
				{

					$headers['Content-Type'] = 'image/png';
				}
			}
		}

		return $headers;
	}

	public function registering_custom_query_var($query_vars)
	{
		$query_vars[] = 'fly';
		$query_vars[] = 'cacheimg';
		$query_vars[] = 'instant_quote';
		$query_vars[] = 'request_submitted';
		return $query_vars;
	}	

	public function cacheimage()
	{
		$path = pathinfo($_SERVER['REQUEST_URI']);
		$dirname = $path['dirname'];
		$basename = $path['basename'];
		$dirname_arr = array_values(array_filter(explode('/', $dirname)));
		$filename = $path['filename'];

		if(is_array($dirname_arr))
		{
			if(count($dirname_arr) > 0)
			{
				if(in_array('cacheimg', $dirname_arr) && str_ends_with($basename, '.png'))
				{
					header('Content-Type: image/png');

					$url = $this->utilities->airport_url_string($this->utilities->airport_data($filename));

					$headers = array(
						'Content-Type' => 'image/png'
					);
					
					$resp = wp_remote_get($url, array(
						'headers' => $headers
					));
					
					if($resp['response']['code'] === 200)
					{
						exit($resp['body']);
					}
				}
			}
		}
	}
}
