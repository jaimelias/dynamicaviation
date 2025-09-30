<?php 

#[AllowDynamicProperties]
class Dynamic_Aviation_Training_Data {

    public function __construct($utilities)
    {
        add_action('wp', array(&$this, 'export_single_file'));
        add_filter('wp_headers', array(&$this, 'single_file_headers'), 999);

        $this->utilities = $utilities;
        $this->alt_formats = ['text', 'json', 'html', 'markdown'];

        $this->all_content_types = [
            'text' => 'text/plain; charset=UTF-8',
            'html' => 'text/html; charset=UTF-8',
            'markdown' => 'text/markdown; charset=UTF-8',
            'json' => 'application/json',
        ];
        $this->all_extensions = [
            'text' => 'txt',
            'json' => 'json',
            'html' => 'html',
            'markdown' => 'md',
        ];
        
        $this->format = 'text';
        $this->extension = 'txt';
        $this->content_type = $this->all_content_types[$this->format];

    }

    public function  single_file_headers($headers)
    {


        if(!empty(secure_get('fly')) && isset($_GET['training-data'])) {


            $format = secure_get('format');

            if(!empty($format) && in_array($format,  $this->alt_formats)) {
                $this->format = $format;
                $this->content_type = $this->all_content_types[$this->format];
                $this->extension = $this->all_extensions[$this->format];
            }

            $headers['Content-Type'] = $this->content_type;
        }

        return $headers;
    }

    public function export_single_file() {
        $slug = get_query_var( 'fly' );

        if(!empty($slug) && isset($_GET['training-data'])) {

            $training_obj = $this->get_training_data($slug);
            if($this->format === 'json')  exit(json_encode($training_obj));

            $output = '';
            $service_name = $training_obj->service_name;

            if(in_array($this->format, ['text', 'markdown'])) {
                $output .= "# {$service_name}\n";
                $output .= concatenate_object_to_text($training_obj, "* ", "- ", "\n");
            }
            else if($this->format === 'html') {
                $output .= '<!DOCTYPE html><html><head><title>'.esc_html($service_name).'</title></head><body>';
                $output .= '<h1>'.esc_html($service_name).'</h1>';
                $output .= concatenate_object_to_html($training_obj);
                $output .= "</body></html>";
            }

            exit($output);
        }

    }

    public function get_training_data($slug) {

        $destination_airport = $this->utilities->airport_data_by_slug($slug);

        if (!is_array($destination_airport) || empty($destination_airport)) wp_die('Unable to fetch airport_data from DB.');
        if (!array_key_exists('iata', $destination_airport)) wp_die('Invalid airport_data schema.');

        global $polylang;
        $home_lang = home_lang();
        $current_language = (string) current_language();
        $languages = (array) get_languages();
        $default_language = (string) default_language();

        // Prefer localized name if available; otherwise fall back to "Airport, City" when different.
        $destination_airport_name = $this->get_airport_name($destination_airport, $current_language);
        $service_name = sprintf(__('Flights %s', 'dynamicaviation'), $destination_airport_name);

        $output = (object) [
            'service_name' =>  $service_name,
            'service_id' => $destination_airport['iata'],
            'service_type' => 'transport',
            'service_categories' => ['Flights', 'Charter Flights', 'Private Jets', 'Helicopter Transfers', 'Air Ticket', 'Plane Ticket'],
            'service_aircrafts' => [],
            'service_rates' => [],
            'service_web_checkout' => 'available',
            'service_links_by_language' => [],
            'service_name_translations' => [],
            'service_enabled_days_of_the_week' => __('Everyday', 'dynamicaviation'),
            'service_hidden_rules' => []
        ];

        $starting_at = PHP_INT_MAX;
        $starting_at_capacity = PHP_INT_MAX;

        if(isset($polylang))
        {
            foreach ($languages as $language) {

                $lang_name = (class_exists('Locale')) ?  \Locale::getDisplayLanguage($language, $default_language) : $language; 

                if ($language === $default_language) {
                    $output->service_links_by_language[$lang_name] = esc_url("{$home_lang}fly/{$slug}");
                }  else {
                    $output->service_links_by_language[$lang_name] = esc_url("{$home_lang}{$language}/fly/{$slug}");
                }

                $output->service_name_translations[$lang_name] = sprintf(pll_translate_string('Charter Flights %s', $language), $destination_airport_name);
            }
        }
        else
        {
            $output->service_links_by_language[$current_language] = "{$home_lang}fly/{$slug}";
        }            

        $args = array(
            'post_type' => 'aircrafts',
            'posts_per_page' => 200, 
            'post_parent' => 0,
            'meta_key' => 'aircraft_price_per_hour',
            'orderby' => 'meta_value_num',
            'order' => 'ASC',
        );

        $wp_query = new WP_Query($args);

        $airports_data_map = [];
        $airports_data_map[$destination_airport['iata']] = $destination_airport;

        if ( $wp_query->have_posts() )
        {
            while ($wp_query->have_posts() ) {
                $wp_query->the_post();
                global $post;

                $aircraft_name = $post->post_title;
                $aircraft_base_iata = (string) aviation_field('aircraft_base_iata', $post->ID);
                $aircraft_type  = $this->utilities->aircraft_type(aviation_field('aircraft_type', $post->ID));

                if(empty($aircraft_base_iata)) continue;

                $aircraft_base_airport = [];

                if($aircraft_base_iata === $destination_airport['iata']) continue;

                if( array_key_exists($aircraft_base_iata, $airports_data_map) ) {
                        $aircraft_base_airport = $airports_data_map[$aircraft_base_iata];
                        
                } else {
                    $aircraft_base_airport = $this->utilities->airport_data_by_iata($aircraft_base_iata);

                    if(!array_key_exists('iata',  $aircraft_base_airport)) continue;

                    $airports_data_map[$aircraft_base_iata] = $aircraft_base_airport;
                }

                $aircraft_base_airport_name = $this->get_airport_name($aircraft_base_airport, $current_language);

                $raw_table_price = (string) aviation_field('aircraft_rates', $post->ID);

                if(empty($raw_table_price)) continue;

                $table_price = json_decode(html_entity_decode($raw_table_price), true);

                if(!array($table_price) || count($table_price) === 0) continue;
                if (!array_key_exists('aircraft_rates_table', $table_price) || !is_array($table_price['aircraft_rates_table'])) continue;

                $table_price  = $table_price['aircraft_rates_table'];

                $has_invalid_cels = false;

                $aircraft_arr = [
                    'aircraft_name' => $aircraft_name,
                    'aircraft_base_id' => $aircraft_base_iata,
                    'aircraft_type' => $aircraft_type
                ];

                $output->service_aircrafts[$aircraft_name] = $aircraft_arr;


                foreach($table_price as $route_row) {
                    $origin_iata = (string) $route_row[0];
                    $destination_iata = (string) $route_row[1];
                    $duration_float = (float) $route_row[2];
                    $one_way_price = (float) $route_row[3];
                    $fees_per_person = (float) $route_row[4];
                    $base_fees = (float) $route_row[5];
                    $seats = (int) $route_row[6];
                    $max_weight = (float) $route_row[7];


                    if(
                        empty($origin_iata) 
                        || empty($destination_iata) 
                        || $origin_iata === $destination_iata 
                        || !in_array($destination_airport['iata'], [$origin_iata, $destination_iata]) 
                        || $duration_float === 0.0 
                        || $one_way_price === 0.0 
                        || $seats === 0) {
                        $has_invalid_cels = true;
                        continue;
                    }


                    if($one_way_price < $starting_at)
                    {
                        $starting_at = $one_way_price;
                        $starting_at_capacity = $seats;
                    }

                    $origin_airport = (array_key_exists($origin_iata, $airports_data_map)) 
                        ? $airports_data_map[$origin_iata]
                        : $this->utilities->airport_data_by_iata($origin_iata);

                    $destination_airport = (array_key_exists($destination_iata, $airports_data_map)) 
                        ? $airports_data_map[$destination_iata]
                        : $this->utilities->airport_data_by_iata($destination_iata);

                    if(!is_array($origin_airport) || count($origin_airport) === 0) continue;
                    if(!is_array($destination_airport) || count($destination_airport) === 0) continue;

                    $base_rate_arr = [
                        'aircraft' => $aircraft_name,
                        'origin' => $this->get_airport_name($origin_airport, $current_language),
                        'origin_id' => $origin_iata,
                        'destination' => $this->get_airport_name($destination_airport, $current_language),
                        'destination_id' => $destination_iata,
                        'duration' => $this->utilities->convertNumberToTime($duration_float) . ' hrs.',
                        'duration_decimal_hours' => $duration_float,
                        'max_capacity' => ($max_weight > 0) 
                            ? "{$seats} passengers or {$max_weight} pounds between passengers or luggage."
                            : "{$seats} passengers."
                    ];

                    // Build both directions for one-way and round-trip
                    $legs = [
                        // one-way
                        ['bucket' => 'one_way_charter_flights',  'origin' => $origin_iata, 'destination' => $destination_iata, 'leg_count' => 1],
                        ['bucket' => 'one_way_charter_flights',  'origin' => $destination_iata, 'destination' => $origin_iata, 'leg_count' => 1], // return of one-way

                        // round-trip
                        ['bucket' => 'round_trip_charter_flights','origin' => $origin_iata, 'destination' => $destination_iata, 'leg_count' => 2],
                        ['bucket' => 'round_trip_charter_flights','origin' => $destination_iata, 'destination' => $origin_iata, 'leg_count' => 2], // return of round-trip
                    ];

                    foreach ($legs as $cfg) {
                        $rate = $base_rate_arr;

                        // Price scales by number of legs (assumes symmetric cost per leg)
                        $rate['price'] = wrap_money_full($one_way_price * $cfg['leg_count']);

                        // Fees scale per leg too; keep your 'none' behavior
                        $rate['fees_per_person']     = ($fees_per_person > 0) ? wrap_money_full($fees_per_person * $cfg['leg_count']) : 'none';
                        $rate['airport_service_fee'] = ($base_fees > 0)        ? wrap_money_full($base_fees * $cfg['leg_count'])        : 'none';

                        // Nice label, e.g. "Cessna (SJO-PTY)" or "Cessna (SJO-PTY-SJO)"
                        $route = ($cfg['leg_count'] === 1)
                            ? "{$cfg['origin']}-{$cfg['destination']}"
                            : "{$cfg['origin']}-{$cfg['destination']}-{$cfg['origin']}";

                        $output->service_rates[$cfg['bucket']]["{$aircraft_type} {$aircraft_name} ({$route})"] = $rate;
                    }

                }

                if($has_invalid_cels) continue;

                }

            wp_reset_postdata();
        }

        

        $output->starting_at =  sprintf(
            '%s one-way or %s round trip for up to %s passengers.', 
            wrap_money_full($starting_at), wrap_money_full($starting_at*2),
            $starting_at_capacity
        );

        $output->service_hidden_rules[] = 'SERVICE_RATES shows the total cost for the service, not the cost per person.';
        $output->service_hidden_rules[] = 'If the client does not specify whether the transport is one-way or round trip, show prices for both (one-way and round-trip).';
        $output->service_hidden_rules[] = 'If the client requests one-way transport, show only one-way prices.';
        $output->service_hidden_rules[] = 'If the client requests round trip transport, show only round-trip prices.';
        $output->service_hidden_rules[] = 'Always label prices clearly as one-way or round trip.';
        $output->service_hidden_rules[] = 'Always include the origin, destination and capacity (number of passengers and weights) next to the price.';
        $output->service_hidden_rules[] = 'Flight prices are calculated as a fixed total aircraft charter fee (price), plus mandatory airport service fees (charged per flight), plus per-person fees (charged for each passenger). One-way flights include the cost of a single charter segment, while round-trip flights include the cost of two segments (outbound and return). The total amount a customer pays will depend on the number of passengers, since per-person fees are multiplied by the passenger count and added to the base charter and airport fees.';

        //write_log($airports_data_map);
        
        return $output;
    }

    public function get_airport_name($arr_obj, $current_language) {
        $airport = $arr_obj['airport'] ?? '';
        $city    = $arr_obj['city'] ?? '';
        $iata =  $arr_obj['iata'] ?? '';

        // Prefer localized name if available; otherwise fall back to "Airport, City" when different.
        $name =
            $arr_obj['airport_names'][$current_language] ?? (
                ($airport !== '' && $city !== '' && $airport !== $city)
                    ? "$airport, $city"
                    : $airport
            );

        return "{$name} ({$iata})";
    }

}