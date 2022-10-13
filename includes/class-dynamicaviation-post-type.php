<?php


class Dynamic_Aviation_Post_Type
{

	public function __construct()
	{
		add_action( 'init', array(&$this, 'aircraft_post_type'), 0);
		add_action( 'init', array(&$this, 'destination_post_type'), 0);
	}

	public function aircraft_post_type() {

		$labels = array(
			'name'  => __( 'Aircrafts', 'Destination General Name', 'dynamicaviation' ),
			'singular_name' => __( 'Aircraft', 'Destination Singular Name', 'dynamicaviation' ),
			'menu_name' => __( 'Aircrafts', 'dynamicaviation' ),
			'name_admin_bar'  => __( 'Aircrafts', 'dynamicaviation' ),
			'parent_item_colon' => __( 'Parent Aircraft:', 'dynamicaviation' ),
			'all_items' => __( 'All Aircrafts', 'dynamicaviation' ),
			'add_new_item'  => __( 'Add New Aircraft', 'dynamicaviation' ),
			'add_new' => __( 'Add New', 'dynamicaviation' ),
			'new_item'  => __( 'New Aircraft', 'dynamicaviation' ),
			'edit_item' => __( 'Edit Aircraft', 'dynamicaviation' ),
			'update_item' => __( 'Update Aircraft', 'dynamicaviation' ),
			'view_item' => __( 'View Aircraft', 'dynamicaviation' ),
			'search_items'  => __( 'Search Aircraft', 'dynamicaviation' ),
			'not_found' => __( 'Not found', 'dynamicaviation' ),
			'not_found_in_trash'  => __( 'Not found in Trash', 'dynamicaviation' ),
			'items_list'  => __( 'Aircrafts list', 'dynamicaviation' ),
			'items_list_navigation' => __( 'Aircrafts list navigation', 'dynamicaviation' ),
			'filter_items_list' => __( 'Filter items list', 'dynamicaviation' )
		);

		$args = array(
			'label' => __('Aircraft', 'dynamicaviation' ),
			'labels'  => $labels,
			'supports'  => array( 'title', 'editor', 'thumbnail'),
			'hierarchical'  => false,
			'public'  => true,
			'show_ui' => true,
			'show_in_menu'  => true,
			'menu_position' => 5,
			'show_in_admin_bar' => true,
			'show_in_nav_menus' => true,
			'can_export'  => true,
			'has_archive' => true,		
			'exclude_from_search' => false,
			'publicly_queryable'  => true,
			'capability_type' => 'page',
			'show_in_rest' => true,
		);
		
		register_post_type( 'aircrafts', $args );

	}
		

	public function Destination_post_type() {

		$labels = array(
			'name'  => __( 'Destinations', 'Destination General Name', 'dynamicaviation' ),
			'singular_name' => __( 'Destination', 'Destination Singular Name', 'dynamicaviation' ),
			'menu_name' => __( 'Destinations', 'dynamicaviation' ),
			'name_admin_bar'  => __( 'Destinations', 'dynamicaviation' ),
			'parent_item_colon' => __( 'Parent Destination:', 'dynamicaviation' ),
			'all_items' => __( 'All Destinations', 'dynamicaviation' ),
			'add_new_item'  => __( 'Add New Destination', 'dynamicaviation' ),
			'add_new' => __( 'Add New', 'dynamicaviation' ),
			'new_item'  => __( 'New Destination', 'dynamicaviation' ),
			'edit_item' => __( 'Edit Destination', 'dynamicaviation' ),
			'update_item' => __( 'Update Destination', 'dynamicaviation' ),
			'view_item' => __( 'View Destination', 'dynamicaviation' ),
			'search_items'  => __( 'Search Destination', 'dynamicaviation' ),
			'not_found' => __( 'Not found', 'dynamicaviation' ),
			'not_found_in_trash'  => __( 'Not found in Trash', 'dynamicaviation' ),
			'items_list'  => __( 'Destinations list', 'dynamicaviation' ),
			'items_list_navigation' => __( 'Destinations list navigation', 'dynamicaviation' ),
			'filter_items_list' => __( 'Filter items list', 'dynamicaviation' )
		);

		$args = array(
			'label' => __('Destination', 'dynamicaviation' ),
			'labels'  => $labels,
			'supports'  => array( 'title', 'editor', 'thumbnail'),
			'hierarchical'  => false,
			'public'  => true,
			'show_ui' => true,
			'show_in_menu'  => true,
			'menu_position' => 6,
			'show_in_admin_bar' => true,
			'show_in_nav_menus' => true,
			'can_export'  => true,
			'has_archive' => true,
			'exclude_from_search' => false,
			'publicly_queryable'  => true,
			'capability_type' => 'page',
			'show_in_rest' => true,
		);
		
		register_post_type( 'Destinations', $args );

	}

}

?>