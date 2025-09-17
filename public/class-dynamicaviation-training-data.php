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
            $destination_airport = $this->utilities->airport_data($query_var);

            if (!is_array($destination_airport) || empty($destination_airport)) wp_die('Unable to fetch airport_data from DB.');
            if (!array_key_exists('iata', $destination_airport)) wp_die('Invalid airport_data schema.');

            $output = [];

            $args = array(
                'post_type' => 'aircrafts',
                'posts_per_page' => 200, 
                'post_parent' => 0,
                'meta_key' => 'aircraft_price_per_hour',
                'orderby' => 'meta_value_num',
                'order' => 'ASC',
            );

            $wp_query = new WP_Query($args);

            if ( $wp_query->have_posts() )
            {
                while ($wp_query->have_posts() ) {
                    $wp_query->the_post();
                    global $post;
                    $output[] = $post->ID;

                    $base_iata = aviation_field('aircraft_base_iata');

                    if($base_iata === $destination_airport['iata']) continue;

                 }

                wp_reset_postdata();
            }

            
            exit(json_encode($output));
        }
    }

}