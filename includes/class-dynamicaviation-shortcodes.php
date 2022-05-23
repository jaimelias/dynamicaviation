<?php 


class Dynamic_Aviation_Shortcodes {
    
    
    public function __construct($utilities, $price_table)
    {
		$this->price_table = $price_table;
        $this->init();
    }

    public function init()
    {
		add_shortcode( 'aviation_search_form', array(&$this, 'search_form'));
		add_shortcode( 'aviation_table', array(&$this, 'table'));
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
					$content = $this->price_table->table(strtoupper($iata));
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
}


?>