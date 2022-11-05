<?php 


class Dynamic_Aviation_Price_Table {
    
    
    public function __construct($utilities)
    {
		$this->utilities = $utilities;
		add_filter('init', array(&$this, 'init'));
        add_filter('dy_aviation_price_table', array(&$this, 'table'), 1, 1);
    }

    public function init()
    {
        $this->home_lang = home_lang();
		$this->is_mobile = wp_is_mobile();
    }

	public function table($iata = '')
	{
		$output = '';
		$airport_array = $this->utilities->airport_data();
		$is_aircraft_page = is_singular('aircrafts');
		$is_destination_page = get_query_var('fly');

		if(!$iata && !$is_aircraft_page)
		{
			$iata = $airport_array['iata'];
		}
		
		$args = array(
			'post_type' => 'aircrafts',
			'posts_per_page' => 200, 
			'post_parent' => 0,
			'meta_key' => 'aircraft_price_per_hour',
			'orderby' => 'meta_value_num',
			'order' => 'ASC',
		);

		if($is_aircraft_page)
		{
			$args['p'] = get_the_ID();
		}

		$wp_query = new WP_Query($args);
		
		if ( $wp_query->have_posts() )
		{

			$count = 0;
			$routes = array();
			$current_language = current_language();
			$all_airports_data = $this->utilities->all_airports_data();

			while ($wp_query->have_posts() )
			{
				$wp_query->the_post();
				global $post;

				$base_iata = aviation_field('aircraft_base_iata');
				$table_price = json_decode(html_entity_decode(aviation_field('aircraft_rates')), true);
				$aircraft_url = $this->home_lang.$post->post_type.'/'.$post->post_name;
				$base = aviation_field( 'aircraft_base_iata', $post->ID);
				
				if(!array_key_exists('aircraft_rates_table', $table_price))
				{
					return __('Local price table is null or invalid.', 'dynamicaviation');
				}
				if(!is_array($all_airports_data))
				{
					return __('Database is not or invalid.', 'dynamicaviation');
				}
				else
				{
					$table_price = $table_price['aircraft_rates_table'];
				}
					
				for($x = 0; $x < count($table_price); $x++)
				{
					$row = '';
					$price = 0;
					$fees = 0;
					$origin_iata = $table_price[$x][0];
					$destination_iata = $table_price[$x][1];
					$destination_slug = '';

					if($iata)
					{
						$origin_iata = ($iata === $origin_iata) ? $table_price[$x][1] : $origin_iata;
						$destination_iata = ($iata === $destination_iata) ? $destination_iata : $table_price[$x][0];
					}


					$show_all = true;

					if($iata)
					{
						if(!in_array($iata, array($origin_iata, $destination_iata)))
						{
							$show_all = false;
						}
					}

					if($show_all && $origin_iata !== $destination_iata && !empty($origin_iata) && !empty($destination_iata))
					{

						$itinerary = array();
						$chart = array();
						
						$request_routes = array($origin_iata, $destination_iata);
						sort($request_routes);
				
						$diff = array_diff($request_routes, array($base, $base));
						$count_diff = count($diff);
				
						if($count_diff === 1)
						{
							$itinerary = array(
								array($origin_iata, $destination_iata)
							);
				
							//option #1
							$chart = $this->utilities->get_rates_from_itinerary($itinerary, $table_price);
						}
						elseif($count_diff === 2)
						{
							$itinerary = array(
								array($base, $origin_iata),
								array($origin_iata, $destination_iata),
								array($destination_iata, $base)
							);
				
							//option #2
							$chart = $this->utilities->get_rates_from_itinerary($itinerary, $table_price);
						}

						//sum fees and prices
						for($c = 0; $c < count($chart); $c++)
						{
							$price += floatval($chart[$c][3]);
							$fees += floatval($chart[$c][4]);
						}



						$route_name = (!$is_destination_page) 
							? $origin_iata 
							: $origin_iata.'_'.$destination_iata;

						for($d = 0; $d < count($all_airports_data); $d++)
						{
							if($destination_iata === $all_airports_data[$d]['iata'])
							{
								$destination_slug = $all_airports_data[$d]['airport'];
								$destination_airport = (array_key_exists('airport_names', $all_airports_data[$d])) 
									? (array_key_exists($current_language, $all_airports_data[$d]['airport_names']))
									? $all_airports_data[$d]['airport_names'][$current_language]
									: $all_airports_data[$d]['airport']
									: $all_airports_data[$d]['airport'];
								$destination_city = $all_airports_data[$d]['city'];
								$destination_country_code = $all_airports_data[$d]['country_code'];
							}
						}


						for($y = 0; $y < count($all_airports_data); $y++)
						{
							if($origin_iata == $all_airports_data[$y]['iata'])
							{
								$origin_airport = (array_key_exists('airport_names', $all_airports_data[$y])) 
									? (array_key_exists($current_language, $all_airports_data[$y]['airport_names']))
									? $all_airports_data[$y]['airport_names'][$current_language]
									: $all_airports_data[$y]['airport']
									: $all_airports_data[$y]['airport'];
								$origin_city = $all_airports_data[$y]['city'];
								$origin_country_code = $all_airports_data[$y]['country_code'];
							}
						}

						if(!array_key_exists($route_name, $routes))
						{
							$routes[$route_name] = array(
								'origin' => array(
									'iata' => $origin_iata,
									'airport' => $origin_airport,
									'city' => $origin_city,
									'country_code' => $origin_country_code
								),
								'destination' => array(
									'iata' => $destination_iata,
									'airport' => $destination_airport,
									'city' => $destination_city,
									'country_code' => $destination_country_code
								),
								'rows' => null
							);
						}

						
						$seats = $table_price[$x][6];
						$weight_pounds = $table_price[$x][7];
						$weight_kg = intval(floatval($weight_pounds)*0.453592);
						$weight_allowed = $weight_pounds.' '.__('pounds', 'dynamicaviation').' | '.$weight_kg.__('kg', 'dynamicaviation');
						$aircraft_type = $this->utilities->aircraft_type(aviation_field( 'aircraft_type' ));
						
						$route = __('Charter Flights', 'dynamicaviation') . ' ' . $aircraft_type .' '. $post->post_title . ' ' . __('from', 'dynamicaviation').' '.$origin_airport.', '.$origin_city.' ('.$origin_iata.') '.__('to', 'dynamicaviation').' '.$destination_airport.', '.$destination_city.' ('.$destination_iata.')';
						
						$row .= '<tr data-aircraft-type="'.esc_html(aviation_field( 'aircraft_type' )).'" data-iata="'.esc_html($origin_iata).'" title="'.esc_html($route).'">';
						
						if(!$is_aircraft_page)
						{
							$row .= '<td><a class="strong" href="'.esc_url($aircraft_url).'/">'.esc_html($post->post_title).'</a><br/><small>'.esc_html($aircraft_type).'</small></td>';
						}
						else
						{
							$destination_url = $this->home_lang . 'fly/' . $this->utilities->sanitize_pathname($destination_slug);
							$destination_link = '<a href="'.esc_url($destination_url).'" title="'.esc_attr(sprintf(__('Flights to %s', 'dynamicaviation'), $destination_airport, $destination_city)).'">'.esc_html($destination_airport).'</a>';
							$row .= '<td><strong>'.$destination_link.'</strong><br/><small class="text-muted">('.esc_html($destination_iata).')</small>, <span>'.esc_html($destination_city.', '.$destination_country_code).'</span></td>';
						}
						

						if(!$is_aircraft_page)
						{
							$row .= '<td><strong><i class="fas fa-male" ></i> '.esc_html($seats).' </strong><br/><small>'.esc_html($weight_allowed).'</small></td>';
						}
						
						if(!$this->is_mobile)
						{

							$row .= '<td><i class="fas fa-clock" ></i> '.esc_html($this->utilities->convertNumberToTime($table_price[$x][2])).'</td>';
						}

						$row .= '<td><strong>'.esc_html('$'.$this->utilities->currency_format($price)).'</strong><br/><span class="text-muted">';

						$row .= __('Charter Flight', 'dynamicaviation');

						$row .= '</span>';
						
						if(floatval($fees) > 0)
						{
							$row .= '<br/><span class="text-muted">' . esc_html(__('Fees per pers.', 'dynamicaviation').' '.'$'.$this->utilities->currency_format($fees)) . '</span>';
						}
						
						$row .= '</td></tr>';

						$routes[$route_name]['rows'] .= $row;

						$count++;	
					}
				}
			}
			
			wp_reset_postdata();
		}

		//write_log($routes);

		if($count > 0)
		{
			foreach($routes as $k => $v)
			{	
				$origin = $v['origin'];
				$destination = $v['destination'];

				if(strlen($origin['iata']) === 3)
				{
					$label_origin = sprintf(__('%s (%s)', 'dynamicaviation'), $origin['airport'], $origin['iata']);
				}
				else
				{
					$label_origin = $origin['airport'];
				}

				if($origin['airport'] !== $origin['city'])
				{
					$label_origin .= ', ' . $origin['city'];
				}
				
				if(strlen($destination['iata']) === 3)
				{
					$label_destination = sprintf(__('to %s (%s)', 'dynamicaviation'), $destination['airport'], $destination['iata']);
				}
				else
				{
					$label_destination = sprintf(__('to %s', 'dynamicaviation'), $destination['airport']);
				}

				if($destination['airport'] !== $destination['city'])
				{
					$label_destination .= ', ' . $destination['city'];
				}

				
				$table = '<div itemscope itemtype="http://schema.org/Table">';
				
				if(!$is_aircraft_page)
				{
					$table .= '<h4 itemprop="about"><span class="light">'.esc_html(__('Charter Flights', 'dynamicaviation')).'</span> <span class="text-muted">'.esc_html($label_origin).'</span> <span>'.esc_html($label_destination).'</span></h4>';
				}
				else
				{
					$table .= '<h4 itemprop="about">'.esc_html(sprintf(__('%s from %s', 'dynamicaviation'), __('Charter Flights', 'dynamicaviation'), $label_origin)).'</h4>';
				}

				$table .= '<table class="dy_table text-center small pure-table pure-table-bordered bottom-40"><thead><tr>';

				if(!$is_aircraft_page)
				{
					$table .= '<th>'.esc_html(__('Flights', 'dynamicaviation')).'</th>';
				}
				else
				{
					$table .= '<th>'.esc_html(__('Destination', 'dynamicaviation')).'</th>';
				}

				if(!$is_aircraft_page)
				{
					$table .= '<th>'.esc_html(__('Passengers', 'dynamicaviation')).'</th>';
				}

				if(!$this->is_mobile)
				{
					
					$table .= '<th>'.esc_html(__('Duration', 'dynamicaviation')).'</th>';
				}
				
				$table .= '<th>'.esc_html(__('One Way', 'dynamicaviation')).'</th>';
				$table .= '</tr></thead><tbody>';
				$table .= $v['rows'];
				$table .= '</tbody></table><hr/>';			
				$table .= '</div>';
				$output .= $table;
			}

			return $output;
		}	
	}

}


?>