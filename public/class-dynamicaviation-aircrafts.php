<?php

#[AllowDynamicProperties]
class Dynamic_Aviation_Aircrafts {


	public function __construct($plugin_name, $version, $utilities) {
		$this->plugin_name = $plugin_name;
        $this->utilities = $utilities;
        $this->plugin_dir_path = plugin_dir_path( dirname( __FILE__ ) );        
        $this->utilities = $utilities;
        $this->pathname = 'aircrafts';
        $this->post_type = $this->pathname;

        //init
        add_action('init', array(&$this, 'init'));
        add_action('admin_enqueue_scripts', array(&$this, 'admin_enqueue_scripts'), 1);

		//filters custom wordpress outputs
        add_filter( 'pre_get_document_title', array(&$this, 'modify_wp_title'), 100);
		add_filter('wp_title', array(&$this, 'modify_wp_title'), 100);
        add_filter('the_title', array(&$this, 'modify_title'), 100);
        add_filter('the_content', array(&$this, 'modify_content'), 100);
        add_filter('the_excerpt', array(&$this, 'modify_excerpt'));

        add_filter('wp_head', array(&$this, 'meta_tags'));
        add_filter('template_include', array(&$this, 'locate_template'), 100 );


        add_action('pre_get_posts', array(&$this, 'main_wp_query'), 100);

        // minimalizr theme conection
		add_filter('minimal_posted_on', array(&$this, 'minimalizr_hide_posted_on'), 100);
		add_filter('minimal_archive_excerpt', array(&$this, 'minimalizr_modify_archive_excerpt'), 100);
		add_filter('minimal_archive_title', array(&$this, 'minimalizr_modify_archive_title'), 100);
        
        //load core scripts
        add_action( 'parse_query', array( &$this, 'load_scripts' ), 100);
	}

    public function init()
    {
		$this->site_name = get_bloginfo('name');
		$this->current_language = current_language();
		$this->get_languages = get_languages();
    }

	public function admin_enqueue_scripts()
	{
		global $typenow;

		if($typenow === $this->post_type)
		{
			$GLOBALS['dy_aviation_load_admin_scripts'] = true;
		}
	}    

    public function meta_tags()
    {
        if(is_singular($this->pathname)):

        ?>
            <meta name="description" content="<?php the_excerpt(); ?>" />
        <?php
            endif;
    }

	public function modify_excerpt($excerpt)
	{
		if(is_singular($this->pathname))
		{
            $title = get_the_title();
			$aircraft_type = $this->utilities->aircraft_type(aviation_field( 'aircraft_type' ));
			$city = aviation_field('aircraft_base_city');
			$airport = aviation_field('aircraft_base_name');
			$price_per_hour = '$'.aviation_field('aircraft_price_per_hour');
			return sprintf(__('%s for rent in %s. Charter Flight Service %s %s in %s, %s from %s per hour.', 'dynamicaviation'), $title, $city, $aircraft_type, $title, $airport, $city, $price_per_hour);
		}

		return $excerpt;
	}


	public function locate_template($template)
	{
		if(is_singular($this->pathname))
		{
			$new_template = locate_template( array( 'page.php' ) );
			return $new_template;			
		}
		return $template;
	}    

    public function modify_content($content)
    {
		if(in_the_loop() && is_singular($this->pathname))
		{
			return $this->table($content);
		}

        return $content;
    }

    public function modify_title($title)
    {

        if(in_the_loop() && is_singular($this->pathname))
        {
            $aircraft_type = $this->utilities->aircraft_type(aviation_field( 'aircraft_type' ));
            $title = '<span class="linkcolor">'.esc_html($aircraft_type).'</span> '.$title;
            return $title;				
        }	        

        return $title;
    }


	public function main_wp_query($query)
	{
        if(is_post_type_archive($this->pathname) && $query->is_main_query())
        {
            $query->set( 'meta_key', 'aircraft_price_per_hour' );
            $query->set( 'orderby', 'meta_value_num' );
            $query->set( 'order', 'ASC');
        }
	}
    
    public function modify_wp_title($title)
    {
		if(is_post_type_archive($this->pathname))
		{
			$output = __('Aircrafts for Rent', 'dynamicaviation') . ' | '. $this->site_name;
		}
		elseif(is_singular($this->pathname))
		{			
			$aircraft_type = $this->utilities->aircraft_type(aviation_field( 'aircraft_type' ));
			$city = aviation_field('aircraft_base_city');
			$label = __('Charter Flights', 'dynamicaviation');
			$title = sprintf(__('%s %s %s in %s', 'dynamicaviation'), $label, $aircraft_type, get_the_title(), $city) .' | '.$this->site_name;
		}

        return $title;
    }

    public function get_table_labels()
    {
        return array(
            __('Type', 'dynamicaviation'),
            __('Manufacturer', 'dynamicaviation'),
            __('Model', 'dynamicaviation'),
            __('Price Per Hour', 'dynamicaviation'),
            __('Year of Construction', 'dynamicaviation'),
            __('Passengers', 'dynamicaviation'),
            __('Range', 'dynamicaviation'),
            __('Cruise Speed', 'dynamicaviation'),
            __('Max Altitude', 'dynamicaviation'),
            __('Takeoff Field', 'dynamicaviation'),
            __('Base Airport', 'dynamicaviation'),
            __('Base Location', 'dynamicaviation')
        );
    }

    public function get_table_keys()
    {
        return  array(
            'aircraft_type',
            'aircraft_manufacturer',
            'aircraft_model',
            'aircraft_price_per_hour',
            'aircraft_year_of_construction',
            'aircraft_passengers',
            'aircraft_range',
            'aircraft_cruise_speed',
            'aircraft_max_altitude',
            'aircraft_takeoff_field',
            'aircraft_base_iata',
            'aircraft_base_city'
        );
    }

    public function table($content)
    {
        $labels = $this->get_table_labels();
        $keys = $this->get_table_keys();
        $base = aviation_field('aircraft_base_name');
        $table = '<table class="text-center pure-table small pure-table-striped bottom-40">';
        
        for($x = 0; $x < count($keys); $x++)
        {
            $key = $keys[$x];
            $value = aviation_field($key);

            if($value !== '')
            {
                if($key == 'aircraft_type')
                {
                    $value = $this->utilities->aircraft_type($value);
                }
                if($key == 'aircraft_price_per_hour')
                {
                    $value = '$'.$value;
                }
                else if($key == 'aircraft_range')
                {
                    $value = $value.__('nm', 'dynamicaviation').' | '.round(intval($value)*1.15078).__('mi', 'dynamicaviation').' | '.round(intval($value)*1.852).__('km', 'dynamicaviation');
                }
                else if($key == 'aircraft_cruise_speed')
                {
                    $value = $value.__('kn', 'dynamicaviation').' | '.round(intval($value)*1.15078).__('mph', 'dynamicaviation').' | '.round(intval($value)*1.852).__('kph', 'dynamicaviation');			
                }
                else if($key == 'aircraft_max_altitude')
                {
                    $value = $value.__('ft', 'dynamicaviation').' | '.round(intval($value)*0.3048).__('m', 'dynamicaviation');
                }
                else if($key == 'aircraft_base_iata')
                {
                    $value = $base;
                }
                
                $table .= '<tr>';
                $table .= '<td><span class="semibold">'.esc_html($labels[$x]).'</span></td>';
                $table .= '<td>'.esc_html($value).'</td>';
                $table .= '</tr>';			
            }
        }
        
        $table .= '</table>';
        
        return $this->container($content, $table);
    }


    public function container($content, $table)
    {
        ob_start();
        ?>
            <div class="pure-g gutters">
                <div class="pure-u-1 pure-u-md-2-3">
                 <?php echo $content; ?>
                </div>
                <div class="pure-u-1 pure-u-md-1-3">
                    <?php echo $table; ?>
                </div>
            </div>

            <hr/>

            <?php echo apply_filters('dy_aviation_price_table', ''); ?>

            <h2><?php echo (esc_html__('Instant Quotes', 'dynamicaviation')); ?></h2>
            <div class="bottom-20">
                <?php echo apply_filters('dy_aviation_search_form', ''); ?>
            </div>
        <?php
    }

	public function minimalizr_hide_posted_on($posted_on)
	{
		if(is_post_type_archive($this->pathname))
		{
			return '';
		}

		return $posted_on;
	}

	public function minimalizr_modify_archive_excerpt($excerpt)
	{
		if(is_post_type_archive($this->pathname))
		{
			$type = $this->utilities->aircraft_type(aviation_field( 'aircraft_type' ));
			$passengers = aviation_field('aircraft_passengers');
			$price_per_hour = aviation_field('aircraft_price_per_hour');
			$excerpt = '<p><strong>'.esc_html(__('Type', 'dynamicaviation')).'</strong>: '.esc_html($type).'<br/>';
			$excerpt .= '<strong>'.esc_html(__('Passengers', 'dynamicaviation')).'</strong>: '.esc_html($passengers).'<br/>';
			$excerpt .= '<strong>'.esc_html(__('Price Per Hour', 'dynamicaviation')).'</strong>: $'.esc_html($price_per_hour).'</p>';
		}

		return $excerpt;
	}
	public function minimalizr_modify_archive_title($title)
	{
		if(is_post_type_archive($this->pathname))
		{
			return __('Aircrafts', 'dynamicaviation');
		}

		return $title;
	}

    public function load_scripts($query)
    {
        global $dy_aviation_load_algolia;

        if(isset($query->query_vars[$this->pathname]))
        {
            $GLOBALS['dy_aviation_load_algolia'] = true;
            $GLOBALS['dy_aviation_load_mapbox'] = true;
            $GLOBALS['dy_load_picker_scripts'] = true;
        }
    }

}

?>