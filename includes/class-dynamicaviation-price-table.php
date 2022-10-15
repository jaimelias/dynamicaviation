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
		global $airport_array;

		if($iata === '')
		{
			if(is_singular('aircrafts'))
			{
				$iata = aviation_field('aircraft_base_iata');
				$query_args['p'] = get_the_ID();
			}
			else 
			{
				$iata = $airport_array['iata'];
			}
		}
		
		$args = array(
			'post_type' => 'aircrafts',
			'posts_per_page' => 200, 
			'post_parent' => 0,
			'meta_key' => 'aircraft_price_per_hour',
			'orderby' => 'meta_value_num',
			'order' => 'ASC',
		);

		if(is_singular('aircrafts'))
		{
			$args['p'] = get_the_ID();
		}

		$wp_query = new WP_Query($args);
		
		if ( $wp_query->have_posts() )
		{

			$count = 0;
			$algolia_full = $this->algolia_full();
			$routes = array();

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
				if(!is_array($algolia_full))
				{
					return __('Database is not or invalid.', 'dynamicaviation');
				}
				else
				{
					$table_price = $table_price['aircraft_rates_table'];
				}

				$is_commercial = (aviation_field( 'aircraft_commercial') == 1) ? true : false;
				

					
				for($x = 0; $x < count($table_price); $x++)
				{
					$row = '';
					$origin_iata = $table_price[$x][0];
					$destination_iata = $table_price[$x][1];

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


						for($d = 0; $d < count($algolia_full); $d++)
						{
							if($destination_iata === $algolia_full[$d]['iata'])
							{
								$destination_airport = $algolia_full[$d]['airport'];
								$destination_city = $algolia_full[$d]['city'];
								$destination_country_code = $algolia_full[$d]['country_code'];
							}
						}


						for($y = 0; $y < count($algolia_full); $y++)
						{
							if($origin_iata == $algolia_full[$y]['iata'])
							{
								$origin_airport = $algolia_full[$y]['airport'];
								$origin_city = $algolia_full[$y]['city'];
								$origin_country_code = $algolia_full[$y]['country_code'];
								$route_name = $origin_iata.'_'.$destination_iata;
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

						$fees = $table_price[$x][4];
						$seats = $table_price[$x][6];
						$weight_pounds = $table_price[$x][7];
						$weight_kg = intval($weight_pounds)*0.453592;
						$weight_allowed = esc_html($weight_pounds.' '.__('pounds', 'dynamicaviation').' | '.$weight_kg.__('kg', 'dynamicaviation'));
						$aircraft_type = $this->utilities->aircraft_type(aviation_field( 'aircraft_type' ));
						
						$route = __('Private Charter Flight', 'dynamicaviation').' '.$aircraft_type.' '.$post->post_title.' '.__('from', 'dynamicaviation').' '.$origin_airport.', '.$origin_city.' ('.$origin_iata.') '.__('to', 'dynamicaviation').' '.$destination_airport.', '.$destination_city.' ('.$destination_iata.')';
						
						$row .= '<tr data-aircraft-type="'.esc_html(aviation_field( 'aircraft_type' )).'" data-iata="'.esc_html($origin_iata).'" title="'.esc_html($route).'">';
						
						if(!is_singular('aircrafts'))
						{
							$row .= ($is_commercial) 
							? '<td><strong>'.esc_html(__('Commercial Flight', 'dynamicaviation')).'</strong></td>'
							: '<td><a class="strong" href="'.esc_url($aircraft_url).'/">'.esc_html($post->post_title).'</a> - <small>'.esc_html($aircraft_type).'</small><br/><i class="fas fa-male" ></i> '.esc_html($seats).' <small>('.$weight_allowed.')</small></td>';
						}
						
						$row .= '<td><small class="text-muted">('.esc_html($origin_iata).')</small> <strong>'.esc_html($origin_city.', '.$origin_country_code).'</strong><br/>'.esc_html($origin_airport).'</td>';

						$row .= '<td><strong>'.esc_html('$'.number_format($table_price[$x][3], 2, '.', ',')).'</strong><br/><span class="text-muted">';

						$row .= ($is_commercial) ? esc_html(__('Per Person', 'dynamicaviation')) : esc_html(__('Charter Flight', 'dynamicaviation'));

						$row .= '</span>';
						
						if(floatval($fees) > 0)
						{
							$row .= '<br/><span class="text-muted">';
							$row .= esc_html(__('Fees per pers.', 'dynamicaviation').' '.'$'.number_format($fees, 2, '.', ','));
							$row .= '</span>';
						}

						$row .= '<br/><span class="small text-muted"><i class="fas fa-clock" ></i> '.esc_html($this->utilities->convertNumberToTime($table_price[$x][2])).'</span>';
						
						$row .= '</td></tr>';

						$routes[$route_name]['rows'] .= $row;

						$count++;	
					}
				}
			}
			
			wp_reset_postdata();
		}	

		if($count > 0)
		{
			foreach($routes as $k => $v)
			{	
				$label = (is_singular('aircrafts')) ? __('Destination', 'dynamicaviation') : __('Origin', 'dynamicaviation');
				$origin = $v['origin'];
				$destination = $v['destination'];
				$label_from = sprintf(__('%s (%s), %s', 'dynamicaviation'), $origin['airport'], $origin['iata'], $origin['city']);
				$label_to = sprintf(__('to %s (%s), %s', 'dynamicaviation'), $destination['airport'], $destination['iata'], $destination['city']);
				$table = '<div itemscope itemtype="http://schema.org/Table">';
				$table .= '<h4 itemprop="about"><span class="text-muted">'.esc_html($label_from).'</span> '.esc_html($label_to).'</h4>';
				$table .= '<table class="dy_table text-center small pure-table pure-table-bordered bottom-40"><thead><tr>';

				if(!is_singular('aircrafts'))
				{
					$table .= '<th>'.esc_html(__('Flights', 'dynamicaviation')).'</th>';
				}

				$table .= '<th>'.esc_html($label).'</th>';
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

	public static function algolia_full()
	{
		$output = array();
		$which_var = 'dynamicaviation_algolia_full';
		global $$which_var;

		if(isset($$which_var))
		{
			return $$which_var;
		}
		else
		{
			$query_param = 'browse?cursor=';
			$algolia_token = get_option('algolia_token');
			$algolia_index = get_option('algolia_index');
			$algolia_id = get_option('algolia_id');
			$headers = array('X-Algolia-API-Key: '.$algolia_token, 'X-Algolia-Application-Id: '.$algolia_id);
			$url = 'https://'.$algolia_id.'-dsn.algolia.net/1/indexes/'.$algolia_index.'/'.$query_param;
			$curl_arr = array(
				CURLOPT_RETURNTRANSFER => 1,
				CURLOPT_REFERER => esc_url(home_url()),
				CURLOPT_URL => esc_url($url)
			);

			$curl = curl_init();
			curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);	
			curl_setopt_array($curl, $curl_arr);
			$resp = curl_exec($curl);
			$resp = json_decode($resp, true);
			$output = $resp['hits'];
			$GLOBALS[$which_var] = $output;
			return $output;
		}
	}
}


?>