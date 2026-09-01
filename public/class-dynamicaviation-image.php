<?php

#[AllowDynamicProperties]
class Dynamic_Aviation_Image {

    static $cache = [];

	public function __construct( $plugin_name, $version, $utilities ) 
	{
		$this->plugin_name = $plugin_name;
		$this->version = $version;
		$this->utilities = $utilities;
        $this->pathname = 'cacheimg';
		add_action('init', array(&$this, 'add_rewrite_rule'));
		add_action('init', array(&$this, 'add_rewrite_tag'), 10, 0);
        add_filter('query_vars', array(&$this, 'registering_custom_query_var'));
        add_action( 'init', array(&$this, 'render_image'), 1000 );
        add_filter('dy_aviation_image_pathname', array(&$this, 'set_pathname'));
	}

    public function set_pathname()
    {
        return $this->pathname;
    }

    public function add_rewrite_rule()
    {
        add_rewrite_rule('^'.$this->pathname.'/([a-z0-9-]+)[.png]?$', 'index.php?'.$this->pathname.'=$matches[1]','top');
    }

    public function add_rewrite_tag()
    {
        add_rewrite_tag('%'.$this->pathname.'%', '([^&]+)');
    }

	public function registering_custom_query_var($query_vars)
	{
		$query_vars[] = $this->pathname;
		return $query_vars;
	}

    public function get_image_pathname()
    {
        $request_uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        $cache_key = 'get_image_pathname_' . $request_uri;

        if(array_key_exists($cache_key, self::$cache))
        {
            return self::$cache[$cache_key];
        }

        $path = pathinfo($request_uri);

        if(!array_key_exists('dirname', $path) || !array_key_exists('basename', $path) || !array_key_exists('filename', $path))
        {
            return self::$cache[$cache_key] = '';
        }

        $output = '';
        $dirname = $path['dirname'];
        $basename = $path['basename'];
        $dirname_arr = array_values(
            array_filter(
                explode('/', $dirname), 
                static fn($value) => $value !== ''
            )
        );

        $filename = $path['filename'];

        if(in_array($this->pathname, $dirname_arr, true) && str_ends_with($basename, '.png'))
        {
            $output = $filename;
        }

		return self::$cache[$cache_key] = $output;       
    }

	public function render_image()
	{
        $filename = $this->get_image_pathname();

        if(!$filename)
        {
            return;
        }

        $airport_data = $this->utilities->airport_data_by_slug($filename);

        if( !is_array($airport_data))
        {
            return;
        }

        $url = $this->airport_url_string($airport_data);

        if(filter_var($url, FILTER_VALIDATE_URL) === false)
        {
            return;
        }

        $resp = wp_remote_get($url, [
            'timeout' => 10
        ]);

        if(is_wp_error( $resp ) || wp_remote_retrieve_response_code($resp) !== 200)
        {
            return;
        }

        $body = wp_remote_retrieve_body($resp);


        if($body === '')
        {
            return;
        }
        
        header('Content-Type: image/png');
        header('Cache-Control: public, max-age=86400');

        echo $body;
        exit;
	}

    public function airport_url_string($json)
    {
        if(!is_array($json) || !array_key_exists('_geoloc', $json))
        {
            return '';
        }

        $_geoloc = $json['_geoloc'];
        $mapbox_token = get_option('mapbox_token');

        return sprintf(
            'https://api.mapbox.com/styles/v1/mapbox/streets-v11/static/pin-l-airport+dd3333(%1$s,%2$s)/%1$s,%2$s,8/660x440?access_token=%3$s',
            esc_html($_geoloc['lng']),
            esc_html($_geoloc['lat']),
            esc_html($mapbox_token)
        );
    }

}

?>