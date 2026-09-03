<?php 

#[AllowDynamicProperties]
class Dynamic_Aviation_Utilities {


	static $cache = [];

	public function __construct(){	
		$this->plugin_dir_url = plugin_dir_url( __DIR__ );
		$this->ip = get_ip_address();
		add_action('init', [$this, 'init'], 1);
	}

	public function init() : void {
		$this->algolia_token = get_option('algolia_token');
		$this->algolia_index = get_option('algolia_index');
		$this->algolia_id = get_option('algolia_id');
	}

	public function airport_img_url($airport_data) : string {

		return is_array($airport_data) && array_key_exists('airport', $airport_data) 
			?  normalize_url(home_url(DY_AVIATION_IMAGE_PATHNAME . '/' .$this->sanitize_pathname($airport_data['airport']).'.png'))
			: '';

	}

	public function plugin_public_args() : string {
		return 'const jsonsrc = () => { return "'.esc_url($this->plugin_dir_url.'public/').'";}';
	}
    
	public function algoliasearch_after(): string
	{
		if(!$this->algolia_token || !$this->algolia_index || !$this->algolia_id)
		{
			return '';
		}

		return sprintf(
			'const algoliaClient = algoliasearch("%s", "%s"); const algoliaIndex = algoliaClient.initIndex("%s");',
			esc_js($this->algolia_id),
			esc_js($this->algolia_token),
			esc_js($this->algolia_index)
		);
	}
  
	public function convertNumberToTime(float|int|null $dec): string
	{
		if($dec === null || $dec < 0)
		{
			return '';
		}

		$hours = (int) floor($dec);
		$minutes = (int) round(($dec - $hours) * 60);

		if($minutes === 60)
		{
			$hours++;
			$minutes = 0;
		}

		return sprintf('%02d:%02d', $hours, $minutes);
	}

	public function aircraft_type(int|string|null $type): string
	{
		if(!is_numeric($type))
		{
			return '';
		}

		return match((int) $type)
		{
			0 => __('Turbo Prop', 'dynamicaviation'),
			1 => __('Light Jet', 'dynamicaviation'),
			2 => __('Mid-size Jet', 'dynamicaviation'),
			3 => __('Heavy Jet', 'dynamicaviation'),
			4 => __('Airliner', 'dynamicaviation'),
			5 => __('Helicopter', 'dynamicaviation'),
			6 => __('Light Aircraft', 'dynamicaviation'),
			default => '',
		};
	}

	public function all_airports_data() : array {
		
		$cache_key = 'dynamicaviation_all_airports_data_v2';

		if(array_key_exists($cache_key, self::$cache)){
			return self::$cache[$cache_key];
		}

		// Try from cache first
		$cached = get_transient($cache_key);

		if (is_array($cached)) {

			return self::$cache[$cache_key] = $cached;
		}

		$url = sprintf(
			'https://%s-dsn.algolia.net/1/indexes/%s/browse?cursor=',
			$this->algolia_id,
			$this->algolia_index
		);

		$headers = [
			'X-Algolia-API-Key' => $this->algolia_token,
			'X-Algolia-Application-Id' => $this->algolia_id,
			'Content-Type' => 'application/json'
		];

		$output = [];

		$resp = wp_remote_get($url, ['headers' => $headers]);

		if (is_wp_error($resp)) {
			return self::$cache[$cache_key] = [];
		}

		if (wp_remote_retrieve_response_code($resp) !== 200) {
			return self::$cache[$cache_key] = [];
		}
		
		$body = json_decode(wp_remote_retrieve_body($resp), true);

		if (is_array($body) && array_key_exists('hits', $body) && is_array($body['hits'])) {

			$output = $body['hits'];

			set_transient($cache_key, $output, 21600);
		}

		return self::$cache[$cache_key] = $output;
	}

	public function airport_data_by_iata ($iata = '') : array {


		$iata = strtoupper(trim($iata));

		if ($iata === '') return [];

		$cache_key = 'dy_airport_data_by_iata_v2_' . $iata;

		if(array_key_exists($cache_key, self::$cache)){
			return self::$cache[$cache_key];
		}

		$all_airports_data = $this->all_airports_data();

		if (empty($all_airports_data)) {
			return [];
		}

		$output = [];

		foreach ($all_airports_data as $row) {
			// Be defensive about missing keys
			if (!isset($row['iata'])) {
				continue;
			}
			if ($iata === strtoupper($row['iata'])) {
				$output = $row;
				break; // stop on first match
			}
		}

		return self::$cache[$cache_key] = $output;
	}

	public function airport_data_by_slug(string $slug = '') : array {

		if ($slug === '') {
			$slug = (string) get_query_var('fly');
		}

		if ($slug === '') {
			return [];
		}

		$slug = $this->sanitize_pathname($slug);

		if ($slug === '') {
			return [];
		}

		$slug = $this->sanitize_pathname($slug);

		$cache_key = 'dy_airport_data_by_slug_v2_' . $slug;

		if(array_key_exists($cache_key, self::$cache)){
			return self::$cache[$cache_key];
		}

		
		$all_airports_data = $this->all_airports_data();

		if (empty($all_airports_data)) {
			return [];
		}

		$output = [];

		foreach ($all_airports_data as $row) {
			// Be defensive about missing keys
			if (!isset($row['airport'])) {
				continue;
			}
			if ($slug === $this->sanitize_pathname($row['airport'])) {
				$output = $row;
				break; // stop on first match
			}
		}

		return self::$cache[$cache_key] = $output;
	}

    public function get_rates_from_itinerary($routes, $table_price)
    {
        $output = [];
        $rows = [];
        $count_routes = count($routes);

        for($r = 0; $r < $count_routes; $r++)
        {
            $o = $routes[$r][0];
            $d = $routes[$r][1];

            $row = array_filter($table_price, function($i) use($o, $d){

                //table
                $a1 = [$i[0], $i[1]];
                sort($a1);

                //route
                $a2 = [$o, $d];
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
            return [];
        }
    }

	public function sanitize_pathname(string $url): string
	{
		$url = strtr(mb_strtolower($url, 'UTF-8'), [
			'š' => 's',
			'ž' => 'z',
			'à' => 'a',
			'á' => 'a',
			'â' => 'a',
			'ã' => 'a',
			'ä' => 'a',
			'å' => 'a',
			'æ' => 'ae',
			'ç' => 'c',
			'è' => 'e',
			'é' => 'e',
			'ê' => 'e',
			'ë' => 'e',
			'ì' => 'i',
			'í' => 'i',
			'î' => 'i',
			'ï' => 'i',
			'ð' => 'o',
			'ñ' => 'n',
			'ò' => 'o',
			'ó' => 'o',
			'ô' => 'o',
			'õ' => 'o',
			'ö' => 'o',
			'ø' => 'o',
			'ù' => 'u',
			'ú' => 'u',
			'û' => 'u',
			'ü' => 'u',
			'ý' => 'y',
			'þ' => 'b',
			'ÿ' => 'y',
			'ß' => 'ss',
		]);

		return trim(
			preg_replace('/[^a-z0-9]+/', '-', $url),
			'-'
		);
	}

}


?>