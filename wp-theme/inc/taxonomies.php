<?php
/**
 * Renobattery — Taxonomies
 *
 * @package Renobattery
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action( 'init', 'renobattery_register_taxonomies' );
function renobattery_register_taxonomies() : void {

	register_taxonomy( 'product_cat', [ 'product' ], [
		'labels' => [
			'name'          => __( 'Product Categories', 'renobattery' ),
			'singular_name' => __( 'Product Category',   'renobattery' ),
			'search_items'  => __( 'Search Categories',  'renobattery' ),
			'all_items'     => __( 'All Categories',     'renobattery' ),
			'edit_item'     => __( 'Edit Category',      'renobattery' ),
		],
		'hierarchical'      => true,
		'public'            => true,
		'show_admin_column' => true,
		'show_in_rest'      => true,
		'rewrite'           => [ 'slug' => 'product-category', 'with_front' => false ],
	] );

	register_taxonomy( 'application_cat', [ 'product', 'case_study', 'application' ], [
		'labels' => [
			'name'          => __( 'Application Categories', 'renobattery' ),
			'singular_name' => __( 'Application Category',   'renobattery' ),
			'search_items'  => __( 'Search Application Categories', 'renobattery' ),
			'all_items'     => __( 'All Application Categories',    'renobattery' ),
			'edit_item'     => __( 'Edit Application Category',     'renobattery' ),
		],
		'hierarchical'      => true,
		'public'            => true,
		'show_admin_column' => true,
		'show_in_rest'      => true,
		'rewrite'           => [ 'slug' => 'application-category', 'with_front' => false ],
	] );
}

// Seed default terms on theme activation.
add_action( 'after_switch_theme', 'renobattery_seed_taxonomy_terms', 20 );
function renobattery_seed_taxonomy_terms() : void {
	$seed = [
		'product_cat' => [
			'lithium-ion'     => 'Lithium-ion',
			'lifepo4'         => 'LiFePO₄',
			'lead-acid'       => 'Lead-acid',
			'solid-state'     => 'Solid-state',
			'energy-storage'  => 'Energy Storage',
			'ev-battery'      => 'EV Battery',
			'portable-power'  => 'Portable Power',
		],
		'application_cat' => [
			'residential-ess' => 'Residential ESS',
			'commercial-ess'  => 'Commercial ESS',
			'utility-scale'   => 'Utility Scale',
			'ev'              => 'Electric Vehicle',
			'marine'          => 'Marine',
			'telecom'         => 'Telecom',
			'industrial'      => 'Industrial',
			'data-center'     => 'Data Center',
		],
	];

	foreach ( $seed as $taxonomy => $terms ) {
		foreach ( $terms as $slug => $name ) {
			if ( ! term_exists( $slug, $taxonomy ) ) {
				wp_insert_term( $name, $taxonomy, [ 'slug' => $slug ] );
			}
		}
	}
}
