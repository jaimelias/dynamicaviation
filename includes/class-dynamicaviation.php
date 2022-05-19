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
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/class-dynamicaviation-admin.php';
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'public/class-dynamicaviation-public.php';
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'mailer/mailer.php';
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'public/partials/validators.php';
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/class-dynamicaviation-settings.php';
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-dynamicaviation-post-type.php';
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-dynamicaviation-meta-box.php';
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-dynamicaviation-yoastseo.php';
		$this->loader = new Dynamic_Aviation_Loader();
	}

	private function set_locale()
	{
		$plugin_i18n = new Dynamic_Aviation_i18n();
		$plugin_i18n->set_domain( $this->get_plugin_name() );
		$this->loader->add_action( 'plugins_loaded', $plugin_i18n, 'load_plugin_textdomain');
	}

	private function define_admin_hooks() {

		$plugin_admin = new Dynamic_Aviation_Admin( $this->get_plugin_name(), $this->get_version() );
		$plugin_public = new Dynamic_Aviation_Public( $this->get_plugin_name(), $this->get_version() );
		
		$plugin_settings = new Jetcharter_Settings();
		$plugin_post_type = new Charterflights_Post_Type();
		$plugin_meta_box = new Charterflights_Meta_Box();

		$this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_styles', 11);
		$this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_scripts' );
		$this->loader->add_action( 'init', $plugin_post_type, 'fly_post_type', 0);
		$this->loader->add_action( 'init', $plugin_post_type, 'jet_post_type', 0 );			
		$this->loader->add_action( 'save_post', $plugin_meta_box, 'jet_save' );
		$this->loader->add_action( 'add_meta_boxes',$plugin_meta_box, 'jet_add_meta_box' );
		$this->loader->add_action( 'add_meta_boxes',$plugin_meta_box, 'destinations_add_meta_box' );
		$this->loader->add_action('init', $plugin_admin, 'custom_rewrite_basic');
		$this->loader->add_action('init', $plugin_admin, 'custom_rewrite_tag', 10, 0);	
		$this->loader->add_action( 'admin_init', $plugin_admin, 'register_pll_strings' );		
		
	}

	private function define_public_hooks() {

		global $wp_version;
		
		$plugin_public = new Dynamic_Aviation_Public( $this->get_plugin_name(), $this->get_version() );
		$plugin_yoast =  new Dynamic_Aviation_YoastSEO_Fix();
		$this->loader->add_action( 'wp_enqueue_scripts', $plugin_public, 'enqueue_styles' );
		$this->loader->add_action( 'wp_enqueue_scripts', $plugin_public, 'enqueue_scripts' );
		$this->loader->add_filter("wp_head", $plugin_public, 'meta_tags');
		$this->loader->add_action('pre_get_posts', $plugin_public, 'main_wp_query', 100);		
		
		if($wp_version >= 4.4)
		{
			$this->loader->add_filter( 'pre_get_document_title', $plugin_public, 'modify_wp_title', 100);
		}

		$this->loader->add_filter( 'wp_title', $plugin_public, 'modify_wp_title', 100);
		$this->loader->add_filter("the_content", $plugin_public, 'modify_content');
		$this->loader->add_filter("the_title", $plugin_public, 'modify_title');
		$this->loader->add_filter( 'jetpack_enable_open_graph', $plugin_public, 'deque_jetpack' );
		$this->loader->add_filter( 'template_include', $plugin_public, 'package_template', 10 );
		$this->loader->add_filter('template_redirect', $plugin_public, 'redirect_cacheimg', 11);
		$this->loader->add_filter('minimal_ld_json', $plugin_public, 'ld_json', 100);
		$this->loader->add_action('init', $plugin_yoast, 'yoast_fixes');
		
		$this->loader->add_filter('body_class', $plugin_public, 'remove_body_class', 100);
		
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
