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


		if(!$iata)
		{
			return __('IATA not found', 'dynamicaviation');
		}

		$output = '';
		$filter = null;
		$aircraft_count = 0;
		$table_row = null;
		$iata_list = array();
		
		$query_args = array(
			'post_type' => 'aircrafts',
			'posts_per_page' => 200, 
			'post_parent' => 0, 
			'meta_key' => 'aircraft_commercial', 
			'orderby' => 'meta_value', 
			'order' => 'ASC'
		);
		


		$wp_query = new WP_Query( $query_args );
		
		//aircraft
		if ( $wp_query->have_posts() )
		{
			
			$algolia_full = $this->algolia_full();
			
			while ($wp_query->have_posts() )
			{
				$wp_query->the_post();
				global $post;
				$base_iata = aviation_field( 'aircraft_base_iata' );
				$table_price = aviation_field('aircraft_rates');
				$table_price = json_decode(html_entity_decode($table_price), true);
				
				if(!array_key_exists('aircraft_rates_table', $table_price))
				{
					return;
				}
				else
				{
					$table_price = $table_price['aircraft_rates_table'];
				}

				$is_commercial = (intval(aviation_field( 'aircraft_commercial')) === 1) ? true : false;
				
				for($x = 0; $x < count($algolia_full); $x++)
				{
					if($iata == $algolia_full[$x]['iata'])
					{
						$destination_airport = $algolia_full[$x]['airport'];
						$destination_city = $algolia_full[$x]['city'];
						$destination_country_code = $algolia_full[$x]['country_code'];
					}
				}
				
				$aircraft_url = home_lang().esc_html($post->post_type).'/'.esc_html($post->post_name);
				
				$limit = 5;
				
				for($x = 0; $x < count($table_price); $x++)
				{
					
					$origin_iata = $table_price[$x][1];
					
					if($iata == $table_price[$x][1])
					{
						$origin_iata = $table_price[$x][0];
					}
					
					if(($iata == $table_price[$x][0] || $iata == $table_price[$x][1]) && ($table_price[$x][0] != '' || $table_price[$x][1] != '') && is_array($algolia_full))
					{
						
						for($y = 0; $y < count($algolia_full); $y++)
						{
							if($origin_iata == $algolia_full[$y]['iata'])
							{
								$origin_airport = $algolia_full[$y]['airport'];
								$origin_city = $algolia_full[$y]['city'];
								$origin_country_code = $algolia_full[$y]['country_code'];
							}
						}

						$fees = $table_price[$x][4];
						$seats = $table_price[$x][6];
						$weight_pounds = $table_price[$x][7];
						$weight_kg = intval(intval($weight_pounds)*0.453592);
						$weight_allowed = esc_html($weight_pounds.' '.__('pounds', 'dynamicaviation').' | '.$weight_kg.__('kg', 'dynamicaviation'));
						$aircraft_type = $this->utilities->aircraft_type(aviation_field( 'aircraft_type' ));
						
						$route = __('Private Charter Flight', 'dynamicaviation').' '.$aircraft_type.' '.$post->post_title.' '.__('from', 'dynamicaviation').' '.$origin_airport.', '.$origin_city.' ('.$origin_iata.') '.__('to', 'dynamicaviation').' '.$destination_airport.', '.$destination_city.' ('.$iata.')';
						
						$table_row .= '<tr data-aircraft-type="'.esc_html(aviation_field( 'aircraft_type' )).'" data-iata="'.esc_html($origin_iata).'" title="'.esc_html($route).'">';
						
						if(!is_singular('aircrafts'))
						{
							if($is_commercial)
							{
								$table_row .= '<td><strong>'.esc_html(__('Commercial Flight', 'dynamicaviation')).'</strong></td>';
							}
							else
							{
								$table_row .= '<td><a class="strong" href="'.esc_url($aircraft_url).'/">'.esc_html($post->post_title).'</a> - <small>'.esc_html($aircraft_type).'</small><br/><i class="fas fa-male" ></i> '.esc_html($seats).' <small>('.$weight_allowed.')</small></td>';
							}
						}
						
						$table_row .= '<td><small class="text-muted">('.esc_html($origin_iata).')</small> <strong>'.esc_html($origin_city.', '.$origin_country_code).'</strong><br/>'.esc_html($origin_airport).'</td>';

						$table_row .= '<td><strong>'.esc_html('$'.number_format($table_price[$x][3], 2, '.', ',')).'</strong><br/><span class="text-muted">';

						if($is_commercial)
						{
							$table_row .= esc_html(__('Per Person', 'dynamicaviation'));
						}
						else
						{
							$table_row .= esc_html(__('Charter Flight', 'dynamicaviation'));
						}
						$table_row .= '</span>';
						
						if(floatval($fees) > 0)
						{
							$table_row .= '<br/><span class="text-muted">';
							$table_row .= esc_html(__('Fees per pers.', 'dynamicaviation').' '.'$'.number_format($fees, 2, '.', ','));
							$table_row .= '</span>';
						}						
						$table_row .= '<br/><span class="small text-muted"><i class="fas fa-clock" ></i> '.esc_html($this->utilities->convertNumberToTime($table_price[$x][2])).'</span>';
						
						$table_row .= '</td></tr>';
						$aircraft_count++;	
					}
				}
			}
			wp_reset_postdata();
		}	

		if($aircraft_count > 0)
		{
			$airport_options = null;
			$aircraft_type_list = array();
			$aircraft_list_option = null;	
			$table = '';
			
			if(is_singular('aircrafts'))
			{
				$table .= '<div itemscope itemtype="http://schema.org/Table"><h4 itemprop="about">'.esc_html(__('Charter Flights', 'dynamicaviation').' '.aviation_field( 'aircraft_base_name' ).' ('.aviation_field( 'aircraft_base_iata' )).') '.aviation_field( 'aircraft_base_city' ).'</h4>';
			}
			else
			{
				$table .= '<div itemscope itemtype="http://schema.org/Table"><h3 itemprop="about">'.esc_html(__('Flights to ', 'dynamicaviation')).' '.esc_html($destination_airport).' ('.esc_html($iata).'), '.esc_html($destination_city).', '.esc_html($destination_country_code).'</h3>';
			}
			
			$table .= '<table id="dy_table" class="text-center small pure-table pure-table-bordered margin-bottom"><thead><tr>';
			
			
			$origin_label = __('Destination', 'dynamicaviation');
			
			if(!is_singular('aircrafts'))
			{
				$origin_label = __('Origin', 'dynamicaviation');
				$table .= '<th>'.esc_html(__('Flights', 'dynamicaviation')).'</th>';
			}
			
			$table .= '<th>'.esc_html($origin_label).'</th>';
			$table .= '<th>'.esc_html(__('One Way', 'dynamicaviation')).'</th>';
			$table .= '</tr></thead><tbody>';
			$table .= $table_row;
			$table .= '</tbody></table>';
			
			if(!get_query_var('fly'))
			{
				$table .= '</div>';
			}
			
			$output .=  $table;
			return $output;
		}		
	}

	public static function algolia_full()
	{
		$query_param = 'browse?cursor=';
		$algolia_token = get_option('algolia_token');
		$algolia_index = get_option('algolia_index');
		$algolia_id = get_option('algolia_id');
		
		$curl = curl_init();
		
		$headers = array();
		$headers[] = 'X-Algolia-API-Key: '.$algolia_token;
		$headers[] = 'X-Algolia-Application-Id: '.$algolia_id;

		$curl_arr = array(
			CURLOPT_RETURNTRANSFER => 1,
			CURLOPT_REFERER => esc_url(home_url()),
			CURLOPT_URL => 'https://'.$algolia_id.'-dsn.algolia.net/1/indexes/'.$algolia_index.'/'.$query_param,
		);

		curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);	
		curl_setopt_array($curl, $curl_arr);
		$resp = curl_exec($curl);
		$resp = json_decode($resp, true);
		$resp = $resp['hits'];
		return $resp;
	}
}


?>