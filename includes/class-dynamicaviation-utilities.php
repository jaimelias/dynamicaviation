<?php 


class Dynamic_Aviation_Utilities {


	public function airport_img_url($json, $redirect_mobile)
	{
		$airport = $json['airport'];
		$url = home_url('cacheimg/'.$this->sanitize_pathname($airport).'.jpg');		
		return $url;
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

	  public function return_json() {
		
		$algolia_token = get_option('algolia_token');
		$algolia_index = get_option('algolia_index');
		$algolia_id = get_option('algolia_id');
		
		$curl = curl_init();
		
		$headers = array();
		$headers[] = 'X-Algolia-API-Key: '.$algolia_token;
		$headers[] = 'X-Algolia-Application-Id: '.$algolia_id;

		curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
		
		if(get_query_var( 'fly' ) != '')
		{
			$new_query_var = get_query_var( 'fly' );
			$query_param = '?query='.$new_query_var.'&hitsPerPage=1';
		}
		if(get_query_var( 'cacheimg' ) != '')
		{
			$new_query_var = get_query_var( 'cacheimg' );
			$query_param = '?query='.$new_query_var.'&hitsPerPage=1';
		}
		else
		{
			$query_param = 'browse?cursor=';
		}
		


		curl_setopt_array($curl, array(
		CURLOPT_RETURNTRANSFER => 1,
		CURLOPT_REFERER => esc_url(home_url()),
		CURLOPT_URL => 'https://'.$algolia_id.'-dsn.algolia.net/1/indexes/'.$algolia_index.'/'.$query_param,
		));
		$resp = curl_exec($curl);
		$resp = json_decode($resp, true);
			
		
		if(get_query_var( 'fly' ) != '' || get_query_var( 'cacheimg' ) != '')
		{
			if(array_key_exists('hits', $resp))
			{
				$hits = $resp['hits'];
				
				if(is_array($hits))
				{
					for($x = 0; $x < count($hits); $x++)
					{
						if($new_query_var === $this->sanitize_pathname($hits[$x]['airport']))
						{
							return json_encode($hits[$x]);
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

}


?>