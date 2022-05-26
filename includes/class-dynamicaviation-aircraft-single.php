<?php

class Dynamic_Aviation_Aircraft_Single {


	public function __construct($utilities) {
        $this->init();
        $this->utilities = $utilities;
	}

    public function init()
    {
        add_filter('dy_aviation_aircraft_template', array(&$this, 'template'));
    }

    public function get_table_labels()
    {
        return array(
            __('Type', 'dynamicaviation'),
            __('Manufacturer', 'dynamicaviation'),
            __('Model', 'dynamicaviation'),
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

    public function template()
    {
        $labels = $this->get_table_labels();
        $keys = $this->get_table_keys();
        $table = '<table class="text-center pure-table small pure-table-striped">';
        
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
                    $value = aviation_field('aircraft_base_name');
                }
                
                $table .= '<tr>';
                $table .= '<td><span class="semibold">'.esc_html($labels[$x]).'</span></td>';
                $table .= '<td>'.esc_html($value).'</td>';
                $table .= '</tr>';			
            }
        }
        
        $table .= '</table>';
        
        return $this->container($table);
    }


    public function container($table)
    {
        $content = get_the_content();
        ob_start();
        ?>
            <div class="pure-g gutters">
                <div class="pure-u-1 pure-u-md-2-3">
                    <?php if(has_post_thumbnail() && empty($content)): ?>
                        <p><?php the_post_thumbnail('medium', array('class' => 'img-responsive')); ?></p>
                    <?php else: ?>
                        <?php echo $content; ?>
                    <?php endif;?>
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

}

?>