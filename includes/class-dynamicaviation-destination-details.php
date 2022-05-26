<?php 


class Dynamic_Aviation_Destination_Details {
    
    
    public function __construct($utilities)
    {
        $this->utilities = $utilities;
        $this->init();
    }

    public function init()
    {
        add_filter('dy_aviation_destination_details', array(&$this, 'template'));
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
        $static_map = $this->utilities->airport_img_url($json, false);
        $lang = $this->utilities->current_language();
        
        if($iata != null && $icao != null)
        {
            $airport .= " ".__('Airport', 'dynamicaviation');
        }
        
        if($lang)
        {
            if(array_key_exists($lang, $country_name))
            {
                $country_lang = $country_name[$lang];
            }
            else
            {
                $country_lang = $country_name['en'];
            }
        }        


        ob_start(); 
        ?>

            <div class="pure-g gutters">

            <div class="pure-u-1 pure-u-sm-1-1 pure-u-md-2-3">
                
                <img class="img-responsive" src="<?php echo esc_url($static_map); ?>" alt="<?php esc_html_e($airport).", ".esc_html($city); ?>" title="<?php esc_attr_e($airport); ?>"/>

            </div>
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

            </div>


            <?php if(is_active_sidebar( 'quote-sidebar' )): ?>
            <h2><span class="linkcolor"><?php echo (esc_html__('Quote Charter Flight to', 'dynamicaviation'));?></span> <?php esc_html_e($airport); ?><span class="linkcolor">, <?php esc_html_e($city);?></span></h2>
            <ul id="quote-sidebar"><?php dynamic_sidebar('quote-sidebar'); ?></ul>
            <?php endif; ?>


        <?php
        
        $content = ob_get_contents();
        ob_end_clean();	
		
		return $content;
    }
}


?>