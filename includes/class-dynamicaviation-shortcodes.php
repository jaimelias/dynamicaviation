<?php 


class Dynamic_Aviation_Shortcodes {
    
    
    public function __construct()
    {
        $this->init();
    }

    public function init()
    {
		add_shortcode( 'mapbox_airports', array('Dynamic_Aviation_Shortcodes', 'mapbox_airports'));
		add_shortcode( 'destination', array('Dynamic_Aviation_Shortcodes', 'filter_destination_table'));        
    }

	public static function filter_destination_table($attr, $content = '')
	{
		if($attr)
		{
			if(array_key_exists('iata', $attr))
			{
				$content = Dynamic_Aviation_Public::get_destination_table($attr['iata']);
			}
		}
		return $content;
	}

	public static function mapbox_airports($attr, $content = '')
	{
		if(!isset($_GET['fl_builder']))
		{
			ob_start();
			
			?>
			<div class="pure-g">
				<div class="mapbox_form pure-u-1 pure-u-sm-1-1 pure-u-md-2-5">
			<?php
			
			require_once(dirname( __DIR__ ) . '/public/partials/price-calculator.php');
					
			?>
				</div>
					<div class="pure-u-1 pure-u-sm-1-1 pure-u-md-3-5">
						<div class="map-container">
							<div class="map" id="mapbox_airports">
							</div>
						</div>
					</div>
			</div>
			<?php
			
			$content = ob_get_contents();
			ob_end_clean();		
		}
		else
		{
			 $content = '<h2 class="text-center">'.__('Map preview not available in editing mode.', 'dynamicaviation').'</h2>';
		}
		
		return $content;
	}



}


?>