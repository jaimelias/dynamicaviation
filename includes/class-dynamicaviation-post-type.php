<?php


class Charterflights_Post_Type
{
	function aircraft_post_type() {

		$labels = array(
			'name'  => __( 'Aircrafts', 'Destination General Name', 'dynamicaviation' ),
			'singular_name' => __( 'Jet', 'Destination Singular Name', 'dynamicaviation' ),
			'menu_name' => __( 'Aircrafts', 'dynamicaviation' ),
			'name_admin_bar'  => __( 'Aircrafts', 'dynamicaviation' ),
			'parent_item_colon' => __( 'Parent Item:', 'dynamicaviation' ),
			'all_items' => __( 'All Items', 'dynamicaviation' ),
			'add_new_item'  => __( 'Add New Item', 'dynamicaviation' ),
			'add_new' => __( 'Add New', 'dynamicaviation' ),
			'new_item'  => __( 'New Item', 'dynamicaviation' ),
			'edit_item' => __( 'Edit Item', 'dynamicaviation' ),
			'update_item' => __( 'Update Item', 'dynamicaviation' ),
			'view_item' => __( 'View Item', 'dynamicaviation' ),
			'search_items'  => __( 'Search Item', 'dynamicaviation' ),
			'not_found' => __( 'Not found', 'dynamicaviation' ),
			'not_found_in_trash'  => __( 'Not found in Trash', 'dynamicaviation' ),
			'items_list'  => __( 'Items list', 'dynamicaviation' ),
			'items_list_navigation' => __( 'Items list navigation', 'dynamicaviation' ),
			'filter_items_list' => __( 'Filter items list', 'dynamicaviation' )
		);
		$args = array(
			'label' => __( 'Jet', 'dynamicaviation' ),
			'labels'  => $labels,
			'supports'  => array( 'title', 'editor', 'thumbnail', 'revisions', ),
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
		

}

?>