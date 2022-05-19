<form class="aircraft_calculator" method="get" action="<?php echo esc_url(home_lang().'/instant_quote/'); ?>">


		<div class="bottom-20"><label><i class="linkcolor fas fa-map-marker"></i> <?php esc_html(_e('Origin', 'dynamicaviation')); ?></label>
		<input type="text" id="aircraft_origin" name="aircraft_origin" class="aircraft_list" spellcheck="false" placeholder="<?php esc_html(_e('country / city / airport', 'dynamicaviation')); ?>" /><input type="hidden" id="aircraft_origin_l" name="aircraft_origin_l"></div>

		
		<div class="bottom-20"><label><i class="linkcolor fas fa-map-marker"></i> <?php esc_html(_e('Destination', 'dynamicaviation')); ?></label>	
		<input type="text" id="aircraft_destination" name="aircraft_destination" class="aircraft_list" spellcheck="false" placeholder="<?php esc_html(_e('country / city / airport', 'dynamicaviation')); ?>" /><input type="hidden" id="aircraft_destination_l" name="aircraft_destination_l"></div>
		
		
		<div class="pure-g gutters">
			<div class="pure-u-1 pure-u-sm-1-2 pure-u-md-1-2">
				<div class="bottom-20">
					<label><i class="linkcolor fas fa-male"></i> <?php esc_html(_e('Passengers', 'dynamicaviation')); ?></label>
				<input type="number" min="1" name="aircraft_pax" id="aircraft_pax"/>
				</div>
			</div>
			<div class="pure-u-1 pure-u-sm-1-2 pure-u-md-1-2">
				<div class="bottom-20">
					<label><i class="linkcolor fas fa-plane"></i> <?php esc_html(_e('Flight', 'dynamicaviation')); ?></label>
					<select name="aircraft_flight" id="aircraft_flight">
						<option value="0"><?php esc_html(_e('One way', 'dynamicaviation')); ?></option>
						<option value="1"><?php esc_html(_e('Round trip', 'dynamicaviation')); ?></option>
					</select>
				</div>
			</div>
		</div>	
		
		<div class="pure-g gutters">
			<div class="pure-u-1 pure-u-sm-1-2 pure-u-md-1-2">
				<div class="bottom-20">
					<label><i class="linkcolor fas fa-calendar-alt"></i> <?php esc_html(_e('Date of Departure', 'dynamicaviation')); ?></label><input type="text" class="datepicker" name="aircraft_departure_date" id="aircraft_departure_date"/>
				</div>
			</div>
			<div class="pure-u-1 pure-u-sm-1-2 pure-u-md-1-2">
				<div class="bottom-20">
					<label><i class="linkcolor fas fa-clock"></i> <?php esc_html(_e('Hour of Departure', 'dynamicaviation')); ?></label><input placeholder="<?php esc_html(_e('Local Time', 'dynamicaviation')); ?>" type="text" class="timepicker" name="aircraft_departure_hour" id="aircraft_departure_hour"/>
				</div>
			</div>
		</div>
		
		<div class="aircraft_return">
			<div class="pure-g gutters">
				<div class="pure-u-1 pure-u-sm-1-2 pure-u-md-1-2">
					<div class="bottom-20">
						<label><i class="linkcolor fas fa-calendar-alt"></i> <?php esc_html(_e('Date of Return', 'dynamicaviation')); ?></label><input type="text" class="datepicker" name="aircraft_return_date" id="aircraft_return_date"/>
					</div>
				</div>
				<div class="pure-u-1 pure-u-sm-1-2 pure-u-md-1-2">
					<div class="bottom-20">
						<label><i class="linkcolor fas fa-clock"></i> <?php esc_html(_e('Hour of Return', 'dynamicaviation')); ?></label><input placeholder="<?php esc_html(_e('Local Time', 'dynamicaviation')); ?>" type="text" class="timepicker" name="aircraft_return_hour" id="aircraft_return_hour"/>
					</div>
				</div>
			</div>	
		</div>

<div class="text-center bottom-20"><button id="aircraft_submit" class="strong uppercase pure-button pure-button-primary" type="button"><i class="fa fa-search" aria-hidden="true"></i> <?php esc_html(_e('Find Aircrafts', 'dynamicaviation')); ?></button></div>

<div class="text-center"><small class="text-muted">Powered by</small> <img style="vertical-align: middle;" width="57" height="18" alt="algolia" src="<?php echo esc_url(plugin_dir_url(__DIR__) . 'img/algolia.svg'); ?>"/></div>
		
</form>