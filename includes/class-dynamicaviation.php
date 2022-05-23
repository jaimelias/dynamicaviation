<?php

class Dynamic_Aviation {

	protected $loader;
	protected $plugin_name;
	protected $version;

	public function __construct() {

		$this->plugin_name = 'dynamicaviation';
		$this->version = '1.0.0';
		$this->load_dependencies();
		$this->set_locale();
		$this->define_admin_hooks();
		$this->define_public_hooks();
	}

	private function load_dependencies() {
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-dynamicaviation-loader.php';
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-dynamicaviation-i18n.php';
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-dynamicaviation-sendgrid-mailer.php';
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-dynamicaviation-utilities.php';
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-dynamicaviation-validators.php';
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/class-dynamicaviation-settings.php';
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-dynamicaviation-post-type.php';
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-dynamicaviation-meta-box.php';
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/class-dynamicaviation-admin.php';
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'public/class-dynamicaviation-public.php';
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-dynamicaviation-shortcodes.php';
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-dynamicaviation-search-form.php';
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-dynamicaviation-price-table.php';
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-dynamicaviation-aircraft-single.php';
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-dynamicaviation-aircrafts-table.php';

		$this->loader = new Dynamic_Aviation_Loader();
	}

	private function set_locale()
	{
		$plugin_i18n = new Dynamic_Aviation_i18n();
		$plugin_i18n->set_domain( $this->get_plugin_name() );
		$this->loader->add_action( 'plugins_loaded', $plugin_i18n, 'load_plugin_textdomain');
	}

	private function define_admin_hooks() {

		$utilities = new Dynamic_Aviation_Utilities();
		$plugin_admin = new Dynamic_Aviation_Admin( $this->get_plugin_name(), $this->get_version(),  $utilities);
		
		new Dynamic_Aviation_Settings();
		new Dynamic_Aviation_Post_Type();
		new Dynamic_Aviation_Meta_Box();

		$this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_styles', 11);
		$this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_scripts');		
		$this->loader->add_action('init', $plugin_admin, 'custom_rewrite_basic');
		$this->loader->add_action('init', $plugin_admin, 'custom_rewrite_tag', 10, 0);	
		$this->loader->add_action( 'admin_init', $plugin_admin, 'register_pll_strings' );		
		
	}

	private function define_public_hooks() 
	{
		$utilities = new Dynamic_Aviation_Utilities();
		new Dynamic_Aviation_Public( $this->get_plugin_name(), $this->get_version(), $utilities);

		new Dynamic_Aviation_Search_Form($utilities);

		$price_table = new Dynamic_Aviation_Price_Table($utilities);

		new Dynamic_Aviation_Shortcodes($utilities, $price_table);		
		
		new Dynamic_Aviation_Aircraft_Single($utilities);

		new Dynamic_Aviation_Aircrafts_Table($utilities);
	}

	public function run() {
		$this->loader->run();
	}

	public function get_plugin_name() {
		return $this->plugin_name;
	}

	public function get_loader() {
		return $this->loader;
	}

	public function get_version() {
		return $this->version;
	}
}
