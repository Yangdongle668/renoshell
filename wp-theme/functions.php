<?php
/**
 * Renobattery — Theme bootstrap
 *
 * @package Renobattery
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Hard-disable PHP error display for any request that is expected to return
 * JSON (admin-ajax, REST API, Elementor preview JSON). Notices from other
 * plugins would otherwise leak into the response body and break the JSON
 * parser on the front-end (Elementor Theme Builder, Finder, library, etc.).
 *
 * Regular front-end page loads still show errors if WP_DEBUG_DISPLAY is true.
 */
$renobattery_uri = isset( $_SERVER['REQUEST_URI'] ) ? (string) $_SERVER['REQUEST_URI'] : '';
if (
	( defined( 'DOING_AJAX' )  && DOING_AJAX )  ||
	( defined( 'REST_REQUEST' ) && REST_REQUEST ) ||
	( $renobattery_uri !== '' && (
		strpos( $renobattery_uri, '/wp-json/' )       !== false ||
		strpos( $renobattery_uri, 'admin-ajax.php' )  !== false ||
		strpos( $renobattery_uri, 'elementor-preview' ) !== false
	) )
) {
	@ini_set( 'display_errors', '0' );
}
unset( $renobattery_uri );

define( 'RENOBATTERY_VERSION',   '1.0.3' );
define( 'RENOBATTERY_DIR',       get_stylesheet_directory() );
define( 'RENOBATTERY_URI',       get_stylesheet_directory_uri() );
define( 'RENOBATTERY_TEXTDOMAIN','renobattery' );

require_once RENOBATTERY_DIR . '/inc/theme-setup.php';
require_once RENOBATTERY_DIR . '/inc/enqueue.php';
require_once RENOBATTERY_DIR . '/inc/cpt.php';
require_once RENOBATTERY_DIR . '/inc/taxonomies.php';
require_once RENOBATTERY_DIR . '/inc/acf-fields.php';
require_once RENOBATTERY_DIR . '/inc/archive-query.php';
require_once RENOBATTERY_DIR . '/inc/polylang.php';
require_once RENOBATTERY_DIR . '/inc/seo-head.php';
require_once RENOBATTERY_DIR . '/inc/schema.php';
require_once RENOBATTERY_DIR . '/inc/elementor-widgets.php';
if ( is_admin() ) {
	require_once RENOBATTERY_DIR . '/inc/loop-binder.php';
}
