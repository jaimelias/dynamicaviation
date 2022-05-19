<?php

$table = '<table class="text-center pure-table small pure-table-striped">';
$labels = array(__('Type', 'dynamicaviation'),
	 __('Manufacturer', 'dynamicaviation'),
	 __('Model', 'dynamicaviation'),
	 __('Year of Construction', 'dynamicaviation'),
	 __('Passengers', 'dynamicaviation'),
	 __('Range', 'dynamicaviation'),
	 __('Cruise Speed', 'dynamicaviation'),
	 __('Max Altitude', 'dynamicaviation'),
	 __('Takeoff Field', 'dynamicaviation'),
	 __('Base Airport', 'dynamicaviation'),
	 __('Base Location', 'dynamicaviation')
	 );
$keys = array('aircraft_type',
	 'aircraft_manufacturer',
	 'aircraft_model',
	 'aircraft_year_of_construction',
	 'aircraft_passengers',
	 'aircraft_range',
	 'aircraft_cruise_speed',
	 'aircraft_max_altitude',
	 'aircraft_takeoff_field',
	 'aircraft_base_iata',
	 'aircraft_base_city'
	 );

for($x = 0; $x < count($keys); $x++)
{
	$key = $keys[$x];
	$value = aviation_field($key);
	
	if($value)
	{
		if($key == 'aircraft_type')
		{
			$value = Dynamic_Aviation_Public::aircraft_type($value);
		}
		else if($key == 'aircraft_range')
		{
			$value = $value.__('nm', 'dynamicaviation').' | '.round(intval($value)*1.15078).__('mi', 'dynamicaviation').' | '.round(intval($value)*1.852).__('km', 'dynamicaviation');
		}
		else if($key == 'aircraft_cruise_speed')
		{
			$value = $value.__('kn', 'dynamicaviation').' | '.round(intval($value)*1.15078).__('mph', 'dynamicaviation').' | '.round(intval($value)*1.852).__('kph', 'dynamicaviation');			
		}
		else if($key == 'aircraft_max_altitude')
		{
			$value = $value.__('ft', 'dynamicaviation').' | '.round(intval($value)*0.3048).__('m', 'dynamicaviation');
		}
		else if($key == 'aircraft_base_iata')
		{
			$value = aviation_field('aircraft_base_name');
		}
		
		$table .= '<tr>';
		$table .= '<td><span class="semibold">'.esc_html($labels[$x]).'</span></td>';
		$table .= '<td>'.esc_html($value).'</td>';
		$table .= '</tr>';			
	}
}

$table .= '</table>';

global $post;

?>


<div class="pure-g gutters">
	<div class="pure-u-1 pure-u-md-2-3">
		<?php if(has_post_thumbnail() && empty($content)): ?>
			<p><?php the_post_thumbnail('medium', array('class' => 'img-responsive')); ?></p>
		<?php else: ?>
			<?php echo $content; ?>
		<?php endif;?>
		</div>
	<div class="pure-u-1 pure-u-md-1-3"><?php echo $table; ?></div>
</div>

<hr/>

<?php echo Dynamic_Aviation_Public::get_destination_table(aviation_field('aircraft_base_iata')); ?>


<h2><?php esc_html_e(__('Instant Quotes', 'dynamicaviation')); ?></h2>
<div class="bottom-20">
	<?php echo Dynamic_Aviation_Public::price_calculator(); ?>
</div>




