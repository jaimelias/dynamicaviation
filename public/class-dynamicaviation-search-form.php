<?php 


class Dynamic_Aviation_Search_Form {
    
    
    public function __construct($utilities)
    {
        $this->utilities = $utilities;
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
            <form class="aircraft_search_form" data-hash-params="<?php echo esc_attr(implode(',', $this->utilities->search_form_hash_param_names()));?>" data-method="post" data-nonce="slug" data-action="<?php echo esc_url(home_lang().'instant_quote'); ?>" autocomplete="off" data-gclid="true">

            <div class="bottom-20"><label><span class="dashicons linkcolor dashicons-location"></span> <?php echo esc_html(__('Origin', 'dynamicaviation')); ?></label>
                <input type="text" id="aircraft_origin" name="aircraft_origin" class="aircraft_list" spellcheck="false" placeholder="<?php echo esc_html(__('country / city / airport', 'dynamicaviation')); ?>" />
            </div>


            <div class="bottom-20">
                <label><span class="dashicons linkcolor dashicons-location"></span> <?php echo esc_html(__('Destination', 'dynamicaviation')); ?></label>	
                <input type="text" id="aircraft_destination" name="aircraft_destination" class="aircraft_list" spellcheck="false" placeholder="<?php echo esc_html(__('country / city / airport', 'dynamicaviation')); ?>"  />
            </div>


            <div class="pure-g gutters">
                <div class="pure-u-1 pure-u-sm-1-2 pure-u-md-1-2">
                    <div class="bottom-20">
                        <label><span class="dashicons linkcolor dashicons-admin-users"></span> <?php echo esc_html(__('Passengers', 'dynamicaviation')); ?></label>
                    <input type="number" min="1" name="pax_num" id="pax_num" />
                    </div>
                </div>
                <div class="pure-u-1 pure-u-sm-1-2 pure-u-md-1-2">
                    <div class="bottom-20">
                        <label><span class="dashicons linkcolor dashicons-airplane"></span> <?php echo esc_html(__('Flight', 'dynamicaviation')); ?></label>
                        <select name="aircraft_flight" id="aircraft_flight" >
                            <option value="0"><?php echo esc_html(__('One way', 'dynamicaviation')); ?></option>
                            <option value="1"><?php echo esc_html(__('Round trip', 'dynamicaviation')); ?></option>
                        </select>
                    </div>
                </div>
            </div>	

            <div class="pure-g gutters">
                <div class="pure-u-1 pure-u-sm-1-2 pure-u-md-1-2">
                    <div class="bottom-20">
                        <label><span class="dashicons linkcolor dashicons-calendar"></span> <?php echo esc_html(__('Departure', 'dynamicaviation')); ?></label><input type="text" class="datepicker" name="start_date" id="start_date" placeholder="<?php echo esc_html(__('YYYY-MM-DD', 'dynamicaviation')); ?>" />
                    </div>
                </div>
                <div class="pure-u-1 pure-u-sm-1-2 pure-u-md-1-2">
                    <div class="bottom-20">
                        <label><span class="dashicons linkcolor dashicons-clock"></span> <?php echo esc_html(__('Departure', 'dynamicaviation')); ?></label><input placeholder="<?php echo esc_html(__('Local Time', 'dynamicaviation')); ?>" type="text" class="timepicker" name="start_time" id="start_time" />
                    </div>
                </div>
            </div>

            <div class="aircraft_return hidden animate-fade">
                <div class="pure-g gutters">
                    <div class="pure-u-1 pure-u-sm-1-2 pure-u-md-1-2">
                        <div class="bottom-20">
                            <label><span class="dashicons linkcolor dashicons-calendar"></span> <?php echo esc_html(__('Return', 'dynamicaviation')); ?></label><input type="text" class="datepicker" name="end_date" id="end_date" placeholder="<?php echo esc_html(__('YYYY-MM-DD', 'dynamicaviation')); ?>"  />
                        </div>
                    </div>
                    <div class="pure-u-1 pure-u-sm-1-2 pure-u-md-1-2">
                        <div class="bottom-20">
                            <label><span class="dashicons linkcolor dashicons-clock"></span> <?php echo esc_html(__('Return', 'dynamicaviation')); ?></label><input placeholder="<?php echo esc_html(__('Local Time', 'dynamicaviation')); ?>" type="text" class="timepicker" name="end_time" id="end_time" />
                        </div>
                    </div>
                </div>	
            </div>

            <div class="text-center bottom-20">
                <button type="button" id="aircraft_search_button" class="pure-button strong pure-button-primary"><span class="dashicons dashicons-airplane"></span> <?php echo esc_html(__('Find Aircrafts', 'dynamicaviation')); ?></button>
            </div>
            <div class="text-center"><small class="text-muted">Powered by</small> <img style="vertical-align: middle;" width="57" height="18" alt="algolia" src="<?php echo esc_url(plugin_dir_url(__DIR__) . 'public/img/algolia.svg'); ?>"/></div>
                
            </form>
        <?php 
            $content = ob_get_contents();
            ob_end_clean();
            return $content;
    }


}


?>