<?php

#[AllowDynamicProperties]
class Dynamic_Aviation_Core {

	protected $id;
	protected $version;

	public function __construct($main_plugin_file) {

		$this->id = 'dynamicaviation';
		$this->id = 'Dynamic Aviation';
		$this->load_dependencies();

		$this->version = is_local_host() ? time() : DYNAMICAVIATION_VERSION;

		add_action('init', [$this, 'load_plugin_textdomain'], PHP_INT_MAX);

		$utilities = new Dynamic_Aviation_Utilities();

		$this->define_admin_hooks($utilities);
		$this->define_public_hooks($utilities);

		register_activation_hook( $main_plugin_file, function() {
			Dynamic_Aviation_Activator::activate();
		} );

		register_deactivation_hook($main_plugin_file, function() {
			Dynamic_Aviation_Deactivator::deactivate();
		} );
	}

	private function load_dependencies() {

		$plugin_dir_path = plugin_dir_path( dirname( __FILE__ ) );

		//includes
		require_once $plugin_dir_path . 'includes/class-dynamicaviation-fields.php';
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
		require_once $plugin_dir_path . 'public/class-dynamicaviation-fly-page.php';
		require_once $plugin_dir_path . 'public/class-dynamicaviation-estimate-page.php';
		require_once $plugin_dir_path . 'public/class-dynamicaviation-estimate-confirmation.php';
		require_once $plugin_dir_path . 'public/class-dynamicaviation-image.php';
		require_once $plugin_dir_path . 'public/class-dynamicaviation-wp-json.php';
		require_once $plugin_dir_path . 'public/class-dynamicaviation-training-data.php';

	}

	public function load_plugin_textdomain() {

		$dir = dirname( plugin_basename( dirname( __FILE__ ) ) ) . '/languages';
		
		load_plugin_textdomain(
			$this->id,
			false,
			$dir
		);

		if(function_exists('pll_register_string')) {
			pll_register_string('charter_flights', 'Charter Flights %s', $this->id);
		}
	}

	private function define_admin_hooks($utilities) {

		new Dynamic_Aviation_Admin( $this->id, $this->version,  $utilities);
		new Dynamic_Aviation_Settings($utilities);
		new Dynamic_Aviation_Post_Type();
		new Dynamic_Aviation_Meta_Box();	
	}

	private function define_public_hooks($utilities) 
	{
		new Dynamic_Aviation_Public( $this->id, $this->version, $utilities);

		new Dynamic_Aviation_Search_Form($utilities);

		new Dynamic_Aviation_Price_Table($utilities);

		new Dynamic_Aviation_Shortcodes();		
		
		new Dynamic_Aviation_Aircrafts($this->id, $this->version, $utilities);

		new Dynamic_Aviation_Estimate_Table($utilities);

		new Dynamic_Aviation_Fly_Page($this->id, $this->version, $utilities);

		new Dynamic_Aviation_Estimate_Confirmation($this->id, $this->version, $utilities);
		
		new Dynamic_Aviation_Estimate_Page($this->id, $this->version, $utilities);

		new Dynamic_Aviation_Image($this->id, $this->version, $utilities);

		new Dynamic_Aviation_WP_JSON($this->id, $this->version, $utilities);

		new Dynamic_Aviation_Training_Data($utilities);
	}
}
