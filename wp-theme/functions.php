<?php
/**
 * Renobattery — Theme bootstrap
 *
 * @package Renobattery
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'RENOBATTERY_VERSION',   '1.0.1' );
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
