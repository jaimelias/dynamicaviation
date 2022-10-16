<?php

class Dynamic_Aviation_Public {


	private $plugin_name;
	private $version;

	public function __construct( $plugin_name, $version, $utilities ) {

		global $wp_version;
		$this->plugin_name = $plugin_name;
		$this->version = $version;
		$this->utilities =  $utilities;
		add_action('init', array(&$this, 'init'));
		add_action( 'parse_query', array( &$this, 'on_quote_submit' ), 1);
		add_filter('minimal_sitemap', array(&$this, 'sitemap'), 10);
		add_filter('dy_aviation_estimate_notes', array(&$this, 'estimate_notes'));
		add_action('wp_enqueue_scripts', array(&$this, 'enqueue_styles'));
		add_action('wp_enqueue_scripts', array(&$this, 'enqueue_scripts'));
		add_filter('wp_head', array(&$this, 'meta_tags'));
		add_action('pre_get_posts', array(&$this, 'main_wp_query'), 100);		
		
		if($wp_version >= 4.4)
		{
			add_filter( 'pre_get_document_title', array(&$this, 'modify_wp_title'), 100);
		}

		add_filter('wp_title', array(&$this, 'modify_wp_title'), 100);
		add_filter('the_content', array(&$this, 'modify_content'));
		add_filter('the_title', array(&$this, 'modify_title'));
		add_filter('the_excerpt', array(&$this, 'modify_excerpt'));
		add_filter('aircraftpack_enable_open_graph', array(&$this, 'dequeue_canonical'));
		add_filter('template_include', array(&$this, 'package_template'), 10 );
		add_filter('template_redirect', array(&$this, 'redirect_cacheimg'), 11);
		add_filter('minimal_ld_json', array(&$this, 'ld_json'), 100);		
		add_filter('body_class', array(&$this, 'remove_body_class'), 100);

		add_filter('minimal_posted_on', array(&$this, 'minimalizr_hide_posted_on'), 100);
		add_filter('minimal_archive_excerpt', array(&$this, 'minimalizr_modify_archive_excerpt'), 100);
		add_filter('minimal_archive_title', array(&$this, 'minimalizr_modify_archive_title'), 100);

	}

	public function init()
	{
		$this->current_language = current_language();
	}

	public function estimate_notes()
	{
		return get_option('dy_aviation_estimate_note_'.$this->current_language);
	}

	
	public static function on_quote_submit()
	{
		global $VALID_JET_RECAPTCHA;
		
		if(!isset($VALID_JET_RECAPTCHA))
		{
			if(Dynamic_Aviation_Validators::valid_aircraft_quote())
			{
				if(Dynamic_Aviation_Validators::validate_recaptcha())
				{
					$data = $_POST;
					$data['lang'] = current_language();
					
					$args50 = array(
						'post_type' => 'aircrafts',
						'posts_per_page' => 1, 
						'p' => intval($data['aircraft_id'])
					);	
					
					$wp_query50 = new WP_Query( $args50 );
					
					if($wp_query50->have_posts())
					{
						while ($wp_query50->have_posts())
						{
							$wp_query50->the_post();
						}
					}
					
					$subject = sprintf(__('%s, Your request was Sent to our Charter Experts!', 'dynamicaviation'), $data['first_name']);

					require_once('email_template.php');
					
					$args = array(
						'subject' => $subject,
						'to' => sanitize_email($_POST['email']),
						'message' => $email_template
					);
					

					echo $email_template;
					exit();

					sg_mail($args);

					self::webhook(json_encode($data));
					$GLOBALS['VALID_JET_RECAPTCHA'] = true;
				}
			}			
		}
	}	
	
	public function dequeue_canonical()
	{
		if(get_query_var('fly'))
		{	
			remove_action( 'wp_head', 'rel_canonical' );
			return false;
		}
	}
	public function ld_json($arr)
	{
		if(get_query_var('fly'))
		{
			global $airport_array;

			if(is_object($airport_array) || is_array($airport_array))
			{
				
				[
					'city' => $city, 
					'iata' => $iata, 
					'city' => $city, 
					'airport' => $airport,
					'country_names' => $country_names
				] = $airport_array;
				
				
				$lang = substr(get_locale(), 0, -3);
				$prices = array();
				
				if($lang)
				{
					if(array_key_exists($lang, $country_names))
					{
						$country_lang = $country_names[$lang];
					}
					else
					{
						$country_lang = $country_names['en'];
					}
				}
				
				$addressArray = array(($airport.' ('.$iata.')'), $city, $country_lang);
				$address = implode(', ', $addressArray);		
				
				$args23 = array(
					'post_type' => 'aircrafts',
					'posts_per_page' => 200,
					'post_parent' => 0,
					'meta_key' => 'aircraft_base_iata',
					'meta_query' => array(
						'key' => 'aircraft_base_iata',
						'value' => esc_html($iata),
						'compare' => '!='
					),
					'orderby' => 'meta_value'
				);
				
				$wp_query23 = new WP_Query( $args23 );

				if ($wp_query23->have_posts())
				{	
					while ( $wp_query23->have_posts() )
					{
						$wp_query23->the_post();
						$table_price = html_entity_decode(aviation_field('aircraft_rates'));
						$table_price = json_decode($table_price, true);

						if(array_key_exists('aircraft_rates_table', $table_price))
						{
							$table_price = $table_price['aircraft_rates_table'];
							
							if(is_array($table_price))
							{
								for($x = 0; $x < count($table_price); $x++)
								{
									$tp = $table_price[$x];
									
									if(($iata == $tp[0] || $iata == $tp[1]) && ($tp[0] != '' || $tp[1] != ''))
									{
										array_push($prices, floatval($tp[3]));
									}
								}							
							}
						}


					}

					wp_reset_postdata();				
					
				}

				if(count($prices) > 0)
				{
					
					$arr = array(
						'@context' => 'http://schema.org/',
						'@type' => 'Product',
						'brand' => array(
							'@type' => 'Thing',
							'name' => esc_html(get_bloginfo('name'))
						),
						'category' => esc_html(__('Charter Flights', 'dynamicaviation')),
						'name' => esc_html(__('Private Charter Flight', 'dynamicaviation').' '.$airport),
						'description' => esc_html(__('Private Charter Flight', 'dynamicaviation').' '.$address.'. '.__('Airplanes and helicopter rides in', 'dynamicaviation').' '.$airport.', '.$city),
						'image' => esc_url($this->utilities->airport_img_url($airport_array, true)),
						'sku' => md5($iata),
						'gtin8' => substr(md5($iata), 0, 8)
					);

					$offers = array(
						'priceCurrency' => 'USD',
						'priceValidUntil' => esc_html(date('Y-m-d', strtotime('+1 year'))),
						'availability' => 'http://schema.org/InStock',
						'url' => esc_url(get_the_permalink())
					);
					
					if(count($prices) == 1)
					{
						$offers['@type'] = 'Offer';
						$offers['@type'] = 'Offer';
						$offers['price'] = number_format($prices[0], 2, '.', '');					
					}
					else
					{
						$offers['@type'] = 'AggregateOffer';
						$offers['lowPrice'] = number_format(min($prices), 2, '.', '');
						$offers['highPrice'] = number_format(max($prices), 2, '.', '');					
					}
					
					$arr['offers'] = $offers;				
				}				
			}
		}
		
		return $arr;
	}

	public function modify_wp_title($title)
	{	 
		if(get_query_var( 'fly' ))
		{
			global $airport_array;
			//jaimelias
			
			if(is_array($airport_array))
			{
				if(count($airport_array) > 0)
				{
					$title = __("Private Charter Flight", "dynamicaviation").' '.$airport_array['airport'];

					if($airport_array['iata'] != null && $airport_array['icao'] != null)
					{
						$title .= ' ['.$airport_array['iata'].']';
					}
					
					$title .= ' '.$airport_array['city'].' | '.get_bloginfo('name');
					$title =  esc_html($title);
				}
				else
				{
					return esc_html(__('Destination Not Found', 'dynamicaviation'));
				}				
			}
			else
			{
				return esc_html(__('Destination Not Found', 'dynamicaviation'));
			}			
		}
		elseif(Dynamic_Aviation_Validators::valid_aircraft_quote())
		{
			return esc_html(__("Request Submitted", "dynamicaviation").' | '.esc_html(get_bloginfo('name')));
		}		
		elseif(Dynamic_Aviation_Validators::valid_aircraft_search())
		{
			$output = '';
			$output .= esc_html(__("Find an Aircraft", "dynamicaviation")).' ';			
			$output .= sanitize_text_field($_GET['aircraft_origin']).'-'.sanitize_text_field($_GET['aircraft_destination']);
			$output .= ' | '.esc_html(get_bloginfo('name'));
			return $output;
			
		}
		elseif(is_post_type_archive('aircrafts'))
		{
			return __('Aircrafts for Rent', 'dynamicaviation') . ' | '. get_bloginfo( 'name', 'display' );
		}
		elseif(is_singular('aircrafts'))
		{			
			$aircraft_type = $this->utilities->aircraft_type(aviation_field( 'aircraft_type' ));
			$city = aviation_field('aircraft_base_city');
			$title = sprintf(__('Charter Flight %s %s in %s', 'dynamicaviation'), $aircraft_type, get_the_title(), $city) .' | '.get_bloginfo( 'name', 'display' );
			return $title;
		}
		return $title;
	}
	public function modify_title($title)
	{
			if(in_the_loop() && is_singular('aircrafts'))
			{
				$aircraft_type = $this->utilities->aircraft_type(aviation_field( 'aircraft_type' ));
				$title = '<span class="linkcolor">'.esc_html($aircraft_type).'</span> '.$title;
				return $title;				
			}
			elseif(in_the_loop() && Dynamic_Aviation_Validators::valid_aircraft_search())
			{
				$title = esc_html(__("Find an Aircraft", "dynamicaviation"));
			}
			elseif(in_the_loop() && Dynamic_Aviation_Validators::valid_aircraft_quote())
			{
				$title = esc_html(__("Request Submitted", "dynamicaviation"));
			}			
			elseif(in_the_loop() && get_query_var( 'fly' ))
			{
				global $airport_array;
				//jaimelias
				
				if(is_array($airport_array))
				{
					if(count($airport_array) > 0)
					{
						$json = $airport_array;
						$title = '<span class="linkcolor">'.esc_html(__('Charter Flights','dynamicaviation')).'</span> '.esc_html($json['airport']).' <span class="linkcolor">'.esc_html($json['city']).'</span>';						
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
			global $airport_array;
			$json = $airport_array;
			$output = '';

			if(is_array($json))
			{
				if(count($json) > 0)
				{
					$output .= apply_filters('dy_aviation_price_table', '');
					$output .= apply_filters('dy_aviation_destination_details', '');
				}				
			}
			
			return $output;
		}
		elseif(Dynamic_Aviation_Validators::valid_aircraft_quote())
		{
			global $VALID_JET_RECAPTCHA;
			
			if(isset($VALID_JET_RECAPTCHA))
			{				
				return '<p class="minimal_success">'.esc_html(__('Request received. Our sales team will be in touch with you soon.', 'dynamicaviation')).'</p>';
			}
			else
			{
				return '<p class="minimal_alert">'.esc_html(__('Invalid Recaptcha', 'dynamicaviation')).'</p>';
			}
		}		
		elseif(Dynamic_Aviation_Validators::valid_aircraft_search())
		{
			if(Dynamic_Aviation_Validators::validate_hash())
			{
				return apply_filters('dy_aviation_aircrafts_table', '');				
			}
			else
			{
				return '<p class="minimal_alert">'.esc_html(__('Invalid Request', 'dynamicaviation')).'</p>';
			}
		}
		elseif(in_the_loop() && is_singular('aircrafts'))
		{
			return apply_filters('dy_aviation_aircraft_template', $content);			
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
			global $airport_array;
			
			if(is_array($airport_array))
			{
				if(count($airport_array) > 0)
				{
					$output = '';
					$json = $airport_array;
					$airport = $json['airport'];
					$iata  = $json['iata'];
					$icao = $json['icao'];
					$codes = '('.$iata.')';
					$city = $json['city'];
					$country_name = $json['country_names'];
					
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
					
					$addressArray = array(($airport.' '.$codes), $city, $country_lang);
					$address = implode(', ', $addressArray);
					$translations = pll_the_languages(array('raw'=>1));
					
					foreach ($translations as $k => $v)
					{
						if($v['slug'] == pll_default_language())
						{
							$hreflang = $v['slug'].'" href="'.$v['url'].'fly/'.$this->utilities->sanitize_pathname($airport);
							$output .= '<link rel="alternate" hreflang="'.($hreflang).'/" />';	
						}
						else
						{
							$hreflang = $v['slug'].'" href="'.home_url('/').$v['slug'].'/fly/'.$this->utilities->sanitize_pathname($airport);
							$output .= '<link rel="alternate" hreflang="'.($hreflang).'/" />';				
						}
					}
					
					$content = __('Private Charter Flight', 'dynamicaviation').' '.$address.' '.__('Airplanes and helicopter rides in', 'dynamicaviation') .' '. $airport;
					$output .= '<meta name="description" content="'.esc_attr($content).'" />';
					$output .= '<link rel="canonical" href="'.esc_url(home_lang().'fly/'.$this->utilities->sanitize_pathname($airport)).'" />';
				
					echo $output;			
				}			
			}
		}
		if(is_singular('aircrafts'))
		{
			ob_start();
			require_once(plugin_dir_path( __FILE__ ).'partials/metatags-aircraft.php');
			$output = ob_get_contents();
			ob_end_clean();
			echo $output;			
		}
	}	
	public function main_wp_query($query)
	{
		if(get_query_var( 'fly' ) && $query->is_main_query())
		{
			$GLOBALS['airport_array'] = json_decode($this->utilities->return_json(), true); 
						
			global $polylang;
			//removes alternate to home
			if($polylang)
			{
				remove_filter('wp_head', array($polylang->links, 'wp_head'));
			}
			
			//add main query to bypass not found error
			$query->set('post_type', 'page');
			$query->set( 'posts_per_page', 1 );
		}
		elseif( Dynamic_Aviation_Validators::valid_aircraft_search() || Dynamic_Aviation_Validators::valid_aircraft_quote())
		{
			if($query->is_main_query())
			{
				$query->set('post_type', 'page');
				$query->set( 'posts_per_page', 1 );				
			}
		}
	}

	
	public static function airport_url_string($json)
	{
		//json
		$_geoloc = $json['_geoloc'];
		
		//mapbox options
		$mapbox_token = get_option('mapbox_token');
		
		//map position
		$mapbox_marker = 'pin-l-airport+dd3333('.$_geoloc['lng'].','.$_geoloc['lat'].')';

		return 'https://api.mapbox.com/styles/v1/mapbox/streets-v11/static/'.esc_html($mapbox_marker).'/'.esc_html($_geoloc['lng']).','.esc_html($_geoloc['lat']).',8/660x440?access_token='.esc_html($mapbox_token);				
	}
	
	public function redirect_cacheimg()
	{
		if(get_query_var( 'cacheimg' ) && !in_the_loop())
		{
			$json = json_decode($this->utilities->return_json(), true);
			$static_map = self::airport_url_string($json);
			wp_redirect(esc_url($static_map));
			exit;
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
					$languages = $this->utilities->get_languages();
					$language_list = array();
					
					for($x = 0; $x < count($languages); $x++)
					{
						if($languages[$x] != pll_default_language())
						{
							array_push($language_list, $languages[$x]);
						}
					}					
				}
				
				$urllist = null;
				$browse_json = $this->utilities->return_json();
				$browse_json = $browse_json['hits'];
				
				for($x = 0; $x < count($browse_json); $x++)
				{
					$url = '<url>';
					$url .= '<loc>'.esc_url(home_url().'/fly/'.$this->utilities->sanitize_pathname($browse_json[$x]['airport'])).'/</loc>';
					$url .= '<image:image>';
					$url .= '<image:loc>'.esc_url(home_url().'/cacheimg/'.$this->utilities->sanitize_pathname($browse_json[$x]['airport'])).'.jpg</image:loc>';
					$url .= '</image:image>';
					$url .= '<mobile:mobile/>';
					$url .= '<changefreq>weekly</changefreq>';
					$url .= '</url>';
					$urllist .= $url;					
				}
				
				if(count($language_list) > 0)
				{
					for($y = 0; $y < count($browse_json); $y++)
					{
						$pll_url = '<url>';
						$pll_url .= '<loc>'.esc_url(home_url().'/'.$language_list[0].'/fly/'.$this->utilities->sanitize_pathname($browse_json[$y]['airport'])).'/</loc>';
						$pll_url .= '<image:image>';
						$pll_url .= '<image:loc>'.esc_url(home_url().'/cacheimg/'.$this->utilities->sanitize_pathname($browse_json[$y]['airport'])).'.jpg</image:loc>';
						$pll_url .= '</image:image>';
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

	public function package_template($template)
	{
		if(Dynamic_Aviation_Validators::valid_aircraft_quote())
		{
			$new_template = locate_template( array( 'page.php' ) );
			return $new_template;			
		}
		if(get_query_var( 'fly' ) || Dynamic_Aviation_Validators::valid_aircraft_search() || is_singular('aircrafts') || is_singular('destinations'))
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

		$dep = array('jquery', 'landing-cookies');

		wp_enqueue_script( 'landing-cookies', plugin_dir_url( __FILE__ ).'js/cookies.js', array('jquery'), $this->version, true );		
		
		if(((is_a($post, 'WP_Post') && has_shortcode( $post->post_content, 'aviation_search_form')) || is_singular('aircrafts')) && !isset($_GET['fl_builder']))
		{
			array_push($dep, 'algolia', 'mapbox', 'markercluster', 'sha512', 'picker-date-js', 'picker-time-js');
			
			wp_enqueue_script('algolia', plugin_dir_url( __FILE__ ).'js/algoliasearch.min.js', array( 'jquery' ), '3.32.0', true );
			wp_add_inline_script('algolia', $this->utilities->json_src_url(), 'before');
			wp_add_inline_script('algolia', $this->utilities->algoliasearch_after(), 'after');
			wp_enqueue_script('algolia_autocomplete', plugin_dir_url( __FILE__ ).'js/autocomplete.jquery.min.js', array( 'jquery' ), '0.36.0', true );
			
			wp_enqueue_script( 'mapbox', 'https://api.mapbox.com/mapbox.js/v3.3.1/mapbox.js', array( 'jquery', 'algolia'), '3.3.1', true );
			
			wp_enqueue_script( 'markercluster', 'https://api.mapbox.com/mapbox.js/plugins/leaflet-markercluster/v1.0.0/leaflet.markercluster.js', array( 'jquery', 'mapbox' ), $this->version, true );		
			wp_add_inline_script('mapbox', $this->get_inline_js('dynamicaviation-arc'), 'after');
			wp_add_inline_script('mapbox', $this->mapbox_vars(), 'after');
			wp_add_inline_script('mapbox', $this->get_inline_js('dynamicaviation-mapbox'), 'after');
			wp_enqueue_script('sha512', plugin_dir_url( __FILE__ ) . 'js/sha512.js', array(), 'async_defer', true );
			self::datepickerJS();			
			wp_enqueue_script($this->plugin_name, plugin_dir_url( __FILE__ ) . 'js/dynamicaviation-public.js', $dep, time(), true );
		}
		
		if(Dynamic_Aviation_Validators::valid_aircraft_search())
		{
			$recap = false;
			
			if(!function_exists('is_booking_page'))
			{
				$recap = true;
			}
			else
			{
				if(!is_booking_page())
				{
					$recap = true;
				}
			}

			if($recap === true)
			{
				//recaptcha
				wp_enqueue_script('invisible-recaptcha', 'https://www.google.com/recaptcha/api.js', '', 'async_defer_dynamicaviation', true );	
				array_push($dep, 'invisible-recaptcha');
			}
			
			wp_enqueue_script($this->plugin_name, plugin_dir_url( __FILE__ ) . 'js/dynamicaviation-public.js', $dep, time(), true );
			wp_add_inline_script($this->plugin_name, $this->utilities->json_src_url(), 'before');
		}
	}
	

	public function css()
	{
		global $post;

		wp_enqueue_style('minimalLayout', plugin_dir_url( __FILE__ ) . 'css/minimal-layout.css', array(), '', 'all' );
		
		
		if(get_query_var('fly'))
		{
			wp_add_inline_style('minimalLayout', $this->get_inline_css('dynamicpackages-public'));
		}
		if(is_singular('aircrafts') || (is_a( $post, 'WP_Post' ) && (has_shortcode( $post->post_content, 'aviation_search_form') || has_shortcode( $post->post_content, 'jc_calculator'))))
		{
			wp_add_inline_style('minimalLayout', $this->get_inline_css('dynamicaviation-public'));
		}
	}
	
	public function datepickerCSS()
	{
		global $post;
		
		if(is_a( $post, 'WP_Post' ) && (has_shortcode( $post->post_content, 'aviation_search_form') || has_shortcode( $post->post_content, 'jc_calculator')) || is_singular('aircrafts'))
		{
			wp_enqueue_style( 'picker-css', plugin_dir_url( __FILE__ ) . 'css/picker/default.css', array(), 'dynamicaviation', 'all' );
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
		wp_enqueue_script( 'picker-js', plugin_dir_url( __FILE__ ) . 'js/picker/picker.js', array('jquery'), '3.6.2', true);
		wp_enqueue_script( 'picker-date-js', plugin_dir_url( __FILE__ ) . 'js/picker/picker.date.js', array('jquery', 'picker-js'), '3.6.2', true);
		wp_enqueue_script( 'picker-time-js', plugin_dir_url( __FILE__ ) . 'js/picker/picker.time.js',array('jquery', 'picker-js'), '3.6.2', true);	
		wp_enqueue_script( 'picker-legacy', plugin_dir_url( __FILE__ ) . 'js/picker/legacy.js', array('jquery', 'picker-js'), '3.6.2', true);

		$picker_translation = 'js/picker/translations/'.get_locale().'.js';
				
		if(file_exists(dirname( __FILE__ ).'/'.$picker_translation))
		{
			wp_enqueue_script( 'picker-time-translation', plugin_dir_url( __FILE__ ).$picker_translation, array('jquery', 'picker-js'), '3.6.2', true);
		}		
	}
	
	public static function webhook($data)
	{
		
		if(get_option('aircraft_webhook'))
		{
			$webhook = get_option('aircraft_webhook');
			
			if(!filter_var($webhook, FILTER_VALIDATE_URL) === false)
			{
				$ch = curl_init();
				curl_setopt($ch, CURLOPT_URL, $webhook);
				curl_setopt($ch, CURLOPT_POST, 1);
				curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
				curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json', 'Content-Length: ' . strlen($data)));
				curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
				curl_setopt($ch,CURLOPT_TIMEOUT, 20);
				$result = curl_exec($ch);
				$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
				curl_close($ch);
				
				if (intval($httpCode) === 200)
				{
					//do nothing
				}
				else
				{
					$admin_email = get_option( 'admin_email' );
					$time = current_time('timestamp', $gmt = 0 );
					$time = date_i18n(get_option('date_format'), $time);
					write_log('Dynamic_Aviation Webhook Error - '.$time.': '.$result);
					wp_mail( $admin_email, 'Dynamic_Aviation Webhook Error - '.$time, $result);	
				}
			}
		}
	}

	public static function algolia_one($string)
	{
		$new_query_var = $string;
		$query_param = '?query='.$new_query_var.'&hitsPerPage=1';
		$algolia_token = get_option('algolia_token');
		$algolia_index = get_option('algolia_index');
		$algolia_id = get_option('algolia_id');
		
		$curl = curl_init();
		$headers = array();
		$headers[] = 'X-Algolia-API-Key: '.$algolia_token;
		$headers[] = 'X-Algolia-Application-Id: '.$algolia_id;
		curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);	

		curl_setopt_array($curl, array(
		CURLOPT_RETURNTRANSFER => 1,
		CURLOPT_REFERER => esc_url(home_url()),
		CURLOPT_URL => 'https://'.$algolia_id.'-dsn.algolia.net/1/indexes/'.$algolia_index.'/'.$query_param,
		));
		$resp = curl_exec($curl);
		$resp = json_decode($resp, true);
		$resp = $resp['hits'];
		return $resp;
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
	
	public function remove_body_class($classes)
	{
		if(get_query_var('fly') || get_query_var('instant_quote') || get_query_var('request_submitted') || is_singular('aircrafts'))
		{
			if(in_array('blog', $classes))
			{
				unset($classes[array_search('blog', $classes)]);
			}
		}
		
		return $classes;
	}

	public function modify_excerpt($excerpt)
	{
		if(is_singular('aircrafts'))
		{
			$aircraft_type = $this->utilities->aircraft_type(aviation_field( 'aircraft_type' ));
			$city = aviation_field('aircraft_base_city');
			$airport = aviation_field('aircraft_base_name');
			return sprintf(__('%s for rent in %s. Private Charter Flight Service %s %s in %s.', 'dynamicaviation'), $aircraft_type, $city, $aircraft_type, get_the_title(), $airport, $city);
		}

		return $excerpt;
	}

	public function minimalizr_hide_posted_on($posted_on)
	{
		if(is_post_type_archive('aircrafts'))
		{
			return '';
		}

		return $posted_on;
	}

	public function minimalizr_modify_archive_excerpt($excerpt)
	{
		if(is_post_type_archive('aircrafts'))
		{
			$type = $this->utilities->aircraft_type(aviation_field( 'aircraft_type' ));
			$passengers = aviation_field('aircraft_passengers');
			$price_per_hour = aviation_field('aircraft_price_per_hour');
			$excerpt = '<p><strong>'.esc_html(__('Type', 'dynamicaviation')).'</strong>: '.esc_html($type).'<br/>';
			$excerpt .= '<strong>'.esc_html(__('Passengers', 'dynamicaviation')).'</strong>: '.esc_html($passengers).'<br/>';
			$excerpt .= '<strong>'.esc_html(__('Price Per Hour', 'dynamicaviation')).'</strong>: '.esc_html($price_per_hour).'</p>';
		}

		return $excerpt;
	}
	public function minimalizr_modify_archive_title($title)
	{
		if(is_post_type_archive('aircrafts'))
		{
			return __('Aircrafts', 'dynamicaviation');
		}

		return $title;
	}
	
}