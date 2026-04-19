<?php
/**
 * Renobattery — Custom Post Types
 * Source: docs/step-01-architecture.json
 *
 * @package Renobattery
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action( 'init', 'renobattery_register_cpts' );
function renobattery_register_cpts() : void {

	register_post_type( 'product', [
		'labels' => [
			'name'               => __( 'Products', 'renobattery' ),
			'singular_name'      => __( 'Product',  'renobattery' ),
			'add_new_item'       => __( 'Add New Product', 'renobattery' ),
			'edit_item'          => __( 'Edit Product',    'renobattery' ),
			'search_items'       => __( 'Search Products', 'renobattery' ),
			'not_found'          => __( 'No products found.', 'renobattery' ),
			'all_items'          => __( 'All Products', 'renobattery' ),
		],
		'public'       => true,
		'has_archive'  => 'products',
		'rewrite'      => [ 'slug' => 'products', 'with_front' => false ],
		'menu_icon'    => 'dashicons-battery',
		'menu_position'=> 20,
		'supports'     => [ 'title', 'editor', 'thumbnail', 'excerpt', 'elementor', 'revisions' ],
		'show_in_rest' => true,
		'taxonomies'   => [ 'product_cat', 'application_cat' ],
	] );

	register_post_type( 'case_study', [
		'labels' => [
			'name'               => __( 'Case Studies', 'renobattery' ),
			'singular_name'      => __( 'Case Study',   'renobattery' ),
			'add_new_item'       => __( 'Add New Case Study', 'renobattery' ),
			'edit_item'          => __( 'Edit Case Study',    'renobattery' ),
			'search_items'       => __( 'Search Case Studies','renobattery' ),
			'not_found'          => __( 'No case studies found.', 'renobattery' ),
			'all_items'          => __( 'All Case Studies', 'renobattery' ),
		],
		'public'       => true,
		'has_archive'  => 'cases',
		'rewrite'      => [ 'slug' => 'cases', 'with_front' => false ],
		'menu_icon'    => 'dashicons-portfolio',
		'menu_position'=> 21,
		'supports'     => [ 'title', 'editor', 'thumbnail', 'excerpt', 'elementor', 'revisions' ],
		'show_in_rest' => true,
		'taxonomies'   => [ 'application_cat' ],
	] );

	register_post_type( 'application', [
		'labels' => [
			'name'               => __( 'Applications', 'renobattery' ),
			'singular_name'      => __( 'Application',  'renobattery' ),
			'add_new_item'       => __( 'Add New Application', 'renobattery' ),
			'edit_item'          => __( 'Edit Application',    'renobattery' ),
			'search_items'       => __( 'Search Applications', 'renobattery' ),
			'not_found'          => __( 'No applications found.', 'renobattery' ),
			'all_items'          => __( 'All Applications', 'renobattery' ),
		],
		'public'       => true,
		'has_archive'  => 'applications',
		'rewrite'      => [ 'slug' => 'applications', 'with_front' => false ],
		'menu_icon'    => 'dashicons-admin-site',
		'menu_position'=> 22,
		'supports'     => [ 'title', 'editor', 'thumbnail', 'excerpt', 'elementor', 'revisions' ],
		'show_in_rest' => true,
		'taxonomies'   => [ 'application_cat' ],
	] );
}

// Flush rewrite rules on theme activation.
add_action( 'after_switch_theme', function() : void {
	renobattery_register_cpts();
	renobattery_register_taxonomies();
	flush_rewrite_rules();
} );
