<?php 

#[AllowDynamicProperties]
class Dynamic_Aviation_Destinations {
    
    public function __construct($plugin_name, $version, $utilities)
    {
        $this->utilities = $utilities;
		$this->plugin_name = $plugin_name;

		//init
        add_action('init', array(&$this, 'init'));
        add_action('admin_enqueue_scripts', array(&$this, 'admin_enqueue_scripts'), 1);

		//admin query vars
		add_action('init', array(&$this, 'add_rewrite_rule'));
		add_action('init', array(&$this, 'add_rewrite_tag'), 10, 0);
		add_filter('query_vars', array(&$this, 'registering_custom_query_var'));


        //filters custom wordpress outputs
        add_action('pre_get_posts', array(&$this, 'main_wp_query'), 100);
		add_filter( 'pre_get_document_title', array(&$this, 'modify_wp_title'), 100);		
		add_filter('wp_title', array(&$this, 'modify_wp_title'), 100);
        add_filter('the_title', array(&$this, 'modify_title'));
        add_filter('the_content', array(&$this, 'modify_content'), 100);

        //meta tags
        add_action('wp_head', array(&$this, 'meta_tags'));

		//headers
		add_action('template_redirect', array(&$this, 'return_404'), 999);

        //minimalizr theme
        add_filter('minimal_ld_json', array(&$this, 'ld_json'), 100);
        add_filter('template_include', array(&$this, 'locate_template'), 100 );

        //enqueue logic in public.php
        add_action( 'wp', array( &$this, 'load_scripts' ), 100);

		//polylang
		add_filter('pll_translation_url', array(&$this, 'pll_translation_url'), 100, 2);
    }

    public function init()
    {
        $this->site_name = get_bloginfo('name');
		$this->get_languages = get_languages();
        $this->current_language = current_language();		
        $this->home_lang = home_lang();
    }

	public function return_404() {

		$slug = get_query_var( 'fly' );

		if($slug) {

			$airport_array = $this->utilities->airport_data_by_slug($slug);

			if(!is_array($airport_array) || count($airport_array) === 0 ) {

				// Evita que WP haga su redirección canónica en esta ruta
				add_filter('redirect_canonical', '__return_false', 99);

				global $wp_query;
				if (method_exists($wp_query, 'set_404')) {
					$wp_query->set_404();
				}				

				status_header(404);
				nocache_headers();

				// Cargar plantilla 404 y cortar
				$template_404 = get_query_template('404');
				if ($template_404) {
					include $template_404;
				}
				exit;
			}

			$url = current_url_full();
			$path = parse_url($url, PHP_URL_PATH);

			if ($path !== '/' && substr($path, -1) === '/') {
				$unslashed_url = normalize_url($url);

				if($unslashed_url && $unslashed_url !== $url) {
					wp_safe_redirect($unslashed_url, 301);
					exit;
				}
				
			}

		}
	}

	public function admin_enqueue_scripts()
	{
		global $typenow;

		if($typenow === 'destinations')
		{
			$GLOBALS['dy_aviation_load_admin_scripts'] = true;
		}
	}

	public function add_rewrite_rule()
	{
		add_rewrite_rule('^fly/([a-z0-9-]+)[/]?$', 'index.php?fly=$matches[1]','top');
		
		add_rewrite_rule('^instant_quote/([a-z0-9-]+)[/]?$', 'index.php?instant_quote=$matches[1]','top');

		$languages = $this->get_languages;
		$arr = array();

		for($x = 0; $x < count($languages); $x++)
		{
			if($languages[$x] != default_language())
			{
				$arr[] = $languages[$x];
			}
		}

		if(count($arr) > 0)
		{
			$arr = implode('|', $arr);
			add_rewrite_rule('('.$arr.')/fly/([a-z0-9-]+)[/]?$', 'index.php?fly=$matches[2]','top');
			add_rewrite_rule('('.$arr.')/instant_quote/([a-z0-9-]+)[/]?$', 'index.php?instant_quote=$matches[2]','top');
		}		
	}

	public function add_rewrite_tag()
	{
		add_rewrite_tag('%fly%', '([^&]+)');
		add_rewrite_tag('%instant_quote%', '([^&]+)');
	}

	public function registering_custom_query_var($query_vars)
	{
		$query_vars[] = 'fly';
		$query_vars[] = 'instant_quote';
		return $query_vars;
	}


	public function main_wp_query($query)
	{
		if(isset($query->query_vars['fly']) && $query->is_main_query())
		{				
			$query->set('post_type', 'page');
			$query->set( 'posts_per_page', 1 );
		}
	}

	public function modify_wp_title($title)
	{
		$slug = get_query_var( 'fly' );

		if ($slug) {

			$airport_array = $this->utilities->airport_data_by_slug($slug);

			if (!empty($airport_array) && count($airport_array) > 0) {
				// Country suffix (", Country") if localized name is present
				$country = '';
				if (
					array_key_exists('country_names', $airport_array) &&
					array_key_exists($this->current_language, $airport_array['country_names'])
				) {
					$country = sprintf(', %s', $airport_array['country_names'][$this->current_language]);
				}

				// Base airport label (either "Airport, City" or "Airport + country")
				$airport = ($airport_array['airport'] !== $airport_array['city'])
					? sprintf('%s, %s', $airport_array['airport'], $airport_array['city'])
					: sprintf('%s%s', $airport_array['airport'], $country);

				// Override with localized airport name if available
				if (
					array_key_exists('airport_names', $airport_array) &&
					array_key_exists($this->current_language, $airport_array['airport_names'])
				) {
					$airport = $airport_array['airport_names'][$this->current_language];
				}

				$title = sprintf(
					'%s | %s',
					sprintf(__('Charter Flights to %s', 'dynamicaviation'), $airport),
					$this->site_name
				);
			} else {
				$title = sprintf(
					'%s | %s',
					__('Destination Not Found', 'dynamicaviation'),
					$this->site_name
				);
			}
		}

		return $title;
	}

	public function modify_title($title)
	{
		$slug = get_query_var( 'fly' );

		if (in_the_loop() && $slug) {

			$airport_array = $this->utilities->airport_data_by_slug($slug);
			$not_found = esc_html(__('Destination Not Found', 'dynamicaviation'));

			if (!empty($airport_array) && count($airport_array) > 0) {
				// Base airport label: "Airport, City" if different, else just "Airport"
				$airport = ($airport_array['airport'] !== $airport_array['city'])
					? sprintf('%s, %s', $airport_array['airport'], $airport_array['city'])
					: $airport_array['airport'];

				// Override with localized airport name if available
				if (
					array_key_exists('airport_names', $airport_array) &&
					array_key_exists($this->current_language, $airport_array['airport_names'])
				) {
					$airport = $airport_array['airport_names'][$this->current_language];
				}

				$title = sprintf(
					'%s <span class="linkcolor">%s</span>',
					__('Charter Flights to', 'dynamicaviation'), // intentionally unescaped (matches original)
					esc_html($airport)                            // only the airport is escaped (matches original)
				);
			} else {
				$title = $not_found;
			}
		}

		return $title;
	}


    
	public function modify_content( $content ) {
		// Fast exits
		$slug = get_query_var( 'fly' );

		if ( ! in_the_loop() || !$slug ) {
			return $content;
		}

		// Ensure we actually have airport data; otherwise keep original content
		$airport_data = (array) $this->utilities->airport_data_by_slug($slug);
		if ( empty( $airport_data ) ) {
			return $content;
		}

		// Resolve dynamic pieces once
		$search_form = apply_filters( 'dy_aviation_search_form', false );
		$price_table = apply_filters( 'dy_aviation_price_table', '' );
		$template = $this->template();

		// Build the markup in one go (no repeated concatenation)
		$output = sprintf(
			'<div class="pure-g gutters">
				<div class="pure-u-1 pure-u-sm-1-1 pure-u-md-1-3">
					<aside><div id="quote-sidebar">%s</div></aside>
				</div>
				<div class="pure-u-1 pure-u-sm-1-1 pure-u-md-2-3 height-100 entry-content">
					%s%s
				</div>
			</div>',
			$search_form,
			$price_table,
			$template
		);

		return $output;
	}


	public function get_destination_content($iata)
	{
		$output = '';
		$current_language = current_language();
		$can_user_edit = current_user_can('editor') || current_user_can('administrator');

		$args = array(
			'post_type' => 'destinations',
			'posts_per_page' => 1,
			'post_parent' => 0,
			'lang' => $current_language,
			'meta_key' => array(), // kept as-is to preserve behavior
			'meta_query' => array(
				array(
					'key' => 'aircraft_base_iata',
					'value' => esc_html($iata),
					'compare' => '='
				)
			)
		);

		$wp_query = new WP_Query($args);

		if ($wp_query->have_posts()) {
			while ($wp_query->have_posts()) {
				$wp_query->the_post();

				global $post; // preserved to keep do_blocks($post->post_content) behavior

				$output .= sprintf(
					'<div class="entry-content">%s</div>',
					do_blocks($post->post_content)
				);

				if ($can_user_edit) {
					$output .= sprintf(
						'<p><a class="pure-button" href="%s"><span class="dashicons dashicons-edit"></span> %s</a></p>',
						esc_url(get_edit_post_link($post->ID)),
						esc_html(__('Edit Destination', 'dynamicaviation'))
					);
				}
			}

			wp_reset_postdata();
		} else {
			if ($can_user_edit) {
				$output .= sprintf(
					'<p><a class="pure-button" href="%s"><span class="dashicons dashicons-plus"></span> %s</a></p>',
					esc_url(admin_url('post-new.php?post_type=destinations&iata=' . $iata)),
					esc_html(__('Add Destination', 'dynamicaviation'))
				);
			}
		}

		return $output;
	}


	public function template()
	{
		$airport_array = $this->utilities->airport_data_by_slug();
		$json = $airport_array;
		$iata = $json['iata'];
		$icao = $json['icao'];
		$city = $json['city'];
		$utc = $json['utc'];
		$_geoloc = $json['_geoloc'];
		$airport = $json['airport'];
		$country = $json['country_names'];
		$static_map = $this->utilities->airport_img_url($json);

		if ($iata != null && $icao != null) {
			$airport .= ' ' . __('Airport', 'dynamicaviation');
		}

		if ($this->current_language) {
			if (array_key_exists($this->current_language, $country)) {
				$country_lang = $country[$this->current_language];
			} else {
				$country_lang = $country['en'];
			}
		}

		ob_start();
		?>

			<?php echo $this->get_destination_content($iata); ?>

			<div class="pure-g gutters bottom-20">

				<div class="pure-u-1 pure-u-sm-1-1 pure-u-md-1-3">
					<table class="airport_description pure-table pure-table-striped bottom-20 small width-100">
						<?php if ($iata != null && $icao != null): ?>
							<?php if ($iata != null): ?>
							<tr><td>IATA</td><td><?php echo esc_html($iata); ?></td></tr>
							<?php endif; ?>
							<?php if ($icao != null): ?>
							<tr><td>ICAO</td><td><?php echo esc_html($icao); ?></td></tr>
							<?php endif; ?>
						<?php endif; ?>
						<tbody>
							<tr><td><?php echo esc_html(__('City', 'dynamicaviation')); ?></td><td><?php echo esc_html($city); ?></td></tr>
							<tr><td><?php echo esc_html(__('Country', 'dynamicaviation')); ?></td><td><?php echo esc_html($country_lang); ?></td></tr>
							<tr><td><?php echo esc_html(__('Longitude', 'dynamicaviation')); ?></td> <td><?php echo esc_html(round($_geoloc['lng'], 4)); ?></td></tr>
							<tr><td><?php echo esc_html(__('Latitude', 'dynamicaviation')); ?></td> <td><?php echo esc_html(round($_geoloc['lat'], 4)); ?></td></tr>
							<tr><td><?php echo esc_html(__('Timezone', 'dynamicaviation')); ?></td> <td><?php echo esc_html($utc) . ' (UTC)'; ?></td></tr>
						</tbody>
					</table>
				</div>

				<div class="pure-u-1 pure-u-sm-1-1 pure-u-md-2-3">
					<img class="bottom-20" width="660" height="440" class="img-responsive"
						src="<?php echo esc_url($static_map); ?>"
						alt="<?php echo esc_html(sprintf(__('Charter Flights to %s', 'dynamicaviation'), $airport)) . ', ' . esc_html($city); ?>"
						title="<?php esc_attr_e($airport); ?>"/>
				</div>

			</div>

		<?php
		return ob_get_clean();
	}



	public function meta_tags()
	{
		$slug = get_query_var( 'fly' );

		if ($slug) {
			$airport_array = $this->utilities->airport_data_by_slug($slug);

			if(empty($airport_array)) {
				echo "\r\n" . '<meta name="robots" content="noindex,nofollow" />' . "\r\n";
				return;
			}

			$slug = $this->utilities->sanitize_pathname($slug);

			$output = "\r\n";
			$addressParts = [];

			$airport_name = $airport_array['airport'] ?? '';
			$iata = $airport_array['iata'] ?? '';
			$icao = $airport_array['icao'] ?? '';
			$city = $airport_array['city'] ?? '';
			$country_names = $airport_array['country_names'] ?? [];
			$country_lang = ''; // keep default empty to preserve original behavior when language not set

			// Localized airport name (if available)
			if (!empty($airport_array['airport_names'][$this->current_language])) {
				$airport_name = $airport_array['airport_names'][$this->current_language];
			}

			// Address line
			$addressParts[] = ($iata && $icao) ? sprintf('%s (%s)', $airport_name, $iata) : $airport_name;
			if ($airport_name !== $city) {
				$addressParts[] = $city;
			}

			// Localized country (matches original logic; empty if language not provided)
			if ($this->current_language) {
				$country_lang = $country_names[$this->current_language] ?? ($country_names['en'] ?? '');
			}
			$addressParts[] = $country_lang;

			$address = implode(', ', $addressParts);

			// Meta description
			$output .= sprintf(
				'<meta name="description" content="%s" />' . "\r\n",
				esc_attr(sprintf(
					__('Charter Flights to %s. Jets, planes and helicopter rental services in %s.', 'dynamicaviation'),
					$address,
					$airport_name
				))
			);

			$home_lang = home_lang();

			// Canonical
			$output .= sprintf(
				'<link rel="canonical" href="%s" />' . "\r\n",
				esc_url(normalize_url("{$home_lang}/fly/{$slug}"))
			);

			echo $output;
			return;
		}
	}


	public function ld_json($arr)
	{
		$slug = get_query_var( 'fly' );

		if( $slug )
		{
			$airport_array = $this->utilities->airport_data_by_slug($slug);

			if(!empty($airport_array))
			{
				
				[
					'city' => $city, 
					'iata' => $iata, 
					'city' => $city, 
					'airport' => $airport,
					'country_names' => $country_names
				] = $airport_array;				
				
				$lang = $this->current_language;
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
				
				
				$args = array(
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
				
				$wp_query = new WP_Query( $args );

				if ($wp_query->have_posts())
				{	
					while ( $wp_query->have_posts() )
					{
						$wp_query->the_post();
						$raw_table_price = aviation_field('aircraft_rates');

						if(empty($raw_table_price)) continue;

						$table_price = html_entity_decode($raw_table_price);
						$table_price = json_decode($table_price, true);

						if(!is_array($table_price) || !array_key_exists('aircraft_rates_table', $table_price)) continue;

							$table_price = $table_price['aircraft_rates_table'];

							if(!is_array($table_price) || count($table_price) === 0) continue;

							for($x = 0; $x < count($table_price); $x++)
							{
								$row = (array) $table_price[$x];
								$origin_iata = (string) $row[0];
								$destination_iata = (string) $row[1];

								if($origin_iata === '' || $destination_iata === '') continue;
								
								if(($iata == $origin_iata || $iata == $destination_iata))
								{
									$prices[] = (float) $row[3];
								}
							}
					}

					wp_reset_postdata();				
				}

				if(count($prices) > 0)
				{

					$raw_url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
					$image_url = esc_url($this->utilities->airport_img_url($airport_array));

					$arr = array(
						'@context' => 'https://schema.org/',
						'@type' => 'Product',
						'brand' => array(
							'@type' => 'Brand',
							'name' => esc_html($this->site_name)
						),
						'category' => esc_html(__('Charter Flights', 'dynamicaviation')),
						'url' => esc_url($raw_url),
						'name' => esc_html(__('Charter Flight', 'dynamicaviation').' '.$airport),
						'description' => esc_html(__('Charter Flight', 'dynamicaviation').' '.$address.'. '.__('Airplanes and helicopter rides in', 'dynamicaviation').' '.$airport.', '.$city),
						'image' => [[
							'@type' => "ImageObject",
							'url' => $image_url,
							'contentUrl' => $image_url,
							'width' => 660,
							'height' => 440
						]],
						'sku' => md5($iata)
					);


					$offers = array(
						'priceCurrency' => 'USD',
						'priceValidUntil' => esc_html(date('Y-m-d', strtotime('+1 year'))),
						'availability' => 'https://schema.org/InStock',
						'url' => esc_url($raw_url),
						'offerCount' => count($prices)
					);
					
					if(count($prices) == 1)
					{
						$offers['@type'] = 'Offer';
						$offers['@type'] = 'Offer';
						$offers['price'] = money($prices[0], '.', '');					
					}
					else
					{
						$offers['@type'] = 'AggregateOffer';
						$offers['lowPrice'] = money(min($prices), '.', '');
						$offers['highPrice'] = money(max($prices), '.', '');					
					}
					
					$arr['offers'] = $offers;				
				}				
			}
		}
		
		return $arr;
	}

	public function locate_template($template)
	{
		if(get_query_var( 'fly' ))
		{
			$new_template = locate_template( array( 'page.php' ) );
			return $new_template;			
		}
		return $template;
	}

    public function load_scripts()
    {
        global $dy_aviation_load_algolia;

		if(!isset($dy_aviation_load_algolia))
        {
            if(get_query_var( 'fly' ))
            {
                $GLOBALS['dy_aviation_load_algolia'] = true;
				$GLOBALS['dy_aviation_load_mapbox'] = true;
				$GLOBALS['dy_load_picker_scripts'] = true;
            }
        }
    }

	public function pll_translation_url( $url, $slug ) {

		global $polylang;

		if(!isset($polylang)) return $url;

		$path = get_query_var( 'fly' );
		if ( empty( $path ) ) {
			return $url;
		}

		// Build "fly/<path>" respecting WP's trailing slash setting
		$base = 'fly/' . ltrim( $path, '/' );
		$prefix = ( $slug === default_language() || $slug === '' ) ? '' : trailingslashit( $slug );
		$built = $prefix . $base;
		$built = user_trailingslashit( $built ); // match site permalink structure

		$url = home_url( $built );
		return $url;
	}



}


?>