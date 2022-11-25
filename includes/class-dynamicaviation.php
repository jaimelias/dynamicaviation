<?php

class Dynamic_Aviation {

	protected $loader;
	protected $plugin_name;
	protected $version;

	public function __construct() {

		$this->plugin_name = 'dynamicaviation';
		$this->version = '1.0.2.1';
		$this->load_dependencies();
		$this->set_locale();

		$utilities = new Dynamic_Aviation_Utilities();

		$this->define_admin_hooks($utilities);
		$this->define_public_hooks($utilities);
	}

	private function load_dependencies() {

		$plugin_dir_path = plugin_dir_path( dirname( __FILE__ ) );

		//includes
		require_once $plugin_dir_path . 'includes/class-dynamicaviation-loader.php';
		require_once $plugin_dir_path . 'includes/class-dynamicaviation-i18n.php';
		require_once $plugin_dir_path . 'includes/class-dynamicaviation-utilities.php';

		//admin
		require_once $plugin_dir_path . 'admin/class-dynamicaviation-settings.php';
		require_once $plugin_dir_path . 'includes/class-dynamicaviation-post-type.php';
		require_once $plugin_dir_path . 'includes/class-dynamicaviation-meta-box.php';
		require_once $plugin_dir_path . 'admin/class-dynamicaviation-admin.php';

		//public
		require_once $plugin_dir_path . 'public/class-dynamicaviation-public.php';
		require_once $plugin_dir_path . 'public/class-dynamicaviation-shortcodes.php';
		require_once $plugin_dir_path . 'public/class-dynamicaviation-search-form.php';
		require_once $plugin_dir_path . 'public/class-dynamicaviation-price-table.php';
		require_once $plugin_dir_path . 'public/class-dynamicaviation-aircrafts.php';
		require_once $plugin_dir_path . 'public/class-dynamicaviation-estimate-table.php';
		require_once $plugin_dir_path . 'public/class-dynamicaviation-destinations.php';
		require_once $plugin_dir_path . 'public/class-dynamicaviation-estimate-page.php';
		require_once $plugin_dir_path . 'public/class-dynamicaviation-estimate-confirmation.php';
		require_once $plugin_dir_path . 'public/class-dynamicaviation-image.php';

		$this->loader = new Dynamic_Aviation_Loader();
	}

	private function set_locale()
	{
		$plugin_i18n = new Dynamic_Aviation_i18n();
		$plugin_i18n->set_domain( $this->get_plugin_name() );
		$this->loader->add_action( 'plugins_loaded', $plugin_i18n, 'load_plugin_textdomain');
	}

	private function define_admin_hooks($utilities) {

		new Dynamic_Aviation_Admin( $this->get_plugin_name(), $this->get_version(),  $utilities);
		new Dynamic_Aviation_Settings($utilities);
		new Dynamic_Aviation_Post_Type();
		new Dynamic_Aviation_Meta_Box($utilities);	
	}

	private function define_public_hooks($utilities) 
	{
		new Dynamic_Aviation_Public( $this->get_plugin_name(), $this->get_version(), $utilities);

		new Dynamic_Aviation_Search_Form($utilities);

		new Dynamic_Aviation_Price_Table($utilities);

		new Dynamic_Aviation_Shortcodes($utilities);		
		
		new Dynamic_Aviation_Aircrafts($this->get_plugin_name(), $this->get_version(), $utilities);

		new Dynamic_Aviation_Estimate_Table($utilities);

		new Dynamic_Aviation_Destinations($this->get_plugin_name(), $this->get_version(), $utilities);

		new Dynamic_Aviation_Estimate_Confirmation($this->get_plugin_name(), $this->get_version(), $utilities);
		
		new Dynamic_Aviation_Estimate_Page($this->get_plugin_name(), $this->get_version(), $utilities);

		new Dynamic_Aviation_Image($this->get_plugin_name(), $this->get_version(), $utilities);
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
