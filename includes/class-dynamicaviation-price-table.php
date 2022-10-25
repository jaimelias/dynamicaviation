<?php 


class Dynamic_Aviation_Price_Table {
    
    
    public function __construct($utilities)
    {
		$this->utilities = $utilities;
        $this->init();
    }

    public function init()
    {
        add_filter('dy_aviation_price_table', array(&$this, 'table'));
    }

	public function table($iata = '')
	{
		$output = '';
		$airport_array = $this->utilities->airport_data();
		$is_aircraft_page = is_singular('aircrafts');
		$is_destination_page = get_query_var('fly');

		if($iata === '' && !$is_aircraft_page)
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
				$aircraft_url = home_lang().esc_html($post->post_type).'/'.esc_html($post->post_name);
				
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

				$transport = aviation_field( 'aircraft_commercial');
				$is_commercial = (aviation_field( 'aircraft_commercial') == 1) ? true : false;
					
				for($x = 0; $x < count($table_price); $x++)
				{
					$row = '';

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
						$route_name = (!$is_destination_page) 
							? $transport.'_'.$origin_iata 
							: $transport.'_'.$origin_iata.'_'.$destination_iata;

						$this_transport_title = $this->utilities->transport_title_plural($post->ID);

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
								'rows' => null,
								'transport_title' => $this_transport_title
							);
						}

						$fees = $table_price[$x][4];
						$seats = $table_price[$x][6];
						$weight_pounds = $table_price[$x][7];
						$weight_kg = intval(floatval($weight_pounds)*0.453592);
						$weight_allowed = $weight_pounds.' '.__('pounds', 'dynamicaviation').' | '.$weight_kg.__('kg', 'dynamicaviation');
						$aircraft_type = $this->utilities->aircraft_type(aviation_field( 'aircraft_type' ));
						
						$route = $this_transport_title . ' ' . $aircraft_type .' '. $post->post_title . ' ' . __('from', 'dynamicaviation').' '.$origin_airport.', '.$origin_city.' ('.$origin_iata.') '.__('to', 'dynamicaviation').' '.$destination_airport.', '.$destination_city.' ('.$destination_iata.')';
						
						$row .= '<tr data-aircraft-type="'.esc_html(aviation_field( 'aircraft_type' )).'" data-iata="'.esc_html($origin_iata).'" title="'.esc_html($route).'">';
						
						if(!$is_aircraft_page)
						{
							$row .= ($is_commercial) 
							? '<td><strong>'.esc_html(__('Commercial Flight', 'dynamicaviation')).'</strong></td>'
							: '<td><a class="strong" href="'.esc_url($aircraft_url).'/">'.esc_html($post->post_title).'</a><br/><small>'.esc_html($aircraft_type).'</small></td>';
						}
						else
						{
							$destination_url = home_lang() . 'fly/' . $this->utilities->sanitize_pathname($destination_slug);
							$destination_link = '<a href="'.esc_url($destination_url).'" title="'.esc_attr(sprintf(__('Flights to %s', 'dynamicaviation'), $destination_airport, $destination_city)).'">'.esc_html($destination_airport).'</a>';
							$row .= '<td><strong>'.$destination_link.'</strong><br/><small class="text-muted">('.esc_html($destination_iata).')</small>, <span>'.esc_html($destination_city.', '.$destination_country_code).'</span></td>';
						}
						
						
						if(!wp_is_mobile())
						{
							$row .= '<td><strong><i class="fas fa-male" ></i> '.esc_html($seats).' </strong><br/><small>'.esc_html($weight_allowed).'</small></td>';

							$row .= '<td><i class="fas fa-clock" ></i> '.esc_html($this->utilities->convertNumberToTime($table_price[$x][2])).'</td>';
						}

						$row .= '<td><strong>'.esc_html('$'.number_format($table_price[$x][3], 2, '.', ',')).'</strong><br/><span class="text-muted">';

						$row .= ($is_commercial) ? esc_html(__('Per Person', 'dynamicaviation')) : esc_html(__('Charter Flight', 'dynamicaviation'));

						$row .= '</span>';
						
						if(floatval($fees) > 0)
						{
							$row .= '<br/><span class="text-muted">' . esc_html(__('Fees per pers.', 'dynamicaviation').' '.'$'.number_format($fees, 2, '.', ',')) . '</span>';
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
				$transport_title = $v['transport_title'];
				$label_origin = sprintf(__('%s (%s), %s', 'dynamicaviation'), $origin['airport'], $origin['iata'], $origin['city']);
				$label_destination = sprintf(__('to %s (%s), %s', 'dynamicaviation'), $destination['airport'], $destination['iata'], $destination['city']);
				$table = '<div itemscope itemtype="http://schema.org/Table">';
				
				if(!$is_aircraft_page)
				{
					$table .= '<h4 itemprop="about"><span class="light">'.esc_html($transport_title).'</span> <span class="text-muted">'.esc_html($label_origin).'</span> <span>'.esc_html($label_destination).'</span></h4>';
				}
				else
				{
					$table .= '<h4 itemprop="about">'.esc_html(sprintf(__('%s from %s', 'dynamicaviation'), $transport_title, $label_origin)).'</h4>';
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

				

				if(!wp_is_mobile())
				{
					$table .= '<th>'.esc_html(__('Passengers', 'dynamicaviation')).'</th>';
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