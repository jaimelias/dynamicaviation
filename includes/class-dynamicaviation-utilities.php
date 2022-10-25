<?php 


class Dynamic_Aviation_Utilities {


	public function airport_img_url($json)
	{
		$airport = $json['airport'];
		$url = home_url('cacheimg/'.$this->sanitize_pathname($airport).'.jpg');		
		return $url;
	}

	public function transport_title_plural($this_id = null)
	{
		if(!$this_id)
		{
			$id = get_the_ID();
		}

		$transport = aviation_field('aircraft_commercial', $this_id);

		if($transport == 0)
		{
			return __('Charter Flights', 'dynamicaviation');
		}
		elseif($transport == 1)
		{
			return __('Commercial Flights', 'dynamicaviation');
		}
	}

	public function transport_title_singular($this_id = null)
	{
		if(!$this_id)
		{
			$id = get_the_ID();
		}

		$transport = aviation_field('aircraft_commercial', $this_id);

		if($transport == 0)
		{
			return __('Charter Flight', 'dynamicaviation');
		}
		elseif($transport == 1)
		{
			return __('Commercial Flight', 'dynamicaviation');
		}
	}

	public function get_languages()
	{
		global $polylang;
		$language_list = array();

		if(isset($polylang))
		{
			$languages = PLL()->model->get_languages_list();

			for($x = 0; $x < count($languages); $x++)
			{
				foreach($languages[$x] as $key => $value)
				{
					if($key == 'slug')
					{
						array_push($language_list, $value);
					}
				}	
			}
		}

		if(count($language_list) === 0)
		{
			$locale_str = get_locale();

			if(strlen($locale_str) === 5)
			{
				array_push($language_list, substr($locale_str, 0, -3));
			}
			else if(strlen($locale_str) === 2)
			{
				array_push($language_list, $locale_str);
			}
		}

		return $language_list;
	}

	public function json_src_url()
	{
		return 'const jsonsrc = () => { return "'.esc_url(plugin_dir_url( __DIR__ )).'/public/";}';
	}
    
	public function algoliasearch_after()
	{
		$output = '';
		$algolia_token = get_option('algolia_token');
		$algolia_index = get_option('algolia_index');
		$algolia_id = get_option('algolia_id');

		if($algolia_token && $algolia_index && $algolia_id)
		{
			$output .= 'const getAlgoliaToken = "'.esc_html($algolia_token).'";';	
			$output .= 'const getAlgoliaIndex = "'.esc_html($algolia_index).'";';
			$output .= 'const getAlgoliaId = "'.esc_html($algolia_id).'";';
			$output .= 'const algoliaClient = algoliasearch(getAlgoliaId, getAlgoliaToken);';
			$output .= 'const algoliaIndex = algoliaClient.initIndex(getAlgoliaIndex);';
		}

		return $output;
	}

	public function distance($lat1, $lon1, $lat2, $lon2, $unit) {

		$theta = $lon1 - $lon2;
		$dist = sin(deg2rad($lat1)) * sin(deg2rad($lat2)) +  cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * cos(deg2rad($theta));
		$dist = acos($dist);
		$dist = rad2deg($dist);
		$miles = $dist * 60 * 1.1515;
		$unit = strtoupper($unit);
  
		if ($unit == "K") {
		  return ($miles * 1.609344);
		} elseif ($unit == "N") {
			return ($miles * 0.8684);
		  } else {
			  return $miles;
			}
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

	  public function return_json($query_var = null) {
		
		$algolia_token = get_option('algolia_token');
		$algolia_index = get_option('algolia_index');
		$algolia_id = get_option('algolia_id');
		
		$curl = curl_init();
		
		$headers = array();
		$headers[] = 'X-Algolia-API-Key: '.$algolia_token;
		$headers[] = 'X-Algolia-Application-Id: '.$algolia_id;

		curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
		
		if($query_var)
		{
			$query_param = '?query='.$query_var.'&hitsPerPage=1';
		}
		else
		{
			if(get_query_var( 'fly' ) != '')
			{
				$query_var = get_query_var( 'fly' );
				$query_param = '?query='.$query_var.'&hitsPerPage=1';
			}
			if(get_query_var( 'cacheimg' ) != '')
			{
				$query_var = get_query_var( 'cacheimg' );
				$query_param = '?query='.$query_var.'&hitsPerPage=1';
			}
			else
			{
				$query_param = 'browse?cursor=';
			}
		}

		curl_setopt_array($curl, array(
		CURLOPT_RETURNTRANSFER => 1,
		CURLOPT_REFERER => esc_url(home_url()),
		CURLOPT_URL => 'https://'.$algolia_id.'-dsn.algolia.net/1/indexes/'.$algolia_index.'/'.$query_param,
		));
		$resp = curl_exec($curl);
		$resp = json_decode($resp, true);
			
		
		if($query_var)
		{
			if(array_key_exists('hits', $resp))
			{
				$hits = $resp['hits'];
				
				if(is_array($hits))
				{
					for($x = 0; $x < count($hits); $x++)
					{
						if($query_var === $this->sanitize_pathname($hits[$x]['airport']))
						{
							return $hits[$x];
						}
					}			
					
				}
			}
		}
		else
		{
			return $resp;
		}
		
	}

	public function airport_url_string($json)
	{
		//json
		$_geoloc = $json['_geoloc'];
		
		//mapbox options
		$mapbox_token = get_option('mapbox_token');
		
		//map position
		$mapbox_marker = 'pin-l-airport+dd3333('.$_geoloc['lng'].','.$_geoloc['lat'].')';

		return 'https://api.mapbox.com/styles/v1/mapbox/streets-v11/static/'.esc_html($mapbox_marker).'/'.esc_html($_geoloc['lng']).','.esc_html($_geoloc['lat']).',8/660x440?access_token='.esc_html($mapbox_token);				
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

	public function algolia_full()
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