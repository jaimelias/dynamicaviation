<?php 


class Dynamic_Aviation_Search_Form {
    
    
    public function __construct()
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
            <form class="aircraft_calculator" method="get" action="<?php echo esc_url(home_lang().'instant_quote/'); ?>">

            <div class="bottom-20"><label><i class="linkcolor fas fa-map-marker"></i> <?php echo esc_html(__('Origin', 'dynamicaviation')); ?></label>
            <input type="text" id="aircraft_origin" name="aircraft_origin" class="aircraft_list" spellcheck="false" placeholder="<?php echo esc_html(__('country / city / airport', 'dynamicaviation')); ?>" autocomplete="off" /><input type="hidden" id="aircraft_origin_l" name="aircraft_origin_l" autocomplete="off" /></div>


            <div class="bottom-20">
                <label><i class="linkcolor fas fa-map-marker"></i> <?php echo esc_html(__('Destination', 'dynamicaviation')); ?></label>	
                <input type="text" id="aircraft_destination" name="aircraft_destination" class="aircraft_list" spellcheck="false" placeholder="<?php echo esc_html(__('country / city / airport', 'dynamicaviation')); ?>" autocomplete="off" /><input type="hidden" id="aircraft_destination_l" name="aircraft_destination_l" autocomplete="off" />
            </div>


            <div class="pure-g gutters">
                <div class="pure-u-1 pure-u-sm-1-2 pure-u-md-1-2">
                    <div class="bottom-20">
                        <label><i class="linkcolor fas fa-male"></i> <?php echo esc_html(__('Passengers', 'dynamicaviation')); ?></label>
                    <input type="number" min="1" name="pax_num" id="pax_num" autocomplete="off"/>
                    </div>
                </div>
                <div class="pure-u-1 pure-u-sm-1-2 pure-u-md-1-2">
                    <div class="bottom-20">
                        <label><i class="linkcolor fas fa-plane"></i> <?php echo esc_html(__('Flight', 'dynamicaviation')); ?></label>
                        <select name="aircraft_flight" id="aircraft_flight" autocomplete="off">
                            <option value="0"><?php echo esc_html(__('One way', 'dynamicaviation')); ?></option>
                            <option value="1"><?php echo esc_html(__('Round trip', 'dynamicaviation')); ?></option>
                        </select>
                    </div>
                </div>
            </div>	

            <div class="pure-g gutters">
                <div class="pure-u-1 pure-u-sm-1-2 pure-u-md-1-2">
                    <div class="bottom-20">
                        <label><i class="linkcolor fas fa-calendar-alt"></i> <?php echo esc_html(__('Departure', 'dynamicaviation')); ?></label><input type="text" class="datepicker" name="start_date" id="start_date" placeholder="<?php echo esc_html(__('YYYY-MM-DD', 'dynamicaviation')); ?>" autocomplete="off"/>
                    </div>
                </div>
                <div class="pure-u-1 pure-u-sm-1-2 pure-u-md-1-2">
                    <div class="bottom-20">
                        <label><i class="linkcolor fas fa-clock"></i> <?php echo esc_html(__('Departure', 'dynamicaviation')); ?></label><input placeholder="<?php echo esc_html(__('Local Time', 'dynamicaviation')); ?>" type="text" class="timepicker" name="start_time" id="start_time" autocomplete="off"/>
                    </div>
                </div>
            </div>

            <div class="aircraft_return">
                <div class="pure-g gutters">
                    <div class="pure-u-1 pure-u-sm-1-2 pure-u-md-1-2">
                        <div class="bottom-20">
                            <label><i class="linkcolor fas fa-calendar-alt"></i> <?php echo esc_html(__('Return', 'dynamicaviation')); ?></label><input type="text" class="datepicker" name="end_date" id="end_date" placeholder="<?php echo esc_html(__('YYYY-MM-DD', 'dynamicaviation')); ?>" autocomplete="off" />
                        </div>
                    </div>
                    <div class="pure-u-1 pure-u-sm-1-2 pure-u-md-1-2">
                        <div class="bottom-20">
                            <label><i class="linkcolor fas fa-clock"></i> <?php echo esc_html(__('Return', 'dynamicaviation')); ?></label><input placeholder="<?php echo esc_html(__('Local Time', 'dynamicaviation')); ?>" type="text" class="timepicker" name="end_time" id="end_time" autocomplete="off"/>
                        </div>
                    </div>
                </div>	
            </div>

            <div class="text-center bottom-20"><button id="aircraft_submit" class="strong uppercase pure-button pure-button-primary" type="button"><i class="fa fa-search" aria-hidden="true"></i> <?php echo esc_html(__('Find Aircrafts', 'dynamicaviation')); ?></button></div>

            <div class="text-center"><small class="text-muted">Powered by</small> <img style="vertical-align: middle;" width="57" height="18" alt="algolia" src="<?php echo esc_url(plugin_dir_url(__DIR__) . 'public/img/algolia.svg'); ?>"/></div>
                
            </form>
        <?php 
            $content = ob_get_contents();
            ob_end_clean();
            return $content;
    }


}


?>