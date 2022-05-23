<?php 


class Dynamic_Aviation_Search_Form {
    
    
    public function __construct()
    {
        $this->init();
    }

    public function init()
    {
        add_filter('dy_aviation_full_search_form', array(&$this, 'full_search_form'));
        add_filter('dy_aviation_search_form', array(&$this, 'search_form'));
    }

	public function full_search_form()
	{
        ob_start(); 
        ?>

        <div class="pure-g">
            <div class="aviation_search_form_container pure-u-1 pure-u-sm-1-1 pure-u-md-2-5">
                <?php echo $this->search_form(); ?>
            </div>
                <div class="pure-u-1 pure-u-sm-1-1 pure-u-md-3-5">
                    <div class="map" id="aviation_map"></div>
                </div>
        </div>
        <?php
        
        $content = ob_get_contents();
        ob_end_clean();	
		
		return $content;
	}

    public function search_form()
    {
		ob_start(); 
        ?>
            <form class="aircraft_calculator" method="get" action="<?php echo esc_url(home_lang().'/instant_quote/'); ?>">

            <div class="bottom-20"><label><i class="linkcolor fas fa-map-marker"></i> <?php esc_html_e(__('Origin', 'dynamicaviation')); ?></label>
            <input type="text" id="aircraft_origin" name="aircraft_origin" class="aircraft_list" spellcheck="false" placeholder="<?php esc_html_e(__('country / city / airport', 'dynamicaviation')); ?>" /><input type="hidden" id="aircraft_origin_l" name="aircraft_origin_l"></div>


            <div class="bottom-20">
                <label><i class="linkcolor fas fa-map-marker"></i> <?php esc_html_e(__('Destination', 'dynamicaviation')); ?></label>	
                <input type="text" id="aircraft_destination" name="aircraft_destination" class="aircraft_list" spellcheck="false" placeholder="<?php esc_html_e(__('country / city / airport', 'dynamicaviation')); ?>" /><input type="hidden" id="aircraft_destination_l" name="aircraft_destination_l">
            </div>


            <div class="pure-g gutters">
                <div class="pure-u-1 pure-u-sm-1-2 pure-u-md-1-2">
                    <div class="bottom-20">
                        <label><i class="linkcolor fas fa-male"></i> <?php esc_html_e(__('Passengers', 'dynamicaviation')); ?></label>
                    <input type="number" min="1" name="aircraft_pax" id="aircraft_pax"/>
                    </div>
                </div>
                <div class="pure-u-1 pure-u-sm-1-2 pure-u-md-1-2">
                    <div class="bottom-20">
                        <label><i class="linkcolor fas fa-plane"></i> <?php esc_html_e(__('Flight', 'dynamicaviation')); ?></label>
                        <select name="aircraft_flight" id="aircraft_flight">
                            <option value="0"><?php esc_html_e(__('One way', 'dynamicaviation')); ?></option>
                            <option value="1"><?php esc_html_e(__('Round trip', 'dynamicaviation')); ?></option>
                        </select>
                    </div>
                </div>
            </div>	

            <div class="pure-g gutters">
                <div class="pure-u-1 pure-u-sm-1-2 pure-u-md-1-2">
                    <div class="bottom-20">
                        <label><i class="linkcolor fas fa-calendar-alt"></i> <?php esc_html_e(__('Date of Departure', 'dynamicaviation')); ?></label><input type="text" class="datepicker" name="aircraft_departure_date" id="aircraft_departure_date"/>
                    </div>
                </div>
                <div class="pure-u-1 pure-u-sm-1-2 pure-u-md-1-2">
                    <div class="bottom-20">
                        <label><i class="linkcolor fas fa-clock"></i> <?php esc_html_e(__('Hour of Departure', 'dynamicaviation')); ?></label><input placeholder="<?php esc_html_e(__('Local Time', 'dynamicaviation')); ?>" type="text" class="timepicker" name="aircraft_departure_hour" id="aircraft_departure_hour"/>
                    </div>
                </div>
            </div>

            <div class="aircraft_return">
                <div class="pure-g gutters">
                    <div class="pure-u-1 pure-u-sm-1-2 pure-u-md-1-2">
                        <div class="bottom-20">
                            <label><i class="linkcolor fas fa-calendar-alt"></i> <?php esc_html_e(__('Date of Return', 'dynamicaviation')); ?></label><input type="text" class="datepicker" name="aircraft_return_date" id="aircraft_return_date"/>
                        </div>
                    </div>
                    <div class="pure-u-1 pure-u-sm-1-2 pure-u-md-1-2">
                        <div class="bottom-20">
                            <label><i class="linkcolor fas fa-clock"></i> <?php esc_html_e(__('Hour of Return', 'dynamicaviation')); ?></label><input placeholder="<?php esc_html_e(__('Local Time', 'dynamicaviation')); ?>" type="text" class="timepicker" name="aircraft_return_hour" id="aircraft_return_hour"/>
                        </div>
                    </div>
                </div>	
            </div>

            <div class="text-center bottom-20"><button id="aircraft_submit" class="strong uppercase pure-button pure-button-primary" type="button"><i class="fa fa-search" aria-hidden="true"></i> <?php esc_html_e(__('Find Aircrafts', 'dynamicaviation')); ?></button></div>

            <div class="text-center"><small class="text-muted">Powered by</small> <img style="vertical-align: middle;" width="57" height="18" alt="algolia" src="<?php echo esc_url(plugin_dir_url(__DIR__) . 'public/img/algolia.svg'); ?>"/></div>
                
            </form>
        <?php 
            $content = ob_get_contents();
            ob_end_clean();
            return $content;
    }


}


?>