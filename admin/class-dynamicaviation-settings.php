<?php

class Jetcharter_Settings
{
	public function __construct()
	{
		$this->init();
	}
	public function init()
	{
		add_action('admin_menu', array(&$this, 'add_settings_page'));
		add_action('admin_init', array(&$this, 'settings_init'));			
	}
	public function add_settings_page()
	{
		add_submenu_page('edit.php?post_type=destinations', 'Dynamic Aviation - Settings', 'Settings', 'manage_options', 'dynamicaviation', array(&$this, 'settings_page'));
	}
	public function settings_page()
		 { 
		?><div class="wrap">
		<form action="options.php" method="post">
			
			<h2><?php esc_html_e(__("Dynamic Aviation", "dynamicaviation")); ?></h2>	
			<?php
			settings_fields( 'jet_settings' );
			do_settings_sections( 'jet_settings' );
			submit_button();
			?>			
		</form>
		
		<?php
	}
	
	public function settings_init(  ) { 

		register_setting( 'jet_settings', 'mapbox_token', 'sanitize_text_field');
		register_setting( 'jet_settings', 'mapbox_map_id', 'sanitize_text_field');
		register_setting( 'jet_settings', 'mapbox_map_zoom', 'intval');
		register_setting( 'jet_settings', 'mapbox_base_lat', 'sanitize_text_field');
		register_setting( 'jet_settings', 'mapbox_base_lon', 'sanitize_text_field');
		register_setting( 'jet_settings', 'algolia_token', 'sanitize_text_field');
		register_setting( 'jet_settings', 'algolia_index', 'sanitize_text_field');
		register_setting( 'jet_settings', 'algolia_id', 'sanitize_text_field');		
		register_setting( 'jet_settings', 'jet_webhook', 'esc_url');		

		add_settings_section(
			'jet_settings_section', 
			esc_html(__( 'General Settings', 'dynamicaviation' )), 
			'', 
			'jet_settings'
		);

		add_settings_field( 
			'mapbox_token', 
			esc_html(__( 'Mapbox Token', 'dynamicaviation' )), 
			array(&$this, 'settings_input'), 
			'jet_settings', 
			'jet_settings_section',
			array('name' => 'mapbox_token', 'type' => 'text')
		);
		add_settings_field( 
			'mapbox_map_id', 
			esc_html(__( 'Map ID', 'dynamicaviation' )), 
			array(&$this, 'render_map_id'), 
			'jet_settings', 
			'jet_settings_section' 
		);
		add_settings_field( 
			'mapbox_map_zoom', 
			esc_html(__( 'Mapbox Map Zoom', 'dynamicaviation' )), 
			array(&$this, 'settings_input'), 
			'jet_settings', 
			'jet_settings_section',
			array('name' => 'mapbox_map_zoom', 'type' => 'number')
		);
		add_settings_field( 
			'mapbox_base_lat', 
			esc_html(__( 'Base Latitud', 'dynamicaviation' )), 
			array(&$this, 'settings_input'), 
			'jet_settings', 
			'jet_settings_section',
			array('name' => 'mapbox_base_lat', 'type' => 'text')
		);
		add_settings_field( 
			'mapbox_base_lon', 
			esc_html(__( 'Base Longitud', 'dynamicaviation' )), 
			array(&$this, 'settings_input'), 
			'jet_settings', 
			'jet_settings_section',
			array('name' => 'mapbox_base_lon', 'type' => 'text')
		);	


		add_settings_field( 
			'algolia_token', 
			esc_html(__( 'Algolia Api Key', 'dynamicaviation' )), 
			array(&$this, 'settings_input'), 
			'jet_settings', 
			'jet_settings_section',
			array('name' => 'algolia_token', 'type' => 'text')
		);

		add_settings_field( 
			'algolia_index', 
			esc_html(__( 'Algolia Index Name', 'dynamicaviation' )), 
			array(&$this, 'settings_input'), 
			'jet_settings', 
			'jet_settings_section',
			array('name' => 'algolia_index', 'type' => 'text')
		);		
		add_settings_field( 
			'algolia_id', 
			esc_html(__( 'Algolia Api Id', 'dynamicaviation' )), 
			array(&$this, 'settings_input'), 
			'jet_settings', 
			'jet_settings_section',
			array('name' => 'algolia_id', 'type' => 'text')
		);	

		add_settings_field( 
			'jet_webhook', 
			esc_html(__( 'Webhook', 'dynamicaviation' )), 
			array(&$this, 'settings_input'), 
			'jet_settings', 
			'jet_settings_section',
			array('name' => 'jet_webhook', 'type' => 'text')
		);
	}
	
	public function settings_input($arr){
			$name = $arr['name'];
			$url = (array_key_exists('url', $arr)) ? '<a href="'.esc_url($arr['url']).'">?</a>' : null;
			$type = (array_key_exists('type', $arr)) ? $arr['type'] : 'text';
		?>
		<input type="<?php echo $type; ?>" name="<?php echo esc_html($name); ?>" id="<?php echo $name; ?>" value="<?php echo esc_html(get_option($name)); ?>" /> <span><?php echo $url; ?></span>

	<?php }
	
	public function render_map_id(  ) { 
		$value = get_option( 'mapbox_map_id' );
		?>
		<select type='text' name='mapbox_map_id'>
			<option value="mapbox.streets" <?php selected($value, 'mapbox.streets'); ?>>Streets</option>
			<option value="mapbox.light" <?php selected($value, 'mapbox.light'); ?>>Light</option>
			<option value="mapbox.dark" <?php selected($value, 'mapbox.dark'); ?>>Dark</option>
			<option value="mapbox.outdoors" <?php selected($value, 'mapbox.outdoors'); ?>>Outdoors</option>
		</select>
		<?php
	}
}

?>