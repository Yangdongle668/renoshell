<?php
/**
 * Renobattery — Schema.org JSON-LD emitters
 *
 * Battery-industry product schema is the key value-add here; Rank Math / Yoast
 * do not know how to map ACF capacity/voltage/cycle-life to additionalProperty.
 *
 * Each emit_* function prints one <script type="application/ld+json"> block.
 *
 * @package Renobattery
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action( 'wp_head', 'renobattery_emit_schema', 8 );
function renobattery_emit_schema() : void {
	if ( is_404() || is_search() ) {
		return;
	}

	if ( is_front_page() ) {
		renobattery_schema_organization();
	}
	if ( is_singular( 'product' ) ) {
		renobattery_schema_product();
	}
	if ( is_singular( 'case_study' ) ) {
		renobattery_schema_case_study();
	}
	if ( is_singular( 'post' ) ) {
		renobattery_schema_article();
	}
	if ( ! is_front_page() ) {
		renobattery_schema_breadcrumbs();
	}
}

function renobattery_schema_emit( array $data ) : void {
	echo "\n<script type=\"application/ld+json\">" .
		wp_json_encode( $data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ) .
		"</script>\n";
}

function renobattery_schema_organization() : void {
	$logo = function_exists( 'get_custom_logo' ) ? wp_get_attachment_image_url( (int) get_theme_mod( 'custom_logo' ), 'full' ) : '';

	$social = [];
	if ( function_exists( 'get_field' ) ) {
		foreach ( (array) get_field( 'social_links', 'option' ) ?: [] as $row ) {
			$url = $row['url'] ?? '';
			if ( $url ) { $social[] = $url; }
		}
	}

	$data = [
		'@context'    => 'https://schema.org',
		'@type'       => 'Organization',
		'@id'         => home_url( '/#organization' ),
		'name'        => get_bloginfo( 'name' ),
		'legalName'   => get_bloginfo( 'name' ) . ' Technology Co., Ltd.',
		'url'         => home_url( '/' ),
		'description' => get_bloginfo( 'description' ),
		'foundingDate'=> '2014',
	];
	if ( $logo )        { $data['logo']   = $logo; }
	if ( ! empty( $social ) ) { $data['sameAs'] = $social; }

	$phone = function_exists( 'get_field' ) ? get_field( 'company_phone', 'option' ) : '';
	$email = function_exists( 'get_field' ) ? get_field( 'company_email', 'option' ) : '';
	if ( $phone || $email ) {
		$cp = [ '@type' => 'ContactPoint', 'contactType' => 'sales' ];
		if ( $phone ) { $cp['telephone'] = $phone; }
		if ( $email ) { $cp['email']     = $email; }
		$data['contactPoint'] = [ $cp ];
	}

	renobattery_schema_emit( $data );
}

function renobattery_schema_product() : void {
	$post_id = get_queried_object_id();

	$images = [];
	if ( function_exists( 'get_field' ) ) {
		foreach ( (array) get_field( 'gallery', $post_id ) ?: [] as $img ) {
			$url = $img['sizes']['rb-gallery'] ?? $img['url'] ?? '';
			if ( $url ) { $images[] = $url; }
		}
	}
	if ( empty( $images ) && has_post_thumbnail( $post_id ) ) {
		$images[] = get_the_post_thumbnail_url( $post_id, 'rb-gallery' );
	}

	$properties = [];
	$prop = function( string $name, $value, ?string $unit = null ) use ( &$properties ) : void {
		if ( $value === '' || $value === null ) { return; }
		$entry = [ '@type' => 'PropertyValue', 'name' => $name, 'value' => $value ];
		if ( $unit ) { $entry['unitText'] = $unit; }
		$properties[] = $entry;
	};
	if ( function_exists( 'get_field' ) ) {
		$prop( 'Capacity',   get_field( 'battery_capacity', $post_id ), 'Ah' );
		$prop( 'Voltage',    get_field( 'nominal_voltage',  $post_id ), 'V' );
		$prop( 'Energy',     get_field( 'energy_kwh',       $post_id ), 'kWh' );
		$prop( 'Cycle Life', get_field( 'cycle_life',       $post_id ) );
		$prop( 'Chemistry',  get_field( 'chemistry',        $post_id ) );
		$prop( 'IP Rating',  get_field( 'ip_rating',        $post_id ) );
		$prop( 'Weight',     get_field( 'weight_kg',        $post_id ), 'kg' );
	}

	$categories = wp_list_pluck( (array) get_the_terms( $post_id, 'product_cat' ) ?: [], 'name' );

	$data = array_filter( [
		'@context'    => 'https://schema.org',
		'@type'       => 'Product',
		'@id'         => get_permalink( $post_id ) . '#product',
		'name'        => get_the_title( $post_id ),
		'description' => trim( wp_strip_all_tags( get_the_excerpt( $post_id ) ) ),
		'sku'         => function_exists( 'get_field' ) ? (string) get_field( 'model_code', $post_id ) : '',
		'brand'       => [ '@type' => 'Brand', 'name' => get_bloginfo( 'name' ) ],
		'category'    => $categories ? implode( ', ', $categories ) : null,
		'image'       => $images ?: null,
		'additionalProperty' => $properties ?: null,
	] );

	renobattery_schema_emit( $data );
}

function renobattery_schema_case_study() : void {
	$post_id = get_queried_object_id();
	$location = function_exists( 'get_field' ) ? (string) get_field( 'location', $post_id ) : '';
	$about    = wp_list_pluck( (array) get_the_terms( $post_id, 'application_cat' ) ?: [], 'name' );

	$data = array_filter( [
		'@context' => 'https://schema.org',
		'@type'    => 'CreativeWork',
		'@id'      => get_permalink( $post_id ) . '#case',
		'name'     => get_the_title( $post_id ),
		'datePublished' => get_the_date( 'c', $post_id ),
		'creator'  => [ '@type' => 'Organization', 'name' => get_bloginfo( 'name' ) ],
		'locationCreated' => $location ? [ '@type' => 'Place', 'name' => $location ] : null,
		'about'    => $about ? array_map( fn( $n ) => [ '@type' => 'Thing', 'name' => $n ], $about ) : null,
		'image'    => has_post_thumbnail( $post_id ) ? get_the_post_thumbnail_url( $post_id, 'rb-hero' ) : null,
	] );

	renobattery_schema_emit( $data );
}

function renobattery_schema_article() : void {
	$post_id = get_queried_object_id();
	$author  = get_userdata( (int) get_post_field( 'post_author', $post_id ) );
	$logo    = wp_get_attachment_image_url( (int) get_theme_mod( 'custom_logo' ), 'full' );

	$data = array_filter( [
		'@context'      => 'https://schema.org',
		'@type'         => 'Article',
		'headline'      => get_the_title( $post_id ),
		'datePublished' => get_the_date( 'c', $post_id ),
		'dateModified'  => get_the_modified_date( 'c', $post_id ),
		'author'        => $author ? [ '@type' => 'Person', 'name' => $author->display_name ] : null,
		'publisher'     => [
			'@type' => 'Organization',
			'name'  => get_bloginfo( 'name' ),
			'logo'  => $logo ? [ '@type' => 'ImageObject', 'url' => $logo ] : null,
		],
		'image'         => has_post_thumbnail( $post_id ) ? get_the_post_thumbnail_url( $post_id, 'rb-hero' ) : null,
		'description'   => trim( wp_strip_all_tags( get_the_excerpt( $post_id ) ) ),
		'mainEntityOfPage' => get_permalink( $post_id ),
	] );

	renobattery_schema_emit( $data );
}

function renobattery_schema_breadcrumbs() : void {
	$trail = [ [ 'name' => __( 'Home', 'renobattery' ), 'url' => home_url( '/' ) ] ];

	if ( is_singular() ) {
		$post      = get_queried_object();
		$post_type = get_post_type_object( $post->post_type );
		if ( $post_type && $post_type->has_archive ) {
			$trail[] = [ 'name' => $post_type->labels->name, 'url' => get_post_type_archive_link( $post->post_type ) ];
		}
		$trail[] = [ 'name' => get_the_title( $post ), 'url' => get_permalink( $post ) ];
	} elseif ( is_post_type_archive() ) {
		$post_type = get_queried_object();
		$trail[] = [ 'name' => $post_type->labels->name, 'url' => get_post_type_archive_link( $post_type->name ) ];
	} elseif ( is_tax() || is_category() || is_tag() ) {
		$term = get_queried_object();
		$trail[] = [ 'name' => $term->name, 'url' => get_term_link( $term ) ];
	} elseif ( is_page() ) {
		$trail[] = [ 'name' => get_the_title(), 'url' => get_permalink() ];
	}

	if ( count( $trail ) < 2 ) {
		return;
	}

	$items = [];
	foreach ( $trail as $i => $crumb ) {
		$items[] = [
			'@type'    => 'ListItem',
			'position' => $i + 1,
			'name'     => $crumb['name'],
			'item'     => $crumb['url'],
		];
	}

	renobattery_schema_emit( [
		'@context'        => 'https://schema.org',
		'@type'           => 'BreadcrumbList',
		'itemListElement' => $items,
	] );
}
