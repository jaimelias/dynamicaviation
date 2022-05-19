<?php


class Charterflights_Post_Type
{
	// Register Custom Destination
	
	function fly_post_type() {

		$labels = array(
			'name'  => __( 'Destinations', 'Destination General Name', 'dynamicaviation' ),
			'singular_name' => __( 'Destination', 'Destination Singular Name', 'dynamicaviation' ),
			'menu_name' => __( 'Destinations', 'dynamicaviation' ),
			'name_admin_bar'  => __( 'Destination', 'dynamicaviation' ),
			'archives'  => __( 'Item Archives', 'dynamicaviation' ),
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
			'featured_image'  => __( 'Featured Image', 'dynamicaviation' ),
			'set_featured_image'  => __( 'Set featured image', 'dynamicaviation' ),
			'remove_featured_image' => __( 'Remove featured image', 'dynamicaviation' ),
			'use_featured_image'  => __( 'Use as featured image', 'dynamicaviation' ),
			'insert_into_item'  => __( 'Insert into item', 'dynamicaviation' ),
			'uploaded_to_this_item' => __( 'Uploaded to this item', 'dynamicaviation' ),
			'items_list'  => __( 'Items list', 'dynamicaviation' ),
			'items_list_navigation' => __( 'Items list navigation', 'dynamicaviation' ),
			'filter_items_list' => __( 'Filter items list', 'dynamicaviation' ),
		);
		$args = array(
			'label' => __( 'Destination', 'dynamicaviation' ),
			'labels' => $labels,
			'supports' => array( ),
			'hierarchical' => false,
			'public' => true,
			'show_ui' => true,
			'show_in_menu' => true,
			'menu_position' => 5,
			'show_in_admin_bar' => true,
			'show_in_nav_menus' => true,
			'can_export' => true,
			'has_archive' => true,		
			'exclude_from_search' => true,
			'publicly_queryable' => false,
			'capability_type' => 'page',
			'show_in_rest' => true,
		);
		register_post_type( 'destinations', $args );

	}
		
	
	// Register Custom Destination
	function jet_post_type() {

		$labels = array(
			'name'  => __( 'Jets', 'Destination General Name', 'dynamicaviation' ),
			'singular_name' => __( 'Jet', 'Destination Singular Name', 'dynamicaviation' ),
			'menu_name' => __( 'Jets', 'dynamicaviation' ),
			'name_admin_bar'  => __( 'Jets', 'dynamicaviation' ),
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
		register_post_type( 'jet', $args );

	}
		

}

?>