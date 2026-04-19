<?php
/**
 * Renobattery — Archive query filter
 *
 * Translates URL params from the pill filter UI (assets/js/filter.js) into
 * WP_Query modifiers on the main query for product / case_study / application
 * post-type archives.
 *
 * Supported params (all whitelisted — unknown values silently ignored):
 *   ?product_cat=<term-slug>
 *   ?application_cat=<term-slug>
 *   ?orderby=date-desc | title-asc | capacity-desc
 *
 * Guards:
 *   - Admin requests untouched
 *   - Only the main query (is_main_query)
 *   - Only known post-type archives
 *   - Unknown taxonomy slugs rejected before tax_query build
 *   - capacity-desc only applies on product archive (uses ACF meta)
 *
 * @package Renobattery
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action( 'pre_get_posts', 'renobattery_archive_filter_query' );
function renobattery_archive_filter_query( WP_Query $query ) : void {

	if ( is_admin() || ! $query->is_main_query() ) {
		return;
	}

	$archive_types = [ 'product', 'case_study', 'application' ];
	if ( ! $query->is_post_type_archive( $archive_types ) ) {
		return;
	}

	// --- taxonomy filters ---
	$allowed_taxes = [ 'product_cat', 'application_cat' ];
	$tax_query     = [];
	foreach ( $allowed_taxes as $tax ) {
		if ( empty( $_GET[ $tax ] ) ) {
			continue;
		}
		if ( ! taxonomy_exists( $tax ) ) {
			continue;
		}
		$slug = sanitize_title( wp_unslash( (string) $_GET[ $tax ] ) );
		if ( $slug === '' || $slug === 'all' ) {
			continue;
		}
		// Reject unknown term slugs so we don't produce "WHERE 1=0" silently.
		if ( ! get_term_by( 'slug', $slug, $tax ) ) {
			continue;
		}
		$tax_query[] = [
			'taxonomy' => $tax,
			'field'    => 'slug',
			'terms'    => [ $slug ],
		];
	}
	if ( count( $tax_query ) > 1 ) {
		$tax_query['relation'] = 'AND';
	}
	if ( ! empty( $tax_query ) ) {
		$query->set( 'tax_query', $tax_query );
	}

	// --- orderby presets ---
	if ( empty( $_GET['orderby'] ) ) {
		return;
	}
	$preset = sanitize_key( wp_unslash( (string) $_GET['orderby'] ) );

	switch ( $preset ) {
		case 'date-desc':
			$query->set( 'orderby', 'date' );
			$query->set( 'order',   'DESC' );
			break;

		case 'title-asc':
			$query->set( 'orderby', 'title' );
			$query->set( 'order',   'ASC' );
			break;

		case 'capacity-desc':
			// Only meaningful on the product archive — battery_capacity is a product-only ACF field.
			if ( $query->is_post_type_archive( 'product' ) ) {
				$query->set( 'meta_key', 'battery_capacity' );
				$query->set( 'orderby',  'meta_value_num' );
				$query->set( 'order',    'DESC' );
			}
			break;

		// Unknown preset: silently leave defaults alone.
	}
}
