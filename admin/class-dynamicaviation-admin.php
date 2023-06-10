<?php

#[AllowDynamicProperties]
class Dynamic_Aviation_Admin {

	private $plugin_name;
	private $version;

	public function __construct( $plugin_name, $version, $utilities ) 
	{
		$this->plugin_name = $plugin_name;
		$this->version = $version;
		$this->utilities = $utilities;
		$this->plugin_dir_url = plugin_dir_url( __FILE__ );

		add_action( 'admin_enqueue_scripts', array(&$this, 'enqueue_styles'));
		add_action( 'admin_enqueue_scripts',  array(&$this, 'enqueue_scripts'), 10);
	}

	public function enqueue_styles()
	 {
		global $dy_aviation_load_admin_scripts;

		if(!is_customize_preview() && isset($dy_aviation_load_admin_scripts))
		{
			wp_enqueue_style( 'handsontableCss', $this->plugin_dir_url . 'css/handsontable.full.min.css', array(), $this->version, 'all' );		 
			wp_enqueue_style( $this->plugin_name, $this->plugin_dir_url . 'css/dynamicaviation-admin.css', array(), time(), 'all' );
		}
	}

	public function enqueue_scripts() {

		global $dy_aviation_load_admin_scripts;

		if(!is_customize_preview() && isset($dy_aviation_load_admin_scripts))
		{
			wp_enqueue_script( 'handsontableJS', $this->plugin_dir_url . 'js/handsontable.full.min.js', array('jquery'), $this->version, true);			
			wp_enqueue_script('algolia', '//cdn.jsdelivr.net/algoliasearch/3/algoliasearch.min.js', array( 'jquery' ), $this->version, true);			
			wp_enqueue_script('algolia_autocomplete', '//cdn.jsdelivr.net/autocomplete.js/0/autocomplete.min.js', array( 'jquery' ), $this->version, true );			
			wp_enqueue_script( $this->plugin_name, $this->plugin_dir_url . 'js/dynamicaviation-admin.js', array( 'jquery', 'algolia', 'algolia_autocomplete', 'handsontableJS'), time(), true );
			wp_add_inline_script($this->plugin_name, $this->utilities->plugin_public_args(), 'before');
			wp_add_inline_script($this->plugin_name, $this->utilities->algoliasearch_after(), 'before');
		}
	}
	
}
