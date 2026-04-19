<?php
/**
 * Renobattery — Theme setup (supports, menus, image sizes)
 *
 * @package Renobattery
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action( 'after_setup_theme', 'renobattery_setup' );
function renobattery_setup() : void {

	load_child_theme_textdomain( RENOBATTERY_TEXTDOMAIN, RENOBATTERY_DIR . '/languages' );

	add_theme_support( 'title-tag' );
	add_theme_support( 'post-thumbnails' );
	add_theme_support( 'html5', [ 'search-form', 'comment-form', 'comment-list', 'gallery', 'caption', 'style', 'script' ] );
	add_theme_support( 'responsive-embeds' );
	add_theme_support( 'align-wide' );
	add_theme_support( 'custom-logo', [
		'height'      => 48,
		'width'       => 240,
		'flex-height' => true,
		'flex-width'  => true,
	] );

	register_nav_menus( [
		'primary'      => __( 'Primary Navigation', 'renobattery' ),
		'footer_col_1' => __( 'Footer — Products', 'renobattery' ),
		'footer_col_2' => __( 'Footer — Company',  'renobattery' ),
		'footer_col_3' => __( 'Footer — Resources','renobattery' ),
		'footer_legal' => __( 'Footer — Legal',    'renobattery' ),
	] );

	// Custom image sizes per step-06-performance.json
	add_image_size( 'rb-card-sm', 480,  360,  true );
	add_image_size( 'rb-card',    720,  540,  true );
	add_image_size( 'rb-card-lg', 1080, 810,  true );
	add_image_size( 'rb-hero-sm', 960,  1080, true );
	add_image_size( 'rb-hero',    1920, 1080, true );
	add_image_size( 'rb-hero-xl', 2560, 1440, true );
	add_image_size( 'rb-gallery', 1440, 1440, false );
	add_image_size( 'rb-thumb',   160,  160,  true );
}

// Disable default WP image sizes we don't use.
add_filter( 'intermediate_image_sizes_advanced', 'renobattery_disable_default_sizes' );
function renobattery_disable_default_sizes( array $sizes ) : array {
	unset( $sizes['medium_large'], $sizes['1536x1536'], $sizes['2048x2048'] );
	return $sizes;
}

// Expose custom sizes in the WP media picker.
add_filter( 'image_size_names_choose', 'renobattery_image_size_names' );
function renobattery_image_size_names( array $sizes ) : array {
	return array_merge( $sizes, [
		'rb-card'    => __( 'Card (720×540)',    'renobattery' ),
		'rb-card-lg' => __( 'Card large (1080×810)', 'renobattery' ),
		'rb-hero'    => __( 'Hero (1920×1080)',  'renobattery' ),
		'rb-gallery' => __( 'Gallery (1440)',    'renobattery' ),
	] );
}

// ACF options page for site-wide settings.
add_action( 'acf/init', 'renobattery_acf_options_page' );
function renobattery_acf_options_page() : void {
	if ( ! function_exists( 'acf_add_options_page' ) ) {
		return;
	}
	acf_add_options_page( [
		'page_title' => __( 'Renobattery Settings', 'renobattery' ),
		'menu_title' => __( 'Site Options', 'renobattery' ),
		'menu_slug'  => 'renobattery-options',
		'capability' => 'manage_options',
		'icon_url'   => 'dashicons-battery',
		'position'   => 58,
	] );
}

// Declare Elementor location support (theme builder uses this).
add_action( 'elementor/theme/register_locations', 'renobattery_register_elementor_locations' );
function renobattery_register_elementor_locations( $elementor_theme_manager ) : void {
	$elementor_theme_manager->register_all_core_location();
}
