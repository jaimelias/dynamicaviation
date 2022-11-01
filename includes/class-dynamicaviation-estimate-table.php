<?php


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

        $intval_params = array('aircraft_pax', 'aircraft_flight');

        $this->param_names = array(
            'aircraft_pax', 
            'aircraft_flight', 
            'aircraft_departure_date', 
            'aircraft_origin_l', 
            'aircraft_destination_l', 
            'aircraft_departure_hour',
            'aircraft_return_date',
            'aircraft_return_hour',
            'aircraft_origin',
            'aircraft_destination',
        );

        $this->get = (object) array();

        for($x = 0; $x < count($this->param_names); $x++)
        {
            $k = $this->param_names[$x];

            if(isset($_REQUEST[$k]))
            {
                $v = sanitize_text_field($_REQUEST[$k]);

                if(in_array($k, $intval_params))
                {
                    $v = intval($v);
                }

                $this->get->$k = $v;
            }
        }
    }
    
    public function obj_to_inputs()
    {
        $output = '';
        $obj = $this->get;
        $obj->departure_itinerary = $this->departure_itinerary();
        $obj->return_itinerary = $this->return_itinerary();
        $obj->channel = '';
        $obj->device = '';
        $obj->landing_path = '';
        $obj->landing_domain = '';

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
        return '<p class="large"><strong>'.esc_html(__('Passengers', 'dynamicaviation')).':</strong> <span class="linkcolor">'.esc_html($this->get->aircraft_pax).'</span></p>';
    }

    public function departure_itinerary()
    {
        $output = '';
        $output .= $this->get->aircraft_origin_l;
        $output .= ' &rsaquo;&rsaquo;&rsaquo; ';
        $output .= $this->get->aircraft_destination_l;
        $output .= ' '.__('on', 'dynamicaviation').' ';
        $output .= date_i18n(get_option( 'date_format' ), strtotime($this->get->aircraft_departure_date));
        $output .= ' '.__('at', 'dynamicaviation').' ';
        $output .= $this->get->aircraft_departure_hour;
        return $output;
    }

    public function return_itinerary()
    {
        $output = '';

        if($this->get->aircraft_flight === 1)
        {
            $output = '';            
            $output .= $this->get->aircraft_destination_l;
            $output .= ' &rsaquo;&rsaquo;&rsaquo; ';
            $output .= $this->get->aircraft_origin_l;
            $output .= ' '.__('on', 'dynamicaviation').' ';
            $output .= date_i18n(get_option('date_format'), strtotime($this->get->aircraft_return_date));
            $output .= ' '.__('at', 'dynamicaviation').' ';
            $output .= $this->get->aircraft_return_hour;
        }

        return $output;
    }

    public function table_container($rows)
    {
        ob_start(); 

        if($rows): ?>

            <hr/>
            
            <table class="bottom-40 pure-table pure-table-bordered pure-table-striped text-center instant_quote_table small">
                <thead>
                    <tr>
                        <th <?php echo (!$this->is_mobile) ? ' colspan="2" ' : '' ;?>><?php echo (esc_html__('Flights', 'dynamicaviation')); ?></th>
        
                        <?php if(!$this->is_mobile): ?>
                            <th><?php echo (esc_html__('Duration', 'dynamicaviation')); ?></th>
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
            'fees' => 0
        );

        $output = $default;
        $itinerary = array();
        $chart = array();
        $base = aviation_field( 'aircraft_base_iata', $aicraft_id);
        $request_routes = array($origin, $destination);
        sort($request_routes);

        $diff = array_diff($request_routes, array($base, $base));
        $count_diff = count($diff);

        if($count_diff === 0)
        {
            return $default;
        }

        if($count_diff === 1)
        {
            $itinerary = array(
                array($origin, $destination)
            );

            //option #1
            $chart = $this->utilities->get_rates_from_itinerary($itinerary, $table_price);
        }

        //this part of the code works perfetly but fails in the price-table.php figing only origin + destionation not including base
        //option #2 gives incorrect prices on price-table.php


        /*  elseif($count_diff === 2)
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
        }*/

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

            $output['fees'] += ($this->get->aircraft_flight === 1)  ? 
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
                $output['duration'] = floatval($chart[$i][2]);
                $output['stops'] = $chart[$i][5];
                $output['seats'] = $chart[$i][6];
                $output['weight_pounds'] = $chart[$i][7];
            }
        }

        return $output;
    }

    public function iterate_rows($post, $table_price)
    {
        $table = '';
        $aircraft_url = $this->home_lang.$post->post_type.'/'.$post->post_name;
        $thumbnail = get_the_post_thumbnail($post->ID, array( 100, 100), array('class' => 'img-responsive', 'alt' => esc_attr($post->post_title)));
        $large_attr = (!$this->is_mobile) ? ' class="large" ' : ''; 
        $align_left_attr = (!$this->is_mobile) ? ' class="text-left" ' : '';

        $origin = $this->get->aircraft_origin;
        $destination = $this->get->aircraft_destination;
        $itinerary = $this->get_routes($post->ID, $origin, $destination, $table_price);

        if(!$itinerary)
        {
            return '';
        }

        if(!isset($itinerary['price']) ||  !isset($itinerary['duration']))
        {
            return '';
        }

        if($itinerary['price'] === 0 || $itinerary['duration'] === 0)
        {
            return '';
        }

        $price = $itinerary['price'];
        $fees = $itinerary['fees'];
        $duration = $itinerary['duration'];
        $seats = $itinerary['seats'];
        $weight_pounds = $itinerary['weight_pounds'];
        $aircraft_price = $price + ($fees * $this->get->aircraft_pax);
        $weight_kg = intval($weight_pounds * 0.453592);
        $weight_allowed = esc_html($weight_pounds.' '.__('pounds', 'dynamicaviation').' | '.$weight_kg.__('kg', 'dynamicaviation'));

        $flight_array = array(
            'aircraft_price' => $aircraft_price,
            'aircraft_name' => $post->post_title,
            'aircraft_id' => $post->ID,
            'aircraft_seats' => $seats,
            'aircraft_weight' => $weight_allowed,
            'aircraft_url' => esc_url($aircraft_url)
        );
        
        $aircraft_col = ($this->is_mobile) ? '<a href="'.esc_url($aircraft_url).'">'.$thumbnail.'</a><br/>' : '';            
        
        $aircraft_col .= '<a class="strong" href="'.esc_url($aircraft_url).'">'.esc_html($post->post_title).'</a><br/><small>'.esc_html($this->utilities->aircraft_type(aviation_field( 'aircraft_type', $post->ID))).'</small> <strong><i class="fas fa-male" aria-hidden="true"></i> '.esc_html($seats).'</strong><br/><small>'.esc_html('Max').' ('.$weight_allowed.')</small>';

        $price_col = '<small class="text-muted">USD</small><br/><strong '.$large_attr.'><span class="text-muted">$</span>'.esc_html(number_format($price, 2, '.', ',')).'</strong>';
        
        if(floatval($fees) > 0)
        {
            $price_col .= '<br/><span class="text-muted">'.__('Fees per pers.', 'dynamicaviation').' $'.esc_html(number_format($fees, 2, '.', ',')).'</span>';
        }
        
        if($this->is_mobile)
        {
            //duration in mobile
            $price_col .= '<hr style="margin-top: 10px; margin-bottom: 10px;"/><small class="text-muted"><i class="text-muted fas fa-clock" aria-hidden="true"></i></small><br/><strong '.$large_attr.'>'.esc_html($this->utilities->convertNumberToTime($duration)).'</strong>';
        }			
        
        $row = '<tr>';
        
        if(!$this->is_mobile)
        {
            $row .= '<td><a href="'.esc_url($aircraft_url).'">'.$thumbnail.'</a></td>';
        }


        $row .= '<td '.$align_left_attr.'>'.$aircraft_col.'</td>';
        
        if(!$this->is_mobile)
        {
            $row .= '<td><i class="fas fa-clock" aria-hidden="true"></i> '.esc_html($this->utilities->convertNumberToTime($duration)).'</td>';
        }
        
        $row .= '<td>'.$price_col.'</td>';
        
        $row .= '<td><button class="strong small button-success pure-button" data-aircraft="'.esc_html(htmlentities(json_encode($flight_array))).'"><i class="fas fa-envelope" aria-hidden="true"></i> '.esc_html(__('Quote', 'dynamicaviation')).'</button></td>';			
        $row .= "</tr>";		
        $table .= $row;

        return $table;
    }

    public function request_form($hide_contact_form)
    {
        ob_start(); 
        ?>

            <div id="aircraft_booking_container" class="<?php echo ($hide_contact_form) ? 'hidden' : ''; ?> animate-fade">

                <form method="post" id="aircraft_booking_request" action="<?php echo esc_url($this->home_lang.'request_submitted');?>/">

                    <div class="modal-header clearfix">
                        <h3 class="pull-left inline-block text-center uppercase linkcolor"><?php echo (esc_html__('Request a Quote', 'dynamicaviation')); ?></h3>
                        <span class="close pointer pull-right large"><i class="fas fa-times"></i></span>
                    </div>				

                    <div class="pure-g gutters">
                        <div class="pure-u-1 pure-u-md-1-2">
                            <div class="bottom-20">
                                <label for="first_name"><?php echo (esc_html__('Name', 'dynamicaviation')); ?></label>
                                <input type="text" name="first_name" />								
                            </div>
                        </div>
                        <div class="pure-u-1 pure-u-md-1-2">
                            <div class="bottom-20">
                                <label for="lastname"><?php echo (esc_html__('Last Name', 'dynamicaviation')); ?></label>
                                <input type="text" name="lastname" />			
                            </div>
                        </div>
                    </div>
                    <div class="pure-g gutters">
                        <div class="pure-u-1 pure-u-md-1-2">
                            <div class="bottom-20">
                                <label for="email"><?php echo (esc_html__('Email', 'dynamicaviation')); ?></label>
                                <input type="email" name="email" />								
                            </div>
                        </div>
                        <div class="pure-u-1 pure-u-md-1-2">
                            <div class="bottom-20">
                                <label for="phone"><?php echo (esc_html__('Phone', 'dynamicaviation')); ?></label>
                                <input type="text" name="phone" />								
                            </div>
                        </div>
                    </div>
                    <div class="pure-g gutters">
                        <div class="pure-u-1 pure-u-md-1-2">
                            <div class="bottom-20">
                                <label for="country"><?php echo (esc_html__('Country', 'dynamicaviation')); ?></label>
                                <select name="country" class="countrylist"><option>--</option></select>								
                            </div>
                        </div>
                        <div class="pure-u-1 pure-u-md-1-2">
                            <! -- empty col -->
                        </div>
                    </div>				
                                    
                    <div class="hidden">
                        <div id="aircraft_fields"></div>                    
                        <?php echo $this->obj_to_inputs(); ?>
                    </div>
                    
                    <?php if(get_option('captcha_site_key')): ?>
                            <button data-badge="bottomleft" data-callback="validateAviationEstimateRequest" class="g-recaptcha pure-button pure-button-primary" data-sitekey="<?php echo esc_attr(get_option('captcha_site_key')); ?>" data-action='estimate'><i class="fas fa-plane"></i> <?php echo esc_html(__('Send Request', 'dynamicaviation'));?></button>	
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
            'value' => $this->get->aircraft_pax,
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
            $output .= '<p class="large"><strong>'.esc_html(__('Departure', 'dynamicaviation')).':</strong> '.$this->departure_itinerary().'</p>';
            
            if($this->get->aircraft_flight === 1)
            {
                $output .= '<p class="large"><strong>'.esc_html(__('Return', 'dynamicaviation')).':</strong> '.$this->return_itinerary().'</p>';
            }
            

            while($wp_query->have_posts())
            {
                $wp_query->the_post();
                global $post;
               
                $table_price = json_decode(html_entity_decode(aviation_field('aircraft_rates', $post->ID)), true);

                if(array_key_exists('aircraft_rates_table', $table_price))
                {
                    $aircraft_rates_table = $table_price['aircraft_rates_table'];

                    if(is_array($aircraft_rates_table))
                    {
                        if(count($aircraft_rates_table) > 0)
                        {
                            $rows .= $this->iterate_rows($post, $aircraft_rates_table);
                        }
                    }

                }
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
            $output = '<hr/><h4>'.esc_html(sprintf(__('Alternative transport options to %s', 'dynamicaviation'), $this->get->aircraft_destination_l)).'</h4>';

            $output .= '<table class="bottom-40 pure-table pure-table-bordered pure-table-striped text-center small"><thead><tr>';

            $output .= '<th>'.__('Transport', 'dynamicaviation').'</th>';

            $output .= '<th>'.__('Duration', 'dynamicaviation').'</th>';
            
            $output .= '</tr></thead><tbody>';

            while ( $wp_query->have_posts() )
            {
                $wp_query->the_post();

                global $post;
                $duration = floatval(package_field('package_duration', $post->ID));
                $duration_unit = intval(package_field('package_length_unit', $post->ID));

                if($duration_unit === 0 )
                {
                    $duration = $duration / 60;
                }

                $output .= '<tr>';
                $output .= '<td><strong><a href="'.esc_url(get_the_permalink()).'">'.esc_html($post->post_title).'</a></strong></td>'; 
                $output .= '<td><i class="fas fa-clock" aria-hidden="true"></i> '.esc_html($this->utilities->convertNumberToTime($duration)).'</td>';
                $output .= '</tr>';
            }

            $output .= '</tbody></table>';

            wp_reset_postdata();
        }  

        return $output;
    }

}

?>