<?php 


class Dynamic_Aviation_Utilities {


	public function __construct()
	{	
		$this->plugin_dir_url = plugin_dir_url( __DIR__ );
		add_action('init', array(&$this, 'init'), 1);
	}

	public function init()
	{
		$this->algolia_token = get_option('algolia_token');
		$this->algolia_index = get_option('algolia_index');
		$this->algolia_id = get_option('algolia_id');
	}

	public function airport_img_url($json)
	{
		if(is_array($json))
		{
			if(array_key_exists('airport', $json))
			{
				return home_url(apply_filters('dy_aviation_image_pathname', '') . '/' .$this->sanitize_pathname($json['airport']).'.png');
			}
		}
	}

	public function plugin_public_args()
	{
		return 'const jsonsrc = () => { return "'.esc_url($this->plugin_dir_url.'public/').'";}';
	}
    
	public function algoliasearch_after()
	{
		$output = '';

		if($this->algolia_token && $this->algolia_index && $this->algolia_id)
		{
			$output .= 'const algoliaClient = algoliasearch("'.esc_html($this->algolia_id).'", "'.esc_html($this->algolia_token).'");';
			$output .= 'const algoliaIndex = algoliaClient.initIndex("'.esc_html($this->algolia_index).'");';
		}

		return $output;
	}
  
	  public function convertNumberToTime($dec)
	  {
		  $seconds = ($dec * 3600);
		  $hours = floor($dec);
		  $seconds -= $hours * 3600;
		  $minutes = floor($seconds / 60);
		  return $this->lz($hours).":".$this->lz($minutes);
	  }
  
	  public function lz($num)
	  {
		  return (strlen($num) < 2) ? "0{$num}" : $num;
	  }

	  public function aircraft_type($type)
	  {
		  $type = intval($type);
  
		  if($type === 0)
		  {
			  return __('Turbo Prop', 'dynamicaviation');
		  }
		  elseif($type === 1)
		  {
			  return __('Light Jet', 'dynamicaviation');			
		  }
		  elseif($type === 2)
		  {
			  return __('Mid-size Jet', 'dynamicaviation');			
		  }
		  elseif($type === 3)
		  {
			  return __('Heavy Jet', 'dynamicaviation');			
		  }
		  elseif($type === 4)
		  {
			  return __('Airliner', 'dynamicaviation');		
		  }
		  elseif($type === 5)
		  {
			  return __('Helicopter', 'dynamicaviation');		
		  }		
	  }

	  public function sanitize_pathname($url)
	  {
		  $url = strtolower($url);
		  $unwanted_array = array('Š'=>'S', 'š'=>'s', 'Ž'=>'Z', 'ž'=>'z', 'À'=>'A', 'Á'=>'A', 'Â'=>'A', 'Ã'=>'A', 'Ä'=>'A', 'Å'=>'A', 'Æ'=>'A', 'Ç'=>'C', 'È'=>'E', 'É'=>'E','Ê'=>'E', 'Ë'=>'E', 'Ì'=>'I', 'Í'=>'I', 'Î'=>'I', 'Ï'=>'I', 'Ñ'=>'N', 'Ò'=>'O', 'Ó'=>'O', 'Ô'=>'O', 'Õ'=>'O', 'Ö'=>'O', 'Ø'=>'O', 'Ù'=>'U', 'Ú'=>'U', 'Û'=>'U', 'Ü'=>'U', 'Ý'=>'Y', 'Þ'=>'B', 'ß'=>'Ss', 'à'=>'a', 'á'=>'a', 'â'=>'a', 'ã'=>'a', 'ä'=>'a', 'å'=>'a', 'æ'=>'a', 'ç'=>'c', 'è'=>'e', 'é'=>'e', 'ê'=>'e', 'ë'=>'e', 'ì'=>'i', 'í'=>'i', 'î'=>'i', 'ï'=>'i', 'ð'=>'o', 'ñ'=>'n', 'ò'=>'o', 'ó'=>'o', 'ô'=>'o', 'õ'=>'o', 'ö'=>'o', 'ø'=>'o', 'ù'=>'u', 'ú'=>'u', 'û'=>'u', 'ý'=>'y', 'þ'=>'b', 'ÿ'=>'y' );
		  $url = strtr( $url, $unwanted_array);
		  $url = preg_replace("/[^a-z0-9\s\-]/i", "", $url);
		  $url = preg_replace("/\s\s+/", " ", $url);
		  $url = preg_replace("/\s/", "-", $url);
		  $url = preg_replace("/\-\-+/", "-", $url);
		  $url = trim($url, "-");
  
		  return $url;
	  }

	  public function airport_data($query_var = null) {
		
		$output = array();

		if(!$query_var)
		{
			if(get_query_var( 'fly' ))
			{
				$query_var = get_query_var( 'fly' );
			}
		}

		if(!$query_var)
		{
			return $this->all_airports_data();
		}

		$query_param = '?query='.$query_var.'&hitsPerPage=1';
		$which_var = 'dynamicaviation_airport_data_'.$query_var;
		global $$which_var;
		
		if(isset($$which_var))
		{
			$output = $$which_var;
		}
		else
		{
			$url = 'https://'.$this->algolia_id.'-dsn.algolia.net/1/indexes/'.$this->algolia_index.'/'.$query_param;
			
			$headers = array(
				'X-Algolia-API-Key' => $this->algolia_token, 
				'X-Algolia-Application-Id' =>$this->algolia_id,
				'Content-Type' => 'application/json'
			);

			$resp = wp_remote_get($url, array(
				'headers' => $headers
			));

			if ( is_array( $resp ) && ! is_wp_error( $resp ) )
			{
				if($resp['response']['code'] === 200)
				{
					$body = json_decode($resp['body'], true);

					if(array_key_exists('hits', $body))
					{
						$hits = $body['hits'];
						
						if(is_array($hits))
						{
							for($x = 0; $x < count($hits); $x++)
							{
								if($query_var === $this->sanitize_pathname($hits[$x]['airport']))
								{
									$output = $hits[$x];
								}
							}			
							
						}
					}
				}
			}

			$GLOBALS[$which_var] = $output;
		}

		return $output;
	}

	public function airport_url_string($json)
	{

		if(is_array($json))
		{
			if(array_key_exists('_geoloc', $json))
			{
				$_geoloc = $json['_geoloc'];
				$mapbox_token = get_option('mapbox_token');
				$mapbox_marker = 'pin-l-airport+dd3333('.$_geoloc['lng'].','.$_geoloc['lat'].')';
				$url = 'https://api.mapbox.com/styles/v1/mapbox/streets-v11/static/'.esc_html($mapbox_marker).'/'.esc_html($_geoloc['lng']).','.esc_html($_geoloc['lat']).',8/660x440?access_token='.esc_html($mapbox_token);				
				
				//write_log($url);

				return $url;
			}
		}
	}

	public function sanitize_items_per_line($sanitize_func, $str, $max)
	{
		if(!$max)
		{
			$max = 20;
		}

		$row = explode("\r\n", html_entity_decode($str));
		
		$arr = array_slice(array_unique(array_filter(array_map($sanitize_func, $row))), 0, $max) ;

		return implode("\r\n", $arr);
	}

	public function items_per_line_to_array($str)
	{
		return explode("\r\n", html_entity_decode($str));
	}

	public function all_airports_data()
	{
		$output = array();
		$which_var = 'dynamicaviation_all_airports_data';
		global $$which_var;

		if(isset($$which_var))
		{
			$output = $$which_var;
		}
		else
		{
			$query_param = 'browse?cursor=';
			$url = 'https://'.$this->algolia_id.'-dsn.algolia.net/1/indexes/'.$this->algolia_index.'/'.$query_param;

			$headers = array(
				'X-Algolia-API-Key' => $this->algolia_token, 
				'X-Algolia-Application-Id' => $this->algolia_id,
				'Content-Type' => 'application/json'
			);
			
			$resp = wp_remote_get($url, array(
				'headers' => $headers
			));


			if ( is_array( $resp ) && ! is_wp_error( $resp ) )
			{
				if($resp['response']['code'] === 200)
				{
					$body = json_decode($resp['body'], true);
					$output = $body['hits'];
					$GLOBALS[$which_var] = $output;
				}
			}

		}		

		return $output;
	}

    public function get_rates_from_itinerary($routes, $table_price)
    {
        $output = array();
        $rows = array();
        $count_routes = count($routes);

        for($r = 0; $r < $count_routes; $r++)
        {
            $o = $routes[$r][0];
            $d = $routes[$r][1];

            $row = array_filter($table_price, function($i) use($o, $d){

                //table
                $a1 = array($i[0], $i[1]);
                sort($a1);

                //route
                $a2 = array($o, $d);
                sort($a2);


                if(count(array_diff($a1, $a2)) === 0)
                {
                    return true;
                }
            });

            if($row > 0)
            {
                array_push($rows, ...$row);
            }
        }


        if(count($rows) === $count_routes)
        {
            $output = $rows;

            if($count_routes === 3)
            {
                $output = array_map(function($v, $i){

                    //divides the rate in to 2
                    if($i === 0 || $i === 2)
                    {
                        $v[3] = floatval($v[3]) / 2;
                    }

                    return $v;
                }, $output, array_keys($output));
            }

            return $output;
        }
        else
        {
            return array();
        }
    }

	public function currency_format($amount)
	{
		return number_format(floatval($amount), 2, '.', ',');
	}

	public function search_form_hash_param_names()
	{
		return array('aircraft_origin', 'aircraft_destination', 'pax_num', 'aircraft_flight', 'start_date', 'start_time', 'end_date', 'end_time');
	}

	public function request_form_hash_param_names()
	{
		return array_merge(array('first_name', 'lastname', 'email', 'phone', 'country'), $this->search_form_hash_param_names());
	}

	public function validate_hash($params)
	{
		$output = true;
		$which_var = 'dy_validate_form_hash';
		global $$which_var;

		if(isset($$which_var))
		{
			$output = $$which_var;
		}
		else
		{
			if(isset($_POST['hash']))
			{
				$str = '';
				$hash_param = sanitize_text_field($_POST['hash']);

				for($x = 0; $x < count($params); $x++)
				{
					if(isset($_POST[$params[$x]]))
					{
						$str .= sanitize_text_field($_POST[$params[$x]]);
					}
					else
					{
						$output = false;
					}
				}

				$hash = hash('sha512', $str);

				if($hash !== $hash_param)
				{
					$output = false;
				}
			}

			if(!$output)
			{
				$GLOBALS['dy_request_invalids'] = array(__('Invalid Hash', 'dynamicpackages'));
			}

			$GLOBALS[$which_var] = $output;
		}

		return $output;
	}

}


?>