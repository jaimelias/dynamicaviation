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
$keys = array('jet_type',
	 'jet_manufacturer',
	 'jet_model',
	 'jet_year_of_construction',
	 'jet_passengers',
	 'jet_range',
	 'jet_cruise_speed',
	 'jet_max_altitude',
	 'jet_takeoff_field',
	 'jet_base_iata',
	 'jet_base_city'
	 );

for($x = 0; $x < count($keys); $x++)
{
	$key = $keys[$x];
	$value = Charterflights_Meta_Box::jet_get_meta($key);
	
	if($value)
	{
		if($key == 'jet_type')
		{
			$value = Dynamic_Aviation_Public::jet_type($value);
		}
		else if($key == 'jet_range')
		{
			$value = $value.__('nm', 'dynamicaviation').' | '.round(intval($value)*1.15078).__('mi', 'dynamicaviation').' | '.round(intval($value)*1.852).__('km', 'dynamicaviation');
		}
		else if($key == 'jet_cruise_speed')
		{
			$value = $value.__('kn', 'dynamicaviation').' | '.round(intval($value)*1.15078).__('mph', 'dynamicaviation').' | '.round(intval($value)*1.852).__('kph', 'dynamicaviation');			
		}
		else if($key == 'jet_max_altitude')
		{
			$value = $value.__('ft', 'dynamicaviation').' | '.round(intval($value)*0.3048).__('m', 'dynamicaviation');
		}
		else if($key == 'jet_base_iata')
		{
			$value = Charterflights_Meta_Box::jet_get_meta('jet_base_name');
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

<?php echo Dynamic_Aviation_Public::get_destination_table(Charterflights_Meta_Box::jet_get_meta('jet_base_iata')); ?>


<h2><?php esc_html_e(__('Instant Quotes', 'dynamicaviation')); ?></h2>
<div class="bottom-20">
	<?php echo Dynamic_Aviation_Public::price_calculator(); ?>
</div>




