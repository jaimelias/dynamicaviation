<?php


class Dynamic_Aviation_Aircrafts_Table {

    public function __construct($utilities) {
        $this->utilities = $utilities;
        $this->init();
	}

    public function init()
    {
        add_filter('dy_aviation_aircrafts_table', array(&$this, 'template'));
    }

    public function get()
    {
        return (object) array(
            'aircraft_pax' => intval($_GET['aircraft_pax']),
            'aircraft_flight' => intval($_GET['aircraft_flight']),
            'aircraft_departure_date' => sanitize_text_field($_GET['aircraft_departure_date']),
            'aircraft_origin_l' => sanitize_text_field($_GET['aircraft_origin_l']),
            'aircraft_destination_l' => sanitize_text_field($_GET['aircraft_destination_l']),
            'aircraft_departure_hour' => sanitize_text_field($_GET['aircraft_departure_hour']),
            'aircraft_return_date' => sanitize_text_field($_GET['aircraft_return_date']),
            'aircraft_return_hour' => sanitize_text_field($_GET['aircraft_return_hour']),
            'aircraft_origin' => sanitize_text_field($_GET['aircraft_origin']),
            'aircraft_destination' => sanitize_text_field($_GET['aircraft_destination'])
        );
    }
    
    public function obj_to_inputs()
    {
        $output = '';
        $obj = $this->get();
        $obj->departure_itinerary = $this->departure_itinerary();
        $obj->return_itinerary = $this->return_itinerary();
        $obj->channel = '';
        $obj->device = '';
        $obj->landing_path = '';
        $obj->landing_domain = '';

        foreach($obj as $key => $value)
        {
            $output .= '<input value="'.esc_attr($value).'" name="'.esc_attr($key).'" class="'.esc_attr($key).'" />';
            $output .= "\n\t\t\t\t\t\t";
        }


        return $output;
    }

    public function capacity_args()
    {
        return array(
            'key' => 'aircraft_passengers',
            'value' => $this->get()->aircraft_pax,
            'type' => 'numeric',
            'compare' => '>='
        );       
    }

    public function query_args()
    {
        return array(
            'post_type' => 'aircrafts',
            'posts_per_page' => 200,
            'meta_query' => array($this->capacity_args()),
            'meta_key' => 'aircraft_commercial',
            'orderby' => 'meta_value',
            'order' => 'ASC'
        );
    }

    public function not_found()
    {
        return '<p class="large">'.esc_html(__('The requested quote is not available in our website yet. Please contact our sales team for an immediate answer.', 'dynamicaviation')).'</p>';
    }

    public function pax_template()
    {
        return '<p class="large"><strong>'.esc_html(__('Passengers', 'dynamicaviation')).':</strong> <span class="linkcolor">'.esc_html($this->get()->aircraft_pax).'</span></p>';
    }

    public function departure_itinerary()
    {
        $output = '';
        $output .= $this->get()->aircraft_origin_l;
        $output .= ' &rsaquo;&rsaquo;&rsaquo; ';
        $output .= $this->get()->aircraft_destination_l;
        $output .= ' '.__('on', 'dynamicaviation').' ';
        $output .= date_i18n(get_option( 'date_format' ), strtotime($this->get()->aircraft_departure_date));
        $output .= ' '.__('at', 'dynamicaviation').' ';
        $output .= $this->get()->aircraft_departure_hour;
        return $output;
    }

    public function return_itinerary()
    {
        $output = '';

        if($this->get()->aircraft_flight === 1)
        {
            $output = '';            
            $output .= $this->get()->aircraft_destination_l;
            $output .= ' &rsaquo;&rsaquo;&rsaquo; ';
            $output .= $this->get()->aircraft_origin_l;
            $output .= ' '.__('on', 'dynamicaviation').' ';
            $output .= date_i18n(get_option('date_format'), strtotime($this->get()->aircraft_return_date));
            $output .= ' '.__('at', 'dynamicaviation').' ';
            $output .= $this->get()->aircraft_return_hour;
        }

        return $output;
    }

    public function table_container($table)
    {
        ob_start(); 
        ?>

        <hr/>
		
        <table class="margin-bottom pure-table pure-table-bordered text-center instant_quote_table small">
            <thead>
                <tr>
                    <th><?php esc_html_e(__('Flights', 'dynamicaviation')); ?></th>
    
                    <?php if(!wp_is_mobile()): ?>
                        <th><?php esc_html_e(__('Duration', 'dynamicaviation')); ?></th>
                    <?php endif; ?>
                    
                    <th colspan="2"><?php esc_html_e(($this->get()->aircraft_flight === 0) ? __('One Way', 'dynamicaviation') : __('Round Trip', 'dynamicaviation'));?></th>
                </tr>
            </thead>
            <tbody>
                <?php echo $table; ?>
            </tbody>
        </table>


        <?php
        $content = ob_get_contents();
        ob_end_clean();	
		
		return $content;
    }


    public function iterate_rows($post, $table_price, $is_commercial)
    {
        $table = '';
        $aircraft_url = home_lang().$post->post_type.'/'.$post->post_name;

		for($x = 0; $x < count($table_price); $x++)
		{
			if(($this->get()->aircraft_origin == $table_price[$x][0] && $this->get()->aircraft_destination == $table_price[$x][1]) || ($this->get()->aircraft_origin == $table_price[$x][1] && $this->get()->aircraft_destination == $table_price[$x][0]))
			{
				$duration = $table_price[$x][2];
				$price = floatval($table_price[$x][3]);
                $fees = floatval($table_price[$x][4]);
				$seats = intval($table_price[$x][6]);
				$weight_pounds = intval($table_price[$x][7]);
				$weight_kg = intval($weight_pounds * 0.453592);
				$weight_allowed = esc_html($weight_pounds.' '.__('pounds', 'dynamicaviation').' | '.$weight_kg.__('kg', 'dynamicaviation'));
				
				
				if($is_commercial)
				{
					$price = $price * $this->get()->aircraft_pax;
				}

				if($this->get()->aircraft_flight === 1)
				{
					$price = $price * 2;
					$fees = $fees * 2;
				}

                $aircraft_price = $price + ($fees * $this->get()->aircraft_pax);

                $flight_array = array(
                    'aircraft_price' => $aircraft_price,
                    'aircraft_name' => $post->post_title,
                    'aircraft_id' => intval($post->ID),
                    'aircraft_seats' => $seats,
                    'aircraft_weight' => $weight_allowed,
                    'aircraft_url' => esc_url($aircraft_url)
                );
				
				$aircraft_description = '';
				
				if(aviation_field( 'aircraft_commercial' ) != 0)
				{
					if($is_commercial)
					{
						$aircraft_description .= '<strong>'.esc_html(__('Commercial Flight', 'dynamicaviation')).'</strong>';
					}
					
					$price_row = '<td><small class="text-muted">USD</small><br/><strong class="large">'.esc_html('$'.number_format($price, 0, '.', ',')).'</strong><br /><span class="small text-muted">'.esc_html('$'.number_format(($price / $this->get()->aircraft_pax), 0, '.', ',')).' '.esc_html(__('Per Person', 'dynamicaviation')).'</span>';
					
					if(floatval($fees) > 0)
					{
						$price_row .= '<br/><span class="text-muted">'.__('Fees per pers.', 'dynamicaviation').' $'.number_format($fees, 0, '.', ',').'</span>';
					}					
					
					$price_row .= '</td>';
				}
				else
				{
					$aircraft_description .= '<a class="strong" href="'.esc_url($aircraft_url).'">'.esc_html($post->post_title).'</a>';
					$aircraft_description .= '<br/>';
					$aircraft_description .= '<small>'.esc_html($this->utilities->aircraft_type(aviation_field( 'aircraft_type' ))).'</small>';
					$aircraft_description .= ' <strong><i class="fas fa-male" aria-hidden="true"></i> '.esc_html($seats).'</strong>';					
					$aircraft_description .= '<br/>';
					$aircraft_description .= '<small>'.esc_html('Max').' ('.$weight_allowed.')</small>';
					$price_row = '<td><small class="text-muted">USD</small><br/><strong class="large">'.esc_html('$'.number_format($price, 0, '.', ',')).'</strong>';
					
					if(floatval($fees) > 0)
					{
						$price_row .= '<br/><span class="text-muted">'.__('Fees per pers.', 'dynamicaviation').' $'.number_format($fees, 0, '.', ',').'</span>';
					}					
					
					$price_row .= '</td>';
				}			
				
				$row = '<tr>';
				$row .= '<td>'.$aircraft_description.'</td>';
				
				if(!wp_is_mobile())
				{
					$row .= '<td><i class="fas fa-clock" aria-hidden="true"></i> '.esc_html($this->utilities->convertNumberToTime($duration)).'</td>';
				}
				
				$row .= $price_row;
				$row .= '<td>';
				
				$select_label = __('Quote', 'dynamicaviation');	
				$row .= '<button class="strong button-success pure-button" data-aircraft="'.esc_html(htmlentities(json_encode($flight_array))).'"><i class="fas fa-envelope" aria-hidden="true"></i> '.esc_html($select_label).'</button>';			
				$row .= '</td>';
				$row .= "</tr>";				
				$table .= $row;
			}
		}

        return $table;
    }

    public function request_form()
    {
        ob_start(); 
        ?>

            <div id="aircraft_booking_container" class="hidden animate-fade">

                <form method="post" id="aircraft_booking_request" action="<?php echo esc_url(home_lang().'request_submitted');?>/">

                    <div class="modal-header clearfix">
                        <h3 class="pull-left inline-block text-center uppercase linkcolor"><?php esc_html_e(__('Request a Quote', 'dynamicaviation')); ?></h3>
                        <span class="close pointer pull-right large"><i class="fas fa-times"></i></span>
                    </div>				

                    <div class="pure-g gutters">
                        <div class="pure-u-1 pure-u-md-1-2">
                            <div class="bottom-20">
                                <label for="first_name"><?php esc_html_e(__('Name', 'dynamicaviation')); ?></label>
                                <input type="text" name="first_name" />								
                            </div>
                        </div>
                        <div class="pure-u-1 pure-u-md-1-2">
                            <div class="bottom-20">
                                <label for="lastname"><?php esc_html_e(__('Last Name', 'dynamicaviation')); ?></label>
                                <input type="text" name="lastname" />			
                            </div>
                        </div>
                    </div>
                    <div class="pure-g gutters">
                        <div class="pure-u-1 pure-u-md-1-2">
                            <div class="bottom-20">
                                <label for="email"><?php esc_html_e(__('Email', 'dynamicaviation')); ?></label>
                                <input type="email" name="email" />								
                            </div>
                        </div>
                        <div class="pure-u-1 pure-u-md-1-2">
                            <div class="bottom-20">
                                <label for="phone"><?php esc_html_e(__('Phone', 'dynamicaviation')); ?></label>
                                <input type="text" name="phone" />								
                            </div>
                        </div>
                    </div>
                    <div class="pure-g gutters">
                        <div class="pure-u-1 pure-u-md-1-2">
                            <div class="bottom-20">
                                <label for="country"><?php esc_html_e(__('Country', 'dynamicaviation')); ?></label>
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
                        <?php if(get_option('captcha_site_key') != null): ?>
                            <button data-badge="bottomleft" data-callback="validateAviationEstimateRequest" class="g-recaptcha pure-button pure-button-primary" data-sitekey="<?php esc_html_e(get_option('captcha_site_key')); ?>" data-action='estimate'><i class="fas fa-plane"></i> <?php esc_html_e(__('Send Request', 'dynamicaviation'));?></button>	
                        <?php endif; ?>
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
        $wp_query = new WP_Query($this->query_args());

        if ($wp_query->have_posts())
        {

            $output .= $this->pax_template();
            $output .= '<p class="large"><strong>'.esc_html(__('Departure', 'dynamicaviation')).':</strong> '.$this->departure_itinerary().'</p>';
            
            if($this->get()->aircraft_flight === 1)
            {
                $output .= '<p class="large"><strong>'.esc_html(__('Return', 'dynamicaviation')).':</strong> '.$this->return_itinerary().'</p>';
            }
            

            while($wp_query->have_posts())
            {
                $wp_query->the_post();
                global $post;
                $has_rows = true;
                
                $table_price = aviation_field( 'aircraft_rates' );
                $table_price = json_decode(html_entity_decode($table_price), true);
                $is_commercial = (intval(aviation_field( 'aircraft_commercial')) === 1) ? true : false;
                $rows .= $this->iterate_rows($post, $table_price, $is_commercial);
            }

            if($rows !== '')
            {
                $output .= $this->table_container($rows);
                $output .= $this->request_form();
            }

            wp_reset_postdata();
        }
        else
        {
            $output = $this->not_found();
        }

        return $output;
    }
}

?>