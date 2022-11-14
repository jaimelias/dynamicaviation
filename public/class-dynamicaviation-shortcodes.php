<?php 


class Dynamic_Aviation_Shortcodes {
    
    
    public function __construct($utilities)
    {
        $this->init();
    }

    public function init()
    {
		add_shortcode( 'aviation_search_form', array(&$this, 'search_form'));
		add_shortcode( 'aviation_table', array(&$this, 'table'));

        //load core scripts
        add_action( 'parse_query', array( &$this, 'load_scripts' ), 100);
    }

	public function table($attr, $content = '')
	{
		if($attr)
		{
			if(array_key_exists('iata', $attr))
			{
				$iata = sanitize_key($attr['iata']);

				if($iata)
				{
					$content = apply_filters('dy_aviation_price_table', strtoupper($iata));
				}
			}
		}
		return $content;
	}

	public function search_form($attr, $content = '')
	{
		$is_full = true;

		if(is_array($attr))
		{
			if(array_key_exists('full', $attr))
			{
				if(filter_var($attr['full'], FILTER_VALIDATE_BOOLEAN) === false)
				{
					$is_full = false;
				}
			}
		}

		if($is_full)
		{
			return apply_filters('dy_aviation_full_search_form', '');
		}
		else
		{
			return apply_filters('dy_aviation_search_form', '');
		}	
	}

	public function load_scripts()
	{
        global $dy_aviation_load_mapbox;
        global $dy_aviation_load_algolia;
		global $post;

		if(isset($post))
		{
			if(is_a($post, 'WP_Post'))
			{
				if(has_shortcode( $post->post_content, 'aviation_search_form'))
				{
					$GLOBALS['dy_aviation_load_algolia'] = true;
					$GLOBALS['dy_aviation_load_mapbox'] = true;
					$GLOBALS['dy_load_picker_scripts'] = true;
				}
			}
		}
	}

}


?>