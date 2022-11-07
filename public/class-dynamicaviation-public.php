<?php

class Dynamic_Aviation_Public {


	private $plugin_name;
	private $version;

	public function __construct( $plugin_name, $version, $utilities ) {

		global $wp_version;
		$this->plugin_name = $plugin_name;
		$this->version = $version;
		$this->utilities =  $utilities;
		$this->plugin_dir_url = plugin_dir_url( __FILE__ );
		$this->plugin_dir_path = plugin_dir_path( dirname( __FILE__ ) );
		add_action('init', array(&$this, 'init'));
		add_filter('minimal_sitemap', array(&$this, 'sitemap'), 10);
		add_action('wp_enqueue_scripts', array(&$this, 'enqueue_styles'));
		add_action('wp_enqueue_scripts', array(&$this, 'enqueue_scripts'));
	}

	public function init()
	{
		$this->get_languages = get_languages();
		$this->home_lang = home_lang();
	}

	public function mapbox_vars()
	{
		global $dy_aviation_load_mapbox;

		if($dy_aviation_load_mapbox)
		{
			$mapbox_vars = array(
				'mapbox_token' => esc_html(get_option('mapbox_token')),
				'mapbox_map_id' => esc_html(get_option('mapbox_map_id')),
				'mapbox_map_zoom' => intval(get_option('mapbox_map_zoom')),
				'mapbox_base_lat' => floatval(get_option('mapbox_base_lat')),
				'mapbox_base_lon' => floatval(get_option('mapbox_base_lon')),
				'home_url' => $this->home_lang,
			);

			return 'function mapbox_vars(){return '.json_encode($mapbox_vars).';}';
		}
	}

	public function sitemap($sitemap)
	{
		if(isset($_GET['minimal-sitemap']))
		{
			if($_GET['minimal-sitemap'] == 'airports')
			{
				global $polylang;

				if(isset($polylang))
				{
					$languages = $this->get_languages;
					$language_list = array();
					
					for($x = 0; $x < count($languages); $x++)
					{
						if($languages[$x] != pll_default_language())
						{
							$language_list[] = $languages[$x];
						}
					}					
				}
				
				$urllist = null;
				$all_airports = $this->utilities->all_airports_data();
				$image_pathname = apply_filters('dy_aviation_image_pathname', '');
				
				for($x = 0; $x < count($all_airports); $x++)
				{
					$airport_pathname = $this->utilities->sanitize_pathname($all_airports[$x]['airport']);
					$url = '<url>';
					$url .= '<loc>'.esc_url(home_url('fly/' .$airport_pathname)).'/</loc>';
					
					if($image_pathname)
					{
						$url .= '<image:image>';
						$url .= '<image:loc>'.esc_url(home_url($image_pathname . '/' . $airport_pathname)).'.png</image:loc>';
						$url .= '</image:image>';
					}

					$url .= '<mobile:mobile/>';
					$url .= '<changefreq>weekly</changefreq>';
					$url .= '</url>';
					$urllist .= $url;					
				}
				
				if(count($language_list) > 0)
				{
					for($y = 0; $y < count($all_airports); $y++)
					{
						$airport_pathname = $this->utilities->sanitize_pathname($all_airports[$y]['airport']);
						$pll_url = '<url>';
						$pll_url .= '<loc>'.esc_url(home_url($language_list[0] . '/fly/' . $airport_pathname)).'/</loc>';
						
						if($image_pathname)
						{
							$pll_url .= '<image:image>';
							$pll_url .= '<image:loc>'.esc_url(home_url($image_pathname . '/' . $airport_pathname)).'.png</image:loc>';
							$pll_url .= '</image:image>';
						}

						$pll_url .= '<mobile:mobile/>';
						$pll_url .= '<changefreq>weekly</changefreq>';
						$pll_url .= '</url>';
						$urllist .= $pll_url;					
					}					
				}
				
				$sitemap =  '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
				$sitemap .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"
				xmlns:mobile="http://www.google.com/schemas/sitemap-mobile/1.0"
				xmlns:image="http://www.google.com/schemas/sitemap-image/1.1">' . "\n";
				$sitemap .= $urllist;
				$sitemap .= '</urlset>';
			}
		}

		return $sitemap;
	}


	
	
	public function enqueue_styles() {

		global $dy_aviation_load_algolia;
		global $dy_aviation_load_mapbox;

		if(isset($dy_aviation_load_algolia))
		{
			wp_enqueue_style($this->plugin_name, $this->plugin_dir_url . 'css/dynamicaviation-public.css', array(), '', 'all');

			//date time picker
			wp_enqueue_style( 'picker-css', $this->plugin_dir_url . 'css/picker/default.css', array(), 'dynamicaviation', 'all' );
			wp_add_inline_style('picker-css', $this->get_inline_css('picker/default.date'));
			wp_add_inline_style('picker-css', $this->get_inline_css('picker/default.time'));	
		}

		if(isset($dy_aviation_load_mapbox))
		{
			wp_enqueue_style('mapbox', 'https://api.mapbox.com/mapbox.js/v3.3.1/mapbox.css', array(), '3.1.1', 'all' );
			wp_add_inline_style('mapbox', $this->get_inline_css('MarkerCluster'));
			wp_add_inline_style('mapbox', $this->get_inline_css('MarkerCluster.Default'));
		}
	}


	public function enqueue_scripts() {

		global $dy_aviation_load_algolia;
		global $dy_aviation_load_mapbox;

		$dep = array('jquery', 'landing-cookies');

		wp_enqueue_script( 'landing-cookies', $this->plugin_dir_url.'js/cookies.js', array('jquery'), $this->version, true );	
		
		if(isset($dy_aviation_load_algolia) && !isset($_GET['fl_builder']))
		{
			array_push($dep, 'algolia', 'sha512', 'picker-date-js', 'picker-time-js');

			wp_enqueue_script('sha512', $this->plugin_dir_url . 'js/sha512.js', array(), 'async_defer', true );
			wp_enqueue_script('algolia', $this->plugin_dir_url.'js/algoliasearch.min.js', array( 'jquery' ), '3.32.0', true );
			wp_add_inline_script('algolia', $this->utilities->algoliasearch_after(), 'after');
			wp_enqueue_script('algolia_autocomplete', $this->plugin_dir_url.'js/autocomplete.jquery.min.js', array( 'jquery' ), '0.36.0', true );

			if(isset($dy_aviation_load_mapbox))
			{
				array_push($dep, 'mapbox', 'markercluster');

				wp_enqueue_script( 'mapbox', 'https://api.mapbox.com/mapbox.js/v3.3.1/mapbox.js', array( 'jquery', 'algolia'), '3.3.1', true );			
				wp_enqueue_script( 'markercluster', 'https://api.mapbox.com/mapbox.js/plugins/leaflet-markercluster/v1.0.0/leaflet.markercluster.js', array( 'jquery', 'mapbox' ), $this->version, true );		
				wp_add_inline_script('mapbox', $this->get_inline_js('dynamicaviation-arc'), 'after');
				wp_add_inline_script('mapbox', $this->mapbox_vars(), 'after');
				wp_add_inline_script('mapbox', $this->get_inline_js('dynamicaviation-mapbox'), 'after');
			}

			//start picker
			wp_enqueue_script( 'picker-js', $this->plugin_dir_url . 'js/picker/picker.js', array('jquery'), '3.6.2', true);
			wp_enqueue_script( 'picker-date-js', $this->plugin_dir_url . 'js/picker/picker.date.js', array('jquery', 'picker-js'), '3.6.2', true);
			wp_enqueue_script( 'picker-time-js', $this->plugin_dir_url . 'js/picker/picker.time.js',array('jquery', 'picker-js'), '3.6.2', true);	
			wp_enqueue_script( 'picker-legacy', $this->plugin_dir_url . 'js/picker/legacy.js', array('jquery', 'picker-js'), '3.6.2', true);

			$picker_translation = 'js/picker/translations/'.get_locale().'.js';
					
			if(file_exists($this->plugin_dir_path.'/'.$picker_translation))
			{
				wp_enqueue_script( 'picker-time-translation', $this->plugin_dir_url.$picker_translation, array('jquery', 'picker-js'), '3.6.2', true);
			}
			//end picker
			
			wp_enqueue_script($this->plugin_name, $this->plugin_dir_url . 'js/dynamicaviation-public.js', $dep, time(), true );
			wp_add_inline_script($this->plugin_name, $this->utilities->json_src_url(), 'before');
		}

	}
	
	public function get_inline_js($file)
	{
		ob_start();
		require_once($this->plugin_dir_path . 'public/js/'.$file.'.js');
		$output = ob_get_contents();
		ob_end_clean();
		return $output;			
	}
	
	public function get_inline_css($file)
	{
		ob_start();
		require_once($this->plugin_dir_path . 'public/css/'.$file.'.css');
		$output = ob_get_contents();
		ob_end_clean();
		return $output;			
	}
}