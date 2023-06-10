<?php

#[AllowDynamicProperties]
class Dynamic_Aviation_Image {

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
        $output = '';
        $which_var = $this->plugin_name . 'get_image_pathname';
        global $$which_var;

        if(isset($$which_var))
        {
            $output = $$which_var;
        }
        else
        {
            $path = pathinfo($_SERVER['REQUEST_URI']);

            if(array_key_exists('dirname', $path) && array_key_exists('basename', $path) && array_key_exists('filename', $path))
            {
                $dirname = $path['dirname'];
                $basename = $path['basename'];
                $dirname_arr = array_values(array_filter(explode('/', $dirname)));
                $filename = $path['filename'];

                if(is_array($dirname_arr))
                {
                    if(count($dirname_arr) > 0)
                    {
                        if(in_array($this->pathname, $dirname_arr) && str_ends_with($basename, '.png'))
                        {

                            $output = $filename;
                        }
                    }
                }
            }

            $GLOBALS[$which_var] = $output;
        }

		return $output;       
    }

	public function render_image()
	{
        $filename = $this->get_image_pathname();

        if($filename)
        {
            $url = $this->utilities->airport_url_string($this->utilities->airport_data($filename));

            $headers = array(
                'Content-Type' => 'image/png'
            );
            
            $resp = wp_remote_get($url, array(
                'headers' => $headers
            ));

            if ( is_array( $resp ) && ! is_wp_error( $resp ) )
            {
                if($resp['response']['code'] === 200)
                {
                    header ('Content-Type: image/png'); 

                    exit($resp['body']);
                }
            }
        }
	}

}

?>