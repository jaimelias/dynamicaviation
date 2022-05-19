<?php

class Charterflights_Meta_Box
{	
	public static function jet_get_meta( $value ) {
		$field = get_post_meta( get_the_ID(), $value, true );
		return $field;
	}	

	public static function jet_add_meta_box() {
		
		add_meta_box(
			'jet-conf',
			__( 'Flights', 'dynamicaviation' ),
			array('Charterflights_Meta_Box', 'jet_conf'),
			'jet',
			'normal',
			'default'
		);	
		
		add_meta_box(
			'jet-jet',
			__( 'Aircraft', 'dynamicaviation' ),
			array('Charterflights_Meta_Box', 'jet_html'),
			'jet',
			'normal',
			'default'
		);	

		add_meta_box(
			'jet-operator',
			__( 'Operator', 'dynamicaviation' ),
			array('Charterflights_Meta_Box', 'operator_html'),
			'jet',
			'normal',
			'default'
		);			
		
	}
	
	public static function destinations_add_meta_box() {
		add_meta_box(
			'destination-destination',
			__( 'destinations', 'dynamicaviation' ),
			array('Charterflights_Meta_Box', 'destination_html'),
			'destinations',
			'normal',
			'default'
		);
	}	


	public static function jet_conf( $post) {
	wp_nonce_field( '_jet_nonce', 'jet_nonce' ); ?>
		<p><label for="jet_commercial"><?php _e( 'Type of Transport', 'dynamicaviation' ); ?></label><br>
			<select name="jet_commercial" id="jet_commercial">
				<option value="0" <?php echo (Charterflights_Meta_Box::jet_get_meta( 'jet_commercial' ) == 0 ) ? 'selected' : '' ?>><?php _e( 'Charter Flight', 'dynamicaviation' ); ?></option>
				<option value="1" <?php echo (Charterflights_Meta_Box::jet_get_meta( 'jet_commercial' ) == 1 ) ? 'selected' : '' ?>><?php _e( 'Commercial Flight', 'dynamicaviation' ); ?></option>
			</select>
		</p>	

		<p>
			<label for="jet_base_iata"><?php _e( 'Base IATA', 'dynamicaviation' ); ?></label><br>
			<input class="jet_list" type="text" name="jet_base_iata" id="jet_base_iata" value="<?php echo Charterflights_Meta_Box::jet_get_meta( 'jet_base_iata' ); ?>">
		</p>
		<p>
			<label for="jet_base_name"><?php _e( 'Base Name', 'dynamicaviation' ); ?></label><br>
			<input class="jet_base_name" type="text" name="jet_base_name" id="jet_base_name" value="<?php echo Charterflights_Meta_Box::jet_get_meta( 'jet_base_name' ); ?>" readonly>
		</p>
		<p>
			<label for="jet_base_city"><?php _e( 'Base City', 'dynamicaviation' ); ?></label><br>
			<input class="jet_base_city" type="text" name="jet_base_city" id="jet_base_city" value="<?php echo Charterflights_Meta_Box::jet_get_meta( 'jet_base_city' ); ?>" readonly>
		</p>		
		<p>
			<label for="jet_base_lat"><?php _e( 'Base Latitude', 'dynamicaviation' ); ?></label><br>
			<input class="jet_lat" type="text" name="jet_base_lat" id="jet_base_lat" value="<?php echo Charterflights_Meta_Box::jet_get_meta( 'jet_base_lat' ); ?>" readonly>
		</p>
		<p>
			<label for="jet_base_lon"><?php _e( 'Base Longitude', 'dynamicaviation' ); ?></label><br>
			<input class="jet_lon" type="text" name="jet_base_lon" id="jet_base_lon" value="<?php echo Charterflights_Meta_Box::jet_get_meta( 'jet_base_lon' ); ?>" readonly>
		</p>			
	
		<p>
			<label for="jet_flights"><?php _e( 'Number of Flights', 'dynamicaviation' ); ?></label><br>
			<input type="number" min="10" name="jet_flights" id="jet_flights" value="<?php echo Charterflights_Meta_Box::jet_get_meta( 'jet_flights' ); ?>">
		</p>	

		<p>
			<label for="jet_rates"><?php _e( 'Prices Per Flight', 'dynamicaviation' ); ?></label><br>
			<textarea class="hidden" type="text" name="jet_rates" id="jet_rates"><?php echo Charterflights_Meta_Box::jet_get_meta( 'jet_rates' ); ?></textarea>
			<div class="jet_rates_table_container"><div id="jet_rates_table" data-sensei-headers="origin,destination,duration,price,fee per person, stops,seats,max weight" data-sensei-type="text,text,currency,currency,currency,numeric,numeric,numeric"></div></div>
		</p>	

	<?php
	}
	
	public static function destination_html( $post) {
	wp_nonce_field( '_jet_nonce', 'jet_nonce' ); ?>

		<p>
			<label for="jet_base_iata"><?php _e( 'Base IATA', 'dynamicaviation' ); ?></label><br>
			<input class="jet_list" type="text" name="jet_base_iata" id="jet_base_iata" value="<?php echo Charterflights_Meta_Box::jet_get_meta( 'jet_base_iata' ); ?>">
		</p>
		<p>
			<label for="jet_base_name"><?php _e( 'Base Name', 'dynamicaviation' ); ?></label><br>
			<input class="jet_base_name" type="text" name="jet_base_name" id="jet_base_name" value="<?php echo Charterflights_Meta_Box::jet_get_meta( 'jet_base_name' ); ?>" readonly>
		</p>
		<p>
			<label for="jet_base_city"><?php _e( 'Base City', 'dynamicaviation' ); ?></label><br>
			<input class="jet_base_city" type="text" name="jet_base_city" id="jet_base_city" value="<?php echo Charterflights_Meta_Box::jet_get_meta( 'jet_base_city' ); ?>" readonly>
		</p>	

	<?php
	}

	public static function jet_html( $post) {
		wp_nonce_field( '_jet_nonce', 'jet_nonce' ); ?>
		
		<p><label for="jet_type"><?php _e( 'Type', 'dynamicaviation' ); ?></label><br>
			<select name="jet_type" id="jet_type">
				<option value="0" <?php echo (Charterflights_Meta_Box::jet_get_meta( 'jet_type' ) == 0 ) ? 'selected' : '' ?>>Turbo Prop</option>
				<option value="1" <?php echo (Charterflights_Meta_Box::jet_get_meta( 'jet_type' ) == 1 ) ? 'selected' : '' ?>>Light Jet</option>
				<option value="2" <?php echo (Charterflights_Meta_Box::jet_get_meta( 'jet_type' ) == 2 ) ? 'selected' : '' ?>>Mid-size Jet</option>
				<option value="3" <?php echo (Charterflights_Meta_Box::jet_get_meta( 'jet_type' ) == 3 ) ? 'selected' : '' ?>>Heavy Jet</option>
				<option value="4" <?php echo (Charterflights_Meta_Box::jet_get_meta( 'jet_type' ) == 4 ) ? 'selected' : '' ?>>Airliner</option>
				<option value="5" <?php echo (Charterflights_Meta_Box::jet_get_meta( 'jet_type' ) == 5 ) ? 'selected' : '' ?>>Helicopter</option>				
			</select>
		</p>
		
		<p>
			<label for="jet_passengers"><?php _e( 'Passengers', 'dynamicaviation' ); ?></label><br>
			<input type="text" name="jet_passengers" id="jet_passengers" value="<?php echo Charterflights_Meta_Box::jet_get_meta( 'jet_passengers' ); ?>">
		</p>	<p>
			<label for="jet_range"><?php _e( 'Range (nautical miles)', 'dynamicaviation' ); ?></label><br>
			<input type="text" name="jet_range" id="jet_range" value="<?php echo Charterflights_Meta_Box::jet_get_meta( 'jet_range' ); ?>">
		</p>	<p>
			<label for="jet_cruise_speed"><?php _e( 'Cruise Speed (knots)', 'dynamicaviation' ); ?></label><br>
			<input type="text" name="jet_cruise_speed" id="jet_cruise_speed" value="<?php echo Charterflights_Meta_Box::jet_get_meta( 'jet_cruise_speed' ); ?>">
		</p>	<p>
			<label for="jet_max_altitude"><?php _e( 'Max. Altitude (feet)', 'dynamicaviation' ); ?></label><br>
			<input type="text" name="jet_max_altitude" id="jet_max_altitude" value="<?php echo Charterflights_Meta_Box::jet_get_meta( 'jet_max_altitude' ); ?>">
		</p>	<p>
			<label for="jet_takeoff_field"><?php _e( 'Takeoff Field Lenght (feet)', 'dynamicaviation' ); ?></label><br>
			<input type="text" name="jet_takeoff_field" id="jet_takeoff_field" value="<?php echo Charterflights_Meta_Box::jet_get_meta( 'jet_takeoff_field' ); ?>">
		</p>	<p>
			<label for="jet_manufacturer"><?php _e( 'Manufacturer', 'dynamicaviation' ); ?></label><br>
			<input type="text" name="jet_manufacturer" id="jet_manufacturer" value="<?php echo Charterflights_Meta_Box::jet_get_meta( 'jet_manufacturer' ); ?>">
		</p>	<p>
			<label for="jet_model"><?php _e( 'Model', 'dynamicaviation' ); ?></label><br>
			<input type="text" name="jet_model" id="jet_model" value="<?php echo Charterflights_Meta_Box::jet_get_meta( 'jet_model' ); ?>">
		</p>	<p>
			<label for="jet_year_of_construction"><?php _e( 'Year of Construction', 'dynamicaviation' ); ?></label><br>
			<input type="text" name="jet_year_of_construction" id="jet_year_of_construction" value="<?php echo Charterflights_Meta_Box::jet_get_meta( 'jet_year_of_construction' ); ?>">
		</p>	<p>
			<label for="jet_show_price"><?php _e( 'Show Price', 'dynamicaviation' ); ?></label><br>
			<select name="jet_show_price" id="jet_show_price">
				<option <?php echo (Charterflights_Meta_Box::jet_get_meta( 'jet_show_price' ) === 'Yes' ) ? 'selected' : '' ?>>Yes</option>
				<option <?php echo (Charterflights_Meta_Box::jet_get_meta( 'jet_show_price' ) === 'No' ) ? 'selected' : '' ?>>No</option>
			</select>
		</p>	<p>
			<label for="jet_price_per_hour"><?php _e( 'Price Per Hour', 'dynamicaviation' ); ?></label><br>
			<input type="text" name="jet_price_per_hour" id="jet_price_per_hour" value="<?php echo Charterflights_Meta_Box::jet_get_meta( 'jet_price_per_hour' ); ?>">
		</p><?php
	}
	
public static function payment_terms($post)
{
	wp_nonce_field( '_jet_nonce', 'jet_nonce' );
	?>
		<p>
			<label for="jet_payment"><?php _e( 'Payment', 'dynamicaviation' ); ?></label><br>
			<select name="jet_payment" id="jet_payment">
				<option value="0" <?php echo (charterflights_meta_box::jet_get_meta( 'jet_payment' ) == 0 ) ? 'selected' : ''; ?>><?php echo esc_html(__('Full payment', 'dynamicaviation'));?></option>
				<option value="1" <?php echo (charterflights_meta_box::jet_get_meta( 'jet_payment' ) == 1 ) ? 'selected' : ''; ?>><?php echo esc_html(__('Deposit', 'dynamicaviation'));?></option>
			</select> <?php if(charterflights_meta_box::jet_get_meta( 'jet_payment' ) == 1):?><label><input type="number" step="0.1" min="0" name="jet_deposit" id="jet_deposit" value="<?php echo charterflights_meta_box::jet_get_meta( 'jet_deposit' ); ?>">%</label><?php endif;?>
		</p>	
		<p>
			<label for="jet_last_minute"><?php _e( 'Last Minute Discount', 'dynamicaviation' ); ?></label><br>
			<select name="jet_last_minute" id="jet_last_minute">
				<option value="0" <?php echo (charterflights_meta_box::jet_get_meta( 'jet_last_minute' ) == 0 ) ? 'selected' : ''; ?>><?php echo esc_html(__('No discount', 'dynamicaviation'));?></option>
				<option value="1" <?php echo (charterflights_meta_box::jet_get_meta( 'jet_last_minute' ) == 1 ) ? 'selected' : ''; ?>><?php echo esc_html(__('Discount from total price', 'dynamicaviation'));?></option>
				<?php if(charterflights_meta_box::jet_get_meta( 'jet_payment' ) == 1):?><option value="2" <?php echo (charterflights_meta_box::jet_get_meta( 'jet_last_minute' ) == 2 ) ? 'selected' : ''; ?>><?php echo esc_html(__('Discount from deposit', 'dynamicaviation'));?></option><?php endif;?>
			</select><?php if(charterflights_meta_box::jet_get_meta( 'jet_last_minute' ) > 0):?><label for="jet_last_minute_discount"><input type="number" step="0.1" name="jet_last_minute_discount" id="jet_last_minute_discount" value="<?php echo charterflights_meta_box::jet_get_meta( 'jet_last_minute_discount' ); ?>">%</label><?php endif;?>
		</p>		
		
		<p>
			<label for="jet_last_minute_days"><?php _e( 'Last Minute Extends to', 'dynamicaviation' ); ?></label><br/><label><input type="number" min="0" name="jet_last_minute_days" id="jet_last_minute_days" value="<?php echo charterflights_meta_box::jet_get_meta( 'jet_last_minute_days' ); ?>"><?php _e( 'days prior departure', 'dynamicaviation' ); ?></label>
		</p>
	
	<?php
}
	
public static function operator_html( $post) {
	wp_nonce_field( '_jet_nonce', 'jet_nonce' ); ?>

		<p>
			<label for="operator"><?php _e( 'Operator', 'dynamicaviation' ); ?></label><br>
			<input type="text" name="operator" id="operator" value="<?php echo Charterflights_Meta_Box::jet_get_meta( 'operator' ); ?>">
		</p>
		<p>
			<label for="operator_email"><?php _e( 'Email', 'dynamicaviation' ); ?></label><br>
			<input type="text" name="operator_email" id="operator_email" value="<?php echo Charterflights_Meta_Box::jet_get_meta( 'operator_email' ); ?>">
		</p>
		<p>
			<label for="operator_tel"><?php _e( 'Telephone', 'dynamicaviation' ); ?></label><br>
			<input type="text" name="operator_tel" id="operator_tel" value="<?php echo Charterflights_Meta_Box::jet_get_meta( 'operator_tel' ); ?>">
		</p>
		<p>
			<label for="operator_location"><?php _e( 'Location', 'dynamicaviation' ); ?></label><br>
			<input type="text" name="operator_location" id="operator_location" value="<?php echo Charterflights_Meta_Box::jet_get_meta( 'operator_location' ); ?>">
		</p>		

<?php
	}

	public static function jet_save( $post_id ) {
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) return;
		if ( ! isset( $_POST['jet_nonce'] ) || ! wp_verify_nonce( $_POST['jet_nonce'], '_jet_nonce' ) ) return;
		if ( ! current_user_can( 'edit_post', $post_id ) ) return;
		
		if ( isset( $_POST['jet_commercial'] ) )
			update_post_meta( $post_id, 'jet_commercial', esc_attr( $_POST['jet_commercial'] ) );		
		
		if ( isset( $_POST['jet_rates'] ) )
			update_post_meta( $post_id, 'jet_rates', esc_attr( $_POST['jet_rates'] ) );
		
		if ( isset( $_POST['jet_flights'] ) )
		{
			if($_POST['jet_flights'] > 10)
			{
				update_post_meta( $post_id, 'jet_flights', esc_attr( $_POST['jet_flights'] ) );
			}
			else
			{
				update_post_meta( $post_id, 'jet_flights', 10 );	
			}
		}

		
		if ( isset( $_POST['jet_type'] ) )
			update_post_meta( $post_id, 'jet_type', esc_attr( $_POST['jet_type'] ) );
		if ( isset( $_POST['jet_base_iata'] ) )
			update_post_meta( $post_id, 'jet_base_iata', esc_attr( $_POST['jet_base_iata'] ) );
		if ( isset( $_POST['jet_base_name'] ) )
			update_post_meta( $post_id, 'jet_base_name', esc_attr( $_POST['jet_base_name'] ) );	
		if ( isset( $_POST['jet_base_city'] ) )
			update_post_meta( $post_id, 'jet_base_city', esc_attr( $_POST['jet_base_city'] ) );			
		if ( isset( $_POST['jet_base_lat'] ) )
			update_post_meta( $post_id, 'jet_base_lat', esc_attr( $_POST['jet_base_lat'] ) );
		if ( isset( $_POST['jet_base_lon'] ) )
			update_post_meta( $post_id, 'jet_base_lon', esc_attr( $_POST['jet_base_lon'] ) );
		if ( isset( $_POST['jet_passengers'] ) )
			update_post_meta( $post_id, 'jet_passengers', esc_attr( $_POST['jet_passengers'] ) );
		if ( isset( $_POST['jet_range'] ) )
			update_post_meta( $post_id, 'jet_range', esc_attr( $_POST['jet_range'] ) );
		if ( isset( $_POST['jet_cruise_speed'] ) )
			update_post_meta( $post_id, 'jet_cruise_speed', esc_attr( $_POST['jet_cruise_speed'] ) );
		if ( isset( $_POST['jet_max_altitude'] ) )
			update_post_meta( $post_id, 'jet_max_altitude', esc_attr( $_POST['jet_max_altitude'] ) );
		if ( isset( $_POST['jet_takeoff_field'] ) )
			update_post_meta( $post_id, 'jet_takeoff_field', esc_attr( $_POST['jet_takeoff_field'] ) );
		if ( isset( $_POST['jet_manufacturer'] ) )
			update_post_meta( $post_id, 'jet_manufacturer', esc_attr( $_POST['jet_manufacturer'] ) );
		if ( isset( $_POST['jet_model'] ) )
			update_post_meta( $post_id, 'jet_model', esc_attr( $_POST['jet_model'] ) );
		if ( isset( $_POST['jet_year_of_construction'] ) )
			update_post_meta( $post_id, 'jet_year_of_construction', esc_attr( $_POST['jet_year_of_construction'] ) );
		if ( isset( $_POST['jet_show_price'] ) )
			update_post_meta( $post_id, 'jet_show_price', esc_attr( $_POST['jet_show_price'] ) );
		if ( isset( $_POST['jet_price_per_hour'] ) )
			update_post_meta( $post_id, 'jet_price_per_hour', esc_attr( $_POST['jet_price_per_hour'] ) );

		if ( isset( $_POST['operator'] ) )
			update_post_meta( $post_id, 'operator', esc_attr( $_POST['operator'] ) );
		if ( isset( $_POST['operator_email'] ) )
			update_post_meta( $post_id, 'operator_email', esc_attr( $_POST['operator_email'] ) );
		if ( isset( $_POST['operator_tel'] ) )
			update_post_meta( $post_id, 'operator_tel', esc_attr( $_POST['operator_tel'] ) );
		if ( isset( $_POST['operator_location'] ) )
			update_post_meta( $post_id, 'operator_location', esc_attr( $_POST['operator_location'] ) );				
	}

	/*
		Usage: Charterflights_Meta_Box::jet_get_meta( 'jet_type' )
		Usage: Charterflights_Meta_Box::jet_get_meta( 'jet_base_iata' )
		Usage: Charterflights_Meta_Box::jet_get_meta( 'jet_base_lat' )
		Usage: Charterflights_Meta_Box::jet_get_meta( 'jet_base_lon' )
		Usage: Charterflights_Meta_Box::jet_get_meta( 'jet_passengers' )
		Usage: Charterflights_Meta_Box::jet_get_meta( 'jet_range' )
		Usage: Charterflights_Meta_Box::jet_get_meta( 'jet_cruise_speed' )
		Usage: Charterflights_Meta_Box::jet_get_meta( 'jet_max_altitude' )
		Usage: Charterflights_Meta_Box::jet_get_meta( 'jet_takeoff_field' )
		Usage: Charterflights_Meta_Box::jet_get_meta( 'jet_manufacturer' )
		Usage: Charterflights_Meta_Box::jet_get_meta( 'jet_model' )
		Usage: Charterflights_Meta_Box::jet_get_meta( 'jet_year_of_construction' )
		Usage: Charterflights_Meta_Box::jet_get_meta( 'jet_show_price' )
		Usage: Charterflights_Meta_Box::jet_get_meta( 'jet_price_per_hour' )
	*/
	
}



?>