<?php 


class Dynamic_Aviation_Utilities {

	public function json_src_url()
	{
		return 'const jsonsrc = () => { return "'.esc_url(plugin_dir_url( __DIR__ )).'/public/";}';
	}
    
	public function algoliasearch_after()
	{
		$output = '';

		if(get_option('algolia_token') && get_option('algolia_index') && get_option('algolia_id'))
		{
			$output .= 'const algoliaClient = algoliasearch(getAlgoliaId, getAlgoliaToken);';
			$output .= 'const algoliaIndex = algoliaClient.initIndex(getAlgoliaIndex);';
		}

		return $output;
	}
	public function algoliasearch_before()
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
}


?>