<?php

#[AllowDynamicProperties]
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
		add_action('init', [$this, 'init']);
		add_filter('dy_sitemap', [$this, 'sitemap'], 10);
		add_action('wp_enqueue_scripts', [$this, 'enqueue_styles']);
		add_action('wp_enqueue_scripts', [$this, 'enqueue_scripts']);
		add_action('wp_head', [$this, 'plugin_public_args']);
	}

	public function init()
	{
		$this->get_languages = get_languages();
		$this->home_lang = home_lang();
	}

	public function plugin_public_args()
	{
		echo '<script>'.$this->utilities->plugin_public_args().'</script>';
	}

	public function mapbox_vars()
	{
		global $dy_aviation_load_mapbox;

		if($dy_aviation_load_mapbox)
		{
			$mapbox_vars = [
				'mapbox_token' => esc_html(get_option('mapbox_token')),
				'mapbox_map_id' => esc_html(get_option('mapbox_map_id')),
				'mapbox_map_zoom' => (int) get_option('mapbox_map_zoom'),
				'mapbox_base_lat' => (float) get_option('mapbox_base_lat'),
				'mapbox_base_lon' => (float) get_option('mapbox_base_lon'), 
				'home_url' => $this->home_lang,
			];

			return 'function mapbox_vars(){return '.json_encode($mapbox_vars).';}';
		}
	}

	public function sitemap($sitemap)
	{
		if(!empty($sitemap))
		{
			return $sitemap;
		}

		global $polylang;

		$language_list = [];

		if(isset($polylang))
		{
			$default_language = default_language();

			$language_list = array_values(
				array_filter(
					$this->get_languages,
					fn($language) => $language !== $default_language
				)
			);
		}

		$urllist = '';
		$all_airports = $this->utilities->all_airports_data();

		foreach($all_airports as $airport)
		{
			$airport_pathname = $this->utilities->sanitize_pathname($airport['airport']);

			$image_url = sprintf(
				'%s.png',
				esc_url(
					normalize_url(
						home_url(
							sprintf(
								'%s/%s',
								DY_AVIATION_IMAGE_PATHNAME,
								$airport_pathname
							)
						)
					)
				)
			);

			$urls = [
				home_url(sprintf('fly/%s', $airport_pathname))
			];

			foreach($language_list as $language)
			{
				$urls[] = home_url(
					sprintf(
						'%s/fly/%s',
						$language,
						$airport_pathname
					)
				);
			}

			foreach($urls as $url)
			{
				$urllist .= sprintf(
					'<url><loc>%s</loc><image:image><image:loc>%s</image:loc></image:image><mobile:mobile/><changefreq>weekly</changefreq></url>',
					esc_url(normalize_url($url)),
					$image_url
				);
			}
		}

		return sprintf(
			'<?xml version="1.0" encoding="UTF-8"?>%1$s<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:mobile="http://www.google.com/schemas/sitemap-mobile/1.0" xmlns:image="http://www.google.com/schemas/sitemap-image/1.1">%1$s%2$s</urlset>',
			"\n",
			$urllist
		);
	}


	
	
	public function enqueue_styles() {

		global $dy_aviation_load_algolia;
		global $dy_aviation_load_mapbox;

		if(isset($dy_aviation_load_algolia))
		{
			wp_enqueue_style($this->plugin_name, $this->plugin_dir_url . 'css/dynamicaviation-public.css', array(), $this->version, 'all');
		}

		if(isset($dy_aviation_load_mapbox))
		{
			wp_enqueue_style('mapbox', 'https://api.mapbox.com/mapbox.js/v3.3.1/mapbox.css', array(), '3.1.1', 'all' );
			wp_add_inline_style('mapbox', get_inline_file($this->plugin_dir_path . 'public/css/MarkerCluster.css'));
			wp_add_inline_style('mapbox', get_inline_file($this->plugin_dir_path . 'public/css/MarkerCluster.Default.css'));
		}
	}


	public function enqueue_scripts() {

		global $dy_aviation_load_algolia;
		global $dy_aviation_load_mapbox;

		$dep = array('jquery', 'dy-core-utilities');
		
		if(isset($dy_aviation_load_algolia) && empty(secure_get('fl_builder')))
		{
			array_push($dep, 'algolia', 'picker-date-js', 'picker-time-js');
			wp_enqueue_script('algolia', $this->plugin_dir_url.'js/algoliasearch.min.js', array( 'jquery' ), '3.32.0', true );
			wp_add_inline_script('algolia', $this->utilities->algoliasearch_after(), 'after');
			wp_enqueue_script('algolia_autocomplete', $this->plugin_dir_url.'js/autocomplete.jquery.min.js', array( 'jquery' ), '0.36.0', true );

			if(isset($dy_aviation_load_mapbox))
			{
				array_push($dep, 'mapbox', 'markercluster');

				wp_enqueue_script( 'mapbox', 'https://api.mapbox.com/mapbox.js/v3.3.1/mapbox.js', array( 'jquery', 'algolia'), '3.3.1', true );			
				wp_enqueue_script( 'markercluster', 'https://api.mapbox.com/mapbox.js/plugins/leaflet-markercluster/v1.0.0/leaflet.markercluster.js', array( 'jquery', 'mapbox' ), $this->version, true );		
				wp_add_inline_script('mapbox', get_inline_file($this->plugin_dir_path . 'public/js/dynamicaviation-arc.js'), 'after');
				wp_add_inline_script('mapbox', $this->mapbox_vars(), 'after');
				wp_add_inline_script('mapbox', get_inline_file($this->plugin_dir_path . 'public/js/dynamicaviation-mapbox.js'), 'after');
			}
			
			wp_enqueue_script($this->plugin_name, $this->plugin_dir_url . 'js/dynamicaviation-public.js', $dep, $this->version, true );
		}

	}
}