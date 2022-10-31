<?php 


class Dynamic_Aviation_Destinations {
    
    public function __construct($plugin_name, $version, $utilities)
    {
        $this->utilities = $utilities;
		$this->plugin_name = $plugin_name;
        $this->pathname = 'fly';
        add_action('init', array(&$this, 'init'));

    
        //filters custom wordpress outputs
        add_action('pre_get_posts', array(&$this, 'main_wp_query'), 100);
		add_filter( 'pre_get_document_title', array(&$this, 'modify_wp_title'), 100);		
		add_filter('wp_title', array(&$this, 'modify_wp_title'), 100);
        add_filter('the_title', array(&$this, 'modify_title'));
        add_filter('the_content', array(&$this, 'modify_content'));


        //meta tags
        add_action('wp_head', array(&$this, 'meta_tags'));

        //minimalizr theme
        add_filter('minimal_ld_json', array(&$this, 'ld_json'), 100);
        add_filter('template_include', array(&$this, 'locate_template'), 100 );


        //enqueue logic in public.php
        add_action( 'parse_query', array( &$this, 'load_algolia_scripts' ), 100);
        add_action( 'parse_query', array( &$this, 'load_mapbox_scripts' ), 100);
    }

    public function init()
    {
        $this->site_name = get_bloginfo('name');
        $this->current_language = current_language();
        $this->home_lang = home_lang();
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
		if(get_query_var('fly'))
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
				$output .= $this->template();
			}
			
			return $output;
		}		

		return $content;
	}

    public function get_destination_content($iata)
    {
        $output = '';
        $current_language = current_language();
        $can_user_edit = (current_user_can('editor') || current_user_can('administrator')) ? true : false;
        
        $args = array(
            'post_type' => 'destinations',
            'posts_per_page' => 1,
            'post_parent' => 0,
            'lang' => $current_language,
            'meta_key' => array(),
            'meta_query' => array(
                array(
                    'key' => 'aircraft_base_iata',
                    'value' => esc_html($iata),
                    'compare' => '='
                )
            )
        );

        $wp_query = new WP_Query( $args );

        if ( $wp_query->have_posts() )
        {
            while ( $wp_query->have_posts() )
            {
                $wp_query->the_post();
                
                global $post;

                $output .= '<div class="entry-content">'.do_blocks($post->post_content).'</div>';

                if( $can_user_edit )
                {
                    $output .= '<p><a class="pure-button" href="'.esc_url(get_edit_post_link($post->ID)).'"><i class="fas fa-pencil-alt" ></i> '.esc_html(__('Edit Destination', 'dynamicaviation')).'</a></p>';
                }
            }

            wp_reset_postdata();
        }
        else
        {
            if($can_user_edit)
            {
                $output .= '<p><a class="pure-button" href="'.esc_url(admin_url('post-new.php?post_type=destinations&iata='.$iata)).'"><i class="fas fa-plus" ></i> '.esc_html(__('Add Destination', 'dynamicaviation')).'</a></p>';
            }
        }

        return $output;

    }

    public function template()
    {

        $airport_array = $this->utilities->airport_data();
        $json = $airport_array;
        $iata  = $json['iata'];
        $icao = $json['icao'];
        $city = $json['city'];
        $utc = $json['utc'];
        $_geoloc = $json['_geoloc'];
        $airport = $json['airport'];
        $country_name = $json['country_names'];
        $static_map = $this->utilities->airport_img_url($json);
        
        if($iata != null && $icao != null)
        {
            $airport .= " ".__('Airport', 'dynamicaviation');
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


        ob_start(); 
        ?>

            <div class="pure-g gutters bottom-20">

                <div class="pure-u-1 pure-u-sm-1-1 pure-u-md-1-3">
                    <table class="airport_description pure-table pure-table-striped bottom-20">
                        <?php if($iata != null && $icao != null): ?>
                            <?php if($iata != null): ?>
                            <tr><td>IATA</td><td><?php esc_html_e($iata); ?></td></tr>
                            <?php endif;?>
                            <?php if($icao != null): ?>
                            <tr><td>ICAO</td><td><?php esc_html_e($icao); ?></td></tr>
                            <?php endif; ?>
                        <?php endif; ?>	
                        <tbody>
                            <tr><td><?php echo (esc_html__('City', 'dynamicaviation')); ?></td><td><?php esc_html_e($city); ?></td></tr>
                            <tr><td><?php echo (esc_html__('Country', 'dynamicaviation')); ?></td><td><?php esc_html_e($country_lang); ?></td></tr>	
                            <tr><td><?php echo (esc_html__('Longitude', 'dynamicaviation')); ?></td> <td><?php esc_html_e(round($_geoloc['lng'], 4)); ?></td></tr>
                            <tr><td><?php echo (esc_html__('Latitude', 'dynamicaviation')); ?></td> <td><?php esc_html_e(round($_geoloc['lat'], 4)); ?></td></tr>	
                            <tr><td><?php echo (esc_html__('Timezone', 'dynamicaviation')); ?></td> <td><?php esc_html_e($utc).' (UTC)'; ?></td></tr>
                        </tbody>
                    </table>
                </div>
                <div class="pure-u-1 pure-u-sm-1-1 pure-u-md-2-3">
                    <div class="entry-content">
                        <img class="bottom-20" width="660" height="440" class="img-responsive" src="<?php echo esc_url($static_map); ?>" alt="<?php esc_html_e($airport).", ".esc_html($city); ?>" title="<?php esc_attr_e($airport); ?>"/>
                        <?php echo $this->get_destination_content($iata); ?>
                    </div>
                </div>
            </div>

            <hr/>

            <h4><span class="linkcolor"><?php echo (esc_html__('Quote Charter Flight to', 'dynamicaviation'));?></span> <?php esc_html_e($airport); ?><span class="linkcolor">, <?php esc_html_e($city);?></span></h4>
            <div id="quote-sidebar">
                <?php echo apply_filters('dy_aviation_search_form', ''); ?>
            </div>


        <?php
        
        $content = ob_get_contents();
        ob_end_clean();	
		
		return $content;
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
				$output .= '<link rel="canonical" href="'.esc_url($this->home_lang.'fly/'.$this->utilities->sanitize_pathname($airport_array['airport'])).'" />';
				$output .= "\r\n";

				echo $output;			
			}
		}
	}


	public function ld_json($arr)
	{
		if(get_query_var($this->pathname))
		{
			$airport_array = $this->utilities->airport_data();

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
										$prices[] = floatval($tp[3]);
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
							'name' => esc_html($this->site_name)
						),
						'category' => esc_html(__('Charter Flights', 'dynamicaviation')),
						'name' => esc_html(__('Private Charter Flight', 'dynamicaviation').' '.$airport),
						'description' => esc_html(__('Private Charter Flight', 'dynamicaviation').' '.$address.'. '.__('Airplanes and helicopter rides in', 'dynamicaviation').' '.$airport.', '.$city),
						'image' => esc_url($this->utilities->airport_img_url($airport_array)),
						'sku' => md5($iata),
						'gtin8' => substr(md5($iata), 0, 8)
					);

					$raw_url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";

					$offers = array(
						'priceCurrency' => 'USD',
						'priceValidUntil' => esc_html(date('Y-m-d', strtotime('+1 year'))),
						'availability' => 'http://schema.org/InStock',
						'url' => esc_url($raw_url)
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

	public function locate_template($template)
	{
		if(get_query_var( 'fly' ))
		{
			$new_template = locate_template( array( 'page.php' ) );
			return $new_template;			
		}
		return $template;
	}

    public function load_algolia_scripts()
    {
        global $dy_aviation_load_algolia;

		if(!isset($dy_aviation_load_algolia))
        {
            if(get_query_var($this->pathname))
            {
                $GLOBALS['dy_aviation_load_algolia'] = true;
            }
        }
    }

    public function load_mapbox_scripts()
    {
        global $dy_aviation_load_mapbox;

		if(!isset($dy_aviation_load_mapbox))
        {
            if(get_query_var($this->pathname))
            {
                $GLOBALS['dy_aviation_load_mapbox'] = true;
            }
        }
    }

}


?>