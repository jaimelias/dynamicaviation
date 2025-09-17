<?php

#[AllowDynamicProperties]
class Dynamic_Aviation_Estimate_Table {

    public function __construct($utilities) {
        $this->utilities = $utilities;
        $this->set_params();
        add_action('init', array(&$this, 'init'));
	}

    public function init()
    {
        $this->current_language = current_language();
        $this->is_mobile = wp_is_mobile();
        $this->home_lang = home_lang();
        add_filter('dy_aviation_aircrafts_table', array(&$this, 'template'));
    }

    public function set_params()
    {

        $intval_params = array('pax_num', 'aircraft_flight');

        $this->param_names = array(
            'pax_num', 
            'aircraft_flight', 
            'start_date',
            'start_time',
            'end_date',
            'end_time',
            'aircraft_origin',
            'aircraft_destination',
        );

        $this->get = (object) array();

        for($x = 0; $x < count($this->param_names); $x++)
        {
            $k = $this->param_names[$x];

            if(isset($_POST[$k]))
            {
                $v = sanitize_text_field($_POST[$k]);

                if(in_array($k, $intval_params))
                {
                    $v = (int)  $v;
                }

                $this->get->$k = $v;
            }
        }
    }
    
    public function obj_to_inputs()
    {
        $output = '';
        $obj = $this->get;
        $obj->start_itinerary = $this->start_itinerary();
        $obj->end_itinerary = $this->end_itinerary();
        $output .= "\r\n";

        foreach($obj as $key => $value)
        {
            $output .= '<input value="'.esc_attr($value).'" name="'.esc_attr($key).'" class="'.esc_attr($key).'" />';
            $output .= "\r\n";
        }


        return $output;
    }




    public function not_found()
    {
        return '<p>'.esc_html(__('The requested quote is not available in our website yet. Please contact our sales team for an immediate answer.', 'dynamicaviation')).'</p>';

    }

    public function pax_template()
    {
        return '<p class="large"><strong>'.esc_html(__('Passengers', 'dynamicaviation')).':</strong> <span class="linkcolor">'.esc_html($this->get->pax_num).'</span></p>';
    }

    public function start_itinerary()
    {
        $output = '';
        $output .= $this->get->aircraft_origin;
        $output .= ' &rsaquo;&rsaquo;&rsaquo; ';
        $output .= $this->get->aircraft_destination;
        $output .= ' '.__('on', 'dynamicaviation').' ';
        $output .= date_i18n(get_option( 'date_format' ), strtotime($this->get->start_date));
        $output .= ' '.__('at', 'dynamicaviation').' ';
        $output .= $this->get->start_time;
        return $output;
    }

    public function end_itinerary()
    {
        $output = '';

        if($this->get->aircraft_flight === 1)
        {
            $output = '';            
            $output .= $this->get->aircraft_destination;
            $output .= ' &rsaquo;&rsaquo;&rsaquo; ';
            $output .= $this->get->aircraft_origin;
            $output .= ' '.__('on', 'dynamicaviation').' ';
            $output .= date_i18n(get_option('date_format'), strtotime($this->get->end_date));
            $output .= ' '.__('at', 'dynamicaviation').' ';
            $output .= $this->get->end_time;
        }

        return $output;
    }

    public function table_container($rows)
    {
        ob_start(); 

        if($rows): ?>

            <hr/>
            
            <table class="bottom-40 pure-table pure-table-bordered pure-table-striped text-center instant_quote_table small width-100">
                <thead>
                    <tr>
                        <th <?php echo (!$this->is_mobile) ? ' colspan="2" ' : '' ;?>><?php echo esc_html(__('Flights', 'dynamicaviation')); ?></th>
        
                        <?php if(!$this->is_mobile): ?>
                            <th><?php echo esc_html(__('Duration', 'dynamicaviation')); ?></th>
                        <?php endif; ?>
                        
                        <th colspan="2"><?php esc_html_e(($this->get->aircraft_flight === 0) ? __('One Way', 'dynamicaviation') : __('Round Trip', 'dynamicaviation'));?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php echo $rows; ?>
                </tbody>
            </table>


        <?php
            $content = ob_get_contents();
            ob_end_clean();	
            endif;

		    return $content;
    } 

    public function get_rates($itinerary, $table_price)
    {
        $output = array();
        $rows = array();
        $count_routes = count($itinerary);

        for($r = 0; $r < $count_routes; $r++)
        {
            $o = $itinerary[$r][0];
            $d = $itinerary[$r][1];

            $row = array_filter($table_price, function($i) use($o, $d){

                //table
                $a1 = array($i[0], $i[1]);
                sort($a1);

                //route
                $a2 = array($o, $d);
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
            return array();
        }
    }


    public function get_routes($aicraft_id, $origin, $destination, $table_price)
    {
        $default = array(
            'price' => 0,
            'fees_per_person' => 0
        );

        $output = $default;
        $itinerary = array();
        $chart = array();
        $base = aviation_field( 'aircraft_base_iata', $aicraft_id);
        $request_routes = array($origin, $destination);
        sort($request_routes);

        $diff = array_diff($request_routes, array($base, $base));
        $count_diff = count($diff);

        if($count_diff === 1)
        {
            $itinerary = array(
                array($origin, $destination)
            );

            //option #1
            $chart = $this->utilities->get_rates_from_itinerary($itinerary, $table_price);
        }
        elseif($count_diff === 2)
        {
            $itinerary = array(
                array($base, $origin),
                array($origin, $destination),
                array($destination, $base)
            );

            //option #2
            $chart = $this->utilities->get_rates_from_itinerary($itinerary, $table_price);

            if(count($chart) === 0)
            {
                $itinerary = array(
                    array($base, $origin),
                    array($destination, $base)
                );

                //option #3
                $chart = $this->utilities->get_rates_from_itinerary($itinerary, $table_price);
            }
        }
        else
        {
            return $default;
        }

        $count_routes = count($itinerary);
        $found_request_in_index = 0;

        for($f = 0; $f < count($chart); $f++)
        {
            $chart_routes = array($chart[$f][0], $chart[$f][1]);
            sort($chart_routes);
            $diff_chart_request = array_diff($request_routes, $chart_routes);

            $output['price'] += ($this->get->aircraft_flight === 1) 
                ? (2 * floatval($chart[$f][3]))
                : floatval($chart[$f][3]);

            $output['fees_per_person'] += ($this->get->aircraft_flight === 1)  ? 
                (2 * floatval($chart[$f][4]))
                : floatval($chart[$f][4]);

            
            if(count($diff_chart_request) === 0)
            {
                $found_request_in_index = $f;
            }
        }

        for($i = 0; $i < count($chart); $i++)
        {
            if($i === $found_request_in_index)
            {
                $output['duration'] = (float) $chart[$i][2];
                $output['base_fees'] = (float) $chart[$i][5];
                $output['seats'] = (float) $chart[$i][6];
                $output['weight_pounds'] = (float) $chart[$i][7];
            }
        }

        return $output;
    }

    public function iterate_rows($post, $table_price)
    {
        $aircraft_url   = esc_url($this->home_lang . $post->post_type . '/' . $post->post_name);
        $thumbnail   = get_the_post_thumbnail(
            $post->ID,
            [100, 100],
            ['class' => 'img-responsive', 'alt' => esc_attr($post->post_title)]
        );

        $align_left_attr= $this->is_mobile ? '' : ' class="text-left"';
        $origin   = $this->get->aircraft_origin;
        $destination = $this->get->aircraft_destination;
        $itinerary   = $this->get_routes($post->ID, $origin, $destination, $table_price);


        // Early exits for invalid itineraries
        if (!$itinerary || !isset($itinerary['price'], $itinerary['duration']) || $itinerary['price'] === 0 || $itinerary['duration'] === 0) {
            return '';
        }

        $price = (float) $itinerary['price'];
        $fees_per_person  = (float) $itinerary['fees_per_person'];
        $base_fees = (float)  $itinerary['base_fees'];
        $duration_float = (float)   $itinerary['duration'];
        $seats = (int)   $itinerary['seats'];
        $weight_pounds  = (float) $itinerary['weight_pounds'];
        $weight_kg   = round($weight_pounds * 0.453592);
        $duration_in_hours  = $this->utilities->convertNumberToTime($duration_float);
        $charter_price = $price + $base_fees + ($fees_per_person * (int) $this->get->pax_num);

        // Translators: 1: weight in pounds, 2: weight in kilograms.
        $weight_allowed = sprintf(
            /* translators: %1$s: pounds, %2$s: kilograms */
            __('%1$s pounds or %2$s kg', 'dynamicaviation'),
            esc_html($weight_pounds),
            esc_html($weight_kg)
        );

        $flight_array = [
            'charter_price'  => $charter_price,
            'title'  => $post->post_title,
            'post_id'   => $post->ID,
            'aircraft_seats'  => $seats,
            'aircraft_weight' => $weight_allowed,
            'aircraft_url' => $aircraft_url,
        ];

        // Aircraft column
        $aircraft_col = '';
        if ($this->is_mobile) {
            $aircraft_col .= sprintf('<a href="%s">%s</a><br/>', $aircraft_url, $thumbnail);
        }

        $aircraft_type = $this->utilities->aircraft_type(aviation_field('aircraft_type', $post->ID));
        $aircraft_col .= sprintf(
            '<a class="strong" href="%1$s">%2$s</a> - <small>%3$s</small><br/>' .
            '<strong>%4$s %5$s</strong><br/>' .
            '<small>%6$s %7$s</small>',
            esc_html($aircraft_url),
            esc_html($post->post_title),
            esc_html($aircraft_type),
            esc_html($seats),
            esc_html(__('passengers', 'dynamicaviation')),
            esc_html(__('Max', 'dynamicaviation')),
            $weight_allowed
        );

        // Price column
        $price_col = sprintf(
            '<strong>%s</strong>',
            esc_html(wrapMoney($price))
        );

        if ($fees_per_person > 0 || $base_fees > 0) {

            $price_col .= sprintf(
                '<br/><span class="text-muted">%s $%s</span>',
                esc_html(__('Fees / pers.', 'dynamicaviation')),
                esc_html(money($fees_per_person))
            );
        }

        if ($base_fees > 0) {
            $price_col .= sprintf(
                '<br/><span class="text-muted">%s $%s</span>',
                esc_html(__('Airport fees', 'dynamicaviation')),
                esc_html(money($base_fees))
            );
        }


        if ($this->is_mobile) {
            $price_col .= sprintf(
                '<hr style="margin-top:10px;margin-bottom:10px;"/>' .
                '<small class="text-muted"><span class="dashicons dashicons-clock text-muted"></span></small><br/>' .
                '<strong>%2$s</strong>',
                esc_html($duration_in_hours)
            );
        }

        // Row assembly
        $cells = [];

        if (!$this->is_mobile) {
            $cells[] = sprintf('<td><a href="%s">%s</a></td>', $aircraft_url, $thumbnail);
        }

        $cells[] = sprintf('<td%1$s>%2$s</td>', $align_left_attr, $aircraft_col);

        if (!$this->is_mobile) {
            $cells[] = sprintf(
                '<td><span class="dashicons dashicons-clock"></span> %s</td>',
                esc_html($duration_in_hours)
            );
        }

        $cells[] = sprintf('<td>%s</td>', $price_col);

        // Use wp_json_encode for safe JSON, then escape for attribute context.
        $data_aircraft = esc_attr(wp_json_encode($flight_array));

        $cells[] = sprintf(
            '<td><button class="strong small button-success pure-button" data-aircraft="%1$s">' .
            '<span class="dashicons dashicons-email"></span> %2$s</button></td>',
            $data_aircraft,
            esc_html(__('Quote', 'dynamicaviation'))
        );

        return sprintf("<tr>%s</tr>", implode('', $cells));
    }


    public function request_form($hide_contact_form)
    {
        ob_start(); 
        ?>

            <div id="aircraft_booking_container" class="<?php echo ($hide_contact_form) ? 'hidden' : ''; ?> animate-fade">

                <form data-method="post" id="aircraft_booking_request" data-hash-params="<?php echo esc_attr(implode(',', $this->utilities->request_form_hash_param_names()));?>" data-nonce="slug" data-action="<?php echo esc_attr(base64_encode($this->home_lang.'request_submitted'));?>">

                    <div class="modal-header clearfix">
                        <h3 class="pull-left inline-block text-center uppercase linkcolor"><?php echo esc_html(__('Request a Quote', 'dynamicaviation')); ?></h3>
                        <span class="close pointer pull-right large"><span class="dashicons dashicons-no"></span></span>
                    </div>				

                    <div class="pure-g gutters">
                        <div class="pure-u-1 pure-u-md-1-2">
                            <div class="bottom-20">
                                <label for="first_name"><?php echo esc_html(__('Name', 'dynamicaviation')); ?></label>
                                <input type="text" name="first_name" />								
                            </div>
                        </div>
                        <div class="pure-u-1 pure-u-md-1-2">
                            <div class="bottom-20">
                                <label for="lastname"><?php echo esc_html(__('Last Name', 'dynamicaviation')); ?></label>
                                <input type="text" name="lastname" />			
                            </div>
                        </div>
                    </div>
                    <div class="pure-g gutters">
                        <div class="pure-u-1 pure-u-md-1-2">
                            <div class="bottom-20">
                                <label for="email"><?php echo esc_html(__('Email', 'dynamicaviation')); ?></label>
                                <input type="email" name="email" />								
                            </div>
                        </div>
                        <div class="pure-u-1 pure-u-md-1-2">
                            <div class="bottom-20">
                                <label for="repeat_email"><?php echo esc_html(__('Repeat Email', 'dynamicaviation')); ?></label>
                                <input type="email" name="repeat_email" />								
                            </div>                           
                        </div>
                    </div>

                    <div class="pure-g gutters">
                        <div class="pure-u-1 pure-u-md-1-2">
                            <div class="bottom-20">
                                <label for="phone"><?php echo esc_html(__('Phone', 'dynamicaviation')); ?></label>
                                <div class="pure-g">
                                    <div class="pure-u-1-2">
                                            <select name="country_calling_code" class="countryCallingCode"><option>--</option></select>
                                    </div>
                                    <div class="pure-u-1-2">
                                         <input type="number" name="phone" />
                                    </div>
                                </div>
                            </div>
                        </div>							
                    </div>

                                    
                    <div class="hidden">
                        <div id="aircraft_fields"></div>                    
                        <?php echo $this->obj_to_inputs(); ?>
                    </div>
                    
                    <?php if(get_option('dy_recaptcha_site_key')): ?>
                            <button data-badge="bottomleft" data-callback="validateAviationEstimateRequest" class="g-recaptcha pure-button pure-button-primary" data-sitekey="<?php echo esc_attr(get_option('dy_recaptcha_site_key')); ?>" data-action='estimate'><span class="dashicons dashicons-airplane"></span> <?php echo esc_html(__('Send Request', 'dynamicaviation'));?></button>	
                    <?php endif; ?>

                </form>
            </div>

        <?php
        
        $content = ob_get_contents();
        ob_end_clean();			
		return $content;
    }

    public function template()
    {
        $output = '';
        $rows = '';

        $capacity_args = array(
            'key' => 'aircraft_passengers',
            'value' => $this->get->pax_num,
            'type' => 'numeric',
            'compare' => '>='
        );

        $query_args = array(
            'post_type' => 'aircrafts',
            'posts_per_page' => 200,
            'meta_query' => array($capacity_args),
			'meta_key' => 'aircraft_price_per_hour',
			'orderby' => 'meta_value_num',
			'order' => 'ASC'
        );

        $wp_query = new WP_Query($query_args);
        
        if ($wp_query->have_posts())
        {
            $output .= $this->pax_template();
            $output .= sprintf(
                '<p class="large"><strong>%s:</strong> %s</p>',
                esc_html(__('Departure', 'dynamicaviation')),
                $this->start_itinerary()
            );

            if ((int) ($this->get->aircraft_flight ?? 0) === 1) {
                $output .= sprintf(
                    '<p class="large"><strong>%s:</strong> %s</p>',
                    esc_html(__('Return', 'dynamicaviation')),
                    $this->end_itinerary()
                );
            }

            while($wp_query->have_posts())
            {
                $wp_query->the_post();
                global $post;

                $raw_table_price = (string) aviation_field('aircraft_rates', $post->ID);

                if(empty($raw_table_price)) continue;
               
                $table_price = json_decode(html_entity_decode($raw_table_price), true);

                if(!is_array($table_price) || count($table_price) === 0) continue;
                if(!array_key_exists('aircraft_rates_table', $table_price)) continue;

                $aircraft_rates_table = $table_price['aircraft_rates_table'];

                if(!is_array($aircraft_rates_table) || count($aircraft_rates_table) === 0) continue;
                

                $rows .= $this->iterate_rows($post, $aircraft_rates_table);
            }

            wp_reset_postdata();

            if($rows)
            {
                $output .= $this->table_container($rows);
                $output .= $this->request_form(true);
            }
            else
            {
                $output = $this->not_found();
                $output .= $this->request_form(false);
            }            
        }
        else
        {
            $output = $this->not_found();
            $output .= $this->request_form(false);
        }

        $output .= $this->connected_packages();

        return $output;
    }

    public function get_destinations_contected_packages_ids()
    {
        $output = array();

        $query_origin = array(
            'key' => 'aircraft_base_iata',
            'value' => $this->get->aircraft_origin,
            'compare' => '='
        );

        $query_destination = array(
            'key' => 'aircraft_base_iata',
            'value' => $this->get->aircraft_destination,
            'compare' => '='
        );

		$args = array(
			'post_type' => 'destinations',
			'posts_per_page' => 200, 
			'post_parent' => 0,
            'lang' => $this->current_language,
            'meta_query' => array('relation' => 'OR', $query_origin,  $query_destination)
		);

        $wp_query = new WP_Query( $args );

        if ( $wp_query->have_posts() )
        {
            while ( $wp_query->have_posts() )
            {
                $wp_query->the_post();
                global $post;

                $base_iata = aviation_field('aircraft_base_iata', $post->ID);

                if($base_iata)
                {
                    $connected_ids = $this->utilities->items_per_line_to_array(aviation_field('aircraft_connected_packages', $post->ID));

                    for($x = 0; $x < count($connected_ids); $x++ )
                    {
                        $output[] = $connected_ids[$x];
                    }
                }
            }

            wp_reset_postdata();
        }

        return $output;
    }

    public function connected_packages()
    {
        if(!function_exists('package_field'))
        {
            return '';
        }

        global $polylang;
        $output = '';
        $connected_ids = $this->get_destinations_contected_packages_ids();

        if(count($connected_ids) === 0)
        {
            return '';
        }

        if(isset($polylang))
        {
            for($x = 0; $x < count($connected_ids); $x++)
            {
                $localized_id = pll_get_post($connected_ids[$x], $this->current_language);

                if($localized_id)
                {
                    $connected_ids[$x] = $localized_id;
                }
                else
                {
                    unset($connected_ids[$x]);
                }
            }
        }


        if(count($connected_ids) === 0)
        {
            return '';
        }


        //limits packages to minutes and hours
        $meta_query = array(
            'key' => 'package_length_unit',
            'value' => '1',
            'compare' => '<='
        );        

		$args = array(
			'post_type' => 'packages',
			'posts_per_page' => 200, 
			'post_parent' => 0,
            'lang' => $this->current_language,
            'post__in' => $connected_ids,
            'meta_query' => array($meta_query)
		);

        $wp_query = new WP_Query( $args );

        if ( $wp_query->have_posts() )
        {
            $output = '<hr/><h4>'.esc_html(sprintf(__('Alternative transport options to %s', 'dynamicaviation'), $this->get->aircraft_destination)).'</h4>';

            $output .= '<table class="bottom-40 pure-table pure-table-bordered pure-table-striped text-center small"><thead><tr>';

            $output .= '<th>'.__('Transport', 'dynamicaviation').'</th>';

            $output .= '<th>'.__('Duration', 'dynamicaviation').'</th>';
            
            $output .= '</tr></thead><tbody>';

            while ( $wp_query->have_posts() )
            {
                $wp_query->the_post();

                global $post;
                $duration_float = floatval(package_field('package_duration', $post->ID));
                $duration_unit = intval(package_field('package_length_unit', $post->ID));

                if($duration_unit === 0 )
                {
                    $duration_float = $duration_float / 60;
                }

                $output .= '<tr>';
                $output .= '<td><strong><a href="'.esc_url(get_the_permalink()).'">'.esc_html($post->post_title).'</a></strong></td>'; 
                $output .= '<td><span class="dashicons dashicons-clock"></span> '.esc_html($this->utilities->convertNumberToTime($duration_float)).'</td>';
                $output .= '</tr>';
            }

            $output .= '</tbody></table>';

            wp_reset_postdata();
        }  

        return $output;
    }

}

?>