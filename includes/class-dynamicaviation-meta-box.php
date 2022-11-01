<?php

class Dynamic_Aviation_Meta_Box
{

	public function __construct($utilities)
	{
		$this->utilities = $utilities;
		add_action( 'save_post', array(&$this, 'save') );
		add_action( 'add_meta_boxes', array(&$this, 'add_meta_box') );
	}

	public function add_meta_box() {
		
		add_meta_box(
			'aircraft_settings',
			__( 'Flights', 'dynamicaviation' ),
			array(&$this, 'aircraft_fields'),
			'aircrafts',
			'normal',
			'default'
		);

		add_meta_box(
			'destination_settings',
			__( 'Flights', 'dynamicaviation' ),
			array(&$this, 'destination_fields'),
			'destinations',
			'normal',
			'default'
		);

	}

	public function base_field()
	{
		?>
			<p>
				<label for="aircraft_base_iata"><?php _e( 'Base IATA', 'dynamicaviation' ); ?></label><br>
				<input class="aircraft_list" type="text" name="aircraft_base_iata" id="aircraft_base_iata" value="<?php echo aviation_field( 'aircraft_base_iata' ); ?>">
			</p>
		<?php
	}

	public function echo_nonce()
	{
		wp_nonce_field( '_aviation_nonce', 'aviation_nonce' );
	}

	public function destination_fields($post)
	{
		$this->echo_nonce();
		$this->base_field();
		$this->connected_packages_field();
	}

	public function connected_packages_field()
	{
		?>

		<p><label for="aircraft_connected_packages"><?php echo esc_html(__('Connected Package IDs', 'dynamicaviation' )); ?></label>
		
			<br/>

			<textarea rows="10" cols="20" name="aircraft_connected_packages" id="aircraft_connected_packages"><?php echo esc_textarea($this->utilities->sanitize_items_per_line('intval', aviation_field('aircraft_connected_packages'), 20)); ?></textarea>


			<br/>
			<?php echo esc_html(__('One ID per line', 'dynamicaviation')); ?>
		</p>

		<?php
	}

	public function aircraft_fields($post) {
		$this->echo_nonce();
		
		$aircraft_type = aviation_field('aircraft_type');
		
		?>


		<?php $this->base_field(); ?>

		<p>
			<label for="aircraft_base_name"><?php _e( 'Base Name', 'dynamicaviation' ); ?></label><br>
			<input class="aircraft_base_name" type="text" name="aircraft_base_name" id="aircraft_base_name" value="<?php echo aviation_field( 'aircraft_base_name' ); ?>" readonly>
		</p>
		<p>
			<label for="aircraft_base_city"><?php _e( 'Base City', 'dynamicaviation' ); ?></label><br>
			<input class="aircraft_base_city" type="text" name="aircraft_base_city" id="aircraft_base_city" value="<?php echo aviation_field( 'aircraft_base_city' ); ?>" readonly>
		</p>		
		<p>
			<label for="aircraft_base_lat"><?php _e( 'Base Latitude', 'dynamicaviation' ); ?></label><br>
			<input class="aircraft_lat" type="text" name="aircraft_base_lat" id="aircraft_base_lat" value="<?php echo aviation_field( 'aircraft_base_lat' ); ?>" readonly>
		</p>
		<p>
			<label for="aircraft_base_lon"><?php _e( 'Base Longitude', 'dynamicaviation' ); ?></label><br>
			<input class="aircraft_lon" type="text" name="aircraft_base_lon" id="aircraft_base_lon" value="<?php echo aviation_field( 'aircraft_base_lon' ); ?>" readonly>
		</p>			
	
		<p>
			<label for="aircraft_flights"><?php _e( 'Number of Flights', 'dynamicaviation' ); ?></label><br>
			<input type="number" min="10" name="aircraft_flights" id="aircraft_flights" value="<?php echo aviation_field( 'aircraft_flights' ); ?>">
		</p>	

		<p>
			<label for="aircraft_rates"><?php _e( 'Prices Per Flight', 'dynamicaviation' ); ?></label><br>
			<textarea class="hidden" type="text" name="aircraft_rates" id="aircraft_rates"><?php echo esc_textarea(aviation_field('aircraft_rates')); ?></textarea>
			<div class="hot" id="aircraft_rates_table" data-sensei-container="aircraft_rates_table" data-sensei-textarea="aircraft_rates" data-sensei-max="aircraft_flights" data-sensei-max="aircraft_flights" data-sensei-headers="origin,destination,duration,price,fee per person, stops,seats,max weight" data-sensei-type="text,text,currency,currency,currency,numeric,numeric,numeric"></div>
		</p>	

		<p><label for="aircraft_type"><?php _e( 'Type', 'dynamicaviation' ); ?></label><br>
			<select name="aircraft_type" id="aircraft_type">
				<option value="0" <?php echo ($aircraft_type == 0 ) ? 'selected' : '' ?>>Turbo Prop</option>
				<option value="1" <?php echo ($aircraft_type == 1 ) ? 'selected' : '' ?>>Light Jet</option>
				<option value="2" <?php echo ($aircraft_type == 2 ) ? 'selected' : '' ?>>Mid-size Jet</option>
				<option value="3" <?php echo ($aircraft_type == 3 ) ? 'selected' : '' ?>>Heavy Jet</option>
				<option value="4" <?php echo ($aircraft_type == 4 ) ? 'selected' : '' ?>>Airliner</option>
				<option value="5" <?php echo ($aircraft_type == 5 ) ? 'selected' : '' ?>>Helicopter</option>				
			</select>
		</p>
		
		<p>
			<label for="aircraft_passengers"><?php _e( 'Passengers', 'dynamicaviation' ); ?></label><br>
			<input type="text" name="aircraft_passengers" id="aircraft_passengers" value="<?php echo aviation_field( 'aircraft_passengers' ); ?>">
		</p>	<p>
			<label for="aircraft_range"><?php _e( 'Range (nautical miles)', 'dynamicaviation' ); ?></label><br>
			<input type="text" name="aircraft_range" id="aircraft_range" value="<?php echo aviation_field( 'aircraft_range' ); ?>">
		</p>	<p>
			<label for="aircraft_cruise_speed"><?php _e( 'Cruise Speed (knots)', 'dynamicaviation' ); ?></label><br>
			<input type="text" name="aircraft_cruise_speed" id="aircraft_cruise_speed" value="<?php echo aviation_field( 'aircraft_cruise_speed' ); ?>">
		</p>	<p>
			<label for="aircraft_max_altitude"><?php _e( 'Max. Altitude (feet)', 'dynamicaviation' ); ?></label><br>
			<input type="text" name="aircraft_max_altitude" id="aircraft_max_altitude" value="<?php echo aviation_field( 'aircraft_max_altitude' ); ?>">
		</p>	<p>
			<label for="aircraft_takeoff_field"><?php _e( 'Takeoff Field Lenght (feet)', 'dynamicaviation' ); ?></label><br>
			<input type="text" name="aircraft_takeoff_field" id="aircraft_takeoff_field" value="<?php echo aviation_field( 'aircraft_takeoff_field' ); ?>">
		</p>	<p>
			<label for="aircraft_manufacturer"><?php _e( 'Manufacturer', 'dynamicaviation' ); ?></label><br>
			<input type="text" name="aircraft_manufacturer" id="aircraft_manufacturer" value="<?php echo aviation_field( 'aircraft_manufacturer' ); ?>">
		</p>	<p>
			<label for="aircraft_model"><?php _e( 'Model', 'dynamicaviation' ); ?></label><br>
			<input type="text" name="aircraft_model" id="aircraft_model" value="<?php echo aviation_field( 'aircraft_model' ); ?>">
		</p>	<p>
			<label for="aircraft_year_of_construction"><?php _e( 'Year of Construction', 'dynamicaviation' ); ?></label><br>
			<input type="text" name="aircraft_year_of_construction" id="aircraft_year_of_construction" value="<?php echo aviation_field( 'aircraft_year_of_construction' ); ?>">
		</p>
		<p>
			<label for="aircraft_price_per_hour"><?php _e( 'Price Per Hour', 'dynamicaviation' ); ?></label><br>
			<input type="text" name="aircraft_price_per_hour" id="aircraft_price_per_hour" value="<?php echo aviation_field( 'aircraft_price_per_hour' ); ?>">
		</p>

	<?php
	}

	public function save( $post_id ) {
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) return;
		if ( ! isset( $_POST['aviation_nonce'] ) || ! wp_verify_nonce( $_POST['aviation_nonce'], '_aviation_nonce' ) ) return;
		if ( ! current_user_can( 'edit_post', $post_id ) ) return;	
		
		if ( isset( $_POST['aircraft_rates'] ) )
			update_post_meta( $post_id, 'aircraft_rates', esc_attr( $_POST['aircraft_rates'] ) );
		
		if ( isset( $_POST['aircraft_flights'] ) )
		{
			if($_POST['aircraft_flights'] > 10)
			{
				update_post_meta( $post_id, 'aircraft_flights', esc_attr( $_POST['aircraft_flights'] ) );
			}
			else
			{
				update_post_meta( $post_id, 'aircraft_flights', 10 );	
			}
		}

		
		if ( isset( $_POST['aircraft_type'] ) )
			update_post_meta( $post_id, 'aircraft_type', esc_attr( $_POST['aircraft_type'] ) );
		if ( isset( $_POST['aircraft_base_iata'] ) )
			update_post_meta( $post_id, 'aircraft_base_iata', esc_attr( $_POST['aircraft_base_iata'] ) );
		if ( isset( $_POST['aircraft_base_name'] ) )
			update_post_meta( $post_id, 'aircraft_base_name', esc_attr( $_POST['aircraft_base_name'] ) );	
		if ( isset( $_POST['aircraft_base_city'] ) )
			update_post_meta( $post_id, 'aircraft_base_city', esc_attr( $_POST['aircraft_base_city'] ) );			
		if ( isset( $_POST['aircraft_base_lat'] ) )
			update_post_meta( $post_id, 'aircraft_base_lat', esc_attr( $_POST['aircraft_base_lat'] ) );
		if ( isset( $_POST['aircraft_base_lon'] ) )
			update_post_meta( $post_id, 'aircraft_base_lon', esc_attr( $_POST['aircraft_base_lon'] ) );
		if ( isset( $_POST['aircraft_passengers'] ) )
			update_post_meta( $post_id, 'aircraft_passengers', esc_attr( $_POST['aircraft_passengers'] ) );
		if ( isset( $_POST['aircraft_range'] ) )
			update_post_meta( $post_id, 'aircraft_range', esc_attr( $_POST['aircraft_range'] ) );
		if ( isset( $_POST['aircraft_cruise_speed'] ) )
			update_post_meta( $post_id, 'aircraft_cruise_speed', esc_attr( $_POST['aircraft_cruise_speed'] ) );
		if ( isset( $_POST['aircraft_max_altitude'] ) )
			update_post_meta( $post_id, 'aircraft_max_altitude', esc_attr( $_POST['aircraft_max_altitude'] ) );
		if ( isset( $_POST['aircraft_takeoff_field'] ) )
			update_post_meta( $post_id, 'aircraft_takeoff_field', esc_attr( $_POST['aircraft_takeoff_field'] ) );
		if ( isset( $_POST['aircraft_manufacturer'] ) )
			update_post_meta( $post_id, 'aircraft_manufacturer', esc_attr( $_POST['aircraft_manufacturer'] ) );
		if ( isset( $_POST['aircraft_model'] ) )
			update_post_meta( $post_id, 'aircraft_model', esc_attr( $_POST['aircraft_model'] ) );
		if ( isset( $_POST['aircraft_year_of_construction'] ) )
			update_post_meta( $post_id, 'aircraft_year_of_construction', esc_attr( $_POST['aircraft_year_of_construction'] ) );
		if ( isset( $_POST['aircraft_price_per_hour'] ) )
			update_post_meta( $post_id, 'aircraft_price_per_hour', esc_attr( $_POST['aircraft_price_per_hour'] ) );			
		if ( isset( $_POST['aircraft_connected_packages'] ) )
			update_post_meta( $post_id, 'aircraft_connected_packages', esc_textarea($this->utilities->sanitize_items_per_line('intval',  $_POST['aircraft_connected_packages'], 20 )) );				

	}
}



?>