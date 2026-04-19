<?php
/**
 * Renobattery — Front-end asset enqueue
 *
 * @package Renobattery
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action( 'wp_enqueue_scripts', 'renobattery_enqueue_assets', 20 );
function renobattery_enqueue_assets() : void {
	$ver = RENOBATTERY_VERSION;

	// Parent theme style.
	wp_enqueue_style( 'hello-elementor', get_template_directory_uri() . '/style.css', [], null );

	// Design tokens → components → motion (cascading deps).
	wp_enqueue_style( 'rb-tokens',     RENOBATTERY_URI . '/assets/css/tokens.css',     [ 'hello-elementor' ], $ver );
	wp_enqueue_style( 'rb-components', RENOBATTERY_URI . '/assets/css/components.css', [ 'rb-tokens' ],       $ver );

	$motion_css = RENOBATTERY_DIR . '/assets/css/motion.css';
	if ( file_exists( $motion_css ) ) {
		wp_enqueue_style( 'rb-motion', RENOBATTERY_URI . '/assets/css/motion.css', [ 'rb-components' ], $ver );
	}

	// Tesla refinement layer — all rules scoped under .rb-tesla, no-op otherwise.
	$tesla_css = RENOBATTERY_DIR . '/assets/css/tesla-refinement.css';
	if ( file_exists( $tesla_css ) ) {
		wp_enqueue_style( 'rb-tesla', RENOBATTERY_URI . '/assets/css/tesla-refinement.css', [ 'rb-components' ], $ver );
	}

	// JS — deferred, only register if file exists.
	foreach ( [ 'navbar', 'motion', 'megamenu', 'filter' ] as $handle ) {
		$path = RENOBATTERY_DIR . "/assets/js/{$handle}.js";
		if ( file_exists( $path ) ) {
			wp_register_script( "rb-{$handle}", RENOBATTERY_URI . "/assets/js/{$handle}.js", [], $ver, true );
			// Only enqueue filter on product archive.
			if ( 'filter' === $handle ) {
				if ( is_post_type_archive( 'product' ) || is_tax( [ 'product_cat', 'application_cat' ] ) ) {
					wp_enqueue_script( "rb-{$handle}" );
				}
			} else {
				wp_enqueue_script( "rb-{$handle}" );
			}
		}
	}

	// Preload hero font.
	add_action( 'wp_head', 'renobattery_preload_fonts', 5 );
}

function renobattery_preload_fonts() : void {
	$font_uri = RENOBATTERY_URI . '/assets/fonts/Inter-SemiBold.woff2';
	if ( file_exists( RENOBATTERY_DIR . '/assets/fonts/Inter-SemiBold.woff2' ) ) {
		printf(
			'<link rel="preload" href="%s" as="font" type="font/woff2" crossorigin>' . "\n",
			esc_url( $font_uri )
		);
	}
}

// Load script with defer attribute.
add_filter( 'script_loader_tag', 'renobattery_defer_scripts', 10, 2 );
function renobattery_defer_scripts( string $tag, string $handle ) : string {
	if ( in_array( $handle, [ 'rb-navbar', 'rb-motion', 'rb-megamenu', 'rb-filter' ], true ) ) {
		return str_replace( ' src=', ' defer src=', $tag );
	}
	return $tag;
}

// Remove WP emoji scripts (perf).
remove_action( 'wp_head',         'print_emoji_detection_script', 7 );
remove_action( 'admin_print_scripts','print_emoji_detection_script' );
remove_action( 'wp_print_styles', 'print_emoji_styles' );
remove_action( 'admin_print_styles','print_emoji_styles' );
