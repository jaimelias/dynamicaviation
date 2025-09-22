<?php 

#[AllowDynamicProperties]
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
		$this->ip = get_ip_address();
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
		$dec = (empty($dec)) ? 1 : $dec;
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
			$types = [
				0 => __('Turbo Prop', 'dynamicaviation'),
				1 => __('Light Jet', 'dynamicaviation'),
				2 => __('Mid-size Jet', 'dynamicaviation'),
				3 => __('Heavy Jet', 'dynamicaviation'),
				4 => __('Airliner', 'dynamicaviation'),
				5 => __('Helicopter', 'dynamicaviation'),
				6 => __('Light Aircraft', 'dynamicaviation'),
			];

			$type = (int) $type;

			return $types[$type] ?? '';
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

	public function airport_data_by_iata ($iata = '') {
		if(empty($iata)) return [];

		$all_airports_data = $this->all_airports_data();

		$which_var = 'dy_airport_data_by_iata_' . $iata;
		global $$which_var; 

		if(isset($$which_var)) {
			return $$which_var;
		}

		$output = [];

		if (!is_array($all_airports_data)) return [];

		foreach ($all_airports_data as $row) {
			// Be defensive about missing keys
			if (!isset($row['airport']) || !isset($row['iata'])) {
				continue;
			}
			if ($iata === $row['iata']) {
				$output = $row;
				break; // stop on first match
			}
		}

		$GLOBALS[$which_var] = $output;

		return $output;
	}

	public function airport_data_by_slug($slug = '') {
		// Pull from query var if not provided explicitly (but don't clobber valid "0")

		if (empty($slug)) {
			$slug = get_query_var('fly');

			if(empty($slug)) {
				return null;
			}
		}

		// Use a consistent, safe cache key (only in $GLOBALS)
		$which_var = 'dy_airport_data_by_slug_' . $slug;
		global $$which_var; 

		if(isset($$which_var)) {
			return $$which_var;
		}

		$output = [];
		$all_airports_data = $this->all_airports_data();
		if(!is_array($all_airports_data) || count($all_airports_data) === 0) return null;

		if (!is_array($all_airports_data)) return [];

		foreach ($all_airports_data as $row) {
			// Be defensive about missing keys
			if (!isset($row['airport']) || !isset($row['iata'])) {
				continue;
			}
			if ($slug === $this->sanitize_pathname($row['airport'])) {
				$output = $row;
				break; // stop on first match
			}
		}

		$GLOBALS[$which_var] = $output;

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
		$transient_key = 'dynamicaviation_all_airports_data';

		// Try from cache first
		$cached = get_transient($transient_key);

		if ($cached !== false) {
			return $cached;
		}

		$query_param = 'browse?cursor=';
		$url = 'https://' . $this->algolia_id . '-dsn.algolia.net/1/indexes/' . $this->algolia_index . '/' . $query_param;

		$headers = array(
			'X-Algolia-API-Key' => $this->algolia_token,
			'X-Algolia-Application-Id' => $this->algolia_id,
			'Content-Type' => 'application/json'
		);

		$resp = wp_remote_get($url, array('headers' => $headers));

		if (is_array($resp) && !is_wp_error($resp)) {
			if ($resp['response']['code'] === 200) {
				$body = json_decode($resp['body'], true);

				if (!empty($body['hits'])) {
					$output = $body['hits'];

					// Store in cache for 6 hours (21600 seconds)
					set_transient($transient_key, $output, 21600);
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

	public function search_form_hash_param_names()
	{
		return array('aircraft_origin', 'aircraft_destination', 'pax_num', 'aircraft_flight', 'start_date', 'start_time', 'end_date', 'end_time');
	}

	public function request_form_hash_param_names()
	{
		return array_merge(array('first_name', 'lastname', 'email', 'repeat_email', 'phone', 'country_calling_code'), $this->search_form_hash_param_names());
	}

	public function validate_hash($param_names)
	{
		$output = true;
		$which_var = 'dy_aviation_validate_hash';
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

				for($x = 0; $x < count($param_names); $x++)
				{
					if(isset($_POST[$param_names[$x]]))
					{
						$str .= sanitize_text_field($_POST[$param_names[$x]]);
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
				$log = array(__('invalid_hash', 'dynamicpackages'));
				write_log(array_merge($log, array('ip' => $this->ip, '_POST' => $_POST)));
				$GLOBALS['dy_request_invalids'] = $log;
			}

			$GLOBALS[$which_var] = $output;
		}

		return $output;
	}

	public function validate_nonce($pathname)
	{
		$output = false;
		$which_var = 'dy_aviation_validate_nonce';
		global $$which_var;

		if(isset($$which_var))
		{
			$output = $$which_var;
		}
		else
		{
			if(!is_user_logged_in())
			{
				if(wp_verify_nonce(get_query_var($pathname), 'dy_nonce'))
				{
					$output = true;
				}
				else
				{
					$log = array(__('invalid_nonce', 'dynamicpackages'));
					write_log(array_merge($log, array('ip' => $this->ip, '_POST' => $_POST)));
					$GLOBALS['dy_request_invalids'] = $log;
					$output = false;
				}
			}
			else
			{
				$output = true;
			}


			$GLOBALS[$which_var] = $output;
		}

		return $output;
	}

	public function validate_legs($value)
	{
		return in_array(intval($value), array(0,1));
	}

	public function validate_params($param_names)
	{
		$output = false;
		$which_var = 'dy_aviation_validate_params';
		global $$which_var;

		if(isset($$which_var))
		{
			$output = $$which_var;
		}
		else
		{
			$not_set_params = array();
			$invalid_params = array();

			$round_trip = (isset($_POST['aircraft_flight'])) 
				? (intval($_POST['aircraft_flight']) === 1)
				? true
				: false
				: false;

			for($x = 0; $x < count($param_names); $x++)
			{
				$param = $param_names[$x];

				if(!isset($_POST[$param]))
				{
					$not_set_params[] = $param;
				}
				else
				{
					$value = sanitize_text_field($_POST[$param]);

					if($param === 'email')
					{
						if(!is_email($value))
						{
							$invalid_params[] = $param;
						}
					}
					else if($param === 'repeat_email')
					{
						if(!is_email($value) || $value !== sanitize_text_field($_POST['email']))
						{
							$invalid_params[] = $param;
						}
					}
					else if($param === 'aircraft_flight')
					{
						if(!$this->validate_legs($value))
						{
							$invalid_params[] = $param;
						}
					}
					else if($param === 'start_date')
					{
						if(!is_valid_date($value))
						{
							$invalid_params[] = $param;
						}
					}
					else if($param === 'start_time')
					{
						if(!is_valid_time($value))
						{
							$invalid_params[] = $param;
						}
					}
					else if($param === 'end_date')
					{
						if($round_trip)
						{
							if(!is_valid_date($value))
							{
								$invalid_params[] = $param;
							}
						}
					}
					else if($param === 'end_time')
					{
						if($round_trip)
						{
							if(!is_valid_time($value))
							{
								$invalid_params[] = $param;
							}
						}
					}
					else
					{
						if(empty($_POST[$param]))
						{
							$invalid_params[] = $param;
						}
					}
				}
			}

			if(empty($not_set_params))
			{
				if(empty($invalid_params))
				{
					$output = true;
				}
				else
				{
					$log = array('invalid_params' => $invalid_params);
					$GLOBALS['dy_request_invalids'] = $log;
					write_log(array_merge($log, array('ip' => $this->ip, '_POST' => $_POST)));

					//block ip
					cloudflare_ban_ip_address();
				}
			}
			else
			{
				$log = array('not_set_params' => $not_set_params);
				write_log(array_merge($log, array('ip' => $this->ip, '_POST' => $_POST)));
				$GLOBALS['dy_request_invalids'] = $log;
			}

			$GLOBALS[$which_var] = $output;
		}

		return $output;
	}
}


?>