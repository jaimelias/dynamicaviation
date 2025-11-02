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
        //get_has == isset($_GET[])
        if(is_singular('aircrafts') && get_has('training-data')) {

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

        //get_has == isset($_GET[])
        if(is_singular('aircrafts') && get_has('training-data')) {

            global $post;

            $training_obj = $this->get_training_data($post->ID);
            if($this->format === 'json')  exit(json_encode($training_obj));

            $output = '';
            $aircraft_name = $training_obj->aircraft_name;

            if(in_array($this->format, ['text', 'markdown'])) {
                $output .= "# {$aircraft_name}\n";
                $output .= concatenate_object_to_text($training_obj, "* ", "- ", "\n");
            }
            else if($this->format === 'html') {
                $output .= '<!DOCTYPE html><html><head><title>'.esc_html($aircraft_name).'</title></head><body>';
                $output .= '<h1>'.esc_html($aircraft_name).'</h1>';
                $output .= concatenate_object_to_html($training_obj);
                $output .= "</body></html>";
            }

            exit($output);
        }

    }

    public function get_training_data($aircraft_id) {

        if (empty($aircraft_id) || !get_post($aircraft_id)) {
            wp_die('Invalid aircraft_id.');
        }

        global $polylang;

        $current_language  = function_exists('current_language') ? (string) current_language() : (function_exists('pll_current_language') ? pll_current_language('slug') : 'en');
        $languages         = function_exists('get_languages') ? (array) get_languages() : (function_exists('pll_languages_list') ? pll_languages_list(['fields' => 'slug']) : [$current_language]);
        $default_language  = function_exists('default_language') ? (string) default_language() : (function_exists('pll_default_language') ? pll_default_language('slug') : $current_language);

        $post                = get_post($aircraft_id);
        $aircraft_name       = $post ? $post->post_title : '';
        $aircraft_slug       = $post ? $post->post_name : (string) $aircraft_id;
        $aircraft_type       = $this->utilities->aircraft_type(aviation_field('aircraft_type', $aircraft_id));
        $aircraft_base_iata  = (string) aviation_field('aircraft_base_iata', $aircraft_id);
        $aircraft_passengers = (int) aviation_field('aircraft_passengers', $aircraft_id);
        $aircraft_manufacturer = (string) aviation_field('aircraft_manufacturer', $aircraft_id);
        $aircraft_model = (string) aviation_field('aircraft_model', $aircraft_id);

        if (empty($aircraft_base_iata)) {
            wp_die('Aircraft base IATA is required.');
        }

        // Base airport data
        $aircraft_base_airport = $this->utilities->airport_data_by_iata($aircraft_base_iata);
        if (!is_array($aircraft_base_airport) || empty($aircraft_base_airport) || !array_key_exists('iata', $aircraft_base_airport)) {
            wp_die('Unable to fetch base airport data.');
        }

        $airports_data_map = [];
        $airports_data_map[$aircraft_base_iata] = $aircraft_base_airport;

        $aircraft_base_airport_name = $this->get_airport_name($aircraft_base_airport, $current_language);

        // Output skeleton (aircraft-centered)
        $output = (object) [
            'aircraft_id'             => "aircraft-{$aircraft_slug}",
            'aircraft_name'           => $aircraft_name,
            'aircraft_type'           => $aircraft_type,
            'aircraft_base_id'        => $aircraft_base_iata,
            'aircraft_base_name'      => $aircraft_base_airport_name,
            'aircraft_max_passengers' => $aircraft_passengers,
            'aircraft_manufacturer' => $aircraft_manufacturer,
            'aircraft_model' => $aircraft_model,

            // Simplified routes keyed by "ORIGIN-DEST" so origin is explicit.
            'routes'                  => [],
            'web_checkout'             => 'available',
            'reservation_links'        => [],
            'service_hidden_rules'             => []
        ];

        // Multilang links (point to this aircraft page in each language)
        if (isset($polylang) && function_exists('pll_get_post')) {
            foreach ($languages as $language) {
                $lang_name = (class_exists('Locale'))
                    ? \Locale::getDisplayLanguage($language, $default_language)
                    : $language;

                $translated_id = pll_get_post($aircraft_id, $language);
                $link_id       = $translated_id ? $translated_id : $aircraft_id;
                $output->reservation_links[$lang_name] = esc_url(get_permalink($link_id));
            }
        } else {
            $output->reservation_links[$current_language] = esc_url(get_permalink($aircraft_id));
        }

        // Parse the aircraft's routes table
        $raw_table_price = (string) aviation_field('aircraft_rates', $aircraft_id);
        $table_price     = [];
        if (!empty($raw_table_price)) {
            $decoded = json_decode(html_entity_decode($raw_table_price), true);
            if (is_array($decoded) && array_key_exists('aircraft_rates_table', $decoded) && is_array($decoded['aircraft_rates_table'])) {
                $table_price = $decoded['aircraft_rates_table'];
            }
        }

        // Build simplified routes keyed by "ORIGIN-DEST".
        // Keep only rows that touch the base (either origin or destination equals aircraft_base_id),
        // and preserve the actual $origin_iata and $destination_iata.
        if (!empty($table_price)) {
            foreach ($table_price as $route_row) {
                $origin_iata       = isset($route_row[0]) ? (string) $route_row[0] : '';
                $destination_iata  = isset($route_row[1]) ? (string) $route_row[1] : '';
                $duration_float    = isset($route_row[2]) ? (float) $route_row[2] : 0.0;
                $one_way_price     = isset($route_row[3]) ? (float) $route_row[3] : 0.0;
                $fees_per_person   = isset($route_row[4]) ? (float) $route_row[4] : 0.0;
                $airport_fees      = isset($route_row[5]) ? (float) $route_row[5] : 0.0;
                $seats             = isset($route_row[6]) ? (int)   $route_row[6] : 0;
                $max_weight        = isset($route_row[7]) ? (float) $route_row[7] : 0.0;

                if (
                    empty($origin_iata) || empty($destination_iata) || $origin_iata === $destination_iata ||
                    ($origin_iata !== $aircraft_base_iata && $destination_iata !== $aircraft_base_iata) ||
                    $duration_float <= 0.0 || $one_way_price <= 0.0 || $seats <= 0
                ) {
                    continue;
                }

                // Cache airports (origin and destination)
                if (!array_key_exists($origin_iata, $airports_data_map)) {
                    $airports_data_map[$origin_iata] = $this->utilities->airport_data_by_iata($origin_iata);
                }
                if (!array_key_exists($destination_iata, $airports_data_map)) {
                    $airports_data_map[$destination_iata] = $this->utilities->airport_data_by_iata($destination_iata);
                }

                $origin_airport      = $airports_data_map[$origin_iata];
                $destination_airport = $airports_data_map[$destination_iata];
                if (!is_array($origin_airport) || empty($origin_airport) || !is_array($destination_airport) || empty($destination_airport)) {
                    continue;
                }

                $route_key = "{$origin_iata}-{$destination_iata}";

                $output->routes[$route_key] = [
                    'origin_id'        => $origin_iata,
                    'origin_name'      => $this->get_airport_name($origin_airport, $current_language),
                    'destination_id'   => $destination_iata,
                    'destination_name' => $this->get_airport_name($destination_airport, $current_language),
                    'duration_hours'   => $duration_float, // numeric; format on render
                    'capacity'         => [
                        'seats'          => $seats,
                        'max_weight_lbs' => $max_weight > 0 ? $max_weight : null,
                    ],
                    'one_way_prices'           => [
                            'aircraft'        => $one_way_price,
                            'airport_fees'    => $airport_fees,
                            'fees_per_person' => $fees_per_person,
                        ],
                ];
            }
        }

        $output->service_hidden_rules[] = __('Routes are keyed as "ORIGIN-DEST". Do not expect a separate "DEST-ORIGIN"; the reverse direction is identical to the stored route.', 'dynamicaviation');
        $output->service_hidden_rules[] = sprintf(__('All prices are in %s.', 'dynamicaviation'), currency_name());
        $output->service_hidden_rules[] = __('All prices are for the entire aircraft (not per person).', 'dynamicaviation');
        $output->service_hidden_rules[] = __('Total trip cost = aircraft + airport_fees + (fees_per_person x number_of_passengers).', 'dynamicaviation');
        $output->service_hidden_rules[] = __('Round-trip price is computed at runtime as exactly 2x each one_way_prices component (aircraft, airport_fees, fees_per_person).', 'dynamicaviation');
        $output->service_hidden_rules[] = __('Durations and prices are numeric; format human-readable values at presentation time.', 'dynamicaviation');



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