<?php 


class Dynamic_Aviation_Destination_Details {
    
    
    public function __construct($utilities)
    {
        $this->utilities = $utilities;
        add_action('init', array(&$this, 'init'));
    }

    public function init()
    {
        $this->current_language = current_language();
        add_filter('dy_aviation_destination_details', array(&$this, 'template'));
    }


    public function get_destination_content($iata)
    {
        $output = '';
        $current_language = current_language();
        $can_user_edit = (current_user_can('editor') || current_user_can('administrator')) ? true : false;
        
        $args = array(
            'post_type' => 'destinations',
            'posts_per_page' => 1,
            'post_parent' => 0,
            'lang' => $current_language,
            'meta_key' => array(),
            'meta_query' => array(
                array(
                    'key' => 'aircraft_base_iata',
                    'value' => esc_html($iata),
                    'compare' => '='
                )
            )
        );

        $wp_query = new WP_Query( $args );

        if ( $wp_query->have_posts() )
        {
            while ( $wp_query->have_posts() )
            {
                $wp_query->the_post();
                global $post;
                $output .= '<div class="entry-content">'.get_the_content().'</div>';

                if( $can_user_edit )
                {
                    $output .= '<p><a class="pure-button" href="'.esc_url(get_edit_post_link($post->ID)).'"><i class="fas fa-pencil-alt" ></i> '.esc_html(__('Edit Destination', 'dynamicaviation')).'</a></p>';
                }
            }

            wp_reset_postdata();
        }
        else
        {
            if($can_user_edit)
            {
                $output .= '<p><a class="pure-button" href="'.esc_url(admin_url('post-new.php?post_type=destinations&iata='.$iata)).'"><i class="fas fa-plus" ></i> '.esc_html(__('Add Destination', 'dynamicaviation')).'</a></p>';
            }
        }

        return $output;

    }

    public function template()
    {

        global $airport_array;
        $json = $airport_array;
        $iata  = $json['iata'];
        $icao = $json['icao'];
        $city = $json['city'];
        $utc = $json['utc'];
        $_geoloc = $json['_geoloc'];
        $airport = $json['airport'];
        $country_name = $json['country_names'];
        $static_map = $this->utilities->airport_img_url($json);
        
        if($iata != null && $icao != null)
        {
            $airport .= " ".__('Airport', 'dynamicaviation');
        }
        
        if($this->current_language)
        {
            if(array_key_exists($this->current_language, $country_name))
            {
                $country_lang = $country_name[$this->current_language];
            }
            else
            {
                $country_lang = $country_name['en'];
            }
        }        


        ob_start(); 
        ?>

            <div class="pure-g gutters bottom-20">

                <div class="pure-u-1 pure-u-sm-1-1 pure-u-md-1-3">
                    <table class="airport_description pure-table pure-table-striped">
                        <?php if($iata != null && $icao != null): ?>
                            <?php if($iata != null): ?>
                            <tr><td>IATA</td><td><?php esc_html_e($iata); ?></td></tr>
                            <?php endif;?>
                            <?php if($icao != null): ?>
                            <tr><td>ICAO</td><td><?php esc_html_e($icao); ?></td></tr>
                            <?php endif; ?>
                        <?php endif; ?>	
                        <tbody>
                            <tr><td><?php echo (esc_html__('City', 'dynamicaviation')); ?></td><td><?php esc_html_e($city); ?></td></tr>
                            <tr><td><?php echo (esc_html__('Country', 'dynamicaviation')); ?></td><td><?php esc_html_e($country_lang); ?></td></tr>	
                            <tr><td><?php echo (esc_html__('Longitude', 'dynamicaviation')); ?></td> <td><?php esc_html_e(round($_geoloc['lng'], 4)); ?></td></tr>
                            <tr><td><?php echo (esc_html__('Latitude', 'dynamicaviation')); ?></td> <td><?php esc_html_e(round($_geoloc['lat'], 4)); ?></td></tr>	
                            <tr><td><?php echo (esc_html__('Timezone', 'dynamicaviation')); ?></td> <td><?php esc_html_e($utc).' (UTC)'; ?></td></tr>
                        </tbody>
                    </table>
                </div>
                <div class="pure-u-1 pure-u-sm-1-1 pure-u-md-2-3">
                    <div class="entry-content">
                        <img width="660" height="440" class="img-responsive" src="<?php echo esc_url($static_map); ?>" alt="<?php esc_html_e($airport).", ".esc_html($city); ?>" title="<?php esc_attr_e($airport); ?>"/>
                        <?php echo $this->get_destination_content($iata); ?>
                    </div>
                </div>
            </div>

            <hr/>

            <h4><span class="linkcolor"><?php echo (esc_html__('Quote Charter Flight to', 'dynamicaviation'));?></span> <?php esc_html_e($airport); ?><span class="linkcolor">, <?php esc_html_e($city);?></span></h4>
            <div id="quote-sidebar">
                <?php echo apply_filters('dy_aviation_search_form', ''); ?>
            </div>


        <?php
        
        $content = ob_get_contents();
        ob_end_clean();	
		
		return $content;
    }
}


?>