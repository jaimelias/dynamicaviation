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
		add_action('init', array(&$this, 'init'));
		add_filter('minimal_sitemap', array(&$this, 'sitemap'), 10);
		
		add_action('wp_enqueue_scripts', array(&$this, 'enqueue_styles'));
		add_action('wp_enqueue_scripts', array(&$this, 'enqueue_scripts'));
		add_action('wp_head', array(&$this, 'meta_tags'));
		add_action('pre_get_posts', array(&$this, 'main_wp_query'), 100);		
		add_filter( 'pre_get_document_title', array(&$this, 'modify_wp_title'), 100);		
		add_filter('wp_title', array(&$this, 'modify_wp_title'), 100);
		add_filter('the_content', array(&$this, 'modify_content'));
		add_filter('the_title', array(&$this, 'modify_title'));
		add_filter('template_include', array(&$this, 'locate_template'), 100 );
	}

	public function init()
	{
		$this->site_name = get_bloginfo('name');
		$this->current_language = current_language();
		$this->get_languages = get_languages();
	}

	public function modify_wp_title($title)
	{
		if(get_query_var( 'fly' ))
		{
			$airport_array = $this->utilities->airport_data();
			
			if(!empty($airport_array))
			{
				if(count($airport_array) > 0)
				{
					$country = '';

					if(array_key_exists('country_names', $airport_array))
					{
						if(array_key_exists($this->current_language, $airport_array['country_names']))
						{
							$country .= ', ' . $airport_array['country_names'][$this->current_language];
						}
					}					

					$airport = ($airport_array['airport'] !== $airport_array['city']) 
						? $airport_array['airport'] . ', ' . $airport_array['city']
						: $airport_array['airport'] . $country;

					if(array_key_exists('airport_names', $airport_array))
					{
						if(array_key_exists($this->current_language, $airport_array['airport_names']))
						{
							$airport = $airport_array['airport_names'][$this->current_language];
						}
					}

					$title = sprintf(__('Charter Flights to %s', 'dynamicaviation'), $airport) . ' | ' . $this->site_name;
				}
				else
				{
					$title =  __('Destination Not Found', 'dynamicaviation') . ' | ' . $this->site_name;
				}				
			}
			else
			{
				$title =  __('Destination Not Found', 'dynamicaviation') . ' | ' . $this->site_name;
			}			
		}

		return $title;
	}

	public function modify_title($title)
	{
			if(in_the_loop() && get_query_var( 'fly' ))
			{
				$airport_array = $this->utilities->airport_data();
				
				if(!empty($airport_array))
				{
					if(count($airport_array) > 0)
					{
						$airport = ($airport_array['airport'] !== $airport_array['city']) 
							? $airport_array['airport'] . ', ' . $airport_array['city']
							: $airport_array['airport'];

						if(array_key_exists('airport_names', $airport_array))
						{
							if(array_key_exists($this->current_language, $airport_array['airport_names']))
							{
								$airport = $airport_array['airport_names'][$this->current_language];
							}
						}							

						$title = __('Charter Flights to','dynamicaviation').' <span class="linkcolor">'.esc_html($airport).'</span>';						
					}
					else
					{
						$title = esc_html(__('Destination Not Found', 'dynamicaviation'));
					}				
				}
				else
				{
					$title = esc_html(__('Destination Not Found', 'dynamicaviation'));
				}					
			}
		return $title;
	}
	public function modify_content($content)
	{	if(in_the_loop() && get_query_var( 'fly' ))
		{
			$airport_array = $this->utilities->airport_data();
			$output = '';

			if(!empty($airport_array))
			{
				$output .= apply_filters('dy_aviation_price_table', '');
				$output .= apply_filters('dy_aviation_destination_details', '');
			}
			
			return $output;
		}		

		return $content;
	}


	public function mapbox_vars()
	{
		$mapbox_vars = array(
			'mapbox_token' => esc_html(get_option('mapbox_token')),
			'mapbox_map_id' => esc_html(get_option('mapbox_map_id')),
			'mapbox_map_zoom' => intval(get_option('mapbox_map_zoom')),
			'mapbox_base_lat' => floatval(get_option('mapbox_base_lat')),
			'mapbox_base_lon' => floatval(get_option('mapbox_base_lon')),
			'home_url' => home_lang(),
		);

		return 'function mapbox_vars(){return '.json_encode($mapbox_vars).';}';
	}
	public function meta_tags()
	{	if(get_query_var( 'fly' ))
		{
			$airport_array = $this->utilities->airport_data();
			
			if(!empty($airport_array))
			{
				$output = "\r\n";
				$addressArray = array();
				$airport = $airport_array['airport'];
				$iata  = $airport_array['iata'];
				$icao = $airport_array['icao'];
				$codes = '('.$iata.')';
				$city = $airport_array['city'];
				$country_name = $airport_array['country_names'];

				if(array_key_exists('airport_names', $airport_array))
				{
					if(array_key_exists($this->current_language, $airport_array['airport_names']))
					{
						$airport = $airport_array['airport_names'][$this->current_language];
					}
				}


				$addressArray[] = ($iata && $icao) ? $airport . ' ('.$iata.')' : $airport;

				if($airport !== $city)
				{
					$addressArray[] = $city;
				}

				
				if($this->current_language)
				{
					if(array_key_exists($this->current_language, $country_name))
					{
						$country_lang = $country_name[$this->current_language];
					}
					else
					{
						$country_lang = $country_name['en'];
					}
				}

				$addressArray[] = $country_lang;
				
				$address = implode(', ', $addressArray);
				$translations = pll_the_languages(array('raw'=>1));
				
				foreach ($translations as $k => $v)
				{
					if($v['slug'] == pll_default_language())
					{
						$output .= '<link rel="alternate" hreflang="'.esc_attr($v['slug']).'" href="'.home_url('fly/'.$this->utilities->sanitize_pathname($airport)).'"/>';	
					}
					else
					{
						$output .= '<link rel="alternate" hreflang="'.esc_attr($v['slug']).'" href="'.home_url($v['slug'].'/fly/'.$this->utilities->sanitize_pathname($airport)).'" />';				
					}

					$output .= "\r\n";
				}
				
				$output .= '<meta name="description" content="'.esc_attr(sprintf(__('Private charter flights to %s. Jets, planes and helicopter rental services in %s.', 'dynamicaviation'), $address, $airport)).'" />';
				$output .= "\r\n";
				$output .= '<link rel="canonical" href="'.esc_url(home_lang().'fly/'.$this->utilities->sanitize_pathname($airport_array['airport'])).'" />';
				$output .= "\r\n";

				echo $output;			
			}
		}
	}


	public function main_wp_query($query)
	{
		if(isset($query->query_vars['fly']) && $query->is_main_query())
		{				
			$query->set('post_type', 'page');
			$query->set( 'posts_per_page', 1 );
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

	public function locate_template($template)
	{
		if(get_query_var( 'fly' ) || is_singular('destinations'))
		{
			$new_template = locate_template( array( 'page.php' ) );
			return $new_template;			
		}
		return $template;
	}
	
	
	public function enqueue_styles() {

		$this->css();
		$this->datepickerCSS();
		$this->mapboxCSS();

	}

	public function enqueue_scripts() {

		global $post;
		global $dy_aviation_load_algolia;
		global $dy_aviation_load_mapbox;

		$dep = array('jquery', 'landing-cookies');

		wp_enqueue_script( 'landing-cookies', $this->plugin_dir_url.'js/cookies.js', array('jquery'), $this->version, true );	
		wp_add_inline_script('landing-cookies', $this->utilities->json_src_url(), 'after');	
		
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

			self::datepickerJS();			
			wp_enqueue_script($this->plugin_name, $this->plugin_dir_url . 'js/dynamicaviation-public.js', $dep, time(), true );
		}

	}
	

	public function css()
	{
		global $post;

		wp_enqueue_style('minimalLayout', $this->plugin_dir_url . 'css/minimal-layout.css', array(), '', 'all' );

		if(is_singular('aircrafts') || get_query_var('fly') || (is_a( $post, 'WP_Post' ) && (has_shortcode( $post->post_content, 'aviation_search_form') || has_shortcode( $post->post_content, 'jc_calculator'))))
		{
			wp_add_inline_style('minimalLayout', $this->get_inline_css('dynamicaviation-public'));
		}
	}
	
	public function datepickerCSS()
	{
		global $post;
		
		if(is_a( $post, 'WP_Post' ) && (has_shortcode( $post->post_content, 'aviation_search_form') || has_shortcode( $post->post_content, 'jc_calculator')) || is_singular('aircrafts') || is_singular('aircrafts') || get_query_var('fly'))
		{
			wp_enqueue_style( 'picker-css', $this->plugin_dir_url . 'css/picker/default.css', array(), 'dynamicaviation', 'all' );
			wp_add_inline_style('picker-css', $this->get_inline_css('picker/default.date'));
			wp_add_inline_style('picker-css', $this->get_inline_css('picker/default.time'));				
		}		
	}
	
	public function mapboxCSS()
	{
		global $post;
		
		if(is_a( $post, 'WP_Post' ) && has_shortcode( $post->post_content, 'aviation_search_form') && !isset($_GET['fl_builder']))
		{
			wp_enqueue_style('mapbox', 'https://api.mapbox.com/mapbox.js/v3.3.1/mapbox.css', array(), '3.1.1', 'all' );
			wp_add_inline_style('mapbox', $this->get_inline_css('MarkerCluster'));
			wp_add_inline_style('mapbox', $this->get_inline_css('MarkerCluster.Default'));
		}		
	}
	
	public function datepickerJS()
	{
		//pikadate
		wp_enqueue_script( 'picker-js', $this->plugin_dir_url . 'js/picker/picker.js', array('jquery'), '3.6.2', true);
		wp_enqueue_script( 'picker-date-js', $this->plugin_dir_url . 'js/picker/picker.date.js', array('jquery', 'picker-js'), '3.6.2', true);
		wp_enqueue_script( 'picker-time-js', $this->plugin_dir_url . 'js/picker/picker.time.js',array('jquery', 'picker-js'), '3.6.2', true);	
		wp_enqueue_script( 'picker-legacy', $this->plugin_dir_url . 'js/picker/legacy.js', array('jquery', 'picker-js'), '3.6.2', true);

		$picker_translation = 'js/picker/translations/'.get_locale().'.js';
				
		if(file_exists(dirname( __FILE__ ).'/'.$picker_translation))
		{
			wp_enqueue_script( 'picker-time-translation', $this->plugin_dir_url.$picker_translation, array('jquery', 'picker-js'), '3.6.2', true);
		}		
	}


	public function get_inline_js($file)
	{
		ob_start();
		require_once(dirname( __FILE__ ) . '/js/'.$file.'.js');
		$output = ob_get_contents();
		ob_end_clean();
		return $output;			
	}
	
	public function get_inline_css($file)
	{
		ob_start();
		require_once(dirname( __FILE__ ) . '/css/'.$file.'.css');
		$output = ob_get_contents();
		ob_end_clean();
		return $output;			
	}
}