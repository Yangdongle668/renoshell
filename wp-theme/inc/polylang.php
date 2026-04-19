<?php
/**
 * Renobattery — Polylang integration
 *
 * - Marks CPTs and taxonomies as translatable
 * - Registers reusable UI strings for Polylang String Translation
 * - Emits hreflang <link> tags in <head>
 *
 * Plugin: Polylang (free) or Polylang Pro. Safe to load when absent —
 * every call is guarded by function_exists().
 *
 * @package Renobattery
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Expose CPTs and taxonomies to Polylang.
add_filter( 'pll_get_post_types', 'renobattery_pll_post_types', 10, 2 );
function renobattery_pll_post_types( array $types, bool $is_settings ) : array {
	foreach ( [ 'product', 'case_study', 'application' ] as $t ) {
		$types[ $t ] = $t;
	}
	return $types;
}

add_filter( 'pll_get_taxonomies', 'renobattery_pll_taxonomies', 10, 2 );
function renobattery_pll_taxonomies( array $taxes, bool $is_settings ) : array {
	foreach ( [ 'product_cat', 'application_cat' ] as $t ) {
		$taxes[ $t ] = $t;
	}
	return $taxes;
}

/**
 * Register strings that are used throughout theme templates / widgets so
 * site editors can translate them in Languages → Strings translations.
 */
add_action( 'init', 'renobattery_pll_register_strings', 20 );
function renobattery_pll_register_strings() : void {
	if ( ! function_exists( 'pll_register_string' ) ) {
		return;
	}

	$strings = [
		// Eyebrow labels
		'eyebrow_products'     => 'Products',
		'eyebrow_applications' => 'Applications',
		'eyebrow_cases'        => 'Case Studies',
		'eyebrow_contact'      => 'Contact',
		'eyebrow_careers'      => 'Careers',
		'eyebrow_journal'      => 'Journal',

		// CTA button labels
		'cta_get_quote'        => 'Get Quote',
		'cta_contact_sales'    => 'Contact Sales',
		'cta_download_catalog' => 'Download Catalog',
		'cta_view_all'         => 'View all',
		'cta_view_products'    => 'View all products',
		'cta_load_more'        => 'Load more',
		'cta_read_article'     => 'Read article',
		'cta_view_details'     => 'View Details',
		'cta_request_quote'    => 'Request Quote',
		'cta_download_data'    => 'Download Datasheet',
		'cta_subscribe'        => 'Subscribe',
		'cta_send_inquiry'     => 'Send inquiry',
		'cta_learn_more'       => 'Learn more',

		// Section headlines
		'section_featured_products_h' => 'Engineered for every scale.',
		'section_applications_h'      => 'Powering every industry.',
		'section_cases_h'             => 'Real projects. Real results.',
		'section_spec_h'              => 'Full technical specifications.',
		'section_related_products_h'  => 'You may also consider.',
		'section_keep_reading_h'      => 'Keep reading',

		// Form placeholders
		'form_name'           => 'Your name',
		'form_email'          => 'name@company.com',
		'form_company'        => 'Company',
		'form_phone'          => '+1 555 000 0000',
		'form_project'        => 'kWh target, site constraints, timeline…',
		'form_accept'         => 'I agree to the privacy policy.',

		// Misc UI
		'all_label'           => 'All',
		'sort_newest'         => 'Newest',
		'sort_capacity'       => 'Highest Capacity',
		'sort_alpha'          => 'A → Z',
		'copyright'           => '© %s Renobattery Technology Co., Ltd. All rights reserved.',
	];

	foreach ( $strings as $key => $value ) {
		pll_register_string( $key, $value, 'renobattery', strlen( $value ) > 60 );
	}
}

/**
 * Emit <link rel="alternate" hreflang="..."> tags + x-default.
 * Handles singular posts, post-type archives, taxonomy archives and the home page.
 */
add_action( 'wp_head', 'renobattery_emit_hreflang', 2 );
function renobattery_emit_hreflang() : void {
	if ( ! function_exists( 'pll_the_languages' ) || ! function_exists( 'pll_default_language' ) ) {
		return;
	}

	$langs = pll_the_languages( [ 'raw' => 1, 'hide_if_no_translation' => 0 ] );
	if ( empty( $langs ) || ! is_array( $langs ) ) {
		return;
	}

	$default = pll_default_language();

	foreach ( $langs as $slug => $lang ) {
		$href = $lang['url'] ?? '';
		if ( ! $href ) {
			continue;
		}
		$locale = $lang['locale'] ?? $slug;
		printf(
			'<link rel="alternate" hreflang="%s" href="%s" />' . "\n",
			esc_attr( str_replace( '_', '-', $locale ) ),
			esc_url( $href )
		);
		if ( $slug === $default ) {
			printf(
				'<link rel="alternate" hreflang="x-default" href="%s" />' . "\n",
				esc_url( $href )
			);
		}
	}
}

/**
 * Set <html lang="..."> per request language (Polylang already does this,
 * but add a safety fallback for non-Polylang installs).
 */
add_filter( 'language_attributes', 'renobattery_language_attributes', 99 );
function renobattery_language_attributes( string $output ) : string {
	if ( function_exists( 'pll_current_language' ) ) {
		$slug = pll_current_language( 'slug' );
		if ( $slug && strpos( $output, 'lang=' ) === false ) {
			$output .= ' lang="' . esc_attr( $slug ) . '"';
		}
	}
	return $output;
}

/**
 * Helper: translated option for ACF global options per language.
 * Usage: renobattery_opt('company_phone')
 */
function renobattery_opt( string $key ) {
	if ( ! function_exists( 'get_field' ) ) {
		return '';
	}
	$suffix = '';
	if ( function_exists( 'pll_current_language' ) ) {
		$lang = pll_current_language( 'slug' );
		if ( $lang ) {
			$localized = get_field( $key . '_' . $lang, 'option' );
			if ( $localized !== null && $localized !== '' ) {
				return $localized;
			}
		}
	}
	return get_field( $key, 'option' );
}
