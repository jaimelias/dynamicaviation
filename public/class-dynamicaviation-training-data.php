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
        if(get_query_var( 'fly' ) && isset($_GET['training-data'])) {

            if(isset($_GET['format']) && in_array($_GET['format'],  $this->alt_formats)) {
                $this->format = sanitize_text_field($_GET['format']);
                $this->content_type = $this->all_content_types[$this->format];
                $this->extension = $this->all_extensions[$this->format];
            }

            $headers['Content-Type'] = $this->content_type;
        }

        return $headers;
    }

    public function export_single_file() {


        $query_var = get_query_var( 'fly' );

        if(!empty($query_var) && isset($_GET['training-data'])) {
            $destination_airport = $this->utilities->airport_data_by_slug($query_var);

            if (!is_array($destination_airport) || empty($destination_airport)) wp_die('Unable to fetch airport_data from DB.');
            if (!array_key_exists('iata', $destination_airport)) wp_die('Invalid airport_data schema.');

            global $polylang;
            $home_lang = home_lang();
            $current_language = (string) current_language();
            $languages = (array) get_languages();
            $default_language = (string) default_language();

            // Prefer localized name if available; otherwise fall back to "Airport, City" when different.
            $destination_airport_name = $this->get_airport_name($destination_airport, $current_language);
            $service_name = sprintf(__('Charter Flights %s', 'dynamicaviation'), $destination_airport_name);

            $output = (object) [
                'service_name' =>  $service_name,
                'service_id' => $destination_airport['iata'],
                'service_rates' => [],
                'service_web_checkout' => 'available',
                'service_links_by_language' => [],
                'service_name_translations' => []
            ];

            if(isset($polylang))
            {
                foreach ($languages as $language) {

                    $lang_name = (class_exists('Locale')) ?  \Locale::getDisplayLanguage($language, $default_language) : $language; 

                    if ($language === $default_language) {
                        $output->service_links_by_language[$lang_name] = esc_url("{$home_lang}fly/{$query_var}");
                    }  else {
                        $output->service_links_by_language[$lang_name] = esc_url("{$home_lang}${language}/fly/{$query_var}");
                    }

                    $output->service_name_translations[$lang_name] = sprintf(pll_translate_string('Charter Flights %s', $language), $destination_airport_name);
                }
            }
            else
            {
                $output->service_links_by_language[$current_language] = "{$home_lang}fly/{$query_var}";
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

                    foreach($table_price as $route_row) {
                        $origin_iata = (string) $route_row[0];
                        $destination_iata = (string) $route_row[1];
                        $duration_float = (float) $route_row[2];
                        $one_way_price = (float) $route_row[3];
                        $fees_per_person = (float) $route_row[4];
                        $base_fees = (float) $route_row[5];
                        $seats = (int) $route_row[6];
                        $max_weight = (float) $route_row[7];

                        if(empty($origin_iata) || empty($destination_iata) || $duration_float === 0 || $seats === 0) {
                            $has_invalid_cels = true;
                            continue;
                        }

                    }

                    if($has_invalid_cels) continue;

                 }

                wp_reset_postdata();
            }

            //write_log($airports_data_map);
            
            exit(json_encode($output));
        }
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