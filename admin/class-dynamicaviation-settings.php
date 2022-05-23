<?php

class Dynamic_Aviation_Settings
{
	public function __construct($utilities)
	{
		$this->utilities = $utilities;
		$this->init();
	}
	public function init()
	{
		add_action('admin_menu', array(&$this, 'add_settings_page'));
		add_action('admin_init', array(&$this, 'settings_init'));
	}


	public function add_settings_page()
	{
		add_submenu_page('edit.php?post_type=aircrafts', 'Dynamic Aviation - Settings', 'Settings', 'manage_options', 'dynamicaviation', array(&$this, 'settings_page'));
	}
	public function settings_page()
		 { 
		?><div class="wrap">
		<form action="options.php" method="post">
			
			<h2><?php esc_html_e(__('Dynamic Aviation', 'dynamicaviation')); ?></h2>	
			<?php
			settings_fields( 'aircraft_settings' );
			do_settings_sections( 'aircraft_settings' );
			submit_button();
			?>			
		</form>
		
		<?php
	}
	
	public function settings_init()
	{ 
		$languages = $this->utilities->get_languages();

		register_setting( 'aircraft_settings', 'dy_email', 'sanitize_email');
		register_setting( 'aircraft_settings', 'dy_whatsapp', 'intval');
		register_setting( 'aircraft_settings', 'dy_phone', 'intval');
		register_setting( 'aircraft_settings', 'dy_address', 'sanitize_text_field');
		register_setting( 'aircraft_settings', 'dy_address', 'sanitize_text_field');
		register_setting( 'aircraft_settings', 'mapbox_token', 'sanitize_text_field');
		register_setting( 'aircraft_settings', 'mapbox_map_id', 'sanitize_text_field');
		register_setting( 'aircraft_settings', 'mapbox_map_zoom', 'intval');
		register_setting( 'aircraft_settings', 'mapbox_base_lat', 'sanitize_text_field');
		register_setting( 'aircraft_settings', 'mapbox_base_lon', 'sanitize_text_field');
		register_setting( 'aircraft_settings', 'algolia_token', 'sanitize_text_field');
		register_setting( 'aircraft_settings', 'algolia_index', 'sanitize_text_field');
		register_setting( 'aircraft_settings', 'algolia_id', 'sanitize_text_field');		
		register_setting( 'aircraft_settings', 'aircraft_webhook', 'esc_url');		

		add_settings_section(
			'aircraft_settings_section', 
			esc_html(__( 'General Settings', 'dynamicaviation' )), 
			'', 
			'aircraft_settings'
		);

		add_settings_field( 
			'dy_email', 
			esc_html(__( 'Company Email', 'dynamicaviation' )), 
			array(&$this, 'input_text'), 
			'aircraft_settings', 
			'aircraft_settings_section',
			array('name' => 'dy_email', 'type' => 'text')
		);

		add_settings_field( 
			'dy_whatsapp', 
			esc_html(__( 'Company Whatsapp', 'dynamicaviation' )), 
			array(&$this, 'input_text'), 
			'aircraft_settings', 
			'aircraft_settings_section',
			array('name' => 'dy_whatsapp', 'type' => 'text')
		);

		add_settings_field( 
			'dy_phone', 
			esc_html(__( 'Company Phone', 'dynamicaviation' )), 
			array(&$this, 'input_text'), 
			'aircraft_settings', 
			'aircraft_settings_section',
			array('name' => 'dy_phone', 'type' => 'text')
		);

		add_settings_field( 
			'dy_address', 
			esc_html(__( 'Company Address', 'dynamicaviation' )), 
			array(&$this, 'input_text'), 
			'aircraft_settings', 
			'aircraft_settings_section',
			array('name' => 'dy_address', 'type' => 'text')
		);

		add_settings_field( 
			'dy_tax_id', 
			esc_html(__( 'Company Tax ID', 'dynamicaviation' )), 
			array(&$this, 'input_text'), 
			'aircraft_settings', 
			'aircraft_settings_section',
			array('name' => 'dy_tax_id', 'type' => 'text')
		);

		for($x = 0; $x < count($languages); $x++)
		{
			$estimate_note_name = 'dy_aviation_estimate_note_'.$languages[$x];
			register_setting( 'aircraft_settings', $estimate_note_name, 'sanitize_textarea_field');

			add_settings_field( 
				$estimate_note_name, 
				esc_html(sprintf(__( 'Estimate Notes in %s language', 'dynamicaviation' ), strtoupper($languages[$x]))), 
				array(&$this, 'textarea'), 
				'aircraft_settings', 
				'aircraft_settings_section',
				array('name' => $estimate_note_name, 'rows' => 4, 'cols' => 50)
			);
		}

		add_settings_field( 
			'mapbox_token', 
			esc_html(__( 'Mapbox Token', 'dynamicaviation' )), 
			array(&$this, 'input_text'), 
			'aircraft_settings', 
			'aircraft_settings_section',
			array('name' => 'mapbox_token', 'type' => 'text')
		);

		$mapbox_map_id_args = array(
			'name' => 'mapbox_map_id',
			'options' => array(
				array(
					'text' => __('Streets', 'dynamicpackages'),
					'value' => 'mapbox.streets'
				),
				array(
					'text' => __('Light', 'dynamicpackages'),
					'value' => 'mapbox.light'
				),
				array(
					'text' => __('Dark', 'dynamicpackages'),
					'value' => 'mapbox.dark'
				),
				array(
					'text' => __('Outdoors', 'dynamicpackages'),
					'value' => 'mapbox.outdoors'
				),
			)
		);

		add_settings_field( 
			'mapbox_map_id', 
			esc_html(__( 'Map ID', 'dynamicaviation' )), 
			array(&$this, 'select'), 
			'aircraft_settings', 
			'aircraft_settings_section',
			$mapbox_map_id_args
		);

		add_settings_field( 
			'mapbox_map_zoom', 
			esc_html(__( 'Mapbox Map Zoom', 'dynamicaviation' )), 
			array(&$this, 'input_text'), 
			'aircraft_settings', 
			'aircraft_settings_section',
			array('name' => 'mapbox_map_zoom', 'type' => 'number')
		);

		add_settings_field( 
			'mapbox_base_lat', 
			esc_html(__( 'Base Latitud', 'dynamicaviation' )), 
			array(&$this, 'input_text'), 
			'aircraft_settings', 
			'aircraft_settings_section',
			array('name' => 'mapbox_base_lat', 'type' => 'text')
		);

		add_settings_field( 
			'mapbox_base_lon', 
			esc_html(__( 'Base Longitud', 'dynamicaviation' )), 
			array(&$this, 'input_text'), 
			'aircraft_settings', 
			'aircraft_settings_section',
			array('name' => 'mapbox_base_lon', 'type' => 'text')
		);	

		add_settings_field( 
			'algolia_token', 
			esc_html(__( 'Algolia Api Key', 'dynamicaviation' )), 
			array(&$this, 'input_text'), 
			'aircraft_settings', 
			'aircraft_settings_section',
			array('name' => 'algolia_token', 'type' => 'text')
		);

		add_settings_field( 
			'algolia_index', 
			esc_html(__( 'Algolia Index Name', 'dynamicaviation' )), 
			array(&$this, 'input_text'), 
			'aircraft_settings', 
			'aircraft_settings_section',
			array('name' => 'algolia_index', 'type' => 'text')
		);		
		add_settings_field( 
			'algolia_id', 
			esc_html(__( 'Algolia Api Id', 'dynamicaviation' )), 
			array(&$this, 'input_text'), 
			'aircraft_settings', 
			'aircraft_settings_section',
			array('name' => 'algolia_id', 'type' => 'text')
		);	

		add_settings_field( 
			'aircraft_webhook', 
			esc_html(__( 'Webhook', 'dynamicaviation' )), 
			array(&$this, 'input_text'), 
			'aircraft_settings', 
			'aircraft_settings_section',
			array('name' => 'aircraft_webhook', 'type' => 'text')
		);


		
		


	}
	
	public function input_text($arr){
			$name = $arr['name'];
			$url = (array_key_exists('url', $arr)) ? '<a href="'.esc_url($arr['url']).'">?</a>' : null;
			$type = (array_key_exists('type', $arr)) ? $arr['type'] : 'text';
		?>
		<input type="<?php echo $type; ?>" name="<?php esc_attr_e($name); ?>" id="<?php echo $name; ?>" value="<?php esc_attr_e(get_option($name)); ?>" /> <span><?php echo $url; ?></span>

		<?php 
	}

	public function textarea($arr)
	{
		$name = $arr['name'];
		$rows = $arr['rows'];
		$cols = $arr['cols'];

		?>
			<textarea cols="<?php esc_attr_e($cols); ?>" rows="<?php esc_attr_e($rows); ?>" name="<?php esc_attr_e($name); ?>"><?php echo esc_textarea(get_option($name)); ?></textarea>
		<?php
	}

	public function select($args) {
		
		$name = $args['name'];
		$options = $args['options'];
		$value = get_option($name);
		$render_options = '';
		
		for($x = 0; $x < count($options); $x++)
		{
			$this_value = $options[$x]['value'];
			$this_text = $options[$x]['text'];
			$selected = ($value === $this_value) ? ' selected ' : '';
			$render_options .= '<option value="'.esc_attr($this_value).'" '.esc_attr($selected).'>'.esc_html($this_text).'</option>';
		}

		?>
			<select name="<?php echo esc_attr($name); ?>">
				<?php echo $render_options; ?>
			</select>
		<?php 
	}
}

?>